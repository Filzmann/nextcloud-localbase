<?php

declare(strict_types=1);

namespace OCA\LocalBase\Integration;

use InvalidArgumentException;
use OCP\EventDispatcher\Event;

/**
 * Zweck: Fragt aktivierte Fachapps synchron nach optional angebotenen Integrationsfähigkeiten.
 * Zusammenspiel: Konsumenten dispatchen das Event; Provider melden nur Fähigkeiten, die sie im aktiven Zustand anbieten.
 * Vertrag: Ohne Listener bleibt die Abfrage leer. Verfügbarkeit erweitert niemals Lese- oder Schreibrechte.
 */
final class IntegrationCapabilityQueryEvent extends Event {
    /** @var array<string,true> */
    private array $requested;

    /** @var array<string,array<string,true>> */
    private array $providers = [];

    /** @param list<string> $capabilities */
    public function __construct(array $capabilities) {
        parent::__construct();
        $normalized = array_values(array_unique(array_filter(array_map('strval', $capabilities))));
        $this->requested = array_fill_keys($normalized, true);
    }

    /** @param list<string> $capabilities */
    public function provide(string $providerAppId, array $capabilities): void {
        $providerAppId = trim($providerAppId);
        if ($providerAppId === '') {
            throw new InvalidArgumentException('Provider-App-ID darf nicht leer sein.');
        }

        foreach (array_values(array_unique(array_map('strval', $capabilities))) as $capability) {
            if (!isset($this->requested[$capability])) continue;
            $this->providers[$capability][$providerAppId] = true;
        }
    }

    public function isAvailable(string $capability): bool {
        return $this->providersFor($capability) !== [];
    }

    /** @return list<string> */
    public function providersFor(string $capability): array {
        $providers = array_keys($this->providers[$capability] ?? []);
        sort($providers, SORT_STRING);
        return $providers;
    }

    /** @return array<string,list<string>> */
    public function available(): array {
        $available = [];
        foreach (array_keys($this->requested) as $capability) {
            $providers = $this->providersFor($capability);
            if ($providers !== []) $available[$capability] = $providers;
        }
        return $available;
    }
}
