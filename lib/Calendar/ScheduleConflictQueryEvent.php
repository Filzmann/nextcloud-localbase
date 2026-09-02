<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;
use OCP\EventDispatcher\Event;

/** Zweck: Fragt optionale Planungsapps synchron nach Konflikten für read-only Planungsprüfungen. */
final class ScheduleConflictQueryEvent extends Event {
    public const CONTRACT_VERSION = '1.0';

    /** @var list<ScheduleConflict> */
    private array $conflicts = [];

    public function __construct(
        private string $employeeUid,
        private DateTimeImmutable $start,
        private DateTimeImmutable $end,
        private string $requesterAppId = '',
    ) {
        parent::__construct();
        if ($employeeUid === ''
            || $start >= $end
            || ($requesterAppId !== '' && preg_match('/^[a-z][a-z0-9_]{0,63}$/', $requesterAppId) !== 1)) {
            throw new InvalidArgumentException('Ungültige Konfliktabfrage.');
        }
    }

    public function employeeUid(): string { return $this->employeeUid; }
    public function start(): DateTimeImmutable { return $this->start; }
    public function end(): DateTimeImmutable { return $this->end; }
    public function requesterAppId(): string { return $this->requesterAppId; }
    public function contractVersion(): string { return self::CONTRACT_VERSION; }

    public function add(ScheduleConflict $conflict): void {
        if ($this->requesterAppId !== '' && $conflict->sourceAppId() === $this->requesterAppId) {
            return;
        }
        $this->conflicts[] = $conflict;
    }

    /** @return list<ScheduleConflict> */
    public function conflicts(): array { return $this->conflicts; }
}
