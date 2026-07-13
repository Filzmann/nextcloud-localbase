<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;
use OCP\EventDispatcher\Event;

/** Zweck: Fragt optionale Planungsapps synchron nach Konflikten vor einer Abwesenheitsgenehmigung. */
final class ScheduleConflictQueryEvent extends Event {
    /** @var list<ScheduleConflict> */ private array $conflicts = [];
    public function __construct(private string $employeeUid, private DateTimeImmutable $start, private DateTimeImmutable $end) { parent::__construct(); if ($employeeUid === '' || $start >= $end) throw new InvalidArgumentException('Ungültige Konfliktabfrage.'); }
    public function employeeUid(): string { return $this->employeeUid; }
    public function start(): DateTimeImmutable { return $this->start; }
    public function end(): DateTimeImmutable { return $this->end; }
    public function add(ScheduleConflict $conflict): void { $this->conflicts[] = $conflict; }
    /** @return list<ScheduleConflict> */ public function conflicts(): array { return $this->conflicts; }
}
