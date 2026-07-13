<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Calendar/AbsenceInterval.php';

if (!class_exists(\OCP\EventDispatcher\Event::class)) {
    eval('namespace OCP\\EventDispatcher; class Event { public function __construct() {} }');
}
require_once __DIR__ . '/../../lib/Calendar/AbsenceQueryEvent.php';

use OCA\LocalBase\Calendar\AbsenceInterval;
use OCA\LocalBase\Calendar\AbsenceQueryEvent;

$start = new DateTimeImmutable('2026-07-13T00:00:00+02:00');
$end = $start->modify('+7 days');
$event = new AbsenceQueryEvent($start, $end, ['alice']);
$event->add(new AbsenceInterval('alice', $start->modify('+1 day'), $start->modify('+2 days'), AbsenceInterval::STATUS_PLANNED));
$event->add(new AbsenceInterval('alice', $start->modify('+3 days'), $start->modify('+4 days'), AbsenceInterval::STATUS_APPROVED));
$event->add(new AbsenceInterval('bob', $start, $end, AbsenceInterval::STATUS_APPROVED));

if (count($event->absences()) !== 2) throw new RuntimeException('QueryEvent filtert Personen nicht korrekt.');
[$planned, $approved] = array_map(static fn(AbsenceInterval $item): array => $item->toArray(), $event->absences());
if ($planned['marker'] !== 'U?' || $planned['blocks']) throw new RuntimeException('Geplanter Urlaub darf nur hinweisen.');
if ($approved['marker'] !== 'U' || !$approved['blocks']) throw new RuntimeException('Genehmigter Urlaub muss blockieren.');

echo "AbsenceQueryEventSmokeTest: OK\n";
