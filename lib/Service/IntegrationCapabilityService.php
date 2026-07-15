<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Zweck: Liefert pro Anfrage einen Snapshot der aktuell angebotenen optionalen Integrationsfähigkeiten.
 * Zusammenspiel: Der Service dispatcht IntegrationCapabilityQueryEvent; nur aktivierte Provider besitzen geladene Listener.
 * Vertrag: Ein leerer Snapshot ist ein regulärer Standalone-Zustand und kein Fehler.
 */
final class IntegrationCapabilityService {
    public function __construct(private IEventDispatcher $events) {
    }

    /**
     * @param list<string> $capabilities
     * @return array<string,list<string>>
     */
    public function query(array $capabilities): array {
        $event = new IntegrationCapabilityQueryEvent($capabilities);
        $this->events->dispatchTyped($event);
        return $event->available();
    }
}
