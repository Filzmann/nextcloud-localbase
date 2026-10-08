<?php

declare(strict_types=1);

namespace OCA\LocalBase\PublicApi\V1;

use OCA\LocalBase\Organization\FlzOrganizationSnapshotService;

/** Öffentliche Projektion der kanonischen LocalBase-Organisationskonfiguration. */
final class OrganizationSnapshotService {
    public function __construct(private FlzOrganizationSnapshotService $source) {
    }

    public function snapshot(): OrganizationSnapshot {
        $snapshot = $this->source->snapshot();
        $payload = $snapshot->toArray();

        return new OrganizationSnapshot(
            $snapshot->isValid(),
            $snapshot->definitionVersion(),
            $payload['roles'],
            $payload['areas'],
        );
    }
}
