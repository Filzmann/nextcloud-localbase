<?php

declare(strict_types=1);

namespace OCA\LocalBase\Settings;

use OCA\LocalBase\Service\AdProductSuiteService;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/** Zweck: Zeigt die gemeinsame Organisationsverwaltung unter der aktiven einzelnen AD-Fachapp an. */
final class StandaloneProductAdminSection implements IIconSection {
    public function __construct(private AdProductSuiteService $suite, private IURLGenerator $url) {
    }

    public function getIcon(): string {
        $appId = $this->getID();
        return $this->url->imagePath($appId, 'app.svg');
    }

    public function getID(): string {
        return $this->suite->standaloneProduct() ?? 'localbase';
    }

    public function getName(): string {
        return AdProductSuiteService::label($this->getID());
    }

    public function getPriority(): int {
        return 60;
    }
}
