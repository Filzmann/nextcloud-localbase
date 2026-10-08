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

    $config = new class implements \OCP\IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $organization = new \OCA\LocalBase\Organization\FlzOrganizationSettingsService($config);
    $service = new \OCA\LocalBase\Organization\FlzSuiteAdminSettingsService($config, $organization);

    $calendar = $service->saveCalendarPeerEditing(['flz-Buero' => true, 'flz-PFK' => false]);
    if (($calendar['flz-Buero'] ?? false) !== true || in_array('flz-PFK', $service->enabledCalendarPeerGroups(), true)) throw new \RuntimeException('Kalender-Peerrechte werden nicht korrekt gespeichert.');

    $asnPeerGroup = $service->asnPeerGroup();
    $vacation = $service->saveVacationPeerApproval([$asnPeerGroup => true, 'flz-PFK' => true]);
    if (($vacation[$asnPeerGroup] ?? false) !== true || !in_array('flz-PFK', $service->enabledVacationPeerGroups(), true)) throw new \RuntimeException('Urlaubs-Peerrechte werden nicht korrekt gespeichert.');

    $definition = $organization->definition()->toArray();
    $definition['roles']['office']['groupId'] = 'flz-Neues-Büro';
    $organization->save($definition);
    if (($service->calendarPeerEditing()['flz-Neues-Büro'] ?? false) !== true) throw new \RuntimeException('Peerrechte folgen nicht dem semantischen Rollenschlüssel.');

    $config->values['localbase']['flz_suite_admin_settings'] = '{kaputt';
    if (array_filter($service->calendarPeerEditing()) !== []) throw new \RuntimeException('Ungültige Persistenz fällt nicht sicher auf deaktivierte Rechte zurück.');
    echo "FlzSuiteAdminSettingsServiceSmokeTest: OK\n";
}
