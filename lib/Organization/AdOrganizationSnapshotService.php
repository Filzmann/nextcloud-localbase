<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

/** Veröffentlicht ausschließlich validierte semantische Rollen- und Bereichszuordnungen. */
final class AdOrganizationSnapshotService {
    public function __construct(private AdOrganizationSettingsService $settings) {}

    public function snapshot(): AdOrganizationSnapshot {
        $state = $this->settings->state();
        $definition = $state['definition'];
        if (!$state['valid']) {
            return new AdOrganizationSnapshot(false, (int)$definition->toArray()['version'], [], []);
        }

        $roles = [];
        foreach ($definition->roles() as $key => $role) {
            $roles[$key] = ['groupId' => $role['groupId'], 'label' => $role['label']];
        }
        $areas = [];
        foreach ($definition->areas() as $key => $area) {
            $areas[$key] = ['groupId' => $area['groupId'], 'label' => $area['label']];
        }
        return new AdOrganizationSnapshot(true, (int)$definition->toArray()['version'], $roles, $areas);
    }
}
