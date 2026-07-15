<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventDispatcher { public function dispatchTyped(object $event): object; }
}

namespace {
    require_once __DIR__ . '/../../lib/Integration/AdIntegrationCapabilities.php';
    require_once __DIR__ . '/../../lib/Integration/IntegrationCapabilityQueryEvent.php';
    require_once __DIR__ . '/../../lib/Service/IntegrationCapabilityService.php';

    use OCA\LocalBase\Integration\AdIntegrationCapabilities;
    use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
    use OCA\LocalBase\Service\IntegrationCapabilityService;
    use OCP\EventDispatcher\IEventDispatcher;

    $dispatcher = new class implements IEventDispatcher {
        public int $calls = 0;
        public function dispatchTyped(object $event): object {
            $this->calls++;
            if (!$event instanceof IntegrationCapabilityQueryEvent) throw new RuntimeException('Falscher Eventtyp.');
            $event->provide('adurlaub', [AdIntegrationCapabilities::ABSENCE_READ]);
            return $event;
        }
    };

    $service = new IntegrationCapabilityService($dispatcher);
    $snapshot = $service->query([
        AdIntegrationCapabilities::ABSENCE_READ,
        AdIntegrationCapabilities::ROOM_BOOKING_WRITE,
    ]);
    if ($snapshot !== [AdIntegrationCapabilities::ABSENCE_READ => ['adurlaub']]) throw new RuntimeException('Capability-Snapshot ist falsch.');
    if ($dispatcher->calls !== 1) throw new RuntimeException('Capability-Abfrage wurde mehrfach dispatcht.');

    echo "IntegrationCapabilityServiceSmokeTest: OK\n";
}
