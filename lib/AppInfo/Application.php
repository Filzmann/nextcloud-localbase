<?php

declare(strict_types=1);

namespace OCA\LocalBase\AppInfo;

use OCA\LocalBase\Service\AdProductSuiteService;
use OCA\LocalBase\Settings\StandaloneOrganizationAdmin;
use OCA\LocalBase\Settings\StandaloneProductAdminSection;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Settings\IManager;

/**
 * Zweck: Registriert LocalBase und platziert die gemeinsame AD-Administration bei einer Einzelproduktinstallation dynamisch.
 * Zusammenspiel: Nextcloud-Bootstrap -> AdProductSuiteService -> Settings-Manager.
 */
class Application extends App implements IBootstrap {
    public const APP_ID = 'localbase';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
    }

    public function boot(IBootContext $context): void {
        $context->injectFn(static function (IManager $settings, AdProductSuiteService $suite): void {
            if ($suite->standaloneProduct() === null) return;
            $settings->registerSection(IManager::SETTINGS_ADMIN, StandaloneProductAdminSection::class);
            $settings->registerSetting(IManager::SETTINGS_ADMIN, StandaloneOrganizationAdmin::class);
        });
    }
}
