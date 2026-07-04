<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function groupExists($gid); public function createGroup($gid); }');
    }

    require __DIR__ . '/../../lib/Service/GroupProvisioningService.php';

    use OCA\LocalBase\Service\GroupProvisioningService;
    use OCP\IGroupManager;

    $checkSame = static function ($expected, $actual, string $message): void {
        if ($expected !== $actual) {
            fwrite(STDERR, $message . PHP_EOL);
            fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
            fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
            exit(1);
        }
    };

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
    };

    $service = new GroupProvisioningService($groupManager);

    $checkSame(
        ['first', 'second'],
        $service->ensureGroups(['existing', 'first', 'second']),
        'Only missing groups should be created.'
    );
    $checkSame(
        [],
        $service->ensureGroups(['existing', 'first', 'second']),
        'Group provisioning should be idempotent.'
    );
    $checkSame(
        ['first', 'second'],
        $groupManager->createdCalls,
        'Existing groups should not be created again.'
    );

    $failingGroupManager = new class implements IGroupManager {
        public function groupExists($gid): bool {
            return false;
        }

        public function createGroup($gid): ?object {
            return null;
        }
    };
    try {
        (new GroupProvisioningService($failingGroupManager))->ensureGroups(['missing']);
        throw new \RuntimeException('Failed group creation should throw.');
    } catch (\RuntimeException $e) {
        if (!str_contains($e->getMessage(), 'missing')) {
            throw new \RuntimeException('Failed group creation should mention the group name.');
        }
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
    };
    $checkSame(
        [],
        (new GroupProvisioningService($eventualGroupManager))->ensureGroups(['eventual']),
        'Null create result should be accepted when the group exists afterwards.'
    );

    echo 'GroupProvisioningService smoke tests passed' . PHP_EOL;
}
