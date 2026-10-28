<?php

declare(strict_types=1);

namespace OCA\LocalBase\PublicApi\V1;

use InvalidArgumentException;
use OCA\LocalBase\Calendar\HolidayCalendar;

/** Datensparsame, unveränderliche Feiertagsprojektion für Consumer. */
final class HolidayCalendarSnapshot {
    public const CONTRACT_VERSION = '1.0';

    /** @param list<string> $publicHolidayDates */
    public function __construct(
        private int $year,
        private string $timezone,
        private string $cacheStatus,
        private array $publicHolidayDates,
        private ?string $fetchedAt,
    ) {
        if ($year < 2000 || $year > 2100) throw new InvalidArgumentException('Invalid holiday year.');
        if ($timezone === '') throw new InvalidArgumentException('Missing holiday timezone.');
        if (!in_array($cacheStatus, ['fresh', 'current', 'stale', 'unavailable'], true)) throw new InvalidArgumentException('Invalid holiday cache status.');
        foreach ($publicHolidayDates as $date) {
            if (!is_string($date) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) throw new InvalidArgumentException('Invalid holiday date.');
        }
        $this->publicHolidayDates = array_values(array_unique($publicHolidayDates));
        sort($this->publicHolidayDates);
        if ($cacheStatus === 'unavailable') $this->publicHolidayDates = [];
    }

    public static function fromCalendar(HolidayCalendar $calendar): self {
        $data = $calendar->toArray();
        $dates = [];
        foreach ($data['publicHolidays'] as $period) {
            $start = new \DateTimeImmutable($period['startDate']);
            $end = new \DateTimeImmutable($period['endDate']);
            for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) $dates[] = $date->format('Y-m-d');
        }
        return new self((int)$data['year'], (string)$data['context']['timezone'], (string)$data['cacheStatus'], $dates, $data['fetchedAt']);
    }

    public function contractVersion(): string { return self::CONTRACT_VERSION; }
    public function year(): int { return $this->year; }
    public function timezone(): string { return $this->timezone; }
    public function cacheStatus(): string { return $this->cacheStatus; }
    /** @return list<string> */
    public function publicHolidayDates(): array { return $this->publicHolidayDates; }
    public function fetchedAt(): ?string { return $this->fetchedAt; }
    public function isUsable(): bool { return $this->cacheStatus !== 'unavailable'; }
}
