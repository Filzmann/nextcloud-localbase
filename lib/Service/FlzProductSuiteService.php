<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCA\LocalBase\Catalog\FlzProductCatalog;
use OCP\App\IAppManager;
use OCP\IUser;
use RuntimeException;

/**
 * Zweck: Beschreibt die installierte FLZ-Produktzusammensetzung unabhängig von Navigation und Fachrechten.
 * Zusammenspiel: Produktinstaller aktiviert OrgSuite ab zwei Apps; LocalBase platziert den Adminadapter bei einer Einzelapp.
 */
final class FlzProductSuiteService {
    public function __construct(private IAppManager $apps, private ?FlzProductCatalog $catalog = null) {
    }

    /** @return list<string> */
    public function enabledProducts(?IUser $user = null): array {
        try {
            $products = $this->catalog()->products();
        } catch (RuntimeException) {
            return [];
        }

        return array_values(array_filter(
            array_column($products, 'id'),
            fn(string $appId): bool => $this->apps->isEnabledForUser($appId, $user),
        ));
    }

    public function standaloneProduct(?IUser $user = null): ?string {
        if ($this->apps->isEnabledForUser('orgsuite', $user)) return null;
        return $this->enabledProducts($user)[0] ?? null;
    }

    public function label(string $appId): string {
        try {
            return $this->catalog()->product($appId)['productLabel'];
        } catch (RuntimeException) {
            return $appId;
        }
    }

    private function catalog(): FlzProductCatalog {
        return $this->catalog ??= new FlzProductCatalog();
    }
}
