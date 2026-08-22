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
            foreach ($this->users as $candidate) if ($candidate->getUID() === $user->getUID()) return true;
            return false;
        }
    };
    $member = $user('member');
    $outsider = $user('outsider');
    $groups = new class([
        'legacy-members' => $group([$member]),
        'Betriebsrat-Vorsitzende' => $group([$member]),
        'Betriebsrat-Stellvertreter' => $group([]),
    ]) implements IGroupManager {
        public function __construct(public array $groups) {}
        public function groupExists($gid): bool { return isset($this->groups[$gid]); }
        public function createGroup($gid): ?IGroup { return null; }
        public function get($gid): ?IGroup { return $this->groups[$gid] ?? null; }
    };
    $config = new class implements IAppConfig {
        public array $values = [];
        public int $writes = 0;
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->writes++; $this->values[$appId][$key] = $value; }
    };
    $service = new BrGroupSettingsService($config, new GroupProvisioningService($groups));

    $initial = $service->state();
    assertSameValue(false, $initial['valid'], 'An unpersisted BR group contract must not authorize consumers.');
    assertSameValue(false, $initial['persisted'], 'Defaults must not pretend to be persisted.');
    assertThrows(static fn() => $service->validatedDefinition(), \DomainException::class, 'Consumers must deny a missing canonical contract.');

    $initialized = $service->initializeFromLegacyMemberGroup('legacy-members');
    assertSameValue('legacy-members', $initialized->groupId(BrGroupDefinition::MEMBER), 'The legacy member group must be preserved additively.');
    assertSameValue(1, $initialized->revision(), 'Initial persistence must start at revision one.');
    assertSameValue('legacy-members', $service->validatedDefinition()->groupId(BrGroupDefinition::MEMBER), 'The persisted definition must be consumable.');
    assertSameValue(1, $config->writes, 'Initialization must persist exactly once.');

    $service->initializeFromLegacyMemberGroup('ignored-later');
    assertSameValue(1, $config->writes, 'Repeated initialization must not replace the canonical contract.');

    $before = $config->values;
    assertThrows(static fn() => $service->save([
        BrGroupDefinition::MEMBER => 'legacy-members',
        BrGroupDefinition::CHAIR => 'legacy-members',
        BrGroupDefinition::DEPUTY => 'Betriebsrat-Stellvertreter',
    ], 1), \InvalidArgumentException::class, 'Semantic group IDs must remain distinct.');
    assertSameValue($before, $config->values, 'Invalid semantic mappings must not be persisted.');

    $groups->groups['Betriebsrat-Stellvertreter'] = $group([$outsider]);
    $membershipError = assertThrows(static fn() => $service->save([
        BrGroupDefinition::MEMBER => 'legacy-members',
        BrGroupDefinition::CHAIR => 'Betriebsrat-Vorsitzende',
        BrGroupDefinition::DEPUTY => 'Betriebsrat-Stellvertreter',
    ], 1), \DomainException::class, 'Chair and deputy memberships outside the BR group must be denied.');
    if (str_contains($membershipError->getMessage(), 'outsider')) throw new \RuntimeException('Membership validation must not disclose account IDs.');
    assertSameValue($before, $config->values, 'Rejected membership contradictions must not mutate AppConfig.');

    $groups->groups['Betriebsrat-Stellvertreter'] = $group([]);
    $saved = $service->save([
        BrGroupDefinition::DEPUTY => 'Betriebsrat-Stellvertreter',
        BrGroupDefinition::MEMBER => 'legacy-members',
        BrGroupDefinition::CHAIR => 'Betriebsrat-Vorsitzende',
    ], 1);
    assertSameValue(2, $saved->revision(), 'Semantic keys must not depend on JSON object order.');
    $before = $config->values;
    assertThrows(static fn() => $service->save([
        BrGroupDefinition::MEMBER => 'legacy-members',
        BrGroupDefinition::CHAIR => 'Betriebsrat-Vorsitzende',
        BrGroupDefinition::DEPUTY => 'missing-group',
    ], 2), \RuntimeException::class, 'Missing referenced groups must be denied.');
    assertThrows(static fn() => $service->save($initialized->groups(), 0), \RuntimeException::class, 'Stale revisions must be denied.');
    assertSameValue($before, $config->values, 'Missing groups and stale revisions must not mutate AppConfig.');

    $config->values['localbase']['br_group_definition'] = '{broken';
    $writes = $config->writes;
    assertSameValue(false, $service->state()['valid'], 'Corrupt persisted contracts must fail closed.');
    assertThrows(static fn() => $service->initializeFromLegacyMemberGroup('legacy-members'), \DomainException::class, 'Initialization must not overwrite corrupt persisted data.');
    assertSameValue($writes, $config->writes, 'Failed recovery must not overwrite corrupt persisted data.');

    echo "BrGroupSettingsService smoke tests passed\n";
}
