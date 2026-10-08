<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;

/**
 * Zweck: Liefert app-übergreifend dieselben neutralen Demopersonen für die konfigurierte FLZ-Organisation.
 * Zusammenspiel: Fachapp-Demo-Packs -> FlzDemoFixtureCatalog -> FlzOrganizationDefinition.
 * Vertrag: Jede konfigurierte Standardrolle und jeder Bürobereich wird durch mindestens eine synthetische Person abgedeckt.
 */
final class FlzDemoFixtureCatalog {
    public function __construct(
        private ?FlzOrganizationSettingsService $organization = null,
        private ?FlzOrganizationDefinition $override = null,
    ) {}

    /** @return list<array{uid:string,displayName:string,groups:list<string>}> */
    public function all(): array {
        $definition = $this->override ?? $this->organization?->definition() ?? FlzOrganizationDefinition::defaults();
        return array_map(static fn(array $fixture): array => [
            'uid' => $fixture['uid'],
            'displayName' => $fixture['displayName'],
            'groups' => array_values(array_filter(array_merge(
                array_map($definition->roleGroupId(...), $fixture['roles']),
                array_map($definition->areaGroupId(...), $fixture['areas']),
            ))),
        ], self::fixtures());
    }

    private static function fixtures(): array {
        return [
            ['uid' => 'flz-demo-gf-as', 'displayName' => 'Alma Adler (GF-AS)', 'roles' => ['gf_as'], 'areas' => []],
            ['uid' => 'flz-demo-gf-digi', 'displayName' => 'David Berger (GF-Digi)', 'roles' => ['gf_digi'], 'areas' => []],
            ['uid' => 'flz-demo-pdl', 'displayName' => 'Paula Lindner (PDL)', 'roles' => ['pdl'], 'areas' => []],
            ['uid' => 'flz-demo-stvpdl', 'displayName' => 'Pia Neumann (Stv. PDL)', 'roles' => ['deputy_pdl'], 'areas' => []],
            ['uid' => 'flz-demo-pflegebuero', 'displayName' => 'Bela Krämer (Büroorganisation Pflege)', 'roles' => ['care_office'], 'areas' => []],
            ['uid' => 'flz-demo-asdgf-digi', 'displayName' => 'Alexis Dorn (AsdGF-Digi)', 'roles' => ['assistant_gf_digi'], 'areas' => []],
            ['uid' => 'flz-demo-finanzleitung', 'displayName' => 'Leonie Frank (Leitung Finanzen und Lohn)', 'roles' => ['finance_lead'], 'areas' => []],
            ['uid' => 'flz-demo-finanzen', 'displayName' => 'Finn Lohmann (Finanzen)', 'roles' => ['finance'], 'areas' => []],
            ['uid' => 'flz-demo-lohn', 'displayName' => 'Luca Hoffmann (Lohn)', 'roles' => ['payroll'], 'areas' => []],
            ['uid' => 'flz-demo-it', 'displayName' => 'Imani Teich (IT)', 'roles' => ['it'], 'areas' => []],
            ['uid' => 'flz-demo-fahrzeugverwaltung', 'displayName' => 'Fatima Wagner (Fahrzeugverwaltung)', 'roles' => ['fleet_management'], 'areas' => []],
            ['uid' => 'flz-demo-sekretariat', 'displayName' => 'Samira König (Sekretariat)', 'roles' => ['secretariat'], 'areas' => []],
            ['uid' => 'flz-demo-empfang', 'displayName' => 'Eleni Schubert (Empfang)', 'roles' => ['reception'], 'areas' => []],
            ['uid' => 'flz-demo-hr', 'displayName' => 'Hanna Reuter (Stabsstelle HR)', 'roles' => ['staff_hr'], 'areas' => []],
            ['uid' => 'flz-demo-qmb', 'displayName' => 'Quinn Meyer (Stabsstelle Qualitätsmanagement)', 'roles' => ['staff_qmb'], 'areas' => []],
            ['uid' => 'flz-demo-bl-now', 'displayName' => 'Nora Winter (Büro Nordost und West, BL)', 'roles' => ['bl', 'office'], 'areas' => ['northeast', 'west']],
            ['uid' => 'flz-demo-bl-sued', 'displayName' => 'Sofia Kern (Büro Süd, BL)', 'roles' => ['bl', 'office'], 'areas' => ['south']],
            ['uid' => 'flz-demo-stvbl-no', 'displayName' => 'Nele Hartmann (EB Nordost, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['northeast']],
            ['uid' => 'flz-demo-stvbl-west', 'displayName' => 'Wiebke Hahn (EB West, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['west']],
            ['uid' => 'flz-demo-stvbl-sued', 'displayName' => 'Sina Maurer (EB Süd, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['south']],
            ['uid' => 'flz-demo-bo-no', 'displayName' => 'Mara Brandt (Büro Nordost)', 'roles' => ['office'], 'areas' => ['northeast']],
            ['uid' => 'flz-demo-bo-west', 'displayName' => 'Mika Werner (Büro West)', 'roles' => ['office'], 'areas' => ['west']],
            ['uid' => 'flz-demo-bo-sued', 'displayName' => 'Selin Krüger (Büro Süd)', 'roles' => ['office'], 'areas' => ['south']],
            ['uid' => 'flz-demo-eb-no', 'displayName' => 'Enna Busch (EB Nordost)', 'roles' => ['eb'], 'areas' => ['northeast']],
            ['uid' => 'flz-demo-eb-west', 'displayName' => 'Emil Weber (EB West)', 'roles' => ['eb'], 'areas' => ['west']],
            ['uid' => 'flz-demo-eb-sued', 'displayName' => 'Eda Sommer (EB Süd)', 'roles' => ['eb'], 'areas' => ['south']],
            ['uid' => 'flz-demo-pfk-a', 'displayName' => 'Petra Falk (PFK)', 'roles' => ['pfk'], 'areas' => []],
            ['uid' => 'flz-demo-pfk-b', 'displayName' => 'Robin Keller (PFK)', 'roles' => ['pfk'], 'areas' => []],
        ];
    }
}
