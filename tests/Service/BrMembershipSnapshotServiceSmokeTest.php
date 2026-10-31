<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IUser::class)) {
        interface IUser { public function getUID(); }
    }
    if (!interface_exists(IGroup::class)) {
        interface IGroup { public function getUsers(); public function inGroup($user); }
    }
    if (!interface_exists(IGroupManager::class)) {
        interface IGroupManager { public function groupExists($gid); public function createGroup($gid); public function get($gid); }
    }
    if (!interface_exists(IAppConfig::class)) {
        interface IAppConfig {
            public function getValueString(string $appId, string $key, string $default = ''): string;
            public function setValueString(string $appId, string $key, string $value): void;
        }
    }
}

namespace OCA\LocalBase\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'localbase'; }
    }
}

namespace {
    use OCA\LocalBase\Organization\BrGroupDefinition;
    use OCA\LocalBase\Organization\BrGroupSettingsService;
    use OCA\LocalBase\PublicApi\V1\BrMembershipSnapshot;
    use OCA\LocalBase\PublicApi\V1\BrMembershipSnapshotService;
    use OCA\LocalBase\Service\GroupProvisioningService;
    use OCP\IAppConfig;
    use OCP\IGroup;
    use OCP\IGroupManager;
    use OCP\IUser;
    use function OCA\LocalBase\Tests\assertSameValue;
    use function OCA\LocalBase\Tests\assertThrows;

    $user = static fn(string $uid) => new class($uid) implements IUser {
        public function __construct(private string $uid) {}
        public function getUID(): string { return $this->uid; }
    };
    $group = static fn(array $users) => new class($users) implements IGroup {
        public function __construct(private array $users) {}
        public function getUsers(): array { return $this->users; }
        public function inGroup($user): bool {
            foreach ($this->users as $candidate) {
                if ($candidate->getUID() === $user->getUID()) return true;
            }
            return false;
        }
    };
    $member = $user('br-member');
    $outsider = $user('not-a-br-member');
    $groups = new class([
        'BR-Mitglieder' => $group([$member]),
        'BR-Vorsitz' => $group([$member]),
        'BR-Stellvertretung' => $group([]),
    ]) implements IGroupManager {
        public function __construct(public array $groups) {}
        public function groupExists($gid): bool { return isset($this->groups[$gid]); }
        public function createGroup($gid): ?IGroup { return null; }
        public function get($gid): ?IGroup { return $this->groups[$gid] ?? null; }
    };
    $config = new class implements IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };

    $settings = new BrGroupSettingsService($config, new GroupProvisioningService($groups));
    $provider = new BrMembershipSnapshotService($settings);

    $missing = $provider->snapshot();
    assertSameValue(BrMembershipSnapshot::CONTRACT_VERSION, $missing->contractVersion(), 'The public BR contract must expose its version.');
    assertSameValue(false, $missing->isValid(), 'A missing canonical BR definition must fail closed.');
    assertSameValue(null, $missing->memberGroupId(), 'A missing BR definition must not expose fallback group identities.');

    $saved = $settings->save([
        BrGroupDefinition::MEMBER => 'BR-Mitglieder',
        BrGroupDefinition::CHAIR => 'BR-Vorsitz',
        BrGroupDefinition::DEPUTY => 'BR-Stellvertretung',
    ], 0);
    $snapshot = $provider->snapshot();
    assertSameValue(true, $snapshot->isValid(), 'A persisted and consistent BR definition must be consumable.');
    assertSameValue(BrGroupDefinition::VERSION, $snapshot->definitionVersion(), 'The source definition version must remain observable.');
    assertSameValue($saved->revision(), $snapshot->definitionRevision(), 'The source revision must remain observable.');
    assertSameValue('BR-Mitglieder', $snapshot->memberGroupId(), 'Only the canonical BR member identity may be projected.');
    assertSameValue(64, strlen($snapshot->checksum()), 'The immutable payload must provide a checksum.');
    $payload = $snapshot->toArray();
    if (isset($payload['members']) || isset($payload['chairGroupId']) || isset($payload['deputyGroupId'])) {
        throw new RuntimeException('The minimal public identity projection leaked members or unrelated BR roles.');
    }

    $config->values['localbase']['br_group_definition'] = '{broken';
    $broken = $provider->snapshot();
    assertSameValue(false, $broken->isValid(), 'Broken BR persistence must fail closed.');
    assertSameValue(null, $broken->memberGroupId(), 'Broken BR persistence must not expose defaults.');

    $settings->save([
        BrGroupDefinition::MEMBER => 'BR-Mitglieder',
        BrGroupDefinition::CHAIR => 'BR-Vorsitz',
        BrGroupDefinition::DEPUTY => 'BR-Stellvertretung',
    ], 0);
    $groups->groups['BR-Vorsitz'] = $group([$outsider]);
    $contradictory = $provider->snapshot();
    assertSameValue(false, $contradictory->isValid(), 'Contradictory native memberships must fail closed.');
    assertSameValue(null, $contradictory->memberGroupId(), 'Contradictory native memberships must not authorize consumers.');

    assertThrows(
        static fn() => new BrMembershipSnapshot(true, 1, 1, ''),
        InvalidArgumentException::class,
        'A valid public BR snapshot must reject an empty member identity.',
    );
    assertThrows(
        static fn() => new BrMembershipSnapshot(true, 1, 0, 'BR-Mitglieder'),
        InvalidArgumentException::class,
        'A valid public BR snapshot must originate from a persisted revision.',
    );

    echo "BrMembershipSnapshotServiceSmokeTest: OK\n";
}
