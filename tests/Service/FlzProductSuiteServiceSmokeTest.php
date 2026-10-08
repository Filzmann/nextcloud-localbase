<?php

declare(strict_types=1);

namespace OCP { interface IUser {} }
namespace OCP\App { interface IAppManager { public function isEnabledForUser($appId, $user = null); } }

namespace {

    use OCA\LocalBase\Catalog\FlzProductCatalog;
    use OCA\LocalBase\Service\FlzProductSuiteService;
    use OCP\App\IAppManager;

    $apps = new class implements IAppManager {
        public array $enabled = ['flzcalendar'];
        public function isEnabledForUser($appId, $user = null): bool { return in_array($appId, $this->enabled, true); }
    };
    $service = new FlzProductSuiteService($apps, new FlzProductCatalog());
    if ($service->enabledProducts() !== ['flzcalendar']) throw new RuntimeException('Einzelprodukt wird nicht erkannt.');
    if ($service->standaloneProduct() !== 'flzcalendar') throw new RuntimeException('Standalone-Ziel fehlt.');

    $apps->enabled = ['flzcalendar', 'flzroom', 'flzrecruitment', 'orgsuite'];
    if ($service->enabledProducts() !== ['flzcalendar', 'flzroom', 'flzrecruitment']) throw new RuntimeException('Produktreihenfolge ist nicht stabil.');
    if ($service->standaloneProduct() !== null) throw new RuntimeException('Bei aktiver OrgSuite darf kein Standalone-Adminziel bestehen.');

    $apps->enabled = [];
    if ($service->standaloneProduct() !== null) throw new RuntimeException('Ohne Fachapp darf kein Adminziel bestehen.');
    if ($service->label('flzrecruitment') !== 'Filzmann Recruitment') throw new RuntimeException('Produktname stammt nicht aus dem Katalog.');

    $missingCatalog = new FlzProductCatalog(__DIR__ . '/missing-catalog.json');
    $brokenService = new FlzProductSuiteService($apps, $missingCatalog);
    if ($brokenService->enabledProducts() !== [] || $brokenService->label('flzcalendar') !== 'flzcalendar') {
        throw new RuntimeException('Fehlender Katalog erweitert Produkte oder verliert den sicheren Label-Fallback.');
    }

    echo "FlzProductSuiteServiceSmokeTest: OK\n";
}
