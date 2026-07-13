<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;
use OCP\EventDispatcher\Event;

/**
 * Zweck: Fragt aktivierte Provider synchron nach Abwesenheiten eines sichtbaren Zeitraums.
 * Zusammenspiel: Konsumenten dispatchen das Event; Provider fuegen ausschliesslich passende AbsenceInterval-Objekte hinzu.
 * Vertrag: Das Event kennt weder Providerinstallation noch Persistenz und bleibt ohne Listener leer.
 */
final class AbsenceQueryEvent extends Event {
    /** @var array<string,true> */
    private array $employees;
    /** @var list<AbsenceInterval> */
    private array $absences = [];

    /** @param list<string> $employeeUids */
    public function __construct(private DateTimeImmutable $start, private DateTimeImmutable $end, array $employeeUids) {
        parent::__construct();
        if ($start >= $end) throw new InvalidArgumentException('Ungueltiger Abfragezeitraum.');
        $this->employees = array_fill_keys(array_values(array_unique(array_filter(array_map('strval', $employeeUids)))), true);
    }

    public function start(): DateTimeImmutable { return $this->start; }
    public function end(): DateTimeImmutable { return $this->end; }
    /** @return list<string> */
    public function employeeUids(): array { return array_keys($this->employees); }

    public function add(AbsenceInterval $absence): void {
        if (!isset($this->employees[$absence->employeeUid()]) || !$absence->overlaps($this->start, $this->end)) return;
        $this->absences[] = $absence;
    }

    /** @return list<AbsenceInterval> */
    public function absences(): array { return $this->absences; }
}
