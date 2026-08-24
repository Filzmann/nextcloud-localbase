<?php

declare(strict_types=1);

namespace OCA\LocalBase\Privacy;

use InvalidArgumentException;

final class PersonalDataSubject {
    public const NEXTCLOUD_USER = 'nextcloud_user';

    public function __construct(private string $type, private string $id) {
        $this->type = trim($type);
        $this->id = trim($id);
        if ($this->type !== self::NEXTCLOUD_USER) throw new InvalidArgumentException('Subject-Typ ist nicht unterstützt.');
        if ($this->id === '' || strlen($this->id) > 255) throw new InvalidArgumentException('Subject-ID ist ungültig.');
    }

    public function type(): string { return $this->type; }
    public function id(): string { return $this->id; }
    public function toArray(): array { return ['type' => $this->type, 'id' => $this->id]; }
}
