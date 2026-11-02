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
    use OCA\LocalBase\Organization\FlzOrganizationSnapshotService;
    use OCA\LocalBase\Organization\FlzOrganizationDefinition;
    use OCA\LocalBase\Service\FlzDemoFixtureCatalog;

    $expectedRoles = [
        'gf_as' => 'GF-AS',
        'pdl' => 'PDL',
        'staff_hr' => 'Stab-HR',
        'staff_qmb' => 'Stab-QMB',
        'gf_digi' => 'GF-Digi',
        'assistant_gf_digi' => 'AsdGF-Digi',
        'finance_lead' => 'Leitung-Finanzen-Lohn',
        'finance' => 'Finanzen',
        'payroll' => 'Lohn',
        'it' => 'IT',
        'fleet_management' => 'Fahrzeugverwaltung',
        'secretariat' => 'Sekretariat',
        'reception' => 'Empfang',
        'bl' => 'BL',
        'deputy_bl' => 'StvBL',
        'office' => 'Buero',
        'deputy_pdl' => 'StvPDL',
        'care_office' => 'Bueroorganisation-Pflege',
        'eb' => 'EB',
        'pfk' => 'PFK',
    ];
    $expectedAreas = [
        'northeast' => 'Bereich-Nordost',
        'west' => 'Bereich-West',
        'south' => 'Bereich-Sued',
    ];

    $definition = FlzOrganizationDefinition::defaults();
    $roleIds = array_map(static fn(array $role): string => $role['groupId'], $definition->roles());
    $areaIds = array_map(static fn(array $area): string => $area['groupId'], $definition->areas());
    if ($roleIds !== $expectedRoles || $areaIds !== $expectedAreas || $definition->teamGroupPrefix() !== 'ASN-') {
        throw new RuntimeException('Die kanonischen Organisationsdefaults verwenden nicht ausschließlich die freigegebenen präfixlosen Gruppen-IDs.');
    }

    foreach ([...array_values($roleIds), ...array_values($areaIds), $definition->teamGroupPrefix() . 'A'] as $groupId) {
        if (preg_match('/^(?:flz|ad)-/i', $groupId) === 1) {
            throw new RuntimeException("Präfixgruppe {$groupId} ist noch Teil des ausführbaren Defaultvertrags.");
        }
    }
    if ($definition->roleKeyForGroup('flz-Finanzen-Lohn') !== null
        || $definition->roleKeysForGroups(['flz-Finanzen-Lohn']) !== []) {
        throw new RuntimeException('Der historische Finanz-/Lohn-Alias erteilt weiterhin eine semantische Rolle.');
    }

    foreach ((new FlzDemoFixtureCatalog(override: $definition))->all() as $fixture) {
        foreach ($fixture['groups'] as $groupId) {
            if (preg_match('/^(?:flz|ad)-/i', $groupId) === 1) {
                throw new RuntimeException("Demofixture {$fixture['uid']} verwendet noch die Präfixgruppe {$groupId}.");
            }
        }
    }

    $config = new class implements \OCP\IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $settings = new FlzOrganizationSettingsService($config);
    $settings->save($definition->toArray());
    $snapshot = (new FlzOrganizationSnapshotService($settings))->snapshot();
    if (!$snapshot->isValid() || $snapshot->roleGroupId('office') !== 'Buero' || $snapshot->areaGroupId('west') !== 'Bereich-West') {
        throw new RuntimeException('Der persistierte Snapshot projiziert nicht dieselben kanonischen präfixlosen Defaults.');
    }

    echo "PrefixlessOrganizationDefaultsContractTest: OK\n";
}
