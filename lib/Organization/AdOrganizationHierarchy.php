<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

/**
 * Zweck: Bildet die gemeinsame transitive AD-Weisungshierarchie fuer lokale Fachapps ab.
 * Vertrag: Bereichsgebundene BL/StvBL-Rechte werden erst in der PermissionPolicy auf gemeinsame Bereiche begrenzt.
 */
class AdOrganizationHierarchy {
    public const ROLE_EB = 'ad-EB';
    public const ROLE_PFK = 'ad-PFK';
    public const ROLE_OFFICE = 'ad-Buero';
    public const ROLE_STAFF_HR = 'ad-Stab-HR';
    public const ROLE_STAFF_QMB = 'ad-Stab-QMB';
    public const AREA_PREFIX = 'ad-Bereich-';
    public const GF_AS = 'ad-GF-AS';
    public const GF_DIGI = 'ad-GF-Digi';
    public const ASSISTANT_GF_DIGI = 'ad-AsdGF-Digi';
    public const FINANCE_LEAD = 'ad-Leitung-Finanzen-Lohn';
    public const FINANCE = 'ad-Finanzen-Lohn';
    public const IT = 'ad-IT';
    public const SECRETARIAT = 'ad-Sekretariat';
    public const PDL = 'ad-PDL';
    public const BL = 'ad-BL';
    public const DEPUT_BL = 'ad-StvBL';

    public function manages(array $actorGroups, array $targetGroups): bool {
        if (in_array(self::GF_AS, $actorGroups, true) && array_intersect($targetGroups, [self::PDL,self::BL,self::DEPUT_BL,self::ROLE_PFK,self::ROLE_OFFICE,self::ROLE_EB,self::ROLE_STAFF_HR,self::ROLE_STAFF_QMB,self::SECRETARIAT]) !== []) return true;
        if (in_array(self::GF_DIGI, $actorGroups, true) && array_intersect($targetGroups, [self::ASSISTANT_GF_DIGI,self::FINANCE_LEAD,self::FINANCE,self::IT,self::SECRETARIAT]) !== []) return true;
        if (in_array(self::ASSISTANT_GF_DIGI, $actorGroups, true) && in_array(self::IT, $targetGroups, true)) return true;
        if (in_array(self::FINANCE_LEAD, $actorGroups, true) && in_array(self::FINANCE, $targetGroups, true)) return true;
        if (in_array(self::PDL, $actorGroups, true) && in_array(self::ROLE_PFK, $targetGroups, true)) return true;
        return false;
    }

    public function targetIsSuperior(array $actorGroups, array $targetGroups): bool { return $this->structurallyManages($targetGroups, $actorGroups) && !$this->structurallyManages($actorGroups, $targetGroups); }
    private function structurallyManages(array $actorGroups, array $targetGroups): bool { return $this->manages($actorGroups, $targetGroups) || (array_intersect([self::BL,self::DEPUT_BL], $actorGroups) !== [] && array_intersect([self::ROLE_OFFICE,self::ROLE_EB], $targetGroups) !== []); }
}
