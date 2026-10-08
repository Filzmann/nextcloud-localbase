<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

final class BrGroupDefinition {
    public const VERSION = 1;
    public const MEMBER = 'member';
    public const CHAIR = 'chair';
    public const DEPUTY = 'deputy';

    private const KEYS = [self::MEMBER, self::CHAIR, self::DEPUTY];
    private const DEFAULT_GROUPS = [
        self::MEMBER => 'Betriebsrat',
        self::CHAIR => 'Betriebsrat-Vorsitzende',
        self::DEPUTY => 'Betriebsrat-Stellvertreter',
    ];

    /** @param array<string,string> $groups */
    private function __construct(private array $groups, private int $revision) {}

    public static function defaults(string $memberGroup = self::DEFAULT_GROUPS[self::MEMBER]): self {
        return self::fromGroups([self::MEMBER => $memberGroup] + self::DEFAULT_GROUPS, 0);
    }

    /** @param array<string,mixed> $data */
    public static function get(array $data): self {
        if (($data['version'] ?? null) !== self::VERSION || !is_array($data['groups'] ?? null)) {
            throw new \InvalidArgumentException('Der BR-Gruppenvertrag besitzt eine ungültige Version oder Struktur.');
        }
        $revision = $data['revision'] ?? null;
        if (!is_int($revision) || $revision < 1) {
            throw new \InvalidArgumentException('Die Revision des BR-Gruppenvertrags ist ungültig.');
        }
        return self::fromGroups($data['groups'], $revision);
    }

    /** @param array<string,mixed> $groups */
    public static function fromGroups(array $groups, int $revision): self {
        $actualKeys = array_keys($groups);
        $expectedKeys = self::KEYS;
        sort($actualKeys);
        sort($expectedKeys);
        if ($actualKeys !== $expectedKeys) {
            throw new \InvalidArgumentException('Der BR-Gruppenvertrag benötigt Mitglieder, Vorsitz und Stellvertretung.');
        }
        $normalized = [];
        foreach (self::KEYS as $key) {
            if (!is_string($groups[$key])) throw new \InvalidArgumentException('Die BR-Gruppen-ID ist ungültig.');
            $groupId = trim($groups[$key]);
            $characters = preg_match_all('/./us', $groupId, $matches);
            if ($groupId === '' || $characters === false || $characters > 64 || preg_match('/[\x00-\x1F\x7F]/u', $groupId) === 1) {
                throw new \InvalidArgumentException('Die BR-Gruppen-ID ist ungültig.');
            }
            $normalized[$key] = $groupId;
        }
        if (count(array_unique($normalized)) !== count(self::KEYS)) {
            throw new \InvalidArgumentException('Mitglieder, Vorsitz und Stellvertretung benötigen getrennte Nextcloud-Gruppen.');
        }
        if ($revision < 0) throw new \InvalidArgumentException('Die Revision des BR-Gruppenvertrags ist ungültig.');
        return new self($normalized, $revision);
    }

    public function groupId(string $key): string {
        if (!array_key_exists($key, $this->groups)) throw new \InvalidArgumentException('Unbekannter semantischer BR-Gruppenschlüssel.');
        return $this->groups[$key];
    }

    /** @return array<string,string> */
    public function groups(): array { return $this->groups; }
    public function revision(): int { return $this->revision; }

    /** @return array{version:int,revision:int,groups:array<string,string>} */
    public function toArray(): array {
        return ['version' => self::VERSION, 'revision' => $this->revision, 'groups' => $this->groups];
    }
}
