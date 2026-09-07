<?php

declare(strict_types=1);

namespace OCP {
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

    use OCA\LocalBase\Organization\AdOrganizationSettingsService;
    use OCA\LocalBase\Organization\AdOrganizationSnapshotService;
    use OCA\LocalBase\PublicApi\V1\OrganizationSnapshot;
    use OCA\LocalBase\PublicApi\V1\OrganizationSnapshotService;

    $config = new class implements \OCP\IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $settings = new AdOrganizationSettingsService($config);
    $snapshots = new AdOrganizationSnapshotService($settings);
    $publicSnapshots = new OrganizationSnapshotService($snapshots);

    $missing = $snapshots->snapshot();
    if ($missing->isValid() || $missing->roleGroupId('staff_hr') !== null || $missing->areaKeys() !== []) {
        throw new RuntimeException('Fehlende Organisationspersistenz erzeugt einen berechtigenden Snapshot.');
    }

    $settings->save($settings->definition()->toArray());
    $snapshot = $snapshots->snapshot();
    if (!$snapshot->isValid()
        || $snapshot->definitionVersion() !== 4
        || $snapshot->roleGroupId('finance') === $snapshot->roleGroupId('payroll')
        || $snapshot->areaGroupId('west') === null
        || strlen($snapshot->checksum()) !== 64) {
        throw new RuntimeException('Der gültige Organisationssnapshot ist unvollständig.');
    }
    $payload = $snapshot->toArray();
    if (isset($payload['members']) || str_contains(json_encode($payload, JSON_THROW_ON_ERROR), 'displayName')) {
        throw new RuntimeException('Der Organisationssnapshot enthält personenbezogene Mitgliederlisten.');
    }

    $config->values['localbase']['ad_organization_definition'] = '{kaputt';
    if ($snapshots->snapshot()->isValid()) {
        throw new RuntimeException('Ungültige Organisationspersistenz wird als gültiger Snapshot veröffentlicht.');
    }

    $config->values['localbase']['ad_organization_definition'] = '';
    $publicMissing = $publicSnapshots->snapshot();
    if ($publicMissing->contractVersion() !== OrganizationSnapshot::CONTRACT_VERSION
        || $publicMissing->isValid()
        || $publicMissing->roles() !== []
        || $publicMissing->areas() !== []) {
        throw new RuntimeException('Der öffentliche V1-Vertrag veröffentlicht fehlende Organisationsdaten nicht fail-closed.');
    }

    $settings->save($settings->definition()->toArray());
    $publicSnapshot = $publicSnapshots->snapshot();
    if (!$publicSnapshot->isValid()
        || $publicSnapshot->definitionVersion() !== 4
        || $publicSnapshot->roles()['finance']['groupId'] === $publicSnapshot->roles()['payroll']['groupId']
        || $publicSnapshot->areas()['west']['groupId'] === ''
        || strlen($publicSnapshot->checksum()) !== 64) {
        throw new RuntimeException('Der öffentliche V1-Organisationssnapshot ist unvollständig.');
    }
    if (isset($publicSnapshot->toArray()['members'])) {
        throw new RuntimeException('Der öffentliche V1-Vertrag enthält Mitgliederlisten.');
    }

    try {
        new OrganizationSnapshot(true, 4, [
            'finance' => ['groupId' => 'shared-group', 'label' => 'Finanzen'],
        ], [
            'west' => ['groupId' => 'shared-group', 'label' => 'West'],
        ]);
        throw new RuntimeException('Mehrdeutige öffentliche Gruppenzuordnungen wurden akzeptiert.');
    } catch (InvalidArgumentException) {
    }

    try {
        new OrganizationSnapshot(true, 4, [
            'finance' => ['groupId' => 'ad-finance', 'label' => ['invalid']],
        ], []);
        throw new RuntimeException('Ungültige öffentliche Mappingfelder wurden akzeptiert.');
    } catch (InvalidArgumentException) {
    }

    echo "AdOrganizationSnapshotServiceSmokeTest: OK\n";
}
