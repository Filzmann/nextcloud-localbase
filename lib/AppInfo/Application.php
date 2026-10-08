<?php

declare(strict_types=1);

namespace OCA\LocalBase\AppInfo;

use OCA\LocalBase\Service\FlzProductSuiteService;
use OCA\LocalBase\Listener\DemoAccountRegistryCleanupListener;
use OCA\LocalBase\Privacy\LocalBasePersonalDataProviderListener;
use OCA\LocalBase\Privacy\LocalBaseProcessingMetadataProviderListener;
use OCA\LocalBase\Privacy\NextcloudAccountPrivacyProviderListener;
use OCA\LocalBase\Privacy\PersonalDataProviderRegistryEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\LocalBase\Settings\StandaloneOrganizationAdmin;
use OCA\LocalBase\Settings\StandaloneProductAdminSection;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Settings\IManager;
use OCP\User\Events\UserDeletedEvent;

/**
 * Zweck: Registriert LocalBase und platziert die gemeinsame FLZ-Administration bei einer Einzelproduktinstallation dynamisch.
 * Zusammenspiel: Nextcloud-Bootstrap -> FlzProductSuiteService -> Settings-Manager.
 */
class Application extends App implements IBootstrap {
    public const APP_ID = AppId::VALUE;

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(PersonalDataProviderRegistryEvent::class, NextcloudAccountPrivacyProviderListener::class);
        $context->registerEventListener(RegisterPersonalDataProvidersEvent::class, LocalBasePersonalDataProviderListener::class);
        $context->registerEventListener(RegisterProcessingMetadataProvidersEvent::class, LocalBaseProcessingMetadataProviderListener::class);
        $context->registerEventListener(UserDeletedEvent::class, DemoAccountRegistryCleanupListener::class);
    }

    public function boot(IBootContext $context): void {
        $context->injectFn(static function (IManager $settings, FlzProductSuiteService $suite): void {
            if ($suite->standaloneProduct() === null) return;
            $settings->registerSection(IManager::SETTINGS_ADMIN, StandaloneProductAdminSection::class);
            $settings->registerSetting(IManager::SETTINGS_ADMIN, StandaloneOrganizationAdmin::class);
        });
    }
}
