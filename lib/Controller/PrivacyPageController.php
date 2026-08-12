<?php

declare(strict_types=1);

namespace OCA\LocalBase\Controller;

use OCA\LocalBase\AppInfo\AppId;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

final class PrivacyPageController extends Controller {
    public function __construct(IRequest $request) { parent::__construct(AppId::VALUE, $request); }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function selfService(): TemplateResponse { return new TemplateResponse(AppId::VALUE, 'privacy', ['mode' => 'self']); }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function admin(): TemplateResponse { return new TemplateResponse(AppId::VALUE, 'privacy', ['mode' => 'admin']); }
}
