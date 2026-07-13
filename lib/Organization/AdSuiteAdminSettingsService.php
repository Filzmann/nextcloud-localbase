<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

use OCA\LocalBase\AppInfo\Application;
use OCP\IAppConfig;

/**
 * Zweck: Persistiert organisationsweite AD-Freigaben semantisch nach Rollen statt nach veränderlichen Gruppen-IDs.
 * Zusammenspiel: OrgSuite schreibt die Admin-Konfiguration; AD Kalender und AD Urlaub lesen daraus ihre aktiven Peer-Gruppen.
 * Vertrag: Nur Rollen mit peerEnabled werden ausgeliefert. Technische Gruppen-IDs werden erst beim Lesen aus der aktuellen Organisationsdefinition abgeleitet.
 */
final class AdSuiteAdminSettingsService {
    private const KEY = 'ad_suite_admin_settings';

    public function __construct(
        private IAppConfig $config,
        private AdOrganizationSettingsService $organization,
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
    public function vacationPeerApproval(): array {
        return [$this->asnPeerGroup() => (bool)($this->data()['vacationAsnPeerApproval'] ?? false)]
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
        $data = $this->data();
        $data['calendarPeerEditing'] = $this->semanticRoleValues($values);
        $this->save($data);
        return $this->calendarPeerEditing();
    }

    /** @return array<string,bool> */
    public function saveVacationPeerApproval(array $values): array {
        $data = $this->data();
        $data['vacationAsnPeerApproval'] = filter_var($values[$this->asnPeerGroup()] ?? false, FILTER_VALIDATE_BOOL);
        $data['vacationPeerApproval'] = $this->semanticRoleValues($values);
        $this->save($data);
        return $this->vacationPeerApproval();
    }

    public function asnPeerGroup(): string {
        return $this->definition()->teamGroupPrefix() . '*';
    }

    /** @return array<string,bool> */
    private function roleValues(string $key): array {
        $stored = $this->data()[$key] ?? [];
        $result = [];
        foreach ($this->peerRoles() as $roleKey => $role) {
            $result[$role['groupId']] = (bool)($stored[$roleKey] ?? false);
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

    private function definition(): AdOrganizationDefinition {
        return $this->organization->definition();
    }

    /** @return array<string,mixed> */
    private function data(): array {
        $raw = $this->config->getValueString(Application::APP_ID, self::KEY, '');
        if ($raw === '') return [];
        try {
            $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** @param array<string,mixed> $data */
    private function save(array $data): void {
        $data['version'] = 1;
        $this->config->setValueString(Application::APP_ID, self::KEY, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
