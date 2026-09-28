<?php

declare(strict_types=1);

namespace OCA\LocalBase\Listener;

use OCA\LocalBase\Service\DemoAccountProvisioningService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

final class DemoAccountRegistryCleanupListener implements IEventListener {
    public function __construct(private DemoAccountProvisioningService $demoAccounts) {}

    public function handle(Event $event): void {
        if (!$event instanceof UserDeletedEvent) return;
        $this->demoAccounts->removeRegistryEntry($event->getUid());
    }
}
