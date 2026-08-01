<?php

declare(strict_types=1);

$class = __DIR__ . '/../../lib/Catalog/AdProductCatalog.php';
$catalogFile = __DIR__ . '/../../resources/ad-product-catalog.json';

if (!is_file($class) || !is_file($catalogFile)) {
    throw new RuntimeException('Der kanonische AD-Produktkatalog fehlt.');
}

require_once $class;

use OCA\LocalBase\Catalog\AdProductCatalog;

$catalog = new AdProductCatalog($catalogFile);
if ($catalog->version() !== 1) {
    throw new RuntimeException('Die Katalogversion ist nicht stabil auf Version 1 festgelegt.');
}

$productIds = array_column($catalog->products(), 'id');
$expectedProducts = ['adcalendar', 'adplaner', 'adurlaub', 'adroom', 'adrecruitment'];
if ($productIds !== $expectedProducts) {
    throw new RuntimeException('AD-Fachprodukte oder ihre Reihenfolge weichen vom Vertrag ab.');
}

$menuIds = array_column($catalog->menuProducts('ad'), 'id');
if ($menuIds !== $expectedProducts) {
    throw new RuntimeException('Das AD-Menü wird nicht vollständig aus dem Katalog abgeleitet.');
}

$fullSuite = $catalog->fullSuiteAppIds();
if ($fullSuite !== ['localbase', 'orgsuite', ...$expectedProducts]) {
    throw new RuntimeException('Das vollständige Suite-Bundle enthält nicht Infrastruktur und alle AD-Produkte.');
}

if ($catalog->productBundleAppIds('adrecruitment') !== ['localbase', 'orgsuite', 'adrecruitment']) {
    throw new RuntimeException('Das Recruitment-Produktbundle ist nicht eigenständig zusammengesetzt.');
}

$recruitment = $catalog->product('adrecruitment');
if ($recruitment['route'] !== 'adrecruitment.page.index'
    || $recruitment['standalone'] !== true
    || $recruitment['menu'] !== true
    || $recruitment['fullSuiteBundle'] !== true
    || $recruitment['productBundle'] !== true) {
    throw new RuntimeException('Der Recruitment-Katalogeintrag erfüllt den Suite-Vertrag nicht.');
}

try {
    $catalog->product('unknown-product');
    throw new RuntimeException('Ein unbekanntes Produkt wurde akzeptiert.');
} catch (InvalidArgumentException) {
}

$invalid = json_decode((string)file_get_contents($catalogFile), true, flags: JSON_THROW_ON_ERROR);
$invalid['entries'][] = $invalid['entries'][2];
try {
    AdProductCatalog::validate($invalid);
    throw new RuntimeException('Eine doppelte Produkt-ID wurde akzeptiert.');
} catch (UnexpectedValueException) {
}

$invalid = json_decode((string)file_get_contents($catalogFile), true, flags: JSON_THROW_ON_ERROR);
$invalid['entries'][2]['route'] = 'foreign.page.index';
try {
    AdProductCatalog::validate($invalid);
    throw new RuntimeException('Eine fremde technische Einstiegsroute wurde akzeptiert.');
} catch (UnexpectedValueException) {
}

$definition = json_decode((string)file_get_contents($catalogFile), true, flags: JSON_THROW_ON_ERROR);
$assertInvalid = static function (array $candidate, string $message): void {
    try {
        AdProductCatalog::validate($candidate);
        throw new RuntimeException($message);
    } catch (UnexpectedValueException) {
    }
};
$candidate = $definition; $candidate['version'] = 99;
$assertInvalid($candidate, 'Eine unbekannte Katalogversion wurde akzeptiert.');
$candidate = $definition; unset($candidate['entries'][2]['route']);
$assertInvalid($candidate, 'Ein unvollständiger Katalogeintrag wurde akzeptiert.');
$candidate = $definition; $candidate['entries'][2]['menu'] = 'yes';
$assertInvalid($candidate, 'Ein nicht-boolesches Katalogflag wurde akzeptiert.');
$candidate = $definition; $candidate['entries'][0]['route'] = 'localbase.page.index';
$assertInvalid($candidate, 'Infrastruktur mit Fachnavigation wurde akzeptiert.');
$candidate = $definition; $candidate['entries'][3]['order'] = $candidate['entries'][2]['order'];
$assertInvalid($candidate, 'Eine doppelte Menüreihenfolge wurde akzeptiert.');
$candidate = $definition; $candidate['entries'][2]['kind'] = 'unknown';
$assertInvalid($candidate, 'Ein unbekannter Produkttyp wurde akzeptiert.');
$candidate = $definition; array_splice($candidate['entries'], 1, 1);
$assertInvalid($candidate, 'Fehlende verbindliche Infrastruktur wurde akzeptiert.');
$candidate = $definition;
$candidate['entries'][0] = array_replace($candidate['entries'][0], [
    'kind' => 'product', 'suite' => 'ad', 'order' => 999, 'route' => 'localbase.page.index',
    'productLabel' => 'LocalBase', 'navigationLabel' => 'LocalBase',
]);
$assertInvalid($candidate, 'Falsch klassifizierte verbindliche Infrastruktur wurde akzeptiert.');

$assertUnreadableCatalog = static function (string $contents, string $message): void {
    $file = tempnam(sys_get_temp_dir(), 'ad-product-catalog-invalid-');
    if ($file === false) throw new RuntimeException('Temporärer Fehlerkatalog konnte nicht angelegt werden.');
    try {
        file_put_contents($file, $contents);
        (new AdProductCatalog($file))->entries();
        throw new RuntimeException($message);
    } catch (UnexpectedValueException) {
    } finally {
        @unlink($file);
    }
};
$assertUnreadableCatalog('{', 'Ungültiges Katalog-JSON wurde akzeptiert.');
$assertUnreadableCatalog('[]', 'Eine Katalogliste wurde als Objektwurzel akzeptiert.');

$reordered = json_decode((string)file_get_contents($catalogFile), true, flags: JSON_THROW_ON_ERROR);
$reordered['entries'] = array_reverse($reordered['entries']);
$temporaryCatalog = tempnam(sys_get_temp_dir(), 'ad-product-catalog-');
if ($temporaryCatalog === false) {
    throw new RuntimeException('Temporärer Katalog konnte nicht angelegt werden.');
}
try {
    file_put_contents($temporaryCatalog, json_encode($reordered, JSON_THROW_ON_ERROR));
    $reorderedCatalog = new AdProductCatalog($temporaryCatalog);
    if (array_column($reorderedCatalog->products(), 'id') !== $expectedProducts
        || $reorderedCatalog->fullSuiteAppIds() !== ['localbase', 'orgsuite', ...$expectedProducts]) {
        throw new RuntimeException('Katalogreihenfolge hängt von der JSON-Dateireihenfolge statt vom Vertrag ab.');
    }
} finally {
    @unlink($temporaryCatalog);
}

echo "AdProductCatalogSmokeTest: OK\n";
