<?php

declare(strict_types=1);

if (!class_exists(\OCP\EventDispatcher\Event::class)) {
    eval('namespace OCP\\EventDispatcher; class Event { public function __construct() {} }');
}

require_once __DIR__ . '/../../lib/Integration/AdIntegrationCapabilities.php';
require_once __DIR__ . '/../../lib/Integration/IntegrationCapabilityQueryEvent.php';

use OCA\LocalBase\Integration\AdIntegrationCapabilities;
use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;

$event = new IntegrationCapabilityQueryEvent([
    AdIntegrationCapabilities::ABSENCE_READ,
    AdIntegrationCapabilities::ROOM_BOOKING_WRITE,
]);

if ($event->isAvailable(AdIntegrationCapabilities::ABSENCE_READ)) {
    throw new RuntimeException('Eine Abfrage ohne Provider muss leer bleiben.');
}

$event->provide('adurlaub', [
    AdIntegrationCapabilities::ABSENCE_READ,
    AdIntegrationCapabilities::SCHEDULE_CONFLICT_READ,
]);
$event->provide('adroom', [AdIntegrationCapabilities::ROOM_BOOKING_WRITE]);
$event->provide('ignored', [AdIntegrationCapabilities::ASSISTANT_SCHEDULE_READ]);

if (!$event->isAvailable(AdIntegrationCapabilities::ABSENCE_READ)) {
    throw new RuntimeException('Angefragte Providerfähigkeit wurde nicht registriert.');
}
if ($event->providersFor(AdIntegrationCapabilities::ABSENCE_READ) !== ['adurlaub']) {
    throw new RuntimeException('Providerliste ist nicht deterministisch.');
}
if ($event->providersFor(AdIntegrationCapabilities::ASSISTANT_SCHEDULE_READ) !== []) {
    throw new RuntimeException('Nicht angefragte Fähigkeiten dürfen nicht erscheinen.');
}

$expected = [
    AdIntegrationCapabilities::ABSENCE_READ => ['adurlaub'],
    AdIntegrationCapabilities::ROOM_BOOKING_WRITE => ['adroom'],
];
if ($event->available() !== $expected) {
    throw new RuntimeException('Capability-Snapshot entspricht nicht dem Vertrag.');
}

foreach (AdIntegrationCapabilities::all() as $capability) {
    if (!preg_match('/^[a-z]+(?:\.[a-z]+)+$/', $capability)) {
        throw new RuntimeException('Capability-ID ist kein stabiler technischer Schlüssel: ' . $capability);
    }
}

echo "IntegrationCapabilityQueryEventSmokeTest: OK\n";
