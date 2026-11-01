<?php

declare(strict_types=1);

namespace OCA\LocalBase\PublicApi\V1;

use OCA\LocalBase\Organization\FlzOrganizationSettingsService;
use OCA\LocalBase\Organization\FlzSuiteAdminSettingsService;

/** Öffentliche Projektion der kanonischen LocalBase-Urlaubsfreigaben. */
final class VacationAuthorizationSnapshotService {
    public function __construct(
        private FlzOrganizationSettingsService $organization,
        private FlzSuiteAdminSettingsService $settings,
    ) {}

    public function snapshot(): VacationAuthorizationSnapshot {
        $state = $this->organization->state();
        $definition = $state['definition'];
        $definitionVersion = (int)$definition->toArray()['version'];
        if (!$state['valid'] || !$this->settings->isValid()) {
            return new VacationAuthorizationSnapshot(false, $definitionVersion, [], [], []);
        }

        $ebRole = $definition->roles()['eb'] ?? null;
        $roleGroupId = is_array($ebRole) ? ($ebRole['groupId'] ?? null) : null;
        if (!is_string($roleGroupId) || trim($roleGroupId) === '' || ($ebRole['areaScoped'] ?? false) !== true) {
            return new VacationAuthorizationSnapshot(false, $definitionVersion, [], [], []);
        }

        $areas = $definition->areas();
        uasort($areas, static fn(array $left, array $right): int => $left['sortOrder'] <=> $right['sortOrder']);
        $views = [];
        foreach ($areas as $areaKey => $area) {
            $views[] = [
                'id' => 'eb-' . $areaKey,
                'label' => 'EB – ' . $area['label'],
                'roleKey' => 'eb',
                'roleGroupId' => $roleGroupId,
                'areaKey' => (string)$areaKey,
                'areaGroupId' => $area['groupId'],
            ];
        }

        return new VacationAuthorizationSnapshot(
            true,
            $definitionVersion,
            $this->settings->vacationPeerEditing(),
            $this->settings->vacationPeerApproval(),
            $views,
        );
    }
}
