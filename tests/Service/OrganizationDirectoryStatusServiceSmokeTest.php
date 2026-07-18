<?php

declare(strict_types=1);

namespace OCP {
    interface IUser {
        public function getUID(): string;
        public function getDisplayName(): string;
    }
    interface IGroup {
        public function getGID(): string;
        public function getBackendNames(): array;
        public function canAddUser(): bool;
        public function getUsers(): array;
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
    use OCP\IUser;

    $definition = AdOrganizationDefinition::defaults();
    $ids = array_merge($definition->roleGroupIds(), $definition->areaGroupIds());
    $groups = [];
    foreach ($ids as $id) {
        $groups[$id] = new class($id) implements IGroup {
            public function __construct(private string $id, public bool $writable = true, public array $backends = ['Database'], public array $users = []) {}
            public function getGID(): string { return $this->id; }
            public function getBackendNames(): array { return $this->backends; }
            public function canAddUser(): bool { return $this->writable; }
            public function getUsers(): array { return $this->users; }
        };
    }
    $user = static fn(string $uid, string $displayName): IUser => new class($uid, $displayName) implements IUser {
        public function __construct(private string $uid, private string $displayName) {}
        public function getUID(): string { return $this->uid; }
        public function getDisplayName(): string { return $this->displayName; }
    };
    $gf = $user('gf-demo', 'Gina Führung');
    $blSouth = $user('bl-south', 'Berta Süd');
    $deputySouthA = $user('stv-south-a', 'Dana Süd');
    $deputySouthB = $user('stv-south-b', 'Dorian Süd');
    $groups[$definition->roleGroupId('gf_as')]->users = [$gf];
    $groups[$definition->roleGroupId('bl')]->users = [$blSouth];
    $groups[$definition->roleGroupId('deputy_bl')]->users = [$deputySouthA, $deputySouthB];
    $groups[$definition->areaGroupId('south')]->users = [$blSouth, $deputySouthA, $deputySouthB];
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
    $positions = [];
    foreach ($status['positions'] ?? [] as $position) $positions[$position['roleKey'] . '#' . ($position['areaKey'] ?? '')] = $position;
    if (($positions['gf_as#']['displayNames'] ?? []) !== ['Gina Führung']) throw new RuntimeException('Organisationsweite Einzelposition wird nicht aufgelöst.');
    if (($positions['bl#south']['displayNames'] ?? []) !== ['Berta Süd']) throw new RuntimeException('Bereichsbezogene Einzelposition wird nicht per Gruppenschnitt aufgelöst.');
    if (($positions['deputy_bl#south']['displayNames'] ?? []) !== ['Dana Süd', 'Dorian Süd']) throw new RuntimeException('Mehrfach besetzte Einzelposition wird nicht vollständig diagnostiziert.');

    echo "OrganizationDirectoryStatusServiceSmokeTest: OK\n";
}
