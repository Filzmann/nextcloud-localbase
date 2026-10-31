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

    $calendarDefaults = $service->calendarPeerEditing();
    foreach (['flz-Buero', 'flz-EB', 'flz-PFK'] as $defaultGroup) {
        if (($calendarDefaults[$defaultGroup] ?? false) !== true) {
            throw new \RuntimeException('Der Kalender-Peerdefault fehlt für ' . $defaultGroup . '.');
        }
    }
    foreach (['flz-Stab-HR', 'flz-Stab-QMB', 'flz-Finanzen'] as $disabledGroup) {
        if (($calendarDefaults[$disabledGroup] ?? true) !== false) {
            throw new \RuntimeException('Ein nicht freigegebener Kalender-Peerdefault ist aktiv: ' . $disabledGroup . '.');
        }
    }

    $calendar = $service->saveCalendarPeerEditing([
        'flz-Buero' => false,
        'flz-EB' => true,
        'flz-PFK' => true,
        'flz-Stab-HR' => true,
    ]);
    if (($calendar['flz-Buero'] ?? true) !== false
        || ($calendar['flz-Stab-HR'] ?? false) !== true
        || in_array('flz-Buero', $service->enabledCalendarPeerGroups(), true)) {
        throw new \RuntimeException('Explizite Kalender-Peerentscheidungen überschreiben die Defaults nicht.');
    }

    $asnPeerGroup = $service->asnPeerGroup();
    if (array_filter($service->vacationPeerApproval()) !== []) {
        throw new \RuntimeException('Urlaubs-Peerrechte sind entgegen dem Default aktiv.');
    }
    $vacation = $service->saveVacationPeerApproval([$asnPeerGroup => true, 'flz-PFK' => true]);
    if (($vacation[$asnPeerGroup] ?? false) !== true || !in_array('flz-PFK', $service->enabledVacationPeerGroups(), true)) throw new \RuntimeException('Urlaubs-Peerrechte werden nicht korrekt gespeichert.');

    $definition = $organization->definition()->toArray();
    $definition['roles']['office']['groupId'] = 'flz-Neues-Büro';
    $organization->save($definition);
    if (($service->calendarPeerEditing()['flz-Neues-Büro'] ?? true) !== false) throw new \RuntimeException('Explizit deaktivierte Peerrechte folgen nicht dem semantischen Rollenschlüssel.');

    $config->values['localbase']['flz_suite_admin_settings'] = '{kaputt';
    if (array_filter($service->calendarPeerEditing()) !== []) throw new \RuntimeException('Ungültige Persistenz fällt nicht sicher auf deaktivierte Rechte zurück.');
    echo "FlzSuiteAdminSettingsServiceSmokeTest: OK\n";
}
