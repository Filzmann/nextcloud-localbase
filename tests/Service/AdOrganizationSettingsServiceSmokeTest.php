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
    $data['diagramOrder'] = ['gf_as', 'bl::west'];
    $saved = $service->save($data);
    if ($saved->roleGroupId('eb') !== 'custom-eb' || $service->definition()->roleLabel('eb') !== 'Teamkoordination' || $service->definition()->diagramOrder() !== ['gf_as', 'bl::west']) throw new \RuntimeException('Organisationseinstellung einschließlich visueller Diagrammordnung wurde nicht persistiert.');

    $legacy = $service->definition()->toArray();
    $legacy['version'] = 1;
    foreach (['deputy_pdl', 'care_office', 'fleet_management', 'reception'] as $key) unset($legacy['roles'][$key], $legacy['hierarchy'][$key]);
    $legacy['hierarchy']['pdl'] = ['pfk'];
    $legacy['organizationTeams'] = array_values(array_filter($legacy['organizationTeams'], static fn(array $team): bool => !in_array($team['id'], ['fleet-management', 'reception'], true)));
    foreach ($legacy['organizationTeams'] as &$team) if ($team['id'] === 'pfk') $team['roles'] = ['pfk'];
    unset($team);
    $config->values['localbase']['ad_organization_definition'] = json_encode($legacy, JSON_THROW_ON_ERROR);
    if ($service->definition()->toArray()['version'] !== 2 || !isset($service->definition()->roles()['deputy_pdl'])) throw new \RuntimeException('Gespeicherte Organisationsversion 1 wird nicht automatisch ergänzt.');
    $persistedMigration = json_decode($config->values['localbase']['ad_organization_definition'], true, 64, JSON_THROW_ON_ERROR);
    if (($persistedMigration['version'] ?? null) !== 2 || !isset($persistedMigration['roles']['fleet_management'])) throw new \RuntimeException('Additive Organisationsmigration wird nicht idempotent persistiert.');

    $config->values['localbase']['ad_organization_definition'] = '{kaputt';
    if ($service->definition()->roleGroupId('eb') !== 'ad-EB') throw new \RuntimeException('Ungültige Persistenz fällt nicht sicher auf Defaults zurück.');
    echo "AdOrganizationSettingsServiceSmokeTest: OK\n";
}
