<?php

declare(strict_types=1);

namespace OCA\LocalBase\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

final class PersonalPrivacy implements ISettings {
    public function __construct(private IURLGenerator $url) {}

    public function getForm(): TemplateResponse {
        return new TemplateResponse('localbase', 'privacy-personal', [
            'privacyUrl' => $this->url->linkToRoute('localbase.privacy_page.selfService'),
        ]);
    }

    public function getSection(): string { return 'localbase_privacy'; }
    public function getPriority(): int { return 10; }
}
