<?php

declare(strict_types=1);

namespace OCP\AppFramework {
    class App { public function __construct(string $appId, array $urlParams = []) {} }
}
namespace OCP\AppFramework\Bootstrap {
    interface IBootstrap {}
    interface IRegistrationContext { public function registerEventListener(string $event, string $listener): void; }
    interface IBootContext { public function injectFn(callable $fn): void; }
}
namespace OCP { interface IUser {} }
namespace OCP\App {
    interface IAppManager { public function isEnabledForUser($appId, $user = null); }
}
namespace OCP\Settings {
    interface IManager {
        public const SETTINGS_ADMIN = 'admin';
        public function registerSection(string $type, string $section);
        public function registerSetting(string $type, string $setting);
    }
}

namespace {

    use OCA\LocalBase\AppInfo\Application;
    use OCA\LocalBase\Privacy\NextcloudAccountPrivacyProviderListener;
    use OCA\LocalBase\Privacy\LocalBasePersonalDataProviderListener;
    use OCA\LocalBase\Privacy\LocalBaseProcessingMetadataProviderListener;
    use OCA\LocalBase\Listener\DemoAccountRegistryCleanupListener;
    use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
    use OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
    use OCA\LocalBase\Privacy\PersonalDataProviderRegistryEvent;
    use OCA\LocalBase\Service\AdProductSuiteService;
    use OCA\LocalBase\Settings\StandaloneOrganizationAdmin;
    use OCA\LocalBase\Settings\StandaloneProductAdminSection;
    use OCP\App\IAppManager;
    use OCP\AppFramework\Bootstrap\IBootContext;
    use OCP\AppFramework\Bootstrap\IRegistrationContext;
    use OCP\Settings\IManager;
    use OCP\User\Events\UserDeletedEvent;

    $apps = new class implements IAppManager {
        public function isEnabledForUser($appId, $user = null): bool {
            return $appId === 'adcalendar';
        }
    };
    $suite = new AdProductSuiteService($apps);
    $settings = new class implements IManager {
        public array $sections = [];
        public array $settings = [];
        public function registerSection(string $type, string $section): void { $this->sections[] = [$type, $section]; }
        public function registerSetting(string $type, string $setting): void { $this->settings[] = [$type, $setting]; }
    };
    $boot = new class($settings, $suite) implements IBootContext {
        public function __construct(private IManager $settings, private AdProductSuiteService $suite) {}
        public function injectFn(callable $fn): void { $fn($this->settings, $this->suite); }
    };

    $application = new Application();
    $registration = new class implements IRegistrationContext {
        public array $listeners = [];
        public function registerEventListener(string $event, string $listener): void { $this->listeners[] = [$event, $listener]; }
    };
    $application->register($registration);
    $application->boot($boot);

    if ($registration->listeners !== [
        [PersonalDataProviderRegistryEvent::class, NextcloudAccountPrivacyProviderListener::class],
        [RegisterPersonalDataProvidersEvent::class, LocalBasePersonalDataProviderListener::class],
        [RegisterProcessingMetadataProvidersEvent::class, LocalBaseProcessingMetadataProviderListener::class],
        [UserDeletedEvent::class, DemoAccountRegistryCleanupListener::class],
    ]) {
        throw new RuntimeException('LocalBase-Provider oder Kontolöschbereinigung wurden nicht registriert.');
    }

    if ($settings->sections !== [[IManager::SETTINGS_ADMIN, StandaloneProductAdminSection::class]]) {
        throw new RuntimeException('Standalone-Adminabschnitt wurde nicht registriert.');
    }
    if ($settings->settings !== [[IManager::SETTINGS_ADMIN, StandaloneOrganizationAdmin::class]]) {
        throw new RuntimeException('Standalone-Organisationseinstellung wurde nicht registriert.');
    }

    echo "LocalBase application bootstrap execution test passed\n";
}
