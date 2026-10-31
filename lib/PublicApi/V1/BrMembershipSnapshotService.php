<?php

declare(strict_types=1);

namespace OCA\LocalBase\PublicApi\V1;

use OCA\LocalBase\Organization\BrGroupDefinition;
use OCA\LocalBase\Organization\BrGroupSettingsService;
use Throwable;

/** Öffentliche read-only Projektion der kanonischen LocalBase-BR-Gruppendefinition. */
final class BrMembershipSnapshotService {
    public function __construct(private BrGroupSettingsService $source) {
    }

    public function snapshot(): BrMembershipSnapshot {
        try {
            $definition = $this->source->validatedDefinition();

            return new BrMembershipSnapshot(
                true,
                BrGroupDefinition::VERSION,
                $definition->revision(),
                $definition->groupId(BrGroupDefinition::MEMBER),
            );
        } catch (Throwable) {
            return new BrMembershipSnapshot(false, BrGroupDefinition::VERSION, 0, null);
        }
    }
}
