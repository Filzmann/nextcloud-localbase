<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;
use OCP\IGroupManager;

/**
 * Zweck: Prüft die konfigurierte AD-Organisation gegen alle in Nextcloud sichtbaren Gruppen-Backends.
 * Zusammenspiel: Admin-API -> OrganizationDirectoryStatusService -> IGroupManager; LDAP bleibt vollständig im Nextcloud-Backend gekapselt.
 * Vertrag: Read-only Gruppen sind für produktive Rechte zulässig, blockieren aber Demo-Packs, die Mitgliedschaften verändern würden.
 */
final class OrganizationDirectoryStatusService {
    public function __construct(
        private IGroupManager $groups,
        private ?AdOrganizationSettingsService $organization = null,
    ) {}

    public function status(): array {
        $definition = $this->organization?->definition() ?? AdOrganizationDefinition::defaults();
        $result = [];
        foreach ($definition->roles() as $key => $role) {
            $result[] = $this->groupStatus('role', (string)$key, $role['groupId'], $role['label']);
        }
        foreach ($definition->areas() as $key => $area) {
            $result[] = $this->groupStatus('area', (string)$key, $area['groupId'], $area['label']);
        }

        return [
            'compatible' => array_filter($result, static fn(array $group): bool => !$group['exists']) === [],
            'demoWritable' => array_filter($result, static fn(array $group): bool => !$group['demoProvisionable']) === [],
            'groups' => $result,
            'positions' => $this->positions($definition),
        ];
    }

    /**
     * Liefert ausschließlich Anzeigenamen für ausdrücklich konfigurierte Einzelpositionen.
     * Bereichsrollen werden als Schnitt aus Rollen- und Bereichsgruppe aufgelöst.
     */
    private function positions(AdOrganizationDefinition $definition): array {
        $positions = [];
        foreach ($definition->roles() as $roleKey => $role) {
            if (!$role['singleOccupant']) continue;
            $roleUsers = $this->groupUsers($role['groupId']);
            if (!$role['areaScoped']) {
                $positions[] = $this->position((string)$roleKey, null, $roleUsers);
                continue;
            }
            foreach ($definition->areas() as $areaKey => $area) {
                $areaUsers = array_fill_keys(array_map(static fn($user): string => $user->getUID(), $this->groupUsers($area['groupId'])), true);
                $users = array_values(array_filter($roleUsers, static fn($user): bool => isset($areaUsers[$user->getUID()])));
                $positions[] = $this->position((string)$roleKey, (string)$areaKey, $users);
            }
        }
        return $positions;
    }

    private function groupUsers(string $groupId): array {
        return $this->groups->get($groupId)?->getUsers() ?? [];
    }

    private function position(string $roleKey, ?string $areaKey, array $users): array {
        $displayNames = array_map(static function ($user): string {
            $displayName = trim($user->getDisplayName());
            return $displayName !== '' ? $displayName : $user->getUID();
        }, $users);
        natcasesort($displayNames);
        return ['roleKey' => $roleKey, 'areaKey' => $areaKey, 'displayNames' => array_values($displayNames)];
    }

    private function groupStatus(string $kind, string $key, string $groupId, string $label): array {
        $group = $this->groups->get($groupId);
        $exists = $group !== null;
        $writable = $exists && $group->canAddUser();

        return [
            'kind' => $kind,
            'key' => $key,
            'label' => $label,
            'groupId' => $groupId,
            'exists' => $exists,
            'backendNames' => $exists ? array_values(array_map('strval', $group->getBackendNames())) : [],
            'membershipWritable' => $writable,
            'demoProvisionable' => !$exists || $writable,
        ];
    }
}
