<?php

declare(strict_types=1);

namespace OCP { interface IUser {} interface IURLGenerator { public function imagePath(string $appName, string $file): string; } }
namespace OCP\App { interface IAppManager { public function isEnabledForUser($appId, $user = null); } }
namespace OCP\Settings { interface ISettings { public function getForm(); public function getSection(): string; public function getPriority(): int; } interface IIconSection { public function getIcon(): string; public function getID(): string; public function getName(): string; public function getPriority(): int; } }
namespace OCP\AppFramework\Http { class TemplateResponse { public function __construct(public string $appName, public string $templateName, public array $params = []) {} } }

namespace {
    require_once __DIR__ . '/../../lib/Service/AdProductSuiteService.php';
    require_once __DIR__ . '/../../lib/Settings/StandaloneProductAdminSection.php';
    require_once __DIR__ . '/../../lib/Settings/StandaloneOrganizationAdmin.php';

    use OCA\LocalBase\Service\AdProductSuiteService;
    use OCA\LocalBase\Settings\StandaloneOrganizationAdmin;
    use OCA\LocalBase\Settings\StandaloneProductAdminSection;
    use OCP\App\IAppManager;
    use OCP\IURLGenerator;

    $apps = new class implements IAppManager { public function isEnabledForUser($appId, $user = null): bool { return $appId === 'adurlaub'; } };
    $suite = new AdProductSuiteService($apps);
    $url = new class implements IURLGenerator { public function imagePath(string $appName, string $file): string { return "$appName/$file"; } };
    $section = new StandaloneProductAdminSection($suite, $url);
    if ($section->getID() !== 'adurlaub' || $section->getName() !== 'AD Urlaub' || $section->getIcon() !== 'adurlaub/app.svg') throw new RuntimeException('Dynamischer Produktabschnitt ist falsch.');

    $setting = new StandaloneOrganizationAdmin($suite);
    $form = $setting->getForm();
    if ($setting->getSection() !== 'adurlaub' || $form->appName !== 'localbase' || $form->templateName !== 'organization-admin') throw new RuntimeException('Standalone-Organisationsformular ist falsch angebunden.');

    echo "StandaloneOrganizationSettingsSmokeTest: OK\n";
}
