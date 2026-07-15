<?php

declare(strict_types=1);

namespace OCA\LocalBase\Integration;

/**
 * Zweck: Definiert stabile technische Schlüssel für optionale Integrationen der eigenständigen AD-Fachapps.
 * Vertrag: Die Schlüssel beschreiben Verfügbarkeit, nicht Berechtigung. Schreibrechte prüft immer die anbietende App.
 */
final class AdIntegrationCapabilities {
    public const ABSENCE_READ = 'absence.read';
    public const SCHEDULE_CONFLICT_READ = 'schedule.conflicts.read';
    public const SCHEDULE_BLOCK_WRITE = 'schedule.block.write';
    public const ROOM_AVAILABILITY_READ = 'room.availability.read';
    public const ROOM_BOOKING_WRITE = 'room.booking.write';
    public const ASSISTANT_SCHEDULE_READ = 'assistant.schedule.read';

    /** @return list<string> */
    public static function all(): array {
        return [
            self::ABSENCE_READ,
            self::SCHEDULE_CONFLICT_READ,
            self::SCHEDULE_BLOCK_WRITE,
            self::ROOM_AVAILABILITY_READ,
            self::ROOM_BOOKING_WRITE,
            self::ASSISTANT_SCHEDULE_READ,
        ];
    }
}
