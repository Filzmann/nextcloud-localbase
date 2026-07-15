<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;

/**
 * Zweck: Liefert app-übergreifend dieselben neutralen Demopersonen für die konfigurierte AD-Organisation.
 * Zusammenspiel: Fachapp-Demo-Packs -> AdDemoFixtureCatalog -> AdOrganizationDefinition.
 * Vertrag: Jede konfigurierte Standardrolle und jeder Bürobereich wird durch mindestens eine synthetische Person abgedeckt.
 */
final class AdDemoFixtureCatalog {
    public function __construct(
        private ?AdOrganizationSettingsService $organization = null,
        private ?AdOrganizationDefinition $override = null,
    ) {}

    /** @return list<array{uid:string,displayName:string,groups:list<string>}> */
    public function all(): array {
        $definition = $this->override ?? $this->organization?->definition() ?? AdOrganizationDefinition::defaults();
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
            ['uid' => 'ad-demo-gf-as', 'displayName' => 'Alma Adler (GF-AS)', 'roles' => ['gf_as'], 'areas' => []],
            ['uid' => 'ad-demo-gf-digi', 'displayName' => 'David Berger (GF-Digi)', 'roles' => ['gf_digi'], 'areas' => []],
            ['uid' => 'ad-demo-pdl', 'displayName' => 'Paula Lindner (PDL)', 'roles' => ['pdl'], 'areas' => []],
            ['uid' => 'ad-demo-asdgf-digi', 'displayName' => 'Alexis Dorn (AsdGF-Digi)', 'roles' => ['assistant_gf_digi'], 'areas' => []],
            ['uid' => 'ad-demo-finanzleitung', 'displayName' => 'Leonie Frank (Leitung Finanzen und Lohn)', 'roles' => ['finance_lead'], 'areas' => []],
            ['uid' => 'ad-demo-finanzen', 'displayName' => 'Finn Lohmann (Finanzen und Lohn)', 'roles' => ['finance'], 'areas' => []],
            ['uid' => 'ad-demo-it', 'displayName' => 'Imani Teich (IT)', 'roles' => ['it'], 'areas' => []],
            ['uid' => 'ad-demo-sekretariat', 'displayName' => 'Samira König (Sekretariat)', 'roles' => ['secretariat'], 'areas' => []],
            ['uid' => 'ad-demo-hr', 'displayName' => 'Hanna Reuter (Stabsstelle HR)', 'roles' => ['staff_hr'], 'areas' => []],
            ['uid' => 'ad-demo-qmb', 'displayName' => 'Quinn Meyer (Stabsstelle Qualitätsmanagement)', 'roles' => ['staff_qmb'], 'areas' => []],
            ['uid' => 'ad-demo-bl-now', 'displayName' => 'Nora Winter (Büro Nordost und West, BL)', 'roles' => ['bl', 'office'], 'areas' => ['northeast', 'west']],
            ['uid' => 'ad-demo-bl-sued', 'displayName' => 'Sofia Kern (Büro Süd, BL)', 'roles' => ['bl', 'office'], 'areas' => ['south']],
            ['uid' => 'ad-demo-stvbl-no', 'displayName' => 'Nele Hartmann (EB Nordost, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['northeast']],
            ['uid' => 'ad-demo-stvbl-west', 'displayName' => 'Wiebke Hahn (EB West, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['west']],
            ['uid' => 'ad-demo-stvbl-sued', 'displayName' => 'Sina Maurer (EB Süd, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['south']],
            ['uid' => 'ad-demo-bo-no', 'displayName' => 'Mara Brandt (Büro Nordost)', 'roles' => ['office'], 'areas' => ['northeast']],
            ['uid' => 'ad-demo-bo-west', 'displayName' => 'Mika Werner (Büro West)', 'roles' => ['office'], 'areas' => ['west']],
            ['uid' => 'ad-demo-bo-sued', 'displayName' => 'Selin Krüger (Büro Süd)', 'roles' => ['office'], 'areas' => ['south']],
            ['uid' => 'ad-demo-eb-no', 'displayName' => 'Enna Busch (EB Nordost)', 'roles' => ['eb'], 'areas' => ['northeast']],
            ['uid' => 'ad-demo-eb-west', 'displayName' => 'Emil Weber (EB West)', 'roles' => ['eb'], 'areas' => ['west']],
            ['uid' => 'ad-demo-eb-sued', 'displayName' => 'Eda Sommer (EB Süd)', 'roles' => ['eb'], 'areas' => ['south']],
            ['uid' => 'ad-demo-pfk-a', 'displayName' => 'Petra Falk (PFK)', 'roles' => ['pfk'], 'areas' => []],
            ['uid' => 'ad-demo-pfk-b', 'displayName' => 'Robin Keller (PFK)', 'roles' => ['pfk'], 'areas' => []],
        ];
    }
}
