<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

use OCA\LocalBase\AppInfo\Application;
use OCP\IAppConfig;

/**
 * Zweck: Persistiert organisationsweite FLZ-Freigaben semantisch nach Rollen statt nach veränderlichen Gruppen-IDs.
 * Zusammenspiel: OrgSuite schreibt die Admin-Konfiguration; Filzmann Kalender und Filzmann Urlaubsplanung lesen daraus ihre aktiven Peer-Gruppen.
 * Vertrag: Nur Rollen mit peerEnabled werden ausgeliefert. Technische Gruppen-IDs werden erst beim Lesen aus der aktuellen Organisationsdefinition abgeleitet.
 */
final class FlzSuiteAdminSettingsService {
    private const KEY = 'flz_suite_admin_settings';
    private const CALENDAR_PEER_DEFAULT_ROLES = ['office', 'eb', 'pfk'];

    public function __construct(
        private IAppConfig $config,
        private FlzOrganizationSettingsService $organization,
    ) {}

    /** @return array<string,bool> */
    public function calendarPeerEditing(): array {
        return $this->roleValues('calendarPeerEditing');
    }

    /** @return list<string> */
    public function enabledCalendarPeerGroups(): array {
        return array_keys(array_filter($this->calendarPeerEditing()));
    }

    /** @return array<string,bool> */
    public function vacationPeerEditing(): array {
        $data = $this->data();
        return [$this->asnPeerGroup() => (bool)($data['vacationAsnPeerEditing'] ?? false)]
            + $this->roleValues('vacationPeerEditing');
    }

    /** @return list<string> */
    public function enabledVacationPeerEditingGroups(): array {
        return array_keys(array_filter($this->vacationPeerEditing()));
    }

    /** @return array<string,bool> */
    public function vacationPeerApproval(): array {
        $data = $this->data();
        return [$this->asnPeerGroup() => (bool)($data['vacationAsnPeerApproval'] ?? false)]
            + $this->roleValues('vacationPeerApproval');
    }

    /** @return list<string> */
    public function enabledVacationPeerGroups(): array {
        return array_keys(array_filter($this->vacationPeerApproval()));
    }

    /** @return list<array{groupId:string,label:string}> */
    public function calendarPeerOptions(): array {
        return $this->roleOptions();
    }

    /** @return list<array{groupId:string,label:string}> */
    public function vacationPeerOptions(): array {
        return array_merge(
            [['groupId' => $this->asnPeerGroup(), 'label' => $this->definition()->teamLabelPrefix() . '-Kolleg*innen']],
            $this->roleOptions(),
        );
    }

    /** @return array<string,bool> */
    public function saveCalendarPeerEditing(array $values): array {
        $data = $this->data() ?? [];
        $data['calendarPeerEditing'] = $this->semanticRoleValues($values);
        $this->save($data);
        return $this->calendarPeerEditing();
    }

    /** @return array<string,bool> */
    public function saveVacationPeerEditing(array $values): array {
        $data = $this->data() ?? [];
        $data['vacationAsnPeerEditing'] = filter_var($values[$this->asnPeerGroup()] ?? false, FILTER_VALIDATE_BOOL);
        $data['vacationPeerEditing'] = $this->semanticRoleValues($values);
        $this->save($data);
        return $this->vacationPeerEditing();
    }

    /** @return array<string,bool> */
    public function saveVacationPeerApproval(array $values): array {
        $data = $this->data() ?? [];
        $data['vacationAsnPeerApproval'] = filter_var($values[$this->asnPeerGroup()] ?? false, FILTER_VALIDATE_BOOL);
        $data['vacationPeerApproval'] = $this->semanticRoleValues($values);
        $this->save($data);
        return $this->vacationPeerApproval();
    }

    public function asnPeerGroup(): string {
        return $this->definition()->teamGroupPrefix() . '*';
    }

    public function isValid(): bool {
        return $this->data() !== null;
    }

    /** @return array<string,bool> */
    private function roleValues(string $key): array {
        $data = $this->data();
        $stored = $data[$key] ?? [];
        if (!is_array($stored)) {
            $data = null;
            $stored = [];
        }
        $result = [];
        foreach ($this->peerRoles() as $roleKey => $role) {
            $result[$role['groupId']] = $data !== null && array_key_exists($roleKey, $stored)
                ? (bool)$stored[$roleKey]
                : $data !== null && $key === 'calendarPeerEditing' && in_array($roleKey, self::CALENDAR_PEER_DEFAULT_ROLES, true);
        }
        return $result;
    }

    /** @return array<string,bool> */
    private function semanticRoleValues(array $values): array {
        $result = [];
        foreach ($this->peerRoles() as $roleKey => $role) {
            $result[$roleKey] = filter_var($values[$role['groupId']] ?? false, FILTER_VALIDATE_BOOL);
        }
        return $result;
    }

    /** @return list<array{groupId:string,label:string}> */
    private function roleOptions(): array {
        $result = [];
        foreach ($this->peerRoles() as $role) $result[] = ['groupId' => $role['groupId'], 'label' => $role['label']];
        return $result;
    }

    /** @return array<string,array<string,mixed>> */
    private function peerRoles(): array {
        return array_filter($this->definition()->roles(), static fn(array $role): bool => $role['peerEnabled']);
    }

    private function definition(): FlzOrganizationDefinition {
        return $this->organization->definition();
    }

    /** @return array<string,mixed>|null Null kennzeichnet beschädigte Persistenz und bleibt fail-closed. */
    private function data(): ?array {
        $raw = $this->config->getValueString(Application::APP_ID, self::KEY, '');
        if ($raw === '') return [];
        try {
            $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !$this->hasValidSchema($data)) return null;
            return $data;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param array<string,mixed> $data */
    private function hasValidSchema(array $data): bool {
        if (array_key_exists('version', $data) && $data['version'] !== 1) return false;
        foreach (['vacationAsnPeerEditing', 'vacationAsnPeerApproval'] as $key) {
            if (array_key_exists($key, $data) && !is_bool($data[$key])) return false;
        }
        foreach (['calendarPeerEditing', 'vacationPeerEditing', 'vacationPeerApproval'] as $key) {
            if (!array_key_exists($key, $data)) continue;
            if (!is_array($data[$key])) return false;
            foreach ($data[$key] as $roleKey => $enabled) {
                if (!is_string($roleKey) || trim($roleKey) === '' || !is_bool($enabled)) return false;
            }
        }
        return true;
    }

    /** @param array<string,mixed> $data */
    private function save(array $data): void {
        $data['version'] = 1;
        $this->config->setValueString(Application::APP_ID, self::KEY, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
