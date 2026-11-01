<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IAppConfig::class)) {
        interface IAppConfig {
            public function getValueString(string $appId, string $key, string $default = ''): string;
            public function setValueString(string $appId, string $key, string $value): void;
        }
    }
}

namespace OCA\LocalBase\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'localbase'; }
    }
}

namespace {
    use OCA\LocalBase\Organization\FlzOrganizationSettingsService;
    use OCA\LocalBase\Organization\FlzSuiteAdminSettingsService;
    use OCA\LocalBase\PublicApi\V1\VacationAuthorizationSnapshot;
    use OCA\LocalBase\PublicApi\V1\VacationAuthorizationSnapshotService;

    $config = new class implements \OCP\IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $organization = new FlzOrganizationSettingsService($config);
    $settings = new FlzSuiteAdminSettingsService($config, $organization);
    $service = new VacationAuthorizationSnapshotService($organization, $settings);

    $missing = $service->snapshot();
    if ($missing->contractVersion() !== VacationAuthorizationSnapshot::CONTRACT_VERSION
        || $missing->isValid()
        || $missing->peerEditing() !== []
        || $missing->peerApproval() !== []
        || $missing->ebAreaViews() !== []) {
        throw new RuntimeException('Eine fehlende Organisationsquelle veröffentlicht Urlaubsrechte oder Ansichten.');
    }

    $definition = $organization->definition()->toArray();
    $definition['roles']['eb']['groupId'] = 'org-eb';
    $definition['areas']['northeast']['groupId'] = 'org-area-no';
    $definition['areas']['northeast']['label'] = 'Nordostbüro';
    $organization->save($definition);

    $snapshot = $service->snapshot();
    $views = array_column($snapshot->ebAreaViews(), null, 'id');
    if (!$snapshot->isValid()
        || array_filter($snapshot->peerEditing()) !== []
        || array_filter($snapshot->peerApproval()) !== []
        || ($views['eb-northeast']['roleGroupId'] ?? null) !== 'org-eb'
        || ($views['eb-northeast']['areaGroupId'] ?? null) !== 'org-area-no'
        || ($views['eb-northeast']['label'] ?? null) !== 'EB – Nordostbüro') {
        throw new RuntimeException('Der öffentliche Urlaubsvertrag leitet Defaults oder EB-Büroansichten nicht kanonisch ab.');
    }

    $settings->saveVacationPeerEditing(['org-eb' => true]);
    $authorized = $service->snapshot();
    if (($authorized->peerEditing()['org-eb'] ?? false) !== true
        || array_filter($authorized->peerApproval()) !== []) {
        throw new RuntimeException('Bearbeitungsrecht erweitert unzulässig das Genehmigungsrecht.');
    }

    $notAreaScoped = $organization->definition()->toArray();
    $notAreaScoped['roles']['eb']['areaScoped'] = false;
    $organization->save($notAreaScoped);
    if ($service->snapshot()->isValid()) {
        throw new RuntimeException('EB-Büroansichten werden ohne bereichsgebundene EB-Rolle veröffentlicht.');
    }
    $organization->save($definition);

    $config->values['localbase']['flz_suite_admin_settings'] = '{kaputt';
    $invalid = $service->snapshot();
    if ($invalid->isValid() || $invalid->peerEditing() !== [] || $invalid->peerApproval() !== [] || $invalid->ebAreaViews() !== []) {
        throw new RuntimeException('Beschädigte Freigabeeinstellungen werden nicht fail-closed veröffentlicht.');
    }

    try {
        new VacationAuthorizationSnapshot(true, 4, ['peer' => true], [], [[
            'id' => 'eb-northeast',
            'label' => 'EB – Nordost',
            'roleKey' => 'eb',
            'roleGroupId' => '',
            'areaKey' => 'northeast',
            'areaGroupId' => 'org-area-no',
        ]]);
        throw new RuntimeException('Ein manipulierter EB-Bürodeskriptor wurde akzeptiert.');
    } catch (\InvalidArgumentException) {
    }

    echo "VacationAuthorizationSnapshotServiceSmokeTest: OK\n";
}
