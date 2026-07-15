<?php

declare(strict_types=1);

namespace OCP\AppFramework {
    class App { public function __construct(string $appId, array $urlParams = []) {} }
}
namespace OCP\AppFramework\Bootstrap {
    interface IBootstrap {}
    interface IRegistrationContext {}
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
    require_once __DIR__ . '/../../lib/Service/AdProductSuiteService.php';
    require_once __DIR__ . '/../../lib/AppInfo/Application.php';

    use OCA\LocalBase\AppInfo\Application;
    use OCA\LocalBase\Service\AdProductSuiteService;
    use OCA\LocalBase\Settings\StandaloneOrganizationAdmin;
    use OCA\LocalBase\Settings\StandaloneProductAdminSection;
    use OCP\App\IAppManager;
    use OCP\AppFramework\Bootstrap\IBootContext;
    use OCP\AppFramework\Bootstrap\IRegistrationContext;
    use OCP\Settings\IManager;

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
    $application->register(new class implements IRegistrationContext {});
    $application->boot($boot);

    if ($settings->sections !== [[IManager::SETTINGS_ADMIN, StandaloneProductAdminSection::class]]) {
        throw new RuntimeException('Standalone-Adminabschnitt wurde nicht registriert.');
    }
    if ($settings->settings !== [[IManager::SETTINGS_ADMIN, StandaloneOrganizationAdmin::class]]) {
        throw new RuntimeException('Standalone-Organisationseinstellung wurde nicht registriert.');
    }

    echo "LocalBase application bootstrap execution test passed\n";
}
