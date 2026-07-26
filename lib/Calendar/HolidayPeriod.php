<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

/** Read-only Zeitraum eines Schulferien- oder Feiertagsproviders; Datumsgrenzen sind inklusive. */
final class HolidayPeriod {
    public const TYPE_SCHOOL = 'school';
    public const TYPE_PUBLIC = 'public';

    private function __construct(
        private string $type,
        private string $name,
        private string $startDate,
        private string $endDate,
    ) {
    }

    public static function get(array $data): self {
        if (array_diff(array_keys($data), ['type', 'name', 'startDate', 'endDate']) !== []) {
            throw new InvalidArgumentException('Der Kalenderzeitraum enthält unbekannte Felder.');
        }
        $type = (string)($data['type'] ?? '');
        $name = trim((string)($data['name'] ?? ''));
        $startDate = self::date($data['startDate'] ?? null);
        $endDate = self::date($data['endDate'] ?? null);
        if (!in_array($type, [self::TYPE_SCHOOL, self::TYPE_PUBLIC], true)) throw new InvalidArgumentException('Der Kalenderzeitraum besitzt einen ungültigen Typ.');
        if ($name === '' || self::length($name) > 320) throw new InvalidArgumentException('Der Kalenderzeitraum besitzt keinen gültigen Namen.');
        if ($endDate < $startDate) throw new InvalidArgumentException('Der Kalenderzeitraum endet vor seinem Beginn.');
        return new self($type, $name, $startDate, $endDate);
    }

    /** @return list<self> */
    public static function get_all(array $items): array {
        return array_map(static fn(array $item): self => self::get($item), $items);
    }

    public function type(): string { return $this->type; }

    /** @return array{type:string,name:string,startDate:string,endDate:string} */
    public function toArray(): array {
        return ['type' => $this->type, 'name' => $this->name, 'startDate' => $this->startDate, 'endDate' => $this->endDate];
    }

    public function save(): never { throw new LogicException('Kalenderzeiträume sind read-only Providerdaten.'); }

    private static function date(mixed $value): string {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) throw new InvalidArgumentException('Der Kalenderzeitraum besitzt ein ungültiges Datum.');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Der Kalenderzeitraum besitzt ein ungültiges Datum.');
        return $value;
    }

    private static function length(string $value): int { return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value); }
}
