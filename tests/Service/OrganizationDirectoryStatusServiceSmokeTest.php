<?php

declare(strict_types=1);

namespace OCP {
    interface IGroup {
        public function getGID(): string;
        public function getBackendNames(): array;
        public function canAddUser(): bool;
    }
    interface IGroupManager { public function get($gid); }
}

namespace {
    require_once __DIR__ . '/../../lib/Organization/AdOrganizationDefinition.php';
    require_once __DIR__ . '/../../lib/Service/OrganizationDirectoryStatusService.php';

    use OCA\LocalBase\Organization\AdOrganizationDefinition;
    use OCA\LocalBase\Service\OrganizationDirectoryStatusService;
    use OCP\IGroup;
    use OCP\IGroupManager;

    $definition = AdOrganizationDefinition::defaults();
    $ids = array_merge($definition->roleGroupIds(), $definition->areaGroupIds());
    $groups = [];
    foreach ($ids as $id) {
        $groups[$id] = new class($id) implements IGroup {
            public function __construct(private string $id, public bool $writable = true, public array $backends = ['Database']) {}
            public function getGID(): string { return $this->id; }
            public function getBackendNames(): array { return $this->backends; }
            public function canAddUser(): bool { return $this->writable; }
        };
    }
    $ldapId = $definition->roleGroupId('office');
    $groups[$ldapId]->writable = false;
    $groups[$ldapId]->backends = ['LDAP'];
    $missingId = $definition->areaGroupId('west');
    unset($groups[$missingId]);

    $manager = new class($groups) implements IGroupManager {
        public function __construct(private array $groups) {}
        public function get($gid): ?IGroup { return $this->groups[(string)$gid] ?? null; }
    };

    $status = (new OrganizationDirectoryStatusService($manager))->status();
    $byId = array_column($status['groups'], null, 'groupId');
    if ($status['compatible'] !== false || $status['demoWritable'] !== false) {
        throw new RuntimeException('Fehlende und read-only Gruppen werden in der Zusammenfassung nicht sicher bewertet.');
    }
    if (($byId[$ldapId]['exists'] ?? null) !== true || ($byId[$ldapId]['membershipWritable'] ?? null) !== false || ($byId[$ldapId]['backendNames'][0] ?? '') !== 'LDAP') {
        throw new RuntimeException('Read-only LDAP-Gruppen werden nicht korrekt beschrieben.');
    }
    if (($byId[$missingId]['exists'] ?? null) !== false || ($byId[$missingId]['demoProvisionable'] ?? null) !== true) {
        throw new RuntimeException('Fehlende, lokal anlegbare Gruppen werden nicht korrekt beschrieben.');
    }

    echo "OrganizationDirectoryStatusServiceSmokeTest: OK\n";
}
