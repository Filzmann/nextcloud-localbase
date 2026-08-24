<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;

final class RetentionPreviewPage {
    /** @param list<RetentionPreviewCandidate> $candidates */
    public function __construct(private array $candidates, private bool $complete = true) {
        foreach ($this->candidates as $candidate) if (!$candidate instanceof RetentionPreviewCandidate) throw new InvalidArgumentException('Retention-Seite enthält einen ungültigen Kandidaten.');
        $this->candidates = array_values($this->candidates);
    }
    /** @return list<RetentionPreviewCandidate> */
    public function candidates(): array { return $this->candidates; }
    public function isComplete(): bool { return $this->complete; }
}
