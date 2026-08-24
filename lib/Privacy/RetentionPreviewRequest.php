<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;

final class RetentionPreviewRequest {
    public function __construct(private PersonalDataSubject $subject, private int $limit = 100) {
        if ($this->limit < 1 || $this->limit > 500) throw new InvalidArgumentException('Retention-Limit ist ungültig.');
    }
    public function subject(): PersonalDataSubject { return $this->subject; }
    public function limit(): int { return $this->limit; }
}
