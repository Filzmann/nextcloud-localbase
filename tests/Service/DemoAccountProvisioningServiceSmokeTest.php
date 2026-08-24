<?php

declare(strict_types=1);

namespace OCP {
    interface IAppConfig {
        public function getValueString(string $appId, string $key, string $default = ''): string;
        public function setValueString(string $appId, string $key, string $value): void;
    }
    interface IUser {
        public function getUID(): string;
        public function getBackendClassName(): string;
        public function canChangePassword(): bool;
        public function canChangeDisplayName(): bool;
        public function setPassword(string $password, ?string $recoveryPassword = null): bool;
        public function setDisplayName(string $displayName): bool;
    }
    interface IUserManager {
        public function get(string $uid): ?IUser;
        public function createUser(string $uid, string $password): ?IUser;
    }
    interface IGroup {
        public function getGID(): string;
        public function canAddUser(): bool;
        public function addUser(IUser $user): void;
    }
    interface IGroupManager {
        public function get(string $gid): ?IGroup;
        public function createGroup(string $gid): ?IGroup;
    }
}

namespace {

    use OCA\LocalBase\Service\DemoAccountProvisioningService;
    use OCP\IAppConfig;
    use OCP\IGroup;
    use OCP\IGroupManager;
    use OCP\IUser;
    use OCP\IUserManager;

    $config = new class implements IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $users = new class implements IUserManager {
        public array $users = [];
        public array $createdPasswords = [];
        public function get(string $uid): ?IUser { return $this->users[$uid] ?? null; }
        public function createUser(string $uid, string $password): ?IUser {
            $this->createdPasswords[$uid] = $password;
            return $this->users[$uid] = new class($uid, $password) implements IUser {
                public string $displayName = '';
                public bool $passwordWritable = true;
                public function __construct(private string $uid, public string $password, public string $backend = 'OC\\User\\Database') {}
                public function getUID(): string { return $this->uid; }
                public function getBackendClassName(): string { return $this->backend; }
                public function canChangePassword(): bool { return $this->passwordWritable; }
                public function canChangeDisplayName(): bool { return true; }
                public function setPassword(string $password, ?string $recoveryPassword = null): bool {
                    if (!$this->passwordWritable) return false;
                    $this->password = $password;
                    return true;
                }
                public function setDisplayName(string $displayName): bool { $this->displayName = $displayName; return true; }
            };
        }
    };
    $groups = new class implements IGroupManager {
        public array $groups = [];
        public function get(string $gid): ?IGroup { return $this->groups[$gid] ?? null; }
        public function createGroup(string $gid): ?IGroup {
            return $this->groups[$gid] ??= new class($gid) implements IGroup {
                public array $members = [];
                public function __construct(private string $gid, public bool $writable = true) {}
                public function getGID(): string { return $this->gid; }
                public function canAddUser(): bool { return $this->writable; }
                public function addUser(IUser $user): void { $this->members[$user->getUID()] = true; }
            };
        }
    };

    $service = new DemoAccountProvisioningService($users, $groups, $config);
    $result = $service->provision('adcalendar', [[
        'uid' => 'adc-demo-office',
        'displayName' => 'Mara Muster (Büro Nordost)',
        'groups' => ['ad-Buero', 'ad-Nordost'],
    ]]);
    if ($result !== ['createdUsers' => 1, 'reusedUsers' => 0, 'createdGroups' => 2]) throw new RuntimeException('Demo-Provisioning meldet falsche Zähler.');
    if (($users->createdPasswords['adc-demo-office'] ?? null) !== 'adc-demo-office') throw new RuntimeException('Das initiale Passwort entspricht nicht der Demo-UID.');
    if (!isset($groups->groups['ad-Buero']->members['adc-demo-office'])) throw new RuntimeException('Demokonto wurde der Gruppe nicht zugeordnet.');

    $users->users['adc-demo-office']->password = 'abweichend';
    $again = $service->provision('adcalendar', [[
        'uid' => 'adc-demo-office',
        'displayName' => 'Mara Muster (Büro Nordost)',
        'groups' => ['ad-Buero'],
    ]]);
    if ($again['createdUsers'] !== 0 || $again['reusedUsers'] !== 1) throw new RuntimeException('Eigene Demokonten werden nicht idempotent wiederverwendet.');
    if ($users->users['adc-demo-office']->password !== 'adc-demo-office') throw new RuntimeException('Ein eigenes wiederverwendetes Demokonto wurde nicht auf UID = Passwort zurückgesetzt.');

    $users->createUser('adc-demo-read-only', 'unverändert');
    $users->users['adc-demo-read-only']->passwordWritable = false;
    $config->values['localbase']['demo_account_registry'] = json_encode([
        'adc-demo-office' => ['ownerAppId' => 'adcalendar', 'backendClass' => 'OC\\User\\Database'],
        'adc-demo-read-only' => ['ownerAppId' => 'adcalendar', 'backendClass' => 'OC\\User\\Database'],
    ], JSON_THROW_ON_ERROR);
    $beforeGroups = count($groups->groups);
    try {
        $service->provision('adcalendar', [[
            'uid' => 'adc-demo-read-only',
            'displayName' => 'Nicht änderbar',
            'groups' => ['must-not-be-created'],
        ]]);
        throw new RuntimeException('Ein nicht passwortänderbares Demokonto wurde akzeptiert.');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'Passwort')) throw $error;
    }
    if ($users->users['adc-demo-read-only']->password !== 'unverändert') throw new RuntimeException('Der fehlgeschlagene Preflight hat das Passwort verändert.');
    if (count($groups->groups) !== $beforeGroups) throw new RuntimeException('Der Passwort-Preflight hat bereits Gruppen verändert.');

    $users->createUser('real-ldap-user', 'unused')->backend = 'OCA\\User_LDAP\\User_Proxy';
    $beforeGroups = count($groups->groups);
    try {
        $service->provision('adcalendar', [[
            'uid' => 'real-ldap-user',
            'displayName' => 'Nicht ändern',
            'groups' => ['would-be-created'],
        ]]);
        throw new RuntimeException('Fremdes bestehendes Konto wurde als Demokonto akzeptiert.');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'bereits vorhanden')) throw $error;
    }
    if (count($groups->groups) !== $beforeGroups) throw new RuntimeException('Der Preflight hat vor dem Abbruch bereits Gruppen verändert.');

    $groups->createGroup('ldap-read-only')->writable = false;
    $beforeUsers = count($users->users);
    try {
        $service->provision('adcalendar', [[
            'uid' => 'adc-demo-blocked',
            'displayName' => 'Blockiert',
            'groups' => ['ldap-read-only'],
        ]]);
        throw new RuntimeException('Read-only LDAP-Gruppe wurde verändert.');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'schreibgeschützt')) throw $error;
    }
    if (count($users->users) !== $beforeUsers) throw new RuntimeException('Der Gruppen-Preflight hat vor dem Abbruch ein Konto erzeugt.');

    echo "DemoAccountProvisioningServiceSmokeTest: OK\n";
}
