<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Organization/AdOrganizationDefinition.php';
require_once __DIR__ . '/../../lib/Organization/AdOrganizationHierarchy.php';
require_once __DIR__ . '/../../lib/Organization/AdOrganizationPermissionPolicy.php';

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationHierarchy;
use OCA\LocalBase\Organization\AdOrganizationPermissionPolicy;

$definition = AdOrganizationDefinition::defaults();
if ($definition->diagramOrder() !== []) throw new RuntimeException('Die visuelle Diagrammordnung muss kompatibel ohne Vorgabe starten.');
if ($definition->roleLabelForGroup('ad-Buero') !== 'Büromitarbeiter*innen') throw new RuntimeException('Sichtbarer Rollenname fehlt.');
if ($definition->areaLabelForGroup('ad-Bereich-Sued') !== 'Süd') throw new RuntimeException('Sichtbarer Bereichsname fehlt.');
$roles = $definition->roles();
if ($definition->toArray()['version'] !== 2) throw new RuntimeException('Die additive Organisationsmigration verwendet nicht Vertragsversion 2.');
foreach ([
    'deputy_pdl' => ['ad-StvPDL', 'Stellvertretende Pflegedienstleitung', true],
    'care_office' => ['ad-Bueroorganisation-Pflege', 'Büroorganisation Pflege', false],
    'fleet_management' => ['ad-Fahrzeugverwaltung', 'Fahrzeugverwaltung', false],
    'reception' => ['ad-Empfang', 'Empfang', false],
] as $key => [$groupId, $label, $singleOccupant]) {
    $role = $roles[$key] ?? null;
    if ($role === null || $role['groupId'] !== $groupId || $role['label'] !== $label || $role['singleOccupant'] !== $singleOccupant || $role['areaScoped'] || $role['staffBlock'] || !$role['calendarVisible']) {
        throw new RuntimeException("Neue globale Kalenderrolle {$key} ist falsch vorbelegt.");
    }
}
if (!(
    $roles['it']['sortOrder'] < $roles['fleet_management']['sortOrder']
    && $roles['fleet_management']['sortOrder'] < $roles['secretariat']['sortOrder']
    && $roles['secretariat']['sortOrder'] < $roles['reception']['sortOrder']
    && $roles['office']['sortOrder'] < $roles['deputy_pdl']['sortOrder']
    && $roles['deputy_pdl']['sortOrder'] < $roles['care_office']['sortOrder']
    && $roles['care_office']['sortOrder'] < $roles['pfk']['sortOrder']
    &&
    $roles['bl']['sortOrder'] < $roles['deputy_bl']['sortOrder']
    && $roles['deputy_bl']['sortOrder'] < $roles['eb']['sortOrder']
    && $roles['eb']['sortOrder'] < $roles['office']['sortOrder']
    && $roles['office']['sortOrder'] < $roles['pfk']['sortOrder']
)) {
    throw new RuntimeException('Die vereinbarte Standardreihenfolge der Organisationsgruppen fehlt.');
}
foreach (['gf_as', 'pdl', 'deputy_pdl', 'gf_digi', 'assistant_gf_digi', 'finance_lead', 'bl', 'deputy_bl'] as $key) {
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
foreach ([['pdl', 'deputy_pdl'], ['pdl', 'care_office'], ['deputy_pdl', 'care_office'], ['deputy_pdl', 'pfk'], ['gf_digi', 'fleet_management'], ['secretariat', 'reception']] as [$manager, $target]) {
    if (!in_array($target, $definition->hierarchy()[$manager] ?? [], true)) throw new RuntimeException("Direkte Hierarchiekante {$manager} -> {$target} fehlt.");
}
$organizationTeams = array_column($definition->organizationTeams(), null, 'id');
if (($organizationTeams['pfk']['roles'] ?? []) !== ['deputy_pdl', 'care_office', 'pfk']) throw new RuntimeException('Die Pflege-Urlaubsansicht beginnt nicht mit Stv. PDL und Büroorganisation Pflege.');
if (($organizationTeams['fleet-management']['roles'] ?? []) !== ['fleet_management'] || ($organizationTeams['reception']['roles'] ?? []) !== ['reception']) throw new RuntimeException('Urlaubsansichten für Fahrzeugverwaltung oder Empfang fehlen.');

$legacyOrganization = $definition->toArray();
$legacyOrganization['version'] = 1;
foreach (['deputy_pdl', 'care_office', 'fleet_management', 'reception'] as $key) unset($legacyOrganization['roles'][$key], $legacyOrganization['hierarchy'][$key]);
$legacyOrganization['roles']['pfk']['label'] = 'Individuelle Pflegebezeichnung';
$legacyOrganization['roles']['pfk']['sortOrder'] = 777;
$legacyOrganization['hierarchy']['pdl'] = ['pfk', 'office'];
$legacyOrganization['hierarchy']['gf_digi'] = array_values(array_diff($legacyOrganization['hierarchy']['gf_digi'], ['fleet_management']));
$legacyOrganization['hierarchy']['secretariat'] = [];
$legacyOrganization['organizationTeams'] = array_values(array_filter($legacyOrganization['organizationTeams'], static fn(array $team): bool => !in_array($team['id'], ['fleet-management', 'reception'], true)));
foreach ($legacyOrganization['organizationTeams'] as &$team) if ($team['id'] === 'pfk') $team['roles'] = ['pfk'];
unset($team);
$migratedOrganization = AdOrganizationDefinition::get($legacyOrganization);
if ($migratedOrganization->toArray()['version'] !== 2 || $migratedOrganization->roleLabel('pfk') !== 'Individuelle Pflegebezeichnung' || $migratedOrganization->roles()['pfk']['sortOrder'] !== 777) throw new RuntimeException('Bestehende Organisationswerte werden bei der Migration verändert.');
if (!in_array('office', $migratedOrganization->hierarchy()['pdl'], true) || !$migratedOrganization->managesRole('deputy_pdl', 'pfk')) throw new RuntimeException('Bestehende Kanten werden nicht bewahrt oder neue Hierarchiekanten fehlen nach der Migration.');

$collidingLegacy = $legacyOrganization;
$collidingLegacy['roles']['custom_collision'] = $collidingLegacy['roles']['office'];
$collidingLegacy['roles']['custom_collision']['groupId'] = 'ad-StvPDL';
$collidingLegacy['roles']['custom_collision']['label'] = 'Kollidierende Testrolle';
try {
    AdOrganizationDefinition::get($collidingLegacy);
    throw new RuntimeException('Kollidierende Gruppen-ID wurde bei der additiven Migration überschrieben.');
} catch (InvalidArgumentException) {
}

$custom = $definition->toArray();
$custom['roles']['office']['groupId'] = 'custom-office';
$custom['roles']['office']['label'] = 'Verwaltung';
$custom['roles']['gf_as']['singleOccupant'] = false;
$custom['areas']['south']['groupId'] = 'custom-south';
$custom['areas']['south']['label'] = 'Südliches Büro';
$custom['diagramOrder'] = ['gf_as', 'bl::west', 'bl::south'];
$configured = AdOrganizationDefinition::get($custom);
if ($configured->roleGroupId('office') !== 'custom-office' || $configured->areaLabel('south') !== 'Südliches Büro' || $configured->roles()['gf_as']['singleOccupant'] || $configured->diagramOrder() !== ['gf_as', 'bl::west', 'bl::south']) throw new RuntimeException('Konfigurierbare Gruppen, Anzeigenamen, Einzelpositionen oder visuelle Diagrammordnung fehlen.');
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

$unknownDiagramNode = $custom;
$unknownDiagramNode['diagramOrder'][] = 'bl::unbekannt';
try {
    AdOrganizationDefinition::get($unknownDiagramNode);
    throw new RuntimeException('Unbekannter Diagrammknoten wurde nicht abgelehnt.');
} catch (InvalidArgumentException) {
}

$duplicateDiagramNode = $custom;
$duplicateDiagramNode['diagramOrder'][] = 'gf_as';
try {
    AdOrganizationDefinition::get($duplicateDiagramNode);
    throw new RuntimeException('Doppelter Diagrammknoten wurde nicht abgelehnt.');
} catch (InvalidArgumentException) {
}

echo "AdOrganizationDefinitionSmokeTest: OK\n";
