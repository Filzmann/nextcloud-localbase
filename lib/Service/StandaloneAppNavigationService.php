<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCA\LocalBase\Catalog\AdProductCatalog;
use InvalidArgumentException;
use OCP\App\IAppManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use RuntimeException;
use UnexpectedValueException;

/**
 * Zweck: Registriert den eigenen Nextcloud-App-Einstieg nur, solange die gemeinsame OrgSuite für das Konto nicht aktiv ist.
 * Zusammenspiel: Kleine Listener der Fachapps liefern ihre Metadaten; OrgSuite ersetzt ab der Mehrfachinstallation diese Einträge.
 * Vertrag: Navigation entscheidet nicht über fachliche Rechte und wird für anonyme Sitzungen nie registriert.
 */
final class StandaloneAppNavigationService {
    public function __construct(
        private IUserSession $userSession,
        private IAppManager $appManager,
        private INavigationManager $navigation,
        private IURLGenerator $url,
        private ?AdProductCatalog $catalog = null,
    ) {
    }

    public function addCatalogProductWhenStandalone(string $appId, string $name, string $icon): void {
        try {
            $product = $this->catalog()->product($appId);
        } catch (RuntimeException) {
            return;
        }
        if ($product['standalone'] !== true || $product['menu'] !== true) {
            return;
        }

        $this->addWhenStandalone($appId, $name, $product['route'], $icon, $product['order']);
    }

    public function addWhenStandalone(
        string $appId,
        string $name,
        string $route,
        string $icon,
        int $order = 80,
    ): void {
        try {
            $product = $this->catalog()->product($appId);
            $route = $product['route'];
            $order = $product['order'];
        } catch (InvalidArgumentException) {
            // Der generische Helfer bleibt für nicht katalogisierte Fachbereiche nutzbar.
        } catch (UnexpectedValueException) {
            return;
        }

        $user = $this->userSession->getUser();
        if ($user === null || $this->appManager->isEnabledForUser('orgsuite', $user)) return;

        $this->navigation->add(fn(): array => [
            'id' => $appId,
            'type' => INavigationManager::TYPE_APPS,
            'app' => $appId,
            'href' => $this->url->linkToRoute($route),
            'icon' => $this->url->imagePath($appId, $icon),
            'name' => $name,
            'order' => $order,
        ]);
    }

    private function catalog(): AdProductCatalog {
        if (!class_exists(AdProductCatalog::class)) {
            require_once dirname(__DIR__) . '/Catalog/AdProductCatalog.php';
        }
        return $this->catalog ??= new AdProductCatalog();
    }
}
