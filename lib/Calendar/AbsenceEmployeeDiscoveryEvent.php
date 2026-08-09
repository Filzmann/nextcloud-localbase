<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;
use OCP\EventDispatcher\Event;

/**
 * Zweck: Ermittelt datensparsam die Konten mit Abwesenheiten in einem begrenzten Zeitraum.
 * Zusammenspiel: Konsumenten dispatchen das Event; optionale Provider melden nur passende Konto-UIDs.
 * Vertrag: Der Zeitraum ist halboffen [Beginn, Ende); ohne Listener bleibt die UID-Menge leer.
 */
final class AbsenceEmployeeDiscoveryEvent extends Event {
    /** @var array<string,string> */
    private array $employees = [];

    public function __construct(private DateTimeImmutable $start, private DateTimeImmutable $end) {
        parent::__construct();
        if ($start >= $end) {
            throw new InvalidArgumentException('Ungültiger Discovery-Zeitraum.');
        }
    }

    public function start(): DateTimeImmutable { return $this->start; }
    public function end(): DateTimeImmutable { return $this->end; }

    /** @param list<string> $employeeUids */
    public function provide(array $employeeUids): void {
        foreach ($employeeUids as $employeeUid) {
            if (!is_string($employeeUid)) {
                continue;
            }
            $employeeUid = trim($employeeUid);
            if ($employeeUid !== '') {
                $this->employees['uid:' . $employeeUid] = $employeeUid;
            }
        }
    }

    /** @return list<string> */
    public function employeeUids(): array {
        $employeeUids = array_values($this->employees);
        sort($employeeUids, SORT_STRING);
        return $employeeUids;
    }
}
