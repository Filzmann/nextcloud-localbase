<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

/** Zweck: Bewertet Hierarchie-, Bereichs- und freigeschaltete Peer-Rechte ohne Nextcloud-Infrastruktur. */
class AdOrganizationPermissionPolicy {
    public function __construct(protected AdOrganizationHierarchy $hierarchy) {}

    public function canManage(string $actorUid, bool $isAdmin, array $actorGroups, string $targetUid, array $targetGroups, array $peerGroups = [], bool $allowSelf = true): bool {
        if ($isAdmin || ($allowSelf && $actorUid === $targetUid)) return true;
        if ($actorUid === $targetUid) return false;
        if ($this->hierarchy->manages($actorGroups, $targetGroups)) return true;
        if ($this->hierarchy->targetIsSuperior($actorGroups, $targetGroups)) return false;
        foreach ($peerGroups as $group) {
            if (!in_array($group, $actorGroups, true) || !in_array($group, $targetGroups, true)) continue;
            if (in_array($group, [AdOrganizationHierarchy::ROLE_OFFICE, AdOrganizationHierarchy::ROLE_EB], true)) {
                if (array_intersect($this->areas($actorGroups), $this->areas($targetGroups)) !== []) return true;
                continue;
            }
            return true;
        }
        if (array_intersect([AdOrganizationHierarchy::ROLE_OFFICE, AdOrganizationHierarchy::ROLE_EB], $targetGroups) === []) return false;
        $leader = array_intersect([AdOrganizationHierarchy::BL, AdOrganizationHierarchy::DEPUT_BL], $actorGroups) !== [];
        return $leader && array_intersect($this->areas($actorGroups), $this->areas($targetGroups)) !== [];
    }

    private function areas(array $groups): array { return array_values(array_filter($groups, static fn(string $id): bool => str_starts_with($id, AdOrganizationHierarchy::AREA_PREFIX))); }
}
