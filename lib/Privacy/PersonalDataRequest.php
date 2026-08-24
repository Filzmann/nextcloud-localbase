<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;

final class PersonalDataRequest {
    public const PURPOSE_SELF_SERVICE = 'self_service';
    public const PURPOSE_ADMIN_REPORT = 'admin_report';

    public function __construct(
        private PersonalDataSubject $subject,
        private string $locale,
        private string $purpose,
        private int $limit = 500,
    ) {
        $this->locale = trim($locale);
        $this->purpose = trim($purpose);
        if (!preg_match('/^[a-z]{2,3}(?:[-_][A-Za-z0-9]+)*$/', $this->locale)) throw new InvalidArgumentException('Sprache ist ungültig.');
        if (!in_array($this->purpose, [self::PURPOSE_SELF_SERVICE, self::PURPOSE_ADMIN_REPORT], true)) throw new InvalidArgumentException('Ausgabezweck ist ungültig.');
        if ($this->limit < 1 || $this->limit > 500) throw new InvalidArgumentException('Ausgabelimit ist ungültig.');
    }

    public function subject(): PersonalDataSubject { return $this->subject; }
    public function locale(): string { return $this->locale; }
    public function purpose(): string { return $this->purpose; }
    public function limit(): int { return $this->limit; }
}
