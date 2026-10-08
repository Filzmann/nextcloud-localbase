<?php

declare(strict_types=1);

namespace OCP\EventDispatcher { if (!class_exists(Event::class)) { class Event { public function __construct() {} } } }

namespace {

use OCA\LocalBase\Integration\FlzIntegrationCapabilities;
use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;

$event = new IntegrationCapabilityQueryEvent([
    FlzIntegrationCapabilities::ABSENCE_READ,
    FlzIntegrationCapabilities::ROOM_BOOKING_WRITE,
]);

if ($event->isAvailable(FlzIntegrationCapabilities::ABSENCE_READ)) {
    throw new RuntimeException('Eine Abfrage ohne Provider muss leer bleiben.');
}

$event->provide('flzurlaub', [
    FlzIntegrationCapabilities::ABSENCE_READ,
    FlzIntegrationCapabilities::SCHEDULE_CONFLICT_READ,
]);
$event->provide('flzroom', [FlzIntegrationCapabilities::ROOM_BOOKING_WRITE]);
$event->provide('ignored', [FlzIntegrationCapabilities::ASSISTANT_SCHEDULE_READ]);

if (!$event->isAvailable(FlzIntegrationCapabilities::ABSENCE_READ)) {
    throw new RuntimeException('Angefragte Providerfähigkeit wurde nicht registriert.');
}
if ($event->providersFor(FlzIntegrationCapabilities::ABSENCE_READ) !== ['flzurlaub']) {
    throw new RuntimeException('Providerliste ist nicht deterministisch.');
}
if ($event->providersFor(FlzIntegrationCapabilities::ASSISTANT_SCHEDULE_READ) !== []) {
    throw new RuntimeException('Nicht angefragte Fähigkeiten dürfen nicht erscheinen.');
}

$expected = [
    FlzIntegrationCapabilities::ABSENCE_READ => ['flzurlaub'],
    FlzIntegrationCapabilities::ROOM_BOOKING_WRITE => ['flzroom'],
];
if ($event->available() !== $expected) {
    throw new RuntimeException('Capability-Snapshot entspricht nicht dem Vertrag.');
}

foreach (FlzIntegrationCapabilities::all() as $capability) {
    if (!preg_match('/^[a-z]+(?:\.[a-z]+)+$/', $capability)) {
        throw new RuntimeException('Capability-ID ist kein stabiler technischer Schlüssel: ' . $capability);
    }
}

echo "IntegrationCapabilityQueryEventSmokeTest: OK\n";
}
