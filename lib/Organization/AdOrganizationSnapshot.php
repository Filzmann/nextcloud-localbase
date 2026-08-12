<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

/** Datensparsamer, unveränderlicher Consumervertrag ohne Mitgliederlisten. */
final class AdOrganizationSnapshot {
    private const CONTRACT_VERSION = 1;

    /** @param array<string, array{groupId: string, label: string}> $roles
     *  @param array<string, array{groupId: string, label: string}> $areas
     */
    public function __construct(
        private bool $valid,
        private int $definitionVersion,
        private array $roles,
        private array $areas,
    ) {
        if (!$valid) {
            $this->roles = [];
            $this->areas = [];
        }
    }

    public function isValid(): bool { return $this->valid; }
    public function definitionVersion(): int { return $this->definitionVersion; }
    public function roleGroupId(string $roleKey): ?string { return $this->roles[$roleKey]['groupId'] ?? null; }
    public function areaGroupId(string $areaKey): ?string { return $this->areas[$areaKey]['groupId'] ?? null; }
    /** @return list<string> */
    public function roleKeys(): array { return array_keys($this->roles); }
    /** @return list<string> */
    public function areaKeys(): array { return array_keys($this->areas); }

    /** @return array{version: int, valid: bool, definitionVersion: int, checksum: string, roles: array<string, array{groupId: string, label: string}>, areas: array<string, array{groupId: string, label: string}>} */
    public function toArray(): array {
        $payload = [
            'version' => self::CONTRACT_VERSION,
            'valid' => $this->valid,
            'definitionVersion' => $this->definitionVersion,
            'roles' => $this->roles,
            'areas' => $this->areas,
        ];
        return ['version' => $payload['version'], 'valid' => $payload['valid'], 'definitionVersion' => $payload['definitionVersion'], 'checksum' => $this->checksum(), 'roles' => $payload['roles'], 'areas' => $payload['areas']];
    }

    public function checksum(): string {
        return hash('sha256', json_encode([
            'version' => self::CONTRACT_VERSION,
            'valid' => $this->valid,
            'definitionVersion' => $this->definitionVersion,
            'roles' => $this->roles,
            'areas' => $this->areas,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
