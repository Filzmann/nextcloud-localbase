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
    require_once __DIR__ . '/../../lib/Organization/AdSuiteAdminSettingsService.php';

    $config = new class implements \OCP\IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $organization = new \OCA\LocalBase\Organization\AdOrganizationSettingsService($config);
    $service = new \OCA\LocalBase\Organization\AdSuiteAdminSettingsService($config, $organization);

    $calendar = $service->saveCalendarPeerEditing(['ad-Buero' => true, 'ad-PFK' => false]);
    if (($calendar['ad-Buero'] ?? false) !== true || in_array('ad-PFK', $service->enabledCalendarPeerGroups(), true)) throw new \RuntimeException('Kalender-Peerrechte werden nicht korrekt gespeichert.');

    $asnPeerGroup = $service->asnPeerGroup();
    $vacation = $service->saveVacationPeerApproval([$asnPeerGroup => true, 'ad-PFK' => true]);
    if (($vacation[$asnPeerGroup] ?? false) !== true || !in_array('ad-PFK', $service->enabledVacationPeerGroups(), true)) throw new \RuntimeException('Urlaubs-Peerrechte werden nicht korrekt gespeichert.');

    $definition = $organization->definition()->toArray();
    $definition['roles']['office']['groupId'] = 'ad-Neues-Büro';
    $organization->save($definition);
    if (($service->calendarPeerEditing()['ad-Neues-Büro'] ?? false) !== true) throw new \RuntimeException('Peerrechte folgen nicht dem semantischen Rollenschlüssel.');

    $config->values['localbase']['ad_suite_admin_settings'] = '{kaputt';
    if (array_filter($service->calendarPeerEditing()) !== []) throw new \RuntimeException('Ungültige Persistenz fällt nicht sicher auf deaktivierte Rechte zurück.');
    echo "AdSuiteAdminSettingsServiceSmokeTest: OK\n";
}
