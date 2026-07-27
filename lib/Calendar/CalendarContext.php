<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeZone;
use InvalidArgumentException;
use LogicException;

/**
 * Zweck: Beschreibt die organisationsweit verbindliche Region und fachliche Zeitzone für gemeinsame Kalenderdaten.
 * Vertrag: Persönliche Zeitzonen verändern nur individuelle Darstellungen und sind kein Bestandteil dieses read-only DTOs.
 */
final class CalendarContext {
    private const VERSION = 1;
    private const DEFAULT_COUNTRY = 'DE';
    private const DEFAULT_SUBDIVISION = 'DE-BE';
    private const DEFAULT_TIMEZONE = 'Europe/Berlin';

    private function __construct(
        private string $countryCode,
        private string $subdivisionCode,
        private string $timezoneName,
    ) {
    }

    public static function get(array $data): self {
        $data += ['version' => self::VERSION];
        if (array_diff(array_keys($data), ['version', 'countryCode', 'subdivisionCode', 'timezone']) !== []) {
            throw new InvalidArgumentException('Der Kalenderkontext enthält unbekannte Felder.');
        }
        if ($data['version'] !== self::VERSION) {
            throw new InvalidArgumentException('Die Kalenderkontext-Version wird nicht unterstützt.');
        }

        $countryCode = strtoupper(trim((string)($data['countryCode'] ?? '')));
        $subdivisionCode = strtoupper(trim((string)($data['subdivisionCode'] ?? '')));
        $timezoneName = trim((string)($data['timezone'] ?? ''));
        if (preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
            throw new InvalidArgumentException('Der Ländercode muss aus zwei Buchstaben bestehen.');
        }
        if (preg_match('/^' . preg_quote($countryCode, '/') . '-[A-Z0-9]{1,3}$/', $subdivisionCode) !== 1) {
            throw new InvalidArgumentException('Der Regionscode muss zum Land passen und ISO 3166-2 entsprechen.');
        }
        if (!self::isTimezone($timezoneName)) {
            throw new InvalidArgumentException('Die fachliche Zeitzone ist keine gültige IANA-Zeitzone.');
        }

        return new self($countryCode, $subdivisionCode, $timezoneName);
    }

    /** @return list<self> */
    public static function get_all(array $items): array {
        return array_map(static fn(array $item): self => self::get($item), $items);
    }

    public static function defaults(): self {
        return self::get([
            'countryCode' => self::DEFAULT_COUNTRY,
            'subdivisionCode' => self::DEFAULT_SUBDIVISION,
            'timezone' => self::DEFAULT_TIMEZONE,
        ]);
    }

    public function countryCode(): string { return $this->countryCode; }
    public function subdivisionCode(): string { return $this->subdivisionCode; }
    public function timezone(): DateTimeZone { return new DateTimeZone($this->timezoneName); }

    /** @return array{version:int,countryCode:string,subdivisionCode:string,timezone:string} */
    public function toArray(): array {
        return [
            'version' => self::VERSION,
            'countryCode' => $this->countryCode,
            'subdivisionCode' => $this->subdivisionCode,
            'timezone' => $this->timezoneName,
        ];
    }

    public function save(): never {
        throw new LogicException('Kalenderkontexte werden über den Einstellungsservice gespeichert.');
    }

    private static function isTimezone(string $timezone): bool {
        return $timezone === 'UTC' || in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true);
    }
}
