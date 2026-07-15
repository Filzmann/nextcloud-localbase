<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Organization/AdOrganizationDefinition.php';
require_once __DIR__ . '/../../lib/Service/AdDemoFixtureCatalog.php';

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Service\AdDemoFixtureCatalog;

$fixtures = (new AdDemoFixtureCatalog())->all();
$definition = AdOrganizationDefinition::defaults();
$covered = array_fill_keys(array_merge(...array_column($fixtures, 'groups')), true);
foreach (array_merge($definition->roleGroupIds(), $definition->areaGroupIds()) as $groupId) {
    if (!isset($covered[$groupId])) throw new RuntimeException("Die gemeinsame Demoorganisation deckt {$groupId} nicht ab.");
}
foreach ($fixtures as $fixture) {
    if (!str_starts_with($fixture['uid'], 'ad-demo-')) throw new RuntimeException('Gemeinsame Demo-UID verwendet keinen stabilen Suite-Namensraum.');
    if (preg_match('/^[^()]+ \([^)]+\)$/', $fixture['displayName']) !== 1) throw new RuntimeException("Demo-Anzeigename ohne Fachgruppe: {$fixture['uid']}");
}

echo "AdDemoFixtureCatalogSmokeTest: OK\n";
