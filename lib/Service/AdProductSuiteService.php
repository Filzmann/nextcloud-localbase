<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCP\App\IAppManager;
use OCP\IUser;

/**
 * Zweck: Beschreibt die installierte AD-Produktzusammensetzung unabhängig von Navigation und Fachrechten.
 * Zusammenspiel: Produktinstaller aktiviert OrgSuite ab zwei Apps; LocalBase platziert den Adminadapter bei einer Einzelapp.
 */
final class AdProductSuiteService {
    /** @var array<string,string> */
    private const PRODUCTS = [
        'adcalendar' => 'AD Kalender',
        'adplaner' => 'Assistenzplanung',
        'adurlaub' => 'AD Urlaub',
        'adroom' => 'AD Raumplaner',
    ];

    public function __construct(private IAppManager $apps) {
    }

    /** @return list<string> */
    public function enabledProducts(?IUser $user = null): array {
        return array_values(array_filter(
            array_keys(self::PRODUCTS),
            fn(string $appId): bool => $this->apps->isEnabledForUser($appId, $user),
        ));
    }

    public function standaloneProduct(?IUser $user = null): ?string {
        if ($this->apps->isEnabledForUser('orgsuite', $user)) return null;
        return $this->enabledProducts($user)[0] ?? null;
    }

    public static function label(string $appId): string {
        return self::PRODUCTS[$appId] ?? $appId;
    }
}
