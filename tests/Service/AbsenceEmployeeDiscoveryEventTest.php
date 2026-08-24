<?php

declare(strict_types=1);

if (!class_exists(\OCP\EventDispatcher\Event::class)) {
    eval('namespace OCP\\EventDispatcher; class Event { public function __construct() {} }');
}

use OCA\LocalBase\Calendar\AbsenceEmployeeDiscoveryEvent;

$start = new DateTimeImmutable('2026-01-01T00:00:00+01:00');
$end = new DateTimeImmutable('2029-01-01T00:00:00+01:00');
$event = new AbsenceEmployeeDiscoveryEvent($start, $end);

if ($event->start() !== $start || $event->end() !== $end || $event->employeeUids() !== []) {
    throw new RuntimeException('Eine unbeantwortete Discovery muss begrenzt und leer bleiben.');
}

$event->provide([' bob ', 'alice', '', 'alice', "\t", '0']);
$event->provide(['carol', 'bob']);
if ($event->employeeUids() !== ['0', 'alice', 'bob', 'carol']) {
    throw new RuntimeException('Provider-UIDs werden nicht normalisiert, dedupliziert und stabil sortiert.');
}

/** @phpstan-ignore-next-line Absichtlich fehlerhafte Providerdaten am öffentlichen Vertrag. */
$event->provide([42, ['nested'], new stdClass()]);
if ($event->employeeUids() !== ['0', 'alice', 'bob', 'carol']) {
    throw new RuntimeException('Ungültige Providerwerte dürfen die Discovery-Menge nicht erweitern.');
}

try {
    new AbsenceEmployeeDiscoveryEvent($end, $start);
    throw new RuntimeException('Ein ungültiges Discovery-Intervall wurde akzeptiert.');
} catch (InvalidArgumentException) {
}

try {
    new AbsenceEmployeeDiscoveryEvent($start, $start);
    throw new RuntimeException('Ein leerer Discovery-Zeitraum wurde akzeptiert.');
} catch (InvalidArgumentException) {
}

echo "AbsenceEmployeeDiscoveryEventTest: OK\n";
