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

    use OCA\LocalBase\Catalog\AdProductCatalog;
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

    $service = new StandaloneAppNavigationService($session, $apps, $navigation, $url, new AdProductCatalog());
    $service->addCatalogProductWhenStandalone('adrecruitment', 'Recruitment', 'app.svg');
    $entry = ($navigation->entries[0] ?? static fn(): array => [])();
    if (($entry['id'] ?? null) !== 'adrecruitment' || ($entry['href'] ?? null) !== '/route/adrecruitment.page.index'
        || ($entry['icon'] ?? null) !== '/image/adrecruitment/app.svg' || ($entry['order'] ?? null) !== 84) {
        throw new RuntimeException('Standalone-Navigation wurde nicht korrekt registriert.');
    }

    $service->addWhenStandalone('adcalendar', 'Kalender', 'manually.duplicated.route', 'app.svg', 999);
    $catalogEntry = ($navigation->entries[1] ?? static fn(): array => [])();
    if (($catalogEntry['href'] ?? null) !== '/route/adcalendar.page.index' || ($catalogEntry['order'] ?? null) !== 80) {
        throw new RuntimeException('Bestehende Standalone-Consumer verwenden nicht die kanonische Katalogroute und -reihenfolge.');
    }

    $service->addWhenStandalone('other_app', 'Andere App', 'other.page.index', 'other.svg', 91);
    $genericEntry = ($navigation->entries[2] ?? static fn(): array => [])();
    if (($genericEntry['href'] ?? null) !== '/route/other.page.index' || ($genericEntry['order'] ?? null) !== 91) {
        throw new RuntimeException('Nicht katalogisierte Fachbereiche verlieren den generischen Navigationsvertrag.');
    }

    $apps->suiteEnabled = true;
    $service->addWhenStandalone('adurlaub', 'Urlaub', 'adurlaub.page.index', 'app.svg', 81);
    if (count($navigation->entries) !== 3) throw new RuntimeException('Bei aktiver OrgSuite darf kein Fachapp-Eintrag hinzukommen.');

    $anonymousNavigation = new class implements INavigationManager { public array $entries = []; public function add(callable $entry): void { $this->entries[] = $entry; } };
    $anonymousSession = new class implements IUserSession { public function getUser(): ?IUser { return null; } };
    (new StandaloneAppNavigationService($anonymousSession, $apps, $anonymousNavigation, $url))->addWhenStandalone('adroom', 'Räume', 'adroom.page.index', 'app.svg');
    if ($anonymousNavigation->entries !== []) throw new RuntimeException('Anonyme Navigation wurde registriert.');

    $missingCatalog = new AdProductCatalog(__DIR__ . '/missing-catalog.json');
    $missingNavigation = new class implements INavigationManager { public array $entries = []; public function add(callable $entry): void { $this->entries[] = $entry; } };
    $apps->suiteEnabled = false;
    (new StandaloneAppNavigationService($session, $apps, $missingNavigation, $url, $missingCatalog))
        ->addCatalogProductWhenStandalone('adrecruitment', 'Recruitment', 'app.svg');
    (new StandaloneAppNavigationService($session, $apps, $missingNavigation, $url, $missingCatalog))
        ->addWhenStandalone('adcalendar', 'Kalender', 'adcalendar.page.index', 'app.svg');
    if ($missingNavigation->entries !== []) throw new RuntimeException('Fehlender Katalogprovider erzeugt unsichere Navigation.');

    echo "StandaloneAppNavigationServiceSmokeTest: OK\n";
}
