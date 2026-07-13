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
    if (!class_exists(Application::class)) {
        final class Application { public const APP_ID = 'localbase'; }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/Organization/AdOrganizationDefinition.php';
    require_once __DIR__ . '/../../lib/Organization/AdOrganizationSettingsService.php';

    $config = new class implements \OCP\IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $service = new \OCA\LocalBase\Organization\AdOrganizationSettingsService($config);
    $data = $service->definition()->toArray();
    $data['roles']['eb']['groupId'] = 'custom-eb';
    $data['roles']['eb']['label'] = 'Teamkoordination';
    $saved = $service->save($data);
    if ($saved->roleGroupId('eb') !== 'custom-eb' || $service->definition()->roleLabel('eb') !== 'Teamkoordination') throw new \RuntimeException('Organisationseinstellung wurde nicht persistiert.');

    $config->values['localbase']['ad_organization_definition'] = '{kaputt';
    if ($service->definition()->roleGroupId('eb') !== 'ad-EB') throw new \RuntimeException('Ungültige Persistenz fällt nicht sicher auf Defaults zurück.');
    echo "AdOrganizationSettingsServiceSmokeTest: OK\n";
}
