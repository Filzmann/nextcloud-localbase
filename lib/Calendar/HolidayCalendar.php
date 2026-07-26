<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

/** Read-only Jahresstand gemeinsamer Schulferien und gesetzlicher Feiertage samt Cache- und Quellenstatus. */
final class HolidayCalendar {
    private const VERSION = 1;
    private const STATUSES = ['fresh', 'current', 'stale', 'unavailable'];

    private function __construct(
        private int $year,
        private CalendarContext $context,
        private ?string $fetchedAt,
        private ?string $refreshAttemptedAt,
        private array $source,
        private array $schoolHolidays,
        private array $publicHolidays,
        private string $cacheStatus,
    ) {
    }

    public static function get(array $data): self {
        $data += ['version' => self::VERSION];
        if (array_diff(array_keys($data), ['version', 'year', 'context', 'fetchedAt', 'refreshAttemptedAt', 'source', 'schoolHolidays', 'publicHolidays', 'cacheStatus']) !== []) {
            throw new InvalidArgumentException('Der Ferien- und Feiertagskalender enthält unbekannte Felder.');
        }
        if ($data['version'] !== self::VERSION) throw new InvalidArgumentException('Die Kalenderdatenversion wird nicht unterstützt.');
        $year = $data['year'] ?? null;
        if (!is_int($year) || $year < 2000 || $year > 2100) throw new InvalidArgumentException('Das Kalenderjahr ist ungültig.');
        $context = CalendarContext::get(is_array($data['context'] ?? null) ? $data['context'] : []);
        $fetchedAt = self::timestamp($data['fetchedAt'] ?? null);
        $refreshAttemptedAt = self::timestamp($data['refreshAttemptedAt'] ?? null);
        $source = self::source($data['source'] ?? null);
        $school = HolidayPeriod::get_all(self::periods($data['schoolHolidays'] ?? null, HolidayPeriod::TYPE_SCHOOL));
        $public = HolidayPeriod::get_all(self::periods($data['publicHolidays'] ?? null, HolidayPeriod::TYPE_PUBLIC));
        $status = (string)($data['cacheStatus'] ?? '');
        if (!in_array($status, self::STATUSES, true)) throw new InvalidArgumentException('Der Kalendercachestatus ist ungültig.');
        return new self($year, $context, $fetchedAt, $refreshAttemptedAt, $source, $school, $public, $status);
    }

    public function withCacheStatus(string $status): self {
        return self::get(array_replace($this->toArray(), ['cacheStatus' => $status]));
    }

    public function year(): int { return $this->year; }
    public function context(): CalendarContext { return $this->context; }
    public function fetchedAt(): ?string { return $this->fetchedAt; }
    public function refreshAttemptedAt(): ?string { return $this->refreshAttemptedAt; }

    public function toArray(): array {
        return [
            'version' => self::VERSION,
            'year' => $this->year,
            'context' => $this->context->toArray(),
            'fetchedAt' => $this->fetchedAt,
            'refreshAttemptedAt' => $this->refreshAttemptedAt,
            'source' => $this->source,
            'schoolHolidays' => array_map(static fn(HolidayPeriod $period): array => $period->toArray(), $this->schoolHolidays),
            'publicHolidays' => array_map(static fn(HolidayPeriod $period): array => $period->toArray(), $this->publicHolidays),
            'cacheStatus' => $this->cacheStatus,
        ];
    }

    public function save(): never { throw new LogicException('Kalenderdaten werden ausschließlich über den gemeinsamen Cache gespeichert.'); }

    private static function timestamp(mixed $value): ?string {
        if ($value === null) return null;
        if (!is_string($value) || strlen($value) > 64) throw new InvalidArgumentException('Der Kalenderzeitstempel ist ungültig.');
        try { new DateTimeImmutable($value); } catch (\Throwable) { throw new InvalidArgumentException('Der Kalenderzeitstempel ist ungültig.'); }
        return $value;
    }

    private static function source(mixed $value): array {
        if (!is_array($value) || array_diff(array_keys($value), ['name', 'url', 'license']) !== []) throw new InvalidArgumentException('Die Kalenderquelle ist ungültig.');
        $source = array_map(static fn(mixed $item): string => trim((string)$item), $value);
        if ($source['name'] === '' || $source['license'] === '' || filter_var($source['url'], FILTER_VALIDATE_URL) === false || !str_starts_with(strtolower($source['url']), 'https://')) {
            throw new InvalidArgumentException('Die Kalenderquelle ist ungültig.');
        }
        return $source;
    }

    private static function periods(mixed $value, string $type): array {
        if (!is_array($value) || !array_is_list($value)) throw new InvalidArgumentException('Die Kalenderzeiträume sind ungültig.');
        return array_map(static fn(mixed $item): array => is_array($item) ? ['type' => $type] + $item : [], $value);
    }
}
