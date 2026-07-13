<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

/** Zweck: Bewertet konfigurierbare Hierarchie-, Bereichs- und freigeschaltete Peer-Rechte ohne Nextcloud-Infrastruktur. */
class AdOrganizationPermissionPolicy {
    public function __construct(protected AdOrganizationHierarchy $hierarchy) {}

    public function canManage(string $actorUid, bool $isAdmin, array $actorGroups, string $targetUid, array $targetGroups, array $peerGroups = [], bool $allowSelf = true): bool {
        if ($isAdmin || ($allowSelf && $actorUid === $targetUid)) return true;
        if ($actorUid === $targetUid) return false;
        if ($this->hierarchy->manages($actorGroups, $targetGroups)) {
            if (!$this->hierarchy->managementRequiresSharedArea($actorGroups, $targetGroups) || $this->sharesArea($actorGroups, $targetGroups)) return true;
        }
        if ($this->hierarchy->targetIsSuperior($actorGroups, $targetGroups)) return false;
        foreach ($peerGroups as $group) {
            if (!in_array($group, $actorGroups, true) || !in_array($group, $targetGroups, true)) continue;
            $definition = $this->hierarchy->definition();
            if ($definition->roleKeyForGroup($group) === null) continue;
            if ($definition->roleIsAreaScopedByGroup($group)) {
                if ($this->sharesArea($actorGroups, $targetGroups)) return true;
                continue;
            }
            return true;
        }
        return false;
    }

    private function sharesArea(array $actorGroups, array $targetGroups): bool {
        $areaGroups = $this->hierarchy->definition()->areaGroupIds();
        return array_intersect($actorGroups, $targetGroups, $areaGroups) !== [];
    }
}
