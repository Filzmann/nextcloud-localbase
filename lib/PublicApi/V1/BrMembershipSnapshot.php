<?php

declare(strict_types=1);

namespace OCA\LocalBase\PublicApi\V1;

use InvalidArgumentException;

/** Datensparsame, unveränderliche V1-Projektion der kanonischen BR-Mitgliedergruppe. */
final class BrMembershipSnapshot {
    public const CONTRACT_VERSION = '1.0';

    public function __construct(
        private bool $valid,
        private int $definitionVersion,
        private int $definitionRevision,
        private ?string $memberGroupId,
    ) {
        if ($definitionVersion < 1) {
            throw new InvalidArgumentException('Invalid BR definition version.');
        }
        if (!$valid) {
            $this->definitionRevision = 0;
            $this->memberGroupId = null;
            return;
        }
        if ($definitionRevision < 1) {
            throw new InvalidArgumentException('A valid BR identity requires a persisted definition revision.');
        }
        if (!is_string($memberGroupId)) {
            throw new InvalidArgumentException('Invalid BR member group identity.');
        }
        $memberGroupId = trim($memberGroupId);
        $characters = preg_match_all('/./us', $memberGroupId, $matches);
        if ($memberGroupId === ''
            || $characters === false
            || $characters > 64
            || preg_match('/[\x00-\x1F\x7F]/u', $memberGroupId) === 1) {
            throw new InvalidArgumentException('Invalid BR member group identity.');
        }
        $this->memberGroupId = $memberGroupId;
    }

    public function contractVersion(): string { return self::CONTRACT_VERSION; }
    public function isValid(): bool { return $this->valid; }
    public function definitionVersion(): int { return $this->definitionVersion; }
    public function definitionRevision(): int { return $this->definitionRevision; }
    public function memberGroupId(): ?string { return $this->memberGroupId; }

    public function checksum(): string {
        return hash('sha256', json_encode($this->payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /** @return array{contractVersion:string,valid:bool,definitionVersion:int,definitionRevision:int,memberGroupId:?string,checksum:string} */
    public function toArray(): array {
        return [...$this->payload(), 'checksum' => $this->checksum()];
    }

    /** @return array{contractVersion:string,valid:bool,definitionVersion:int,definitionRevision:int,memberGroupId:?string} */
    private function payload(): array {
        return [
            'contractVersion' => self::CONTRACT_VERSION,
            'valid' => $this->valid,
            'definitionVersion' => $this->definitionVersion,
            'definitionRevision' => $this->definitionRevision,
            'memberGroupId' => $this->memberGroupId,
        ];
    }
}
