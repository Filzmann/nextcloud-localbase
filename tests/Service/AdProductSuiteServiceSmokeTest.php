<?php

declare(strict_types=1);

namespace OCP { interface IUser {} }
namespace OCP\App { interface IAppManager { public function isEnabledForUser($appId, $user = null); } }

namespace {
    require_once __DIR__ . '/../../lib/Service/AdProductSuiteService.php';

    use OCA\LocalBase\Service\AdProductSuiteService;
    use OCP\App\IAppManager;

    $apps = new class implements IAppManager {
        public array $enabled = ['adcalendar'];
        public function isEnabledForUser($appId, $user = null): bool { return in_array($appId, $this->enabled, true); }
    };
    $service = new AdProductSuiteService($apps);
    if ($service->enabledProducts() !== ['adcalendar']) throw new RuntimeException('Einzelprodukt wird nicht erkannt.');
    if ($service->standaloneProduct() !== 'adcalendar') throw new RuntimeException('Standalone-Ziel fehlt.');

    $apps->enabled = ['adcalendar', 'adroom', 'orgsuite'];
    if ($service->enabledProducts() !== ['adcalendar', 'adroom']) throw new RuntimeException('Produktreihenfolge ist nicht stabil.');
    if ($service->standaloneProduct() !== null) throw new RuntimeException('Bei aktiver OrgSuite darf kein Standalone-Adminziel bestehen.');

    $apps->enabled = [];
    if ($service->standaloneProduct() !== null) throw new RuntimeException('Ohne Fachapp darf kein Adminziel bestehen.');
    if (AdProductSuiteService::label('adurlaub') !== 'AD Urlaub') throw new RuntimeException('Produktname fehlt.');

    echo "AdProductSuiteServiceSmokeTest: OK\n";
}
