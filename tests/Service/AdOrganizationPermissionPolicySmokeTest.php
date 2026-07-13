<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Organization/AdOrganizationDefinition.php';
require_once __DIR__ . '/../../lib/Organization/AdOrganizationHierarchy.php';
require_once __DIR__ . '/../../lib/Organization/AdOrganizationPermissionPolicy.php';

use OCA\LocalBase\Organization\AdOrganizationHierarchy;
use OCA\LocalBase\Organization\AdOrganizationPermissionPolicy;

$policy = new AdOrganizationPermissionPolicy(new AdOrganizationHierarchy());
$assert = static fn(bool $expected, bool $actual, string $message) => $expected === $actual ?: throw new RuntimeException($message);
$assert(true, $policy->canManage('pdl', false, ['ad-PDL'], 'pfk', ['ad-PFK']), 'PDL-PFK-Hierarchie fehlt.');
$assert(true, $policy->canManage('bl', false, ['ad-BL','ad-Bereich-West'], 'bo', ['ad-Buero','ad-Bereich-West']), 'BL-Bereich fehlt.');
$assert(false, $policy->canManage('bl', false, ['ad-BL','ad-Bereich-West'], 'bo', ['ad-Buero','ad-Bereich-Sued']), 'BL darf fremden Bereich nicht verwalten.');
$assert(true, $policy->canManage('a', false, ['ad-PFK'], 'b', ['ad-PFK'], ['ad-PFK']), 'Freigeschaltetes Peer-Recht fehlt.');
$assert(false, $policy->canManage('a', false, ['ad-PFK'], 'a', ['ad-PFK'], ['ad-PFK'], false), 'Self-Verbot muss Admin ausgenommen erzwingen.');
$assert(true, $policy->canManage('admin', true, [], 'admin', [], [], false), 'Adminrecht muss Self-Verbot uebersteuern.');
$assert(false, $policy->canManage('bl', false, ['ad-BL','ad-Bereich-West'], 'bo', ['ad-Buero','ad-Bereich-Nordost']), 'Konfigurierte Bereichsgrenze fehlt.');
echo "AdOrganizationPermissionPolicySmokeTest: OK\n";
