<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class NextcloudAccountPrivacyProviderListener implements IEventListener {
    public function __construct(private NextcloudAccountPersonalDataProvider $provider) {}

    public function handle(Event $event): void {
        if ($event instanceof PersonalDataProviderRegistryEvent) $event->register($this->provider);
    }
}
