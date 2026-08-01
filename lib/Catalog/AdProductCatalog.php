<?php

declare(strict_types=1);

namespace OCA\LocalBase\Catalog;

use InvalidArgumentException;
use JsonException;
use UnexpectedValueException;

/**
 * Kanonischer, versionierter Vertrag für AD-Produkte, Navigation und Bundles.
 * Sichtbare Bezeichnungen bleiben Übersetzungsquellen der jeweiligen Fachapp.
 */
final class AdProductCatalog {
    public const VERSION = 1;
    private const REQUIRED_INFRASTRUCTURE = ['localbase', 'orgsuite'];

    /** @var array{version: int, entries: list<array<string, mixed>>}|null */
    private ?array $catalog = null;

    public function __construct(private ?string $catalogFile = null) {
    }

    public function version(): int {
        return $this->definition()['version'];
    }

    /** @return list<array<string, mixed>> */
    public function entries(): array {
        return $this->definition()['entries'];
    }

    /** @return list<array<string, mixed>> */
    public function products(): array {
        $products = array_values(array_filter(
            $this->entries(),
            static fn(array $entry): bool => $entry['kind'] === 'product',
        ));
        usort(
            $products,
            static fn(array $left, array $right): int => [$left['order'], $left['id']] <=> [$right['order'], $right['id']],
        );
        return $products;
    }

    /** @return list<array<string, mixed>> */
    public function menuProducts(string $suite): array {
        return array_values(array_filter(
            $this->products(),
            static fn(array $entry): bool => $entry['suite'] === $suite && $entry['menu'] === true,
        ));
    }

    /** @return array<string, mixed> */
    public function product(string $appId): array {
        foreach ($this->products() as $entry) {
            if ($entry['id'] === $appId) {
                return $entry;
            }
        }

        throw new InvalidArgumentException("Unbekanntes AD-Produkt: {$appId}");
    }

    /** @return list<string> */
    public function fullSuiteAppIds(): array {
        return [
            ...array_column($this->infrastructureFor('fullSuiteBundle'), 'id'),
            ...array_column(
                array_filter($this->products(), static fn(array $entry): bool => $entry['fullSuiteBundle'] === true),
                'id',
            ),
        ];
    }

    /** @return list<string> */
    public function productBundleAppIds(string $productId): array {
        $product = $this->product($productId);
        if ($product['productBundle'] !== true) {
            throw new InvalidArgumentException("Produkt besitzt kein Einzelbundle: {$productId}");
        }

        $apps = array_column($this->infrastructureFor('productBundle'), 'id');
        $apps[] = $productId;
        return $apps;
    }

    /**
     * @param array<string, mixed> $catalog
     * @return array{version: int, entries: list<array<string, mixed>>}
     */
    public static function validate(array $catalog): array {
        if (($catalog['version'] ?? null) !== self::VERSION || !is_array($catalog['entries'] ?? null)) {
            throw new UnexpectedValueException('Unbekannte Katalogversion oder fehlende Einträge.');
        }

        $required = [
            'id', 'kind', 'suite', 'order', 'route', 'productLabel', 'navigationLabel',
            'standalone', 'menu', 'fullSuiteBundle', 'productBundle',
        ];
        $seenIds = [];
        $seenMenuOrders = [];
        $entries = [];

        foreach ($catalog['entries'] as $entry) {
            if (!is_array($entry) || array_diff($required, array_keys($entry)) !== []) {
                throw new UnexpectedValueException('Katalogeintrag ist unvollständig.');
            }
            $id = $entry['id'];
            if (!is_string($id) || preg_match('/^[a-z][a-z0-9_]*$/', $id) !== 1 || isset($seenIds[$id])) {
                throw new UnexpectedValueException('Katalog-ID ist ungültig oder doppelt.');
            }
            $seenIds[$id] = true;

            foreach (['standalone', 'menu', 'fullSuiteBundle', 'productBundle'] as $flag) {
                if (!is_bool($entry[$flag])) {
                    throw new UnexpectedValueException("Katalogflag {$flag} ist nicht boolesch.");
                }
            }

            if ($entry['kind'] === 'infrastructure') {
                if ($entry['suite'] !== null || $entry['order'] !== null || $entry['route'] !== null
                    || $entry['productLabel'] !== null || $entry['navigationLabel'] !== null || $entry['standalone'] || $entry['menu']) {
                    throw new UnexpectedValueException('Infrastruktur darf keine Fachnavigation definieren.');
                }
            } elseif ($entry['kind'] === 'product') {
                if (!is_string($entry['suite']) || $entry['suite'] === ''
                    || !is_int($entry['order']) || $entry['order'] < 0
                    || !is_string($entry['route']) || !str_starts_with($entry['route'], $id . '.')
                    || !is_string($entry['productLabel']) || $entry['productLabel'] === ''
                    || !is_string($entry['navigationLabel']) || $entry['navigationLabel'] === '') {
                    throw new UnexpectedValueException("Fachprodukt {$id} besitzt ungültige Navigationsdaten.");
                }
                if ($entry['menu']) {
                    $orderKey = $entry['suite'] . ':' . $entry['order'];
                    if (isset($seenMenuOrders[$orderKey])) {
                        throw new UnexpectedValueException('Menüreihenfolge ist nicht eindeutig.');
                    }
                    $seenMenuOrders[$orderKey] = true;
                }
            } else {
                throw new UnexpectedValueException("Unbekannter Produkttyp für {$id}.");
            }

            $entries[] = $entry;
        }

        if (!isset($seenIds['localbase'], $seenIds['orgsuite'])) {
            throw new UnexpectedValueException('Verbindliche Suite-Infrastruktur fehlt.');
        }
        foreach (self::REQUIRED_INFRASTRUCTURE as $infrastructureId) {
            $matching = array_values(array_filter(
                $entries,
                static fn(array $entry): bool => $entry['id'] === $infrastructureId,
            ));
            if (($matching[0]['kind'] ?? null) !== 'infrastructure') {
                throw new UnexpectedValueException("Verbindliche Infrastruktur ist falsch klassifiziert: {$infrastructureId}");
            }
        }

        return ['version' => self::VERSION, 'entries' => $entries];
    }

    /**
     * @param 'fullSuiteBundle'|'productBundle' $bundleFlag
     * @return list<array<string, mixed>>
     */
    private function infrastructureFor(string $bundleFlag): array {
        $infrastructure = array_values(array_filter(
            $this->entries(),
            static fn(array $entry): bool => $entry['kind'] === 'infrastructure' && $entry[$bundleFlag] === true,
        ));
        $priority = array_flip(self::REQUIRED_INFRASTRUCTURE);
        usort($infrastructure, static function (array $left, array $right) use ($priority): int {
            return [$priority[$left['id']] ?? PHP_INT_MAX, $left['id']]
                <=> [$priority[$right['id']] ?? PHP_INT_MAX, $right['id']];
        });
        return $infrastructure;
    }

    /** @return array{version: int, entries: list<array<string, mixed>>} */
    private function definition(): array {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $file = $this->catalogFile ?? dirname(__DIR__, 2) . '/resources/ad-product-catalog.json';
        $contents = @file_get_contents($file);
        if ($contents === false) {
            throw new UnexpectedValueException('AD-Produktkatalog ist nicht verfügbar.');
        }
        try {
            $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new UnexpectedValueException('AD-Produktkatalog enthält ungültiges JSON.', previous: $error);
        }
        if (!is_array($decoded)) {
            throw new UnexpectedValueException('AD-Produktkatalog besitzt kein Objekt als Wurzel.');
        }

        return $this->catalog = self::validate($decoded);
    }
}
