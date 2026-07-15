<?php

declare(strict_types=1);

namespace OCA\LocalBase\Settings;

use OCA\LocalBase\Service\AdProductSuiteService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

/** Zweck: Bindet denselben Organisationseditor bei einer Einzelinstallation in deren Fachapp-Adminabschnitt ein. */
final class StandaloneOrganizationAdmin implements ISettings {
    public function __construct(private AdProductSuiteService $suite) {
    }

    public function getForm(): TemplateResponse {
        return new TemplateResponse('localbase', 'organization-admin', ['standalone' => true]);
    }

    public function getSection(): string {
        return $this->suite->standaloneProduct() ?? 'localbase';
    }

    public function getPriority(): int {
        return 20;
    }
}
