<?php

declare(strict_types=1);


use OCA\LocalBase\Organization\FlzOrganizationHierarchy;
use OCA\LocalBase\Organization\FlzOrganizationPermissionPolicy;

$policy = new FlzOrganizationPermissionPolicy(new FlzOrganizationHierarchy());
$assert = static fn(bool $expected, bool $actual, string $message) => $expected === $actual ?: throw new RuntimeException($message);
$assert(true, $policy->canManage('pdl', false, ['flz-PDL'], 'pfk', ['flz-PFK']), 'PDL-PFK-Hierarchie fehlt.');
$assert(true, $policy->canManage('stv-pdl', false, ['flz-StvPDL'], 'pfk', ['flz-PFK']), 'Stv. PDL muss Pflegefachkräfte verwalten dürfen.');
$assert(true, $policy->canManage('stv-pdl', false, ['flz-StvPDL'], 'pflegebuero', ['flz-Bueroorganisation-Pflege']), 'Stv. PDL muss Büroorganisation Pflege verwalten dürfen.');
$assert(true, $policy->canManage('gf-digi', false, ['flz-GF-Digi'], 'fuhrpark', ['flz-Fahrzeugverwaltung']), 'GF-Digi muss Fahrzeugverwaltung verwalten dürfen.');
$assert(true, $policy->canManage('sekretariat', false, ['flz-Sekretariat'], 'empfang', ['flz-Empfang']), 'Sekretariat muss Empfang verwalten dürfen.');
$assert(false, $policy->canManage('empfang', false, ['flz-Empfang'], 'sekretariat', ['flz-Sekretariat']), 'Empfang darf das Sekretariat nicht verwalten.');
$assert(true, $policy->canManage('bl', false, ['flz-BL','flz-Bereich-West'], 'bo', ['flz-Buero','flz-Bereich-West']), 'BL-Bereich fehlt.');
$assert(false, $policy->canManage('bl', false, ['flz-BL','flz-Bereich-West'], 'bo', ['flz-Buero','flz-Bereich-Sued']), 'BL darf fremden Bereich nicht verwalten.');
$assert(true, $policy->canManage('a', false, ['flz-PFK'], 'b', ['flz-PFK'], ['flz-PFK']), 'Freigeschaltetes Peer-Recht fehlt.');
$assert(false, $policy->canManage('a', false, ['flz-PFK'], 'a', ['flz-PFK'], ['flz-PFK'], false), 'Self-Verbot muss Admin ausgenommen erzwingen.');
$assert(true, $policy->canManage('admin', true, [], 'admin', [], [], false), 'Adminrecht muss Self-Verbot uebersteuern.');
$assert(false, $policy->canManage('bl', false, ['flz-BL','flz-Bereich-West'], 'bo', ['flz-Buero','flz-Bereich-Nordost']), 'Konfigurierte Bereichsgrenze fehlt.');
echo "FlzOrganizationPermissionPolicySmokeTest: OK\n";
