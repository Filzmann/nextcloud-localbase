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
$roles = $definition->roles();
foreach (['gf_as', 'pdl', 'gf_digi', 'assistant_gf_digi', 'finance_lead', 'bl', 'deputy_bl'] as $key) {
    if (($roles[$key]['singleOccupant'] ?? null) !== true) throw new RuntimeException("Einzelposition {$key} fehlt in der Standarddefinition.");
}
if (($roles['staff_hr']['singleOccupant'] ?? null) !== false || ($roles['office']['singleOccupant'] ?? null) !== false) throw new RuntimeException('Mehrpersonenrollen wurden als Einzelposition vorbelegt.');
$legacy = $definition->toArray();
foreach ($legacy['roles'] as &$legacyRole) unset($legacyRole['singleOccupant']);
unset($legacyRole);
$legacyRoles = AdOrganizationDefinition::get($legacy)->roles();
if (!$legacyRoles['gf_as']['singleOccupant'] || $legacyRoles['office']['singleOccupant']) throw new RuntimeException('Bestehende Organisationsdefinitionen erhalten keine kompatible Einzelpositions-Vorbelegung.');
$organizationTeams = array_column($definition->organizationTeams(), null, 'id');
if (($organizationTeams['office-northeast']['areas'] ?? []) !== ['northeast']) throw new RuntimeException('Urlaubsansicht Büro Nordost fehlt.');
if (($organizationTeams['office-west']['areas'] ?? []) !== ['west']) throw new RuntimeException('Urlaubsansicht Büro West fehlt.');
if (isset($organizationTeams['office-now'])) throw new RuntimeException('Büros Nordost und West werden noch unzulässig zusammengefasst.');
if ($definition->normalizeTeamCode(' TämA ') !== 'TämA') throw new RuntimeException('Unicode-Teamkürzel wird nicht normalisiert.');
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
$custom['roles']['gf_as']['singleOccupant'] = false;
$custom['areas']['south']['groupId'] = 'custom-south';
$custom['areas']['south']['label'] = 'Südliches Büro';
$configured = AdOrganizationDefinition::get($custom);
if ($configured->roleGroupId('office') !== 'custom-office' || $configured->areaLabel('south') !== 'Südliches Büro' || $configured->roles()['gf_as']['singleOccupant']) throw new RuntimeException('Konfigurierbare Gruppen, Anzeigenamen oder Einzelpositionen fehlen.');
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
