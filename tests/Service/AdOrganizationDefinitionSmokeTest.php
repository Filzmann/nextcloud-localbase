<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Organization/AdOrganizationDefinition.php';
require_once __DIR__ . '/../../lib/Organization/AdOrganizationHierarchy.php';
require_once __DIR__ . '/../../lib/Organization/AdOrganizationPermissionPolicy.php';

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationHierarchy;
use OCA\LocalBase\Organization\AdOrganizationPermissionPolicy;

$definition = AdOrganizationDefinition::defaults();
if ($definition->roleLabelForGroup('ad-Buero') !== 'Büromitarbeiter*innen') throw new RuntimeException('Sichtbarer Rollenname fehlt.');
if ($definition->areaLabelForGroup('ad-Bereich-Sued') !== 'Süd') throw new RuntimeException('Sichtbarer Bereichsname fehlt.');
if ($definition->normalizeTeamCode(' TeamA ') !== 'TeamA') throw new RuntimeException('Unicode-Teamkürzel wird nicht normalisiert.');
try {
    $definition->normalizeTeamCode('TeamA-Urlaub');
    throw new RuntimeException('Suffix-Gruppe wurde als Assistenzteam akzeptiert.');
} catch (InvalidArgumentException) {
}
if (!$definition->managesRole('gf_as', 'pfk')) throw new RuntimeException('Transitive GF-AS-/PFK-Hierarchie fehlt.');
if ($definition->managesRole('gf_digi', 'pfk')) throw new RuntimeException('Unzulässige GF-Digi-/PFK-Hierarchie.');

$custom = $definition->toArray();
$custom['roles']['office']['groupId'] = 'custom-office';
$custom['roles']['office']['label'] = 'Verwaltung';
$custom['areas']['south']['groupId'] = 'custom-south';
$custom['areas']['south']['label'] = 'Südliches Büro';
$configured = AdOrganizationDefinition::get($custom);
if ($configured->roleGroupId('office') !== 'custom-office' || $configured->areaLabel('south') !== 'Südliches Büro') throw new RuntimeException('Konfigurierbare Gruppen oder Anzeigenamen fehlen.');
$custom['hierarchy']['pdl'] = ['office'];
$configured = AdOrganizationDefinition::get($custom);
$policy = new AdOrganizationPermissionPolicy(new AdOrganizationHierarchy(null, $configured));
if (!$policy->canManage('pdl', false, ['ad-PDL'], 'office', ['custom-office'])) throw new RuntimeException('Konfigurierte Hierarchiekante wird nicht angewendet.');
if ($policy->canManage('pdl', false, ['ad-PDL'], 'pfk', ['ad-PFK'])) throw new RuntimeException('Entfernte Hierarchiekante bleibt unzulässig aktiv.');

$cyclic = $custom;
$cyclic['hierarchy']['office'] = ['gf_as'];
try {
    AdOrganizationDefinition::get($cyclic);
    throw new RuntimeException('Zyklische Hierarchie wurde nicht abgelehnt.');
} catch (InvalidArgumentException) {
}

$unknown = $custom;
$unknown['hierarchy']['pdl'] = ['nicht_vorhanden'];
try {
    AdOrganizationDefinition::get($unknown);
    throw new RuntimeException('Unbekanntes Hierarchieziel wurde nicht abgelehnt.');
} catch (InvalidArgumentException) {
}

echo "AdOrganizationDefinitionSmokeTest: OK\n";
