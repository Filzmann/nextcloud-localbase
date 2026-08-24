<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;

final class RetentionPreviewCandidate {
    public const REVIEW = 'REVIEW';

    public function __construct(private string $reference, private string $category, private string $action, private string $reason) {
        $this->reference = trim($reference);
        $this->category = trim($category);
        $this->action = trim($action);
        $this->reason = trim($reason);
        if ($this->reference === '' || strlen($this->reference) > 255) throw new InvalidArgumentException('Retention-Referenz ist ungültig.');
        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $this->category)) throw new InvalidArgumentException('Retention-Kategorie ist ungültig.');
        if ($this->action !== self::REVIEW) throw new InvalidArgumentException('Pilot unterstützt ausschließlich REVIEW.');
        if ($this->reason === '' || strlen($this->reason) > 500) throw new InvalidArgumentException('Retention-Begründung ist ungültig.');
    }

    public function toArray(): array {
        return ['reference' => $this->reference, 'category' => $this->category, 'action' => $this->action, 'reason' => $this->reason];
    }
}
