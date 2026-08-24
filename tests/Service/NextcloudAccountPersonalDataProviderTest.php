<?php

declare(strict_types=1);

namespace OCP {
    interface IUser {
        public function getUID(): string;
        public function getDisplayName(): string;
        public function isEnabled(): bool;
    }
    interface IUserManager { public function get(string $uid): ?IUser; }
    interface IGroupManager {
        public function getUserGroupIds(IUser $user): array;
        public function getDisplayName(string $groupId): ?string;
    }
}
namespace OCP\Accounts {
    interface IAccountProperty {
        public function getName(): string;
        public function getValue(): string;
        public function getScope(): string;
        public function getVerified(): string;
    }
    interface IAccount {
        public function getProperty(string $property): IAccountProperty;
        public function getAllProperties(): \Generator;
    }
    interface IAccountManager {
        public const PROPERTY_EMAIL = 'email';
        public function getAccount(\OCP\IUser $user): IAccount;
    }
}

namespace {
    use OCA\LocalBase\Privacy\NextcloudAccountPersonalDataProvider;
    use OCA\LocalBase\Privacy\PersonalDataRequest;
    use OCA\LocalBase\Privacy\PersonalDataSubject;
    use OCP\IGroupManager;
    use OCP\IUser;
    use OCP\IUserManager;

    $user = new class implements IUser {
        public function getUID(): string { return 'user-17'; }
        public function getDisplayName(): string { return 'Alex Beispiel'; }
        public function isEnabled(): bool { return true; }
    };
    $users = new class($user) implements IUserManager {
        public function __construct(private IUser $user) {}
        public function get(string $uid): ?IUser { return $uid === $this->user->getUID() ? $this->user : null; }
    };
    $accounts = new class implements \OCP\Accounts\IAccountManager {
        public function getAccount(IUser $user): \OCP\Accounts\IAccount {
            return new class implements \OCP\Accounts\IAccount {
                public function getProperty(string $property): \OCP\Accounts\IAccountProperty {
                    return $this->property('email', 'alex@example.invalid', 'v2-local', '2');
                }
                public function getAllProperties(): \Generator {
                    yield $this->property('displayname', 'Alex Beispiel', 'v2-local', '0');
                    yield $this->property('email', 'alex@example.invalid', 'v2-private', '2');
                    yield $this->property('phone', '+49 30 555000', 'v2-private', '0');
                    yield $this->property('organisation', 'Beispielbetrieb', 'v2-local', '0');
                    yield $this->property('additional_mail', 'alex.zweit@example.invalid', 'v2-private', '0');
                }
                private function property(string $name, string $value, string $scope, string $verified): \OCP\Accounts\IAccountProperty {
                    return new class($name, $value, $scope, $verified) implements \OCP\Accounts\IAccountProperty {
                        public function __construct(private string $name, private string $value, private string $scope, private string $verified) {}
                        public function getName(): string { return $this->name; }
                        public function getValue(): string { return $this->value; }
                        public function getScope(): string { return $this->scope; }
                        public function getVerified(): string { return $this->verified; }
                    };
                }
            };
        }
    };
    $groups = new class implements IGroupManager {
        public ?string $requestedUid = null;
        public function getUserGroupIds(IUser $user): array {
            $this->requestedUid = $user->getUID();
            return $user->getUID() === 'user-17' ? ['team-nord', 'privacy-team'] : ['other-group'];
        }
        public function getDisplayName(string $groupId): ?string {
            return ['team-nord' => 'Team Nord', 'privacy-team' => 'Datenschutz-Team', 'other-group' => 'Fremde Gruppe'][$groupId] ?? null;
        }
    };

    $provider = new NextcloudAccountPersonalDataProvider($users, $accounts, $groups);
    $report = $provider->collect(new PersonalDataRequest(
        new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, 'user-17'),
        'de',
        PersonalDataRequest::PURPOSE_SELF_SERVICE,
    ));
    $encoded = json_encode(array_map(static fn($item) => $item->toArray(), $report->items()), JSON_THROW_ON_ERROR);
    foreach (['Nextcloud', 'user-17', 'Alex Beispiel', 'alex@example.invalid', '+49 30 555000', 'Beispielbetrieb', 'alex.zweit@example.invalid', 'Benutzerprofil', 'Gruppenzuordnung', 'team-nord', 'Team Nord', 'privacy-team', 'Datenschutz-Team', 'Anmeldung', 'nicht ausgegeben'] as $expected) {
        if (!str_contains($encoded, $expected)) throw new RuntimeException("Nextcloud-Kontoangabe fehlt: {$expected}");
    }
    foreach (['passwordHash', 'secret', '$2y$', 'other-group', 'Fremde Gruppe'] as $forbidden) {
        if (str_contains($encoded, $forbidden)) throw new RuntimeException("Geheimer Anmeldewert wurde ausgegeben: {$forbidden}");
    }
    if ($groups->requestedUid !== 'user-17') throw new RuntimeException('Gruppenzuordnungen wurden nicht strikt für die betroffene Person abgefragt.');

    $missing = $provider->collect(new PersonalDataRequest(
        new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, 'missing'),
        'de',
        PersonalDataRequest::PURPOSE_ADMIN_REPORT,
    ));
    if ($missing->items() !== []) throw new RuntimeException('Ein nicht vorhandenes Konto erzeugt erfundene Kontodaten.');

    echo "NextcloudAccountPersonalDataProviderTest: OK\n";
}
