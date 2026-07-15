<?php

declare(strict_types=1);

namespace OCP {
    interface IUser {}
    interface IUserSession { public function getUser(): ?IUser; }
    interface IURLGenerator { public function linkToRoute(string $routeName, array $arguments = []): string; public function imagePath(string $appName, string $file): string; }
    interface INavigationManager { public const TYPE_APPS = 'link'; public function add(callable $entry): void; }
}
namespace OCP\App { interface IAppManager { public function isEnabledForUser($appId, $user = null); } }

namespace {
    require_once __DIR__ . '/../../lib/Service/StandaloneAppNavigationService.php';

    use OCA\LocalBase\Service\StandaloneAppNavigationService;
    use OCP\App\IAppManager;
    use OCP\INavigationManager;
    use OCP\IURLGenerator;
    use OCP\IUser;
    use OCP\IUserSession;

    $user = new class implements IUser {};
    $session = new class($user) implements IUserSession { public function __construct(private ?IUser $user) {} public function getUser(): ?IUser { return $this->user; } };
    $apps = new class implements IAppManager { public bool $suiteEnabled = false; public function isEnabledForUser($appId, $user = null): bool { return $appId === 'orgsuite' && $this->suiteEnabled; } };
    $navigation = new class implements INavigationManager { public array $entries = []; public function add(callable $entry): void { $this->entries[] = $entry; } };
    $url = new class implements IURLGenerator {
        public function linkToRoute(string $routeName, array $arguments = []): string { return '/route/' . $routeName; }
        public function imagePath(string $appName, string $file): string { return '/image/' . $appName . '/' . $file; }
    };

    $service = new StandaloneAppNavigationService($session, $apps, $navigation, $url);
    $service->addWhenStandalone('adcalendar', 'Kalender', 'adcalendar.page.index', 'app.svg', 80);
    $entry = ($navigation->entries[0] ?? static fn(): array => [])();
    if (($entry['id'] ?? null) !== 'adcalendar' || ($entry['href'] ?? null) !== '/route/adcalendar.page.index' || ($entry['icon'] ?? null) !== '/image/adcalendar/app.svg') {
        throw new RuntimeException('Standalone-Navigation wurde nicht korrekt registriert.');
    }

    $apps->suiteEnabled = true;
    $service->addWhenStandalone('adurlaub', 'Urlaub', 'adurlaub.page.index', 'app.svg', 81);
    if (count($navigation->entries) !== 1) throw new RuntimeException('Bei aktiver OrgSuite darf kein Fachapp-Eintrag hinzukommen.');

    $anonymousNavigation = new class implements INavigationManager { public array $entries = []; public function add(callable $entry): void { $this->entries[] = $entry; } };
    $anonymousSession = new class implements IUserSession { public function getUser(): ?IUser { return null; } };
    (new StandaloneAppNavigationService($anonymousSession, $apps, $anonymousNavigation, $url))->addWhenStandalone('adroom', 'Räume', 'adroom.page.index', 'app.svg');
    if ($anonymousNavigation->entries !== []) throw new RuntimeException('Anonyme Navigation wurde registriert.');

    echo "StandaloneAppNavigationServiceSmokeTest: OK\n";
}
