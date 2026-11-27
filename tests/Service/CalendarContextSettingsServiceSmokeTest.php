<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IAppConfig::class)) {
        interface IAppConfig {
            public function getValueString(string $appId, string $key, string $default = ''): string;
            public function setValueString(string $appId, string $key, string $value): void;
        }
    }
    if (!interface_exists(IConfig::class)) {
        interface IConfig {
            public function getSystemValueString(string $key, string $default = ''): string;
        }
    }
}

namespace OCA\LocalBase\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'localbase'; }
    }
}

namespace {

    use OCA\LocalBase\Calendar\CalendarContext;
    use OCA\LocalBase\Calendar\CalendarContextSettingsService;
    use OCP\IAppConfig;
    use OCP\IConfig;

    $config = new class implements IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $system = new class implements IConfig {
        public array $values = ['timezone' => 'Europe/Vienna'];
        public function getSystemValueString(string $key, string $default = ''): string { return $this->values[$key] ?? $default; }
    };
    $service = new CalendarContextSettingsService($config, $system);

    $default = $service->context();
    if ($default->toArray() !== [
        'version' => 1,
        'countryCode' => 'DE',
        'subdivisionCode' => 'DE-BE',
        'timezone' => 'Europe/Vienna',
    ]) throw new RuntimeException('Der Kalenderkontext muss die Instanz-Zeitzone aus den Nextcloud-Systemeinstellungen als fachliche Zeitzone verwenden.');

    $fallback = new CalendarContextSettingsService($config, new class implements IConfig {
        public function getSystemValueString(string $key, string $default = ''): string { return $default; }
    });
    if ($fallback->context()->timezone()->getName() !== 'UTC') throw new RuntimeException('Ohne Instanz-Zeitzone muss die fachliche Zeitzone auf UTC zurückfallen.');

    $saved = $service->save([
        'countryCode' => 'fr',
        'subdivisionCode' => 'fr-idf',
        'timezone' => 'Europe/Paris',
    ]);
    if ($saved->countryCode() !== 'FR' || $saved->subdivisionCode() !== 'FR-IDF' || $saved->timezone()->getName() !== 'Europe/Vienna') {
        throw new RuntimeException('Der administrative Kalenderkontext wird nicht normalisiert; die Zeitzone muss immer die Instanz-Zeitzone bleiben.');
    }
    if ($service->context()->toArray() !== $saved->toArray()) throw new RuntimeException('Der Kalenderkontext wird nicht gemeinsam aus LocalBase gelesen.');

    foreach ([
        ['countryCode' => 'D', 'subdivisionCode' => 'DE-BE', 'timezone' => 'Europe/Berlin'],
        ['countryCode' => 'FR', 'subdivisionCode' => 'DE-BE', 'timezone' => 'Europe/Paris'],
        ['countryCode' => 'DE', 'subdivisionCode' => 'DE-BERLIN', 'timezone' => 'Europe/Berlin'],
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
    if ($service->context()->toArray() !== CalendarContext::defaults('Europe/Vienna')->toArray()) {
        throw new RuntimeException('Ungültige Kalenderkontext-Persistenz fällt nicht sicher auf den Bestandsdefault mit Instanz-Zeitzone zurück.');
    }

    echo "CalendarContextSettingsServiceSmokeTest: OK\n";
}
