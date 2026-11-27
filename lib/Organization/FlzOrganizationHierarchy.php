<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

use OCP\IL10N;

/**
 * Zweck: Bewertet die konfigurierbare, transitive FLZ-Weisungshierarchie für lokale Fachapps.
 * Zusammenspiel: SettingsService -> Definition -> Hierarchy -> PermissionPolicy.
 * Vertrag: Bereichsgebundene Leitungsrechte werden erst in der PermissionPolicy auf gemeinsame Bereiche begrenzt.
 */
class FlzOrganizationHierarchy {
    protected FlzOrganizationDefinition $definition;

    public function __construct(?FlzOrganizationSettingsService $settings = null, ?FlzOrganizationDefinition $definition = null, private ?IL10N $l10n = null) {
        $this->definition = $settings?->definition() ?? $definition ?? FlzOrganizationDefinition::defaults($this->l10n);
    }

    public function definition(): FlzOrganizationDefinition { return $this->definition; }

    public function manages(array $actorGroups, array $targetGroups): bool {
        foreach ($this->definition->roleKeysForGroups($actorGroups) as $actorRole) {
            foreach ($this->definition->roleKeysForGroups($targetGroups) as $targetRole) {
                if ($this->definition->managesRole($actorRole, $targetRole)) return true;
            }
        }
        return false;
    }

    public function managementRequiresSharedArea(array $actorGroups, array $targetGroups): bool {
        $matched = false;
        foreach ($this->definition->roleKeysForGroups($actorGroups) as $actorRole) {
            foreach ($this->definition->roleKeysForGroups($targetGroups) as $targetRole) {
                if (!$this->definition->managesRole($actorRole, $targetRole)) continue;
                $matched = true;
                if (!$this->definition->roleManagementIsAreaScoped($actorRole)) return false;
            }
        }
        return $matched;
    }

    public function targetIsSuperior(array $actorGroups, array $targetGroups): bool {
        return $this->manages($targetGroups, $actorGroups) && !$this->manages($actorGroups, $targetGroups);
    }
}
