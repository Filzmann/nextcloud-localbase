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
    require_once __DIR__ . '/../../lib/Calendar/CalendarContext.php';
    require_once __DIR__ . '/../../lib/Calendar/CalendarContextSettingsService.php';

    use OCA\LocalBase\Calendar\CalendarContext;
    use OCA\LocalBase\Calendar\CalendarContextSettingsService;
    use OCP\IAppConfig;

    $config = new class implements IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $service = new CalendarContextSettingsService($config);

    $default = $service->context();
    if ($default->toArray() !== [
        'version' => 1,
        'countryCode' => 'DE',
        'subdivisionCode' => 'DE-BE',
        'timezone' => 'Europe/Berlin',
    ]) throw new RuntimeException('Der Kalenderkontext besitzt nicht den freigegebenen Berlin-Bestandsdefault.');

    $saved = $service->save([
        'countryCode' => 'fr',
        'subdivisionCode' => 'fr-idf',
        'timezone' => 'Europe/Paris',
    ]);
    if ($saved->countryCode() !== 'FR' || $saved->subdivisionCode() !== 'FR-IDF' || $saved->timezone()->getName() !== 'Europe/Paris') {
        throw new RuntimeException('Der administrative Kalenderkontext wird nicht normalisiert und persistiert.');
    }
    if ($service->context()->toArray() !== $saved->toArray()) throw new RuntimeException('Der Kalenderkontext wird nicht gemeinsam aus LocalBase gelesen.');

    foreach ([
        ['countryCode' => 'D', 'subdivisionCode' => 'DE-BE', 'timezone' => 'Europe/Berlin'],
        ['countryCode' => 'FR', 'subdivisionCode' => 'DE-BE', 'timezone' => 'Europe/Paris'],
        ['countryCode' => 'DE', 'subdivisionCode' => 'DE-BERLIN', 'timezone' => 'Europe/Berlin'],
        ['countryCode' => 'DE', 'subdivisionCode' => 'DE-BE', 'timezone' => 'Mars/Olympus'],
        ['countryCode' => 'DE', 'subdivisionCode' => 'DE-BE', 'timezone' => 'Europe/Berlin', 'personalTimezone' => 'UTC'],
    ] as $invalid) {
        try {
            $service->save($invalid);
            throw new RuntimeException('Ein ungültiger Kalenderkontext wurde gespeichert.');
        } catch (InvalidArgumentException) {
        }
    }
    if ($service->context()->toArray() !== $saved->toArray()) throw new RuntimeException('Ein abgelehnter Kalenderkontext verändert den letzten gültigen Stand.');

    try {
        $saved->save();
        throw new RuntimeException('Der read-only Kalenderkontext kann unerwartet direkt persistiert werden.');
    } catch (LogicException) {
    }

    $config->values['localbase']['calendar_context'] = '{kaputt';
    if ($service->context()->toArray() !== CalendarContext::defaults()->toArray()) {
        throw new RuntimeException('Ungültige Kalenderkontext-Persistenz fällt nicht sicher auf den Bestandsdefault zurück.');
    }

    echo "CalendarContextSettingsServiceSmokeTest: OK\n";
}
