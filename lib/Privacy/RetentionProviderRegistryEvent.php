<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;
use OCP\EventDispatcher\Event;

final class RetentionProviderRegistryEvent extends Event {
    /** @var array<string,RetentionProvider> */
    private array $providers = [];
    public function __construct() { parent::__construct(); }
    public function register(RetentionProvider $provider): void {
        $appId = trim($provider->appId());
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $appId)) throw new InvalidArgumentException('Retention-Provider-App-ID ist ungültig.');
        if (isset($this->providers[$appId])) throw new InvalidArgumentException('Retention-Provider-App-ID ist doppelt registriert.');
        $this->providers[$appId] = $provider;
    }
    /** @return array<string,RetentionProvider> */
    public function providers(): array { ksort($this->providers, SORT_STRING); return $this->providers; }
}
