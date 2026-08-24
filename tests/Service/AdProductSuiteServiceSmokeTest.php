<?php

declare(strict_types=1);

namespace OCP { interface IUser {} }
namespace OCP\App { interface IAppManager { public function isEnabledForUser($appId, $user = null); } }

namespace {

    use OCA\LocalBase\Catalog\AdProductCatalog;
    use OCA\LocalBase\Service\AdProductSuiteService;
    use OCP\App\IAppManager;

    $apps = new class implements IAppManager {
        public array $enabled = ['adcalendar'];
        public function isEnabledForUser($appId, $user = null): bool { return in_array($appId, $this->enabled, true); }
    };
    $service = new AdProductSuiteService($apps, new AdProductCatalog());
    if ($service->enabledProducts() !== ['adcalendar']) throw new RuntimeException('Einzelprodukt wird nicht erkannt.');
    if ($service->standaloneProduct() !== 'adcalendar') throw new RuntimeException('Standalone-Ziel fehlt.');

    $apps->enabled = ['adcalendar', 'adroom', 'adrecruitment', 'orgsuite'];
    if ($service->enabledProducts() !== ['adcalendar', 'adroom', 'adrecruitment']) throw new RuntimeException('Produktreihenfolge ist nicht stabil.');
    if ($service->standaloneProduct() !== null) throw new RuntimeException('Bei aktiver OrgSuite darf kein Standalone-Adminziel bestehen.');

    $apps->enabled = [];
    if ($service->standaloneProduct() !== null) throw new RuntimeException('Ohne Fachapp darf kein Adminziel bestehen.');
    if ($service->label('adrecruitment') !== 'AD Recruitment') throw new RuntimeException('Produktname stammt nicht aus dem Katalog.');

    $missingCatalog = new AdProductCatalog(__DIR__ . '/missing-catalog.json');
    $brokenService = new AdProductSuiteService($apps, $missingCatalog);
    if ($brokenService->enabledProducts() !== [] || $brokenService->label('adcalendar') !== 'adcalendar') {
        throw new RuntimeException('Fehlender Katalog erweitert Produkte oder verliert den sicheren Label-Fallback.');
    }

    echo "AdProductSuiteServiceSmokeTest: OK\n";
}
