<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IUser::class)) {
        eval('namespace OCP; interface IUser { public function getUID(); }');
    }
    if (!interface_exists(\OCP\IGroup::class)) {
        eval('namespace OCP; interface IGroup { public function getUsers(); public function inGroup($user); }');
    }
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function groupExists($gid); public function createGroup($gid); public function get($gid); }');
    }


    use OCA\LocalBase\Service\GroupProvisioningService;
    use OCP\IGroup;
    use OCP\IGroupManager;
    use OCP\IUser;
    use function OCA\LocalBase\Tests\assertSameValue;
    use function OCA\LocalBase\Tests\assertThrows;

    $groupManager = new class(['existing']) implements IGroupManager {
        public array $groups = [];
        public array $createdCalls = [];

        public function __construct(array $groups) {
            foreach ($groups as $group) {
                $this->groups[$group] = true;
            }
        }

        public function groupExists($gid): bool {
            return isset($this->groups[$gid]);
        }

        public function createGroup($gid): object {
            $this->createdCalls[] = $gid;
            $this->groups[$gid] = true;

            return new \stdClass();
        }

        public function get($gid): ?IGroup {
            return null;
        }
    };

    $service = new GroupProvisioningService($groupManager);

    assertSameValue(
        ['first', 'second'],
        $service->ensureGroups(['existing', 'first', 'second']),
        'Only missing groups should be created.'
    );
    assertSameValue(
        [],
        $service->ensureGroups(['existing', 'first', 'second']),
        'Group provisioning should be idempotent.'
    );
    assertSameValue(
        ['first', 'second'],
        $groupManager->createdCalls,
        'Existing groups should not be created again.'
    );
    assertSameValue(
        [],
        $service->ensureGroups(['second', 'second']),
        'Duplicate group names should not trigger additional creations after the first run.'
    );

    $failingGroupManager = new class implements IGroupManager {
        public function groupExists($gid): bool {
            return false;
        }

        public function createGroup($gid): ?object {
            return null;
        }

        public function get($gid): ?IGroup { return null; }
    };
    $failedCreation = assertThrows(
        static fn() => (new GroupProvisioningService($failingGroupManager))->ensureGroups(['missing']),
        \RuntimeException::class,
        'Failed group creation should throw.'
    );
    if (!str_contains($failedCreation->getMessage(), 'missing')) {
        throw new \RuntimeException('Failed group creation should mention the group name.');
    }

    $eventualGroupManager = new class implements IGroupManager {
        private bool $created = false;

        public function groupExists($gid): bool {
            return $this->created;
        }

        public function createGroup($gid): ?object {
            $this->created = true;

            return null;
        }

        public function get($gid): ?IGroup { return null; }
    };
    assertSameValue(
        [],
        (new GroupProvisioningService($eventualGroupManager))->ensureGroups(['eventual']),
        'Null create result should be accepted when the group exists afterwards.'
    );

    $member = new class('member') implements IUser {
        public function __construct(private string $uid) {}
        public function getUID(): string { return $this->uid; }
    };
    $outsider = new class('outsider') implements IUser {
        public function __construct(private string $uid) {}
        public function getUID(): string { return $this->uid; }
    };
    $group = static fn(array $users) => new class($users) implements IGroup {
        public function __construct(private array $users) {}
        public function getUsers(): array { return $this->users; }
        public function inGroup($user): bool {
            return in_array($user->getUID(), array_map(static fn(IUser $item): string => $item->getUID(), $this->users), true);
        }
    };
    $membershipManager = new class([
        'members' => $group([$member]),
        'chair' => $group([$member]),
        'deputy' => $group([]),
    ]) implements IGroupManager {
        public function __construct(public array $groups) {}
        public function groupExists($gid): bool { return isset($this->groups[$gid]); }
        public function createGroup($gid): ?IGroup { return $this->groups[$gid] ??= new class implements IGroup {
            public function getUsers(): array { return []; }
            public function inGroup($user): bool { return false; }
        }; }
        public function get($gid): ?IGroup { return $this->groups[$gid] ?? null; }
    };
    $membershipService = new GroupProvisioningService($membershipManager);
    $membershipService->assertRoleMembersBelongTo('members', ['chair', 'deputy']);

    $membershipManager->groups['deputy'] = $group([$outsider]);
    $before = array_map(static fn(IUser $user): string => $user->getUID(), $membershipManager->groups['members']->getUsers());
    $invalidMembership = assertThrows(
        static fn() => $membershipService->assertRoleMembersBelongTo('members', ['chair', 'deputy']),
        \DomainException::class,
        'A role member outside the required base group must be rejected.',
    );
    if (!str_contains($invalidMembership->getMessage(), 'deputy') || str_contains($invalidMembership->getMessage(), 'outsider')) {
        throw new \RuntimeException('Membership errors must identify the role group without disclosing account IDs.');
    }
    assertSameValue(
        $before,
        array_map(static fn(IUser $user): string => $user->getUID(), $membershipManager->groups['members']->getUsers()),
        'Rejected hierarchy checks must not repair or otherwise mutate memberships.',
    );

    echo 'GroupProvisioningService smoke tests passed' . PHP_EOL;
}
