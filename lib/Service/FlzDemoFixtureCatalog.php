<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;
use OCP\IL10N;

/**
 * Zweck: Liefert app-übergreifend dieselben neutralen Demopersonen für die konfigurierte FLZ-Organisation.
 * Zusammenspiel: Fachapp-Demo-Packs -> FlzDemoFixtureCatalog -> FlzOrganizationDefinition.
 * Vertrag: Jede konfigurierte Standardrolle und jeder Bürobereich wird durch mindestens eine synthetische Person abgedeckt.
 */
final class FlzDemoFixtureCatalog {
    public function __construct(private ?FlzOrganizationSettingsService $organization = null,
        private ?FlzOrganizationDefinition $override = null,
        private ?IL10N $l10n = null) {}

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    /** @return list<array{uid:string,displayName:string,groups:list<string>}> */
    public function all(): array {
        $definition = $this->override ?? $this->organization?->definition() ?? FlzOrganizationDefinition::defaults($this->l10n);
        return array_map(static fn(array $fixture): array => [
            'uid' => $fixture['uid'],
            'displayName' => $fixture['displayName'],
            'groups' => array_values(array_filter(array_merge(
                array_map($definition->roleGroupId(...), $fixture['roles']),
                array_map($definition->areaGroupId(...), $fixture['areas']),
            ))),
        ], $this->fixtures());
    }

    private function fixtures(): array {
        return [
            ['uid' => 'flz-demo-gf-as', 'displayName' => $this->t('Alma Adler (GF-AS)'), 'roles' => ['gf_as'], 'areas' => []],
            ['uid' => 'flz-demo-gf-digi', 'displayName' => $this->t('David Berger (GF-Digi)'), 'roles' => ['gf_digi'], 'areas' => []],
            ['uid' => 'flz-demo-pdl', 'displayName' => $this->t('Paula Lindner (PDL)'), 'roles' => ['pdl'], 'areas' => []],
            ['uid' => 'flz-demo-stvpdl', 'displayName' => $this->t('Pia Neumann (Stv. PDL)'), 'roles' => ['deputy_pdl'], 'areas' => []],
            ['uid' => 'flz-demo-pflegebuero', 'displayName' => $this->t('Bela Krämer (Büroorganisation Pflege)'), 'roles' => ['care_office'], 'areas' => []],
            ['uid' => 'flz-demo-asdgf-digi', 'displayName' => $this->t('Alexis Dorn (AsdGF-Digi)'), 'roles' => ['assistant_gf_digi'], 'areas' => []],
            ['uid' => 'flz-demo-finanzleitung', 'displayName' => $this->t('Leonie Frank (Leitung Finanzen und Lohn)'), 'roles' => ['finance_lead'], 'areas' => []],
            ['uid' => 'flz-demo-finanzen', 'displayName' => $this->t('Finn Lohmann (Finanzen)'), 'roles' => ['finance'], 'areas' => []],
            ['uid' => 'flz-demo-lohn', 'displayName' => $this->t('Luca Hoffmann (Lohn)'), 'roles' => ['payroll'], 'areas' => []],
            ['uid' => 'flz-demo-it', 'displayName' => $this->t('Imani Teich (IT)'), 'roles' => ['it'], 'areas' => []],
            ['uid' => 'flz-demo-fahrzeugverwaltung', 'displayName' => $this->t('Fatima Wagner (Fahrzeugverwaltung)'), 'roles' => ['fleet_management'], 'areas' => []],
            ['uid' => 'flz-demo-sekretariat', 'displayName' => $this->t('Samira König (Sekretariat)'), 'roles' => ['secretariat'], 'areas' => []],
            ['uid' => 'flz-demo-empfang', 'displayName' => $this->t('Eleni Schubert (Empfang)'), 'roles' => ['reception'], 'areas' => []],
            ['uid' => 'flz-demo-hr', 'displayName' => $this->t('Hanna Reuter (Stabsstelle HR)'), 'roles' => ['staff_hr'], 'areas' => []],
            ['uid' => 'flz-demo-qmb', 'displayName' => $this->t('Quinn Meyer (Stabsstelle Qualitätsmanagement)'), 'roles' => ['staff_qmb'], 'areas' => []],
            ['uid' => 'flz-demo-bl-now', 'displayName' => $this->t('Nora Winter (Büro Nordost und West, BL)'), 'roles' => ['bl', 'office'], 'areas' => ['northeast', 'west']],
            ['uid' => 'flz-demo-bl-sued', 'displayName' => $this->t('Sofia Kern (Büro Süd, BL)'), 'roles' => ['bl', 'office'], 'areas' => ['south']],
            ['uid' => 'flz-demo-stvbl-no', 'displayName' => $this->t('Nele Hartmann (EB Nordost, Stv. BL)'), 'roles' => ['deputy_bl', 'eb'], 'areas' => ['northeast']],
            ['uid' => 'flz-demo-stvbl-west', 'displayName' => $this->t('Wiebke Hahn (EB West, Stv. BL)'), 'roles' => ['deputy_bl', 'eb'], 'areas' => ['west']],
            ['uid' => 'flz-demo-stvbl-sued', 'displayName' => $this->t('Sina Maurer (EB Süd, Stv. BL)'), 'roles' => ['deputy_bl', 'eb'], 'areas' => ['south']],
            ['uid' => 'flz-demo-bo-no', 'displayName' => $this->t('Mara Brandt (Büro Nordost)'), 'roles' => ['office'], 'areas' => ['northeast']],
            ['uid' => 'flz-demo-bo-west', 'displayName' => $this->t('Mika Werner (Büro West)'), 'roles' => ['office'], 'areas' => ['west']],
            ['uid' => 'flz-demo-bo-sued', 'displayName' => $this->t('Selin Krüger (Büro Süd)'), 'roles' => ['office'], 'areas' => ['south']],
            ['uid' => 'flz-demo-eb-no', 'displayName' => $this->t('Enna Busch (EB Nordost)'), 'roles' => ['eb'], 'areas' => ['northeast']],
            ['uid' => 'flz-demo-eb-west', 'displayName' => $this->t('Emil Weber (EB West)'), 'roles' => ['eb'], 'areas' => ['west']],
            ['uid' => 'flz-demo-eb-sued', 'displayName' => $this->t('Eda Sommer (EB Süd)'), 'roles' => ['eb'], 'areas' => ['south']],
            ['uid' => 'flz-demo-pfk-a', 'displayName' => $this->t('Petra Falk (PFK)'), 'roles' => ['pfk'], 'areas' => []],
            ['uid' => 'flz-demo-pfk-b', 'displayName' => $this->t('Robin Keller (PFK)'), 'roles' => ['pfk'], 'areas' => []],
        ];
    }
}
