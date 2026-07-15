<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCP\App\IAppManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;

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
    ) {
    }

    public function addWhenStandalone(
        string $appId,
        string $name,
        string $route,
        string $icon,
        int $order = 80,
    ): void {
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
}
