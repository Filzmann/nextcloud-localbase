<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event {}
    interface IEventListener { public function handle(Event $event): void; }
}
namespace OCP {
    interface IUser { public function getUID(): string; }
}
namespace OCP\User\Events {
    final class UserDeletedEvent extends \OCP\EventDispatcher\Event {
        public function __construct(private \OCP\IUser $user) {}
        public function getUid(): string { return $this->user->getUID(); }
    }
}
namespace OCA\LocalBase\Service {
    class DemoAccountProvisioningService {
        public array $removed = [];
        public function removeRegistryEntry(string $uid): void { $this->removed[] = $uid; }
    }
}

namespace {
    use OCA\LocalBase\Listener\DemoAccountRegistryCleanupListener;
    use OCA\LocalBase\Service\DemoAccountProvisioningService;
    use OCP\EventDispatcher\Event;
    use OCP\IUser;
    use OCP\User\Events\UserDeletedEvent;

    $service = new DemoAccountProvisioningService();
    $listener = new DemoAccountRegistryCleanupListener($service);
    $listener->handle(new Event());
    if ($service->removed !== []) throw new RuntimeException('Ein fremdes Event verändert die Demo-Registry.');
    $listener->handle(new UserDeletedEvent(new class implements IUser { public function getUID(): string { return 'synthetic-demo'; } }));
    if ($service->removed !== ['synthetic-demo']) throw new RuntimeException('Kontolöschung entfernt den UID-genauen Registry-Eintrag nicht.');

    echo "Demo account registry cleanup listener test passed\n";
}
