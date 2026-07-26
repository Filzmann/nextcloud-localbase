<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;

/** Liefert gemeinsame dynamische Kalenderdaten mit täglichem DB-Cache und ausfallsicherem Altbestand. */
final class HolidayCalendarService {
    private const CACHE_TTL_SECONDS = 24 * 3600;
    private const FAILURE_RETRY_SECONDS = 15 * 60;

    public function __construct(
        private OpenHolidaysClient $provider,
        private HolidayCalendarCacheStore $cache,
        private CalendarContextSettingsService $contexts,
        private ITimeFactory $time,
        private LoggerInterface $logger,
    ) {}

    public function forYear(int $year, bool $forceRefresh = false): HolidayCalendar {
        if ($year < 2000 || $year > 2100) throw new InvalidArgumentException('Ungültiges Kalenderjahr.');
        $context = $this->contexts->context();
        $cached = $this->cache->get($year, $context);
        if (!$forceRefresh && $cached !== null && $this->isCurrent($cached)) return $cached->withCacheStatus('current');
        if (!$forceRefresh && $cached !== null && !$this->retryDue($cached)) return $cached->withCacheStatus($cached->fetchedAt() === null ? 'unavailable' : 'stale');

        $now = $this->dateTime($this->time->getTime());
        try {
            $remote = $this->provider->fetchYear($year, $context);
            $calendar = HolidayCalendar::get([
                'year' => $year,
                'context' => $context->toArray(),
                'fetchedAt' => $now,
                'refreshAttemptedAt' => $now,
                'source' => $this->source(),
                'schoolHolidays' => $remote['schoolHolidays'],
                'publicHolidays' => $remote['publicHolidays'],
                'cacheStatus' => 'fresh',
            ]);
        } catch (\Throwable $error) {
            $this->logger->warning('Gemeinsame Ferien- und Feiertagsdaten konnten nicht aktualisiert werden.', [
                'year' => $year,
                'subdivision' => $context->subdivisionCode(),
                'exception' => $error,
            ]);
            $fallback = $cached?->toArray() ?? [
                'year' => $year,
                'context' => $context->toArray(),
                'fetchedAt' => null,
                'source' => $this->source(),
                'schoolHolidays' => [],
                'publicHolidays' => [],
            ];
            $fallback['refreshAttemptedAt'] = $now;
            $fallback['cacheStatus'] = $cached !== null ? 'stale' : 'unavailable';
            $calendar = HolidayCalendar::get($fallback);
        }
        $this->cache->save($calendar);
        return $calendar;
    }

    private function isCurrent(HolidayCalendar $calendar): bool {
        $fetchedAt = $calendar->fetchedAt() === null ? false : strtotime($calendar->fetchedAt());
        return $fetchedAt !== false && $fetchedAt >= $this->time->getTime() - self::CACHE_TTL_SECONDS;
    }

    private function retryDue(HolidayCalendar $calendar): bool {
        $attemptedAt = $calendar->refreshAttemptedAt() === null ? false : strtotime($calendar->refreshAttemptedAt());
        return $attemptedAt === false || $attemptedAt < $this->time->getTime() - self::FAILURE_RETRY_SECONDS;
    }

    private function dateTime(int $timestamp): string {
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM);
    }

    private function source(): array {
        return ['name' => OpenHolidaysClient::SOURCE_NAME, 'url' => OpenHolidaysClient::SOURCE_URL, 'license' => OpenHolidaysClient::SOURCE_LICENSE];
    }
}
