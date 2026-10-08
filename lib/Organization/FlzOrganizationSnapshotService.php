<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

/** Veröffentlicht ausschließlich validierte semantische Rollen- und Bereichszuordnungen. */
final class FlzOrganizationSnapshotService {
    public function __construct(private FlzOrganizationSettingsService $settings) {}

    public function snapshot(): FlzOrganizationSnapshot {
        $state = $this->settings->state();
        $definition = $state['definition'];
        if (!$state['valid']) {
            return new FlzOrganizationSnapshot(false, (int)$definition->toArray()['version'], [], []);
        }

        $roles = [];
        foreach ($definition->roles() as $key => $role) {
            $roles[$key] = ['groupId' => $role['groupId'], 'label' => $role['label']];
        }
        $areas = [];
        foreach ($definition->areas() as $key => $area) {
            $areas[$key] = ['groupId' => $area['groupId'], 'label' => $area['label']];
        }
        return new FlzOrganizationSnapshot(true, (int)$definition->toArray()['version'], $roles, $areas);
    }
}
