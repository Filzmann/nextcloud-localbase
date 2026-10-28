<?php

declare(strict_types=1);

use OCA\LocalBase\Calendar\HolidayCalendar;
use OCA\LocalBase\PublicApi\V1\HolidayCalendarSnapshot;

require_once __DIR__ . '/../bootstrap.php';

$internal = HolidayCalendar::get([
    'year' => 2027,
    'context' => ['countryCode' => 'DE', 'subdivisionCode' => 'DE-BE', 'timezone' => 'Europe/Berlin'],
    'fetchedAt' => '2026-10-27T08:00:00+00:00',
    'refreshAttemptedAt' => '2026-10-27T08:00:00+00:00',
    'source' => ['name' => 'Testquelle', 'url' => 'https://example.invalid/holidays', 'license' => 'Test'],
    'schoolHolidays' => [],
    'publicHolidays' => [['name' => 'Feiertag', 'startDate' => '2027-01-01', 'endDate' => '2027-01-01']],
    'cacheStatus' => 'current',
]);

$snapshot = HolidayCalendarSnapshot::fromCalendar($internal);
if ($snapshot->contractVersion() !== '1.0' || $snapshot->year() !== 2027 || $snapshot->timezone() !== 'Europe/Berlin') {
    throw new RuntimeException('Der öffentliche Feiertagsvertrag projiziert Version, Jahr oder Zeitzone nicht stabil.');
}
if ($snapshot->cacheStatus() !== 'current' || !$snapshot->isUsable()) {
    throw new RuntimeException('Ein aktueller Cache muss als verwendbar ausgewiesen werden.');
}
if ($snapshot->publicHolidayDates() !== ['2027-01-01']) {
    throw new RuntimeException('Gesetzliche Feiertage werden nicht datensparsam als Datumsliste projiziert.');
}

$unavailable = HolidayCalendarSnapshot::fromCalendar($internal->withCacheStatus('unavailable'));
if ($unavailable->isUsable() || $unavailable->publicHolidayDates() !== []) {
    throw new RuntimeException('Ein nicht verfügbarer Kalender darf niemals als leere konfliktfreie Liste gelten.');
}

echo "LocalBase public holiday contract tests passed\n";
