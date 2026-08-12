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

    $config = new class implements \OCP\IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $settings = new AdOrganizationSettingsService($config);
    $snapshots = new AdOrganizationSnapshotService($settings);

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

    echo "AdOrganizationSnapshotServiceSmokeTest: OK\n";
}
