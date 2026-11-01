<?php

declare(strict_types=1);

namespace OCA\LocalBase\PublicApi\V1;

use InvalidArgumentException;

/**
 * Unveränderlicher, datensparsamer Vertrag für organisationsweite Urlaubsfreigaben und EB-Büroansichten.
 */
final class VacationAuthorizationSnapshot {
    public const CONTRACT_VERSION = '1.0';

    /**
     * @param array<string,bool> $peerEditing
     * @param array<string,bool> $peerApproval
     * @param list<array{id:string,label:string,roleKey:string,roleGroupId:string,areaKey:string,areaGroupId:string}> $ebAreaViews
     */
    public function __construct(
        private bool $valid,
        private int $definitionVersion,
        private array $peerEditing,
        private array $peerApproval,
        private array $ebAreaViews,
    ) {
        if ($definitionVersion < 1) {
            throw new InvalidArgumentException('Invalid organization definition version.');
        }
        if (!$valid) {
            $this->peerEditing = [];
            $this->peerApproval = [];
            $this->ebAreaViews = [];
            return;
        }

        $this->peerEditing = $this->normalizePermissions($peerEditing);
        $this->peerApproval = $this->normalizePermissions($peerApproval);
        $this->ebAreaViews = $this->normalizeViews($ebAreaViews);
    }

    public function contractVersion(): string { return self::CONTRACT_VERSION; }
    public function isValid(): bool { return $this->valid; }
    public function definitionVersion(): int { return $this->definitionVersion; }

    /** @return array<string,bool> */
    public function peerEditing(): array { return $this->peerEditing; }

    /** @return array<string,bool> */
    public function peerApproval(): array { return $this->peerApproval; }

    /** @return list<array{id:string,label:string,roleKey:string,roleGroupId:string,areaKey:string,areaGroupId:string}> */
    public function ebAreaViews(): array { return $this->ebAreaViews; }

    public function checksum(): string {
        return hash('sha256', json_encode($this->payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /** @return array{contractVersion:string,valid:bool,definitionVersion:int,checksum:string,peerEditing:array<string,bool>,peerApproval:array<string,bool>,ebAreaViews:list<array{id:string,label:string,roleKey:string,roleGroupId:string,areaKey:string,areaGroupId:string}>} */
    public function toArray(): array {
        return [...$this->payload(), 'checksum' => $this->checksum()];
    }

    /** @return array{contractVersion:string,valid:bool,definitionVersion:int,peerEditing:array<string,bool>,peerApproval:array<string,bool>,ebAreaViews:list<array{id:string,label:string,roleKey:string,roleGroupId:string,areaKey:string,areaGroupId:string}>} */
    private function payload(): array {
        return [
            'contractVersion' => self::CONTRACT_VERSION,
            'valid' => $this->valid,
            'definitionVersion' => $this->definitionVersion,
            'peerEditing' => $this->peerEditing,
            'peerApproval' => $this->peerApproval,
            'ebAreaViews' => $this->ebAreaViews,
        ];
    }

    /** @param array<mixed> $permissions @return array<string,bool> */
    private function normalizePermissions(array $permissions): array {
        $normalized = [];
        foreach ($permissions as $groupId => $enabled) {
            if (!is_string($groupId) || trim($groupId) === '' || !is_bool($enabled)) {
                throw new InvalidArgumentException('Invalid vacation peer permission.');
            }
            $normalized[trim($groupId)] = $enabled;
        }
        ksort($normalized);
        return $normalized;
    }

    /**
     * @param array<mixed> $views
     * @return list<array{id:string,label:string,roleKey:string,roleGroupId:string,areaKey:string,areaGroupId:string}>
     */
    private function normalizeViews(array $views): array {
        $normalized = [];
        $ids = [];
        $areaKeys = [];
        foreach ($views as $view) {
            if (!is_array($view)) throw new InvalidArgumentException('Invalid vacation EB area view.');
            $item = [];
            foreach (['id', 'label', 'roleKey', 'roleGroupId', 'areaKey', 'areaGroupId'] as $key) {
                if (!is_string($view[$key] ?? null) || trim($view[$key]) === '') {
                    throw new InvalidArgumentException('Invalid vacation EB area view field.');
                }
                $item[$key] = trim($view[$key]);
            }
            if ($item['roleKey'] !== 'eb' || !preg_match('/^[a-z][a-z0-9_-]*$/', $item['id'])) {
                throw new InvalidArgumentException('Invalid vacation EB area view identity.');
            }
            if (isset($ids[$item['id']]) || isset($areaKeys[$item['areaKey']])) {
                throw new InvalidArgumentException('Vacation EB area views must be unique.');
            }
            $ids[$item['id']] = true;
            $areaKeys[$item['areaKey']] = true;
            $normalized[] = $item;
        }
        return $normalized;
    }
}
