<?php

declare(strict_types=1);


use OCA\LocalBase\Organization\FlzOrganizationHierarchy;
use OCA\LocalBase\Organization\FlzOrganizationPermissionPolicy;

$policy = new FlzOrganizationPermissionPolicy(new FlzOrganizationHierarchy());
$assert = static fn(bool $expected, bool $actual, string $message) => $expected === $actual ?: throw new RuntimeException($message);
$assert(true, $policy->canManage('pdl', false, ['PDL'], 'pfk', ['PFK']), 'PDL-PFK-Hierarchie fehlt.');
$assert(true, $policy->canManage('stv-pdl', false, ['StvPDL'], 'pfk', ['PFK']), 'Stv. PDL muss Pflegefachkräfte verwalten dürfen.');
$assert(true, $policy->canManage('stv-pdl', false, ['StvPDL'], 'pflegebuero', ['Bueroorganisation-Pflege']), 'Stv. PDL muss Büroorganisation Pflege verwalten dürfen.');
$assert(true, $policy->canManage('gf-digi', false, ['GF-Digi'], 'fuhrpark', ['Fahrzeugverwaltung']), 'GF-Digi muss Fahrzeugverwaltung verwalten dürfen.');
$assert(true, $policy->canManage('sekretariat', false, ['Sekretariat'], 'empfang', ['Empfang']), 'Sekretariat muss Empfang verwalten dürfen.');
$assert(false, $policy->canManage('empfang', false, ['Empfang'], 'sekretariat', ['Sekretariat']), 'Empfang darf das Sekretariat nicht verwalten.');
$assert(true, $policy->canManage('bl', false, ['BL','Bereich-West'], 'bo', ['Buero','Bereich-West']), 'BL-Bereich fehlt.');
$assert(false, $policy->canManage('bl', false, ['BL','Bereich-West'], 'bo', ['Buero','Bereich-Sued']), 'BL darf fremden Bereich nicht verwalten.');
$assert(true, $policy->canManage('a', false, ['PFK'], 'b', ['PFK'], ['PFK']), 'Freigeschaltetes Peer-Recht fehlt.');
$assert(false, $policy->canManage('a', false, ['PFK'], 'a', ['PFK'], ['PFK'], false), 'Self-Verbot muss Admin ausgenommen erzwingen.');
$assert(true, $policy->canManage('admin', true, [], 'admin', [], [], false), 'Adminrecht muss Self-Verbot uebersteuern.');
$assert(false, $policy->canManage('bl', false, ['BL','Bereich-West'], 'bo', ['Buero','Bereich-Nordost']), 'Konfigurierte Bereichsgrenze fehlt.');
echo "FlzOrganizationPermissionPolicySmokeTest: OK\n";
