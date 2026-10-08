<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class LocalBasePersonalDataProviderListener implements IEventListener {
    public function __construct(private LocalBasePersonalDataProvider $provider) {}

    public function handle(Event $event): void {
        if ($event instanceof RegisterPersonalDataProvidersEvent) $event->register($this->provider);
    }
}
