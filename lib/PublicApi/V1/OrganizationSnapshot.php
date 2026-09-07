<?php

declare(strict_types=1);

namespace OCA\LocalBase\PublicApi\V1;

use InvalidArgumentException;

/** Datensparsamer, unveränderlicher V1-Vertrag für semantische Organisationsgruppen. */
final class OrganizationSnapshot {
    public const CONTRACT_VERSION = '1.0';

    /**
     * @param array<string, array{groupId: string, label: string}> $roles
     * @param array<string, array{groupId: string, label: string}> $areas
     */
    public function __construct(
        private bool $valid,
        private int $definitionVersion,
        private array $roles,
        private array $areas,
    ) {
        if ($definitionVersion < 1) {
            throw new InvalidArgumentException('Invalid organization definition version.');
        }
        if (!$valid) {
            $this->roles = [];
            $this->areas = [];
            return;
        }

        $this->roles = $this->normalizeMappings($roles);
        $this->areas = $this->normalizeMappings($areas);
        $groupIds = [
            ...array_column($this->roles, 'groupId'),
            ...array_column($this->areas, 'groupId'),
        ];
        if (count($groupIds) !== count(array_unique($groupIds))) {
            throw new InvalidArgumentException('Organization group mappings must be unique.');
        }
    }

    public function contractVersion(): string { return self::CONTRACT_VERSION; }
    public function isValid(): bool { return $this->valid; }
    public function definitionVersion(): int { return $this->definitionVersion; }

    /** @return array<string, array{groupId: string, label: string}> */
    public function roles(): array { return $this->roles; }

    /** @return array<string, array{groupId: string, label: string}> */
    public function areas(): array { return $this->areas; }

    public function checksum(): string {
        return hash('sha256', json_encode($this->payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /** @return array{contractVersion: string, valid: bool, definitionVersion: int, checksum: string, roles: array<string, array{groupId: string, label: string}>, areas: array<string, array{groupId: string, label: string}>} */
    public function toArray(): array {
        return [...$this->payload(), 'checksum' => $this->checksum()];
    }

    /** @return array{contractVersion: string, valid: bool, definitionVersion: int, roles: array<string, array{groupId: string, label: string}>, areas: array<string, array{groupId: string, label: string}>} */
    private function payload(): array {
        return [
            'contractVersion' => self::CONTRACT_VERSION,
            'valid' => $this->valid,
            'definitionVersion' => $this->definitionVersion,
            'roles' => $this->roles,
            'areas' => $this->areas,
        ];
    }

    /**
     * @param array<mixed> $mappings
     * @return array<string, array{groupId: string, label: string}>
     */
    private function normalizeMappings(array $mappings): array {
        $normalized = [];
        foreach ($mappings as $key => $mapping) {
            if (!is_string($key) || trim($key) === '' || !is_array($mapping)) {
                throw new InvalidArgumentException('Invalid organization mapping identity.');
            }
            $groupId = $mapping['groupId'] ?? null;
            $label = $mapping['label'] ?? null;
            if (!is_string($groupId) || trim($groupId) === '' || !is_string($label) || trim($label) === '') {
                throw new InvalidArgumentException('Invalid organization mapping fields.');
            }
            $normalized[trim($key)] = ['groupId' => trim($groupId), 'label' => trim($label)];
        }
        return $normalized;
    }
}
