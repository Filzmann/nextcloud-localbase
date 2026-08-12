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
namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; }
    interface IClient { public function get(string $url, array $options = []): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}
namespace OCP\AppFramework\Utility { interface ITimeFactory { public function getTime(): int; } }
namespace Psr\Log { interface LoggerInterface { public function warning(string $message, array $context = []): void; } }
namespace OCA\LocalBase\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'localbase'; }
    }
}

namespace {

    use OCA\LocalBase\Calendar\CalendarContextSettingsService;
    use OCA\LocalBase\Calendar\HolidayCalendarCacheStore;
    use OCA\LocalBase\Calendar\HolidayCalendarService;
    use OCA\LocalBase\Calendar\OpenHolidaysClient;
    use OCP\AppFramework\Utility\ITimeFactory;
    use OCP\Http\Client\IClient;
    use OCP\Http\Client\IClientService;
    use OCP\Http\Client\IResponse;
    use OCP\IAppConfig;
    use Psr\Log\LoggerInterface;

    final class SharedHolidayResponse implements IResponse {
        public function __construct(private array $body) {}
        public function getBody(): string { return json_encode($this->body, JSON_THROW_ON_ERROR); }
        public function getStatusCode(): int { return 200; }
    }
    final class SharedHolidayHttpClient implements IClient {
        public array $requests = [];
        public bool $fail = false;
        public bool $invalid = false;
        public function get(string $url, array $options = []): IResponse {
            $this->requests[] = [$url, $options];
            if ($this->fail) throw new RuntimeException('synthetischer Netzfehler');
            $school = str_contains($url, '/SchoolHolidays?');
            return new SharedHolidayResponse($school ? [[
                'startDate' => $this->invalid ? '02.02.2026' : '2026-02-02',
                'endDate' => '2026-02-07',
                'type' => 'School',
                'name' => [['language' => 'DE', 'text' => 'Winterferien']],
            ]] : [[
                'startDate' => '2026-03-08',
                'endDate' => '2026-03-08',
                'type' => 'Public',
                'name' => [['language' => 'DE', 'text' => 'Internationaler Frauentag']],
            ]]);
        }
    }

    $config = new class implements IAppConfig {
        public array $values = [];
        public array $writes = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; $this->writes[] = [$appId, $key]; }
    };
    $http = new SharedHolidayHttpClient();
    $clients = new class($http) implements IClientService {
        public function __construct(private IClient $client) {}
        public function newClient(): IClient { return $this->client; }
    };
    $now = strtotime('2026-07-22T12:00:00Z');
    $time = new class($now) implements ITimeFactory {
        public function __construct(public int $now) {}
        public function getTime(): int { return $this->now; }
    };
    $logger = new class implements LoggerInterface {
        public array $warnings = [];
        public function warning(string $message, array $context = []): void { $this->warnings[] = [$message, $context]; }
    };
    $contexts = new CalendarContextSettingsService($config);
    $service = new HolidayCalendarService(
        new OpenHolidaysClient($clients),
        new HolidayCalendarCacheStore($config),
        $contexts,
        $time,
        $logger,
    );

    $fresh = $service->forYear(2026)->toArray();
    if (($fresh['cacheStatus'] ?? '') !== 'fresh'
        || ($fresh['schoolHolidays'][0]['name'] ?? '') !== 'Winterferien'
        || ($fresh['publicHolidays'][0]['name'] ?? '') !== 'Internationaler Frauentag'
        || ($fresh['context']['subdivisionCode'] ?? '') !== 'DE-BE') {
        throw new RuntimeException('Der gemeinsame Erstabruf ist nicht vollständig und frisch.');
    }
    if (count($http->requests) !== 2) throw new RuntimeException('OpenHolidays wird nicht genau einmal je Datentyp geladen.');
    foreach ($http->requests as [$url, $options]) {
        if (!str_contains($url, 'countryIsoCode=DE')
            || !str_contains($url, 'subdivisionCode=DE-BE')
            || !str_contains($url, 'languageIsoCode=DE')
            || ($options['timeout'] ?? null) !== 10
            || ($options['headers']['Accept'] ?? '') !== 'application/json') {
            throw new RuntimeException('OpenHolidays erhält nicht den validierten Kontext und die sicheren HTTP-Grenzen.');
        }
    }
    $cacheWrites = array_values(array_filter($config->writes, static fn(array $write): bool => str_starts_with($write[1], 'holiday_calendar_')));
    if (count($cacheWrites) !== 1 || $cacheWrites[0][0] !== 'localbase') throw new RuntimeException('Der gemeinsame Kalendercache liegt nicht eindeutig in LocalBase.');

    if ($service->forYear(2026)->toArray()['cacheStatus'] !== 'current' || count($http->requests) !== 2) {
        throw new RuntimeException('Ein aktueller gemeinsamer Cache löst unnötige Provideranfragen aus.');
    }
    $time->now += 25 * 3600;
    $http->fail = true;
    $stale = $service->forYear(2026)->toArray();
    if ($stale['cacheStatus'] !== 'stale' || $stale['schoolHolidays'][0]['name'] !== 'Winterferien' || $logger->warnings === []) {
        throw new RuntimeException('Der letzte gültige gemeinsame Cache bleibt bei Ausfall nicht verfügbar.');
    }
    $requestsAfterFailure = count($http->requests);
    if ($service->forYear(2026)->toArray()['cacheStatus'] !== 'stale' || count($http->requests) !== $requestsAfterFailure) {
        throw new RuntimeException('Die Rückoffzeit nach einem Providerfehler wird nicht eingehalten.');
    }
    $unavailable = $service->forYear(2027)->toArray();
    if ($unavailable['cacheStatus'] !== 'unavailable' || $unavailable['schoolHolidays'] !== [] || $unavailable['publicHolidays'] !== []) {
        throw new RuntimeException('Ein Erstfehler wird nicht transparent als leerer Ausfall ausgeliefert.');
    }

    $contexts->save(['countryCode' => 'FR', 'subdivisionCode' => 'FR-IDF', 'timezone' => 'Europe/Paris']);
    $http->fail = false;
    $requestsBeforeRegionChange = count($http->requests);
    $service->forYear(2026);
    if (count($http->requests) !== $requestsBeforeRegionChange + 2) throw new RuntimeException('Eine neue Region verwendet unerwartet den Cache der vorherigen Region.');
    foreach (array_slice($http->requests, -2) as [$url]) {
        if (!str_contains($url, 'countryIsoCode=FR') || !str_contains($url, 'subdivisionCode=FR-IDF')) {
            throw new RuntimeException('Der Provider erhält nach einer Adminänderung nicht den neuen Kontext.');
        }
    }

    $http->invalid = true;
    try {
        (new OpenHolidaysClient($clients))->fetchYear(2028, $contexts->context());
        throw new RuntimeException('Ungültige externe Datumswerte werden akzeptiert.');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'ungültiges Datum')) throw $error;
    }

    echo "HolidayCalendarServiceSmokeTest: OK\n";
}
