<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

use InvalidArgumentException;

/**
 * Zweck: Hält den validierten, app-übergreifenden Vertrag der AD-Organisation.
 * Zusammenspiel: AdOrganizationSettingsService persistiert die Definition; Hierarchie, Rechte und Fachapps lesen denselben Snapshot.
 * Vertrag: Technische Rollenschlüssel bleiben stabil, während Gruppen-IDs, Anzeigenamen, Bereiche, Teams und Hierarchiekanten konfigurierbar sind.
 */
final class AdOrganizationDefinition {
    private const DEFAULT_SINGLE_OCCUPANT_ROLES = ['gf_as', 'pdl', 'deputy_pdl', 'gf_digi', 'assistant_gf_digi', 'finance_lead', 'bl', 'deputy_bl'];

    private function __construct(private array $data) {}

    public static function get(array $data): self {
        return new self(self::validate(self::migrate($data)));
    }

    /** @return list<self> */
    public static function get_all(array $items): array {
        return array_map(static fn(array $item): self => self::get($item), $items);
    }

    public static function defaults(): self {
        return self::get([
            'version' => 2,
            'teamGroupPrefix' => 'ad-ASN-',
            'teamLabelPrefix' => 'Assistenzteam',
            'teamCodeMaxLength' => 16,
            'staffBlockLabel' => 'Geschäftsführung, Leitungen und Stabsstellen',
            'roles' => [
                'gf_as' => self::role('ad-GF-AS', 'Geschäftsführung Assistenz', 10, staffBlock: true, singleOccupant: true),
                'pdl' => self::role('ad-PDL', 'Pflegedienstleitung', 20, staffBlock: true, singleOccupant: true),
                'staff_hr' => self::role('ad-Stab-HR', 'Stabsstelle HR', 30, peerEnabled: true, staffBlock: true),
                'staff_qmb' => self::role('ad-Stab-QMB', 'Stabsstelle Qualitätsmanagement', 40, peerEnabled: true, staffBlock: true),
                'gf_digi' => self::role('ad-GF-Digi', 'Geschäftsführung Digitales und Finanzen', 50, staffBlock: true, singleOccupant: true),
                'assistant_gf_digi' => self::role('ad-AsdGF-Digi', 'Assistenz der Geschäftsführung Digitalisierung', 60, staffBlock: true, singleOccupant: true),
                'finance_lead' => self::role('ad-Leitung-Finanzen-Lohn', 'Leitung Finanzen und Lohn', 70, staffBlock: true, singleOccupant: true),
                'finance' => self::role('ad-Finanzen-Lohn', 'Finanzen und Lohn', 80, peerEnabled: true, staffBlock: true),
                'it' => self::role('ad-IT', 'IT', 90, peerEnabled: true, staffBlock: true),
                'fleet_management' => self::role('ad-Fahrzeugverwaltung', 'Fahrzeugverwaltung', 95),
                'secretariat' => self::role('ad-Sekretariat', 'Sekretariat', 100, peerEnabled: true, staffBlock: true),
                'reception' => self::role('ad-Empfang', 'Empfang', 105),
                'bl' => self::role('ad-BL', 'Büroleitung', 200, areaScoped: true, managementAreaScoped: true, singleOccupant: true),
                'deputy_bl' => self::role('ad-StvBL', 'Stellvertretende Büroleitung', 210, areaScoped: true, managementAreaScoped: true, singleOccupant: true),
                'office' => self::role('ad-Buero', 'Büromitarbeiter*innen', 230, areaScoped: true, peerEnabled: true),
                'deputy_pdl' => self::role('ad-StvPDL', 'Stellvertretende Pflegedienstleitung', 235, singleOccupant: true),
                'care_office' => self::role('ad-Bueroorganisation-Pflege', 'Büroorganisation Pflege', 237),
                'eb' => self::role('ad-EB', 'Einsatzbegleitung', 220, areaScoped: true, peerEnabled: true),
                'pfk' => self::role('ad-PFK', 'Pflegefachkraft', 240, peerEnabled: true),
            ],
            'areas' => [
                'northeast' => ['groupId' => 'ad-Bereich-Nordost', 'label' => 'Nordost', 'sortOrder' => 10],
                'west' => ['groupId' => 'ad-Bereich-West', 'label' => 'West', 'sortOrder' => 20],
                'south' => ['groupId' => 'ad-Bereich-Sued', 'label' => 'Süd', 'sortOrder' => 30],
            ],
            'hierarchy' => [
                'gf_as' => ['pdl', 'bl', 'staff_hr', 'staff_qmb', 'secretariat'],
                'gf_digi' => ['assistant_gf_digi', 'finance_lead', 'fleet_management', 'secretariat'],
                'assistant_gf_digi' => ['it'],
                'finance_lead' => ['finance'],
                'pdl' => ['deputy_pdl', 'care_office', 'pfk'],
                'deputy_pdl' => ['care_office', 'pfk'],
                'secretariat' => ['reception'],
                'bl' => ['deputy_bl', 'office', 'eb'],
                'deputy_bl' => ['office', 'eb'],
            ],
            'diagramOrder' => [],
            'organizationTeams' => [
                ['id' => 'office-northeast', 'label' => 'Büro Nordost', 'roles' => ['office', 'bl', 'deputy_bl'], 'areas' => ['northeast'], 'sortOrder' => 10],
                ['id' => 'office-west', 'label' => 'Büro West', 'roles' => ['office', 'bl', 'deputy_bl'], 'areas' => ['west'], 'sortOrder' => 20],
                ['id' => 'office-south', 'label' => 'Büro Süd', 'roles' => ['office', 'bl', 'deputy_bl'], 'areas' => ['south'], 'sortOrder' => 30],
                ['id' => 'eb', 'label' => 'Einsatzbegleitungen', 'roles' => ['eb'], 'areas' => [], 'sortOrder' => 40],
                ['id' => 'pfk', 'label' => 'Pflegefachkräfte', 'roles' => ['deputy_pdl', 'care_office', 'pfk'], 'areas' => [], 'sortOrder' => 50],
                ['id' => 'staff', 'label' => 'Geschäftsführung, Leitungen und Stabsstellen', 'roles' => ['gf_as', 'pdl', 'staff_hr', 'staff_qmb', 'gf_digi', 'assistant_gf_digi', 'finance_lead', 'finance', 'it', 'secretariat'], 'areas' => [], 'sortOrder' => 60],
                ['id' => 'fleet-management', 'label' => 'Fahrzeugverwaltung', 'roles' => ['fleet_management'], 'areas' => [], 'sortOrder' => 70],
                ['id' => 'reception', 'label' => 'Empfang', 'roles' => ['reception'], 'areas' => [], 'sortOrder' => 80],
            ],
        ]);
    }

    public function toArray(): array { return $this->data; }

    public function save(): never {
        throw new \LogicException('Organisationsdefinitionen werden über den Einstellungsservice gespeichert.');
    }

    public function roles(): array { return $this->data['roles']; }
    public function areas(): array { return $this->data['areas']; }
    public function hierarchy(): array { return $this->data['hierarchy']; }
    public function diagramOrder(): array { return $this->data['diagramOrder']; }
    public function organizationTeams(): array { return $this->data['organizationTeams']; }
    public function teamGroupPrefix(): string { return $this->data['teamGroupPrefix']; }
    public function teamLabelPrefix(): string { return $this->data['teamLabelPrefix']; }
    public function teamCodeMaxLength(): int { return $this->data['teamCodeMaxLength']; }
    public function staffBlockLabel(): string { return $this->data['staffBlockLabel']; }

    /**
     * Vertrag: Ein Assistenzteam-Kürzel besteht nur aus Unicode-Buchstaben und Ziffern.
     * Dadurch werden ähnlich benannte technische oder frühere Suffix-Gruppen nicht versehentlich als eigene Teams behandelt.
     */
    public function normalizeTeamCode(string $teamCode): string {
        $teamCode = trim($teamCode);
        $maxLength = $this->teamCodeMaxLength();
        if (!preg_match('/^[\p{L}\p{N}]{1,' . $maxLength . '}$/u', $teamCode)) {
            throw new InvalidArgumentException("Teamkürzel dürfen höchstens {$maxLength} Buchstaben oder Ziffern enthalten; Umlaute sind erlaubt.");
        }
        return $teamCode;
    }

    /** @return list<string> */
    public function roleGroupIds(?callable $filter = null): array {
        $result = [];
        foreach ($this->roles() as $role) if ($filter === null || $filter($role)) $result[] = $role['groupId'];
        return $result;
    }

    /** @return list<string> */
    public function areaGroupIds(): array { return array_values(array_column($this->areas(), 'groupId')); }

    /** @return list<string> */
    public function roleKeysForGroups(array $groupIds): array {
        $lookup = array_fill_keys(array_map('strval', $groupIds), true);
        $result = [];
        foreach ($this->roles() as $key => $role) if (isset($lookup[$role['groupId']])) $result[] = $key;
        return $result;
    }

    /** @return list<string> */
    public function areaKeysForGroups(array $groupIds): array {
        $lookup = array_fill_keys(array_map('strval', $groupIds), true);
        $result = [];
        foreach ($this->areas() as $key => $area) if (isset($lookup[$area['groupId']])) $result[] = $key;
        return $result;
    }

    public function roleGroupId(string $key): ?string { return $this->roles()[$key]['groupId'] ?? null; }
    public function roleLabel(string $key): string { return $this->roles()[$key]['label'] ?? $key; }
    public function areaGroupId(string $key): ?string { return $this->areas()[$key]['groupId'] ?? null; }
    public function areaLabel(string $key): string { return $this->areas()[$key]['label'] ?? $key; }

    public function roleLabelForGroup(string $groupId): string {
        foreach ($this->roles() as $role) if ($role['groupId'] === $groupId) return $role['label'];
        return $groupId;
    }

    public function areaLabelForGroup(string $groupId): string {
        foreach ($this->areas() as $area) if ($area['groupId'] === $groupId) return $area['label'];
        return $groupId;
    }

    public function roleIsAreaScopedByGroup(string $groupId): bool {
        foreach ($this->roles() as $role) if ($role['groupId'] === $groupId) return $role['areaScoped'];
        return false;
    }

    public function roleManagementIsAreaScoped(string $key): bool { return (bool)($this->roles()[$key]['managementAreaScoped'] ?? false); }

    public function roleKeyForGroup(string $groupId): ?string {
        foreach ($this->roles() as $key => $role) if ($role['groupId'] === $groupId) return $key;
        return null;
    }

    public function managesRole(string $actorRole, string $targetRole): bool {
        return $this->reaches($actorRole, $targetRole, []);
    }

    private function reaches(string $actorRole, string $targetRole, array $visited): bool {
        if (isset($visited[$actorRole])) return false;
        $visited[$actorRole] = true;
        foreach ($this->hierarchy()[$actorRole] ?? [] as $child) {
            if ($child === $targetRole || $this->reaches($child, $targetRole, $visited)) return true;
        }
        return false;
    }

    private static function role(string $groupId, string $label, int $sortOrder, bool $areaScoped = false, bool $managementAreaScoped = false, bool $peerEnabled = false, bool $staffBlock = false, bool $singleOccupant = false): array {
        return compact('groupId', 'label', 'sortOrder', 'areaScoped', 'managementAreaScoped', 'peerEnabled', 'staffBlock', 'singleOccupant') + ['calendarVisible' => true];
    }

    /**
     * Zweck: Ergänzt den freigegebenen Organisationsvertrag v1 additiv, ohne vorhandene Fachwerte umzuschreiben.
     * Vertrag: Kollisionen und durch neue Kanten entstehende Zyklen werden anschließend von validate() abgelehnt.
     */
    private static function migrate(array $data): array {
        $version = (int)($data['version'] ?? 1);
        if ($version === 2) return $data;
        if ($version !== 1) throw new InvalidArgumentException("Organisationsversion {$version} wird nicht unterstützt.");
        if (!isset($data['roles'], $data['hierarchy'], $data['organizationTeams']) || !is_array($data['roles']) || !is_array($data['hierarchy']) || !is_array($data['organizationTeams'])) return $data;

        foreach (self::versionTwoRoles() as $key => $role) if (!isset($data['roles'][$key])) $data['roles'][$key] = $role;
        foreach ([
            'pdl' => ['deputy_pdl', 'care_office', 'pfk'],
            'deputy_pdl' => ['care_office', 'pfk'],
            'gf_digi' => ['fleet_management'],
            'secretariat' => ['reception'],
        ] as $manager => $targets) {
            if (!isset($data['hierarchy'][$manager])) $data['hierarchy'][$manager] = [];
            if (!is_array($data['hierarchy'][$manager])) continue;
            foreach ($targets as $target) if (!in_array($target, $data['hierarchy'][$manager], true)) $data['hierarchy'][$manager][] = $target;
        }

        self::migrateOrganizationTeam($data['organizationTeams'], 'pfk', 'Pflegefachkräfte', ['deputy_pdl', 'care_office', 'pfk'], 50, true);
        self::migrateOrganizationTeam($data['organizationTeams'], 'fleet-management', 'Fahrzeugverwaltung', ['fleet_management'], 70);
        self::migrateOrganizationTeam($data['organizationTeams'], 'reception', 'Empfang', ['reception'], 80);
        $data['version'] = 2;
        return $data;
    }

    private static function versionTwoRoles(): array {
        return [
            'deputy_pdl' => self::role('ad-StvPDL', 'Stellvertretende Pflegedienstleitung', 235, singleOccupant: true),
            'care_office' => self::role('ad-Bueroorganisation-Pflege', 'Büroorganisation Pflege', 237),
            'fleet_management' => self::role('ad-Fahrzeugverwaltung', 'Fahrzeugverwaltung', 95),
            'reception' => self::role('ad-Empfang', 'Empfang', 105),
        ];
    }

    private static function migrateOrganizationTeam(array &$teams, string $id, string $label, array $requiredRoles, int $sortOrder, bool $prepend = false): void {
        foreach ($teams as &$team) {
            if (!is_array($team) || ($team['id'] ?? null) !== $id) continue;
            if (!isset($team['roles']) || !is_array($team['roles'])) return;
            $existingRoles = array_values(array_map('strval', $team['roles']));
            $missingRoles = array_values(array_diff($requiredRoles, $existingRoles));
            $team['roles'] = $prepend ? array_values(array_unique([...$missingRoles, ...$existingRoles])) : array_values(array_unique([...$existingRoles, ...$missingRoles]));
            unset($team);
            return;
        }
        unset($team);
        $teams[] = ['id' => $id, 'label' => $label, 'roles' => $requiredRoles, 'areas' => [], 'sortOrder' => $sortOrder];
    }

    private static function validate(array $data): array {
        foreach (['roles', 'areas', 'hierarchy', 'organizationTeams'] as $key) if (!isset($data[$key]) || !is_array($data[$key])) throw new InvalidArgumentException("Organisationsfeld {$key} fehlt.");
        $teamGroupPrefix = self::text($data['teamGroupPrefix'] ?? '', 'Team-Gruppenpräfix', 64);
        $teamLabelPrefix = self::text($data['teamLabelPrefix'] ?? '', 'Anzeigename der Assistenzteams', 64);
        $teamCodeMaxLength = (int)($data['teamCodeMaxLength'] ?? 0);
        if ($teamCodeMaxLength < 1 || $teamCodeMaxLength > 64) throw new InvalidArgumentException('Die maximale Teamkürzellänge muss zwischen 1 und 64 liegen.');
        $staffBlockLabel = self::text($data['staffBlockLabel'] ?? '', 'Titel des Leitungs- und Stabsblocks', 120);

        $roles = [];
        $groupIds = [];
        foreach ($data['roles'] as $key => $role) {
            self::key((string)$key, 'Rollenschlüssel');
            if (!is_array($role)) throw new InvalidArgumentException("Rolle {$key} ist ungültig.");
            $groupId = self::text($role['groupId'] ?? '', "Gruppen-ID der Rolle {$key}", 255);
            if (isset($groupIds[$groupId])) throw new InvalidArgumentException("Die Gruppen-ID {$groupId} ist mehrfach vergeben.");
            $groupIds[$groupId] = true;
            $roles[(string)$key] = [
                'groupId' => $groupId,
                'label' => self::text($role['label'] ?? '', "Anzeigename der Rolle {$key}", 120),
                'sortOrder' => (int)($role['sortOrder'] ?? 0),
                'areaScoped' => (bool)($role['areaScoped'] ?? false),
                'managementAreaScoped' => (bool)($role['managementAreaScoped'] ?? false),
                'peerEnabled' => (bool)($role['peerEnabled'] ?? false),
                'staffBlock' => (bool)($role['staffBlock'] ?? false),
                'singleOccupant' => (bool)($role['singleOccupant'] ?? in_array((string)$key, self::DEFAULT_SINGLE_OCCUPANT_ROLES, true)),
                'calendarVisible' => (bool)($role['calendarVisible'] ?? true),
            ];
        }
        foreach (['eb', 'deputy_pdl', 'care_office', 'fleet_management', 'reception'] as $requiredRole) if (!isset($roles[$requiredRole])) throw new InvalidArgumentException("Die fachliche Rolle {$requiredRole} fehlt.");

        $areas = [];
        foreach ($data['areas'] as $key => $area) {
            self::key((string)$key, 'Bereichsschlüssel');
            if (!is_array($area)) throw new InvalidArgumentException("Bereich {$key} ist ungültig.");
            $groupId = self::text($area['groupId'] ?? '', "Gruppen-ID des Bereichs {$key}", 255);
            if (isset($groupIds[$groupId])) throw new InvalidArgumentException("Die Gruppen-ID {$groupId} ist mehrfach vergeben.");
            $groupIds[$groupId] = true;
            $areas[(string)$key] = ['groupId' => $groupId, 'label' => self::text($area['label'] ?? '', "Anzeigename des Bereichs {$key}", 120), 'sortOrder' => (int)($area['sortOrder'] ?? 0)];
        }

        // Spiegelvertrag: js/components/hierarchy-board.js erzeugt dieselben Rollen- bzw. Rolle::Bereich-Knoten-IDs.
        $knownDiagramNodes = [];
        foreach ($roles as $roleKey => $role) {
            if (!$role['areaScoped']) {
                $knownDiagramNodes[$roleKey] = true;
                continue;
            }
            foreach (array_keys($areas) as $areaKey) $knownDiagramNodes[$roleKey . '::' . $areaKey] = true;
        }
        $rawDiagramOrder = $data['diagramOrder'] ?? [];
        if (!is_array($rawDiagramOrder)) throw new InvalidArgumentException('Die visuelle Diagrammordnung ist ungültig.');
        $diagramOrder = [];
        $seenDiagramNodes = [];
        foreach ($rawDiagramOrder as $nodeId) {
            $nodeId = (string)$nodeId;
            if (!isset($knownDiagramNodes[$nodeId])) throw new InvalidArgumentException("Diagrammknoten {$nodeId} ist ungültig.");
            if (isset($seenDiagramNodes[$nodeId])) throw new InvalidArgumentException("Diagrammknoten {$nodeId} ist mehrfach angeordnet.");
            $seenDiagramNodes[$nodeId] = true;
            $diagramOrder[] = $nodeId;
        }

        $hierarchy = [];
        foreach ($data['hierarchy'] as $manager => $targets) {
            if (!isset($roles[$manager]) || !is_array($targets)) throw new InvalidArgumentException("Hierarchieeintrag {$manager} ist ungültig.");
            $normalizedTargets = [];
            foreach ($targets as $target) {
                $target = (string)$target;
                if (!isset($roles[$target]) || $target === $manager) throw new InvalidArgumentException("Hierarchieziel {$target} ist ungültig.");
                $normalizedTargets[] = $target;
            }
            $hierarchy[(string)$manager] = array_values(array_unique($normalizedTargets));
        }
        self::assertAcyclic($hierarchy, array_keys($roles));

        $teams = [];
        $teamIds = [];
        foreach ($data['organizationTeams'] as $team) {
            if (!is_array($team)) throw new InvalidArgumentException('Eine Organisationsteam-Definition ist ungültig.');
            $id = self::text($team['id'] ?? '', 'Team-ID', 64);
            self::key($id, 'Team-ID');
            if (isset($teamIds[$id])) throw new InvalidArgumentException("Die Team-ID {$id} ist mehrfach vergeben.");
            $teamIds[$id] = true;
            $roleKeys = self::references($team['roles'] ?? null, $roles, "Rollen des Teams {$id}");
            $areaKeys = self::references($team['areas'] ?? null, $areas, "Bereiche des Teams {$id}");
            $teams[] = ['id' => $id, 'label' => self::text($team['label'] ?? '', "Anzeigename des Teams {$id}", 120), 'roles' => $roleKeys, 'areas' => $areaKeys, 'sortOrder' => (int)($team['sortOrder'] ?? 0)];
        }

        return [
            'version' => 2,
            'teamGroupPrefix' => $teamGroupPrefix,
            'teamLabelPrefix' => $teamLabelPrefix,
            'teamCodeMaxLength' => $teamCodeMaxLength,
            'staffBlockLabel' => $staffBlockLabel,
            'roles' => $roles,
            'areas' => $areas,
            'hierarchy' => $hierarchy,
            'diagramOrder' => $diagramOrder,
            'organizationTeams' => $teams,
        ];
    }

    private static function references(mixed $values, array $known, string $label): array {
        if (!is_array($values)) throw new InvalidArgumentException("{$label} fehlen.");
        $result = [];
        foreach ($values as $value) {
            $value = (string)$value;
            if (!isset($known[$value])) throw new InvalidArgumentException("{$label} enthalten den unbekannten Schlüssel {$value}.");
            $result[] = $value;
        }
        return array_values(array_unique($result));
    }

    private static function assertAcyclic(array $hierarchy, array $roles): void {
        $visit = static function (string $role, array $path) use (&$visit, $hierarchy): void {
            if (isset($path[$role])) throw new InvalidArgumentException('Die Organisationshierarchie enthält einen Zyklus.');
            $path[$role] = true;
            foreach ($hierarchy[$role] ?? [] as $child) $visit($child, $path);
        };
        foreach ($roles as $role) $visit($role, []);
    }

    private static function text(mixed $value, string $label, int $maxLength): string {
        $value = trim((string)$value);
        $length = preg_match_all('/./us', $value, $characters);
        if ($value === '' || $length === false || $length > $maxLength) throw new InvalidArgumentException("{$label} ist leer, ungültig kodiert oder zu lang.");
        return $value;
    }

    private static function key(string $value, string $label): void {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $value)) throw new InvalidArgumentException("{$label} {$value} ist ungültig.");
    }
}
