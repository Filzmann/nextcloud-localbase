<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = file_get_contents($root . '/appinfo/routes.php');
$application = file_get_contents($root . '/lib/AppInfo/Application.php');
$controller = file_get_contents($root . '/lib/Controller/AdSuiteAdminApiController.php');
$template = file_get_contents($root . '/templates/organization-admin.php');
foreach ([$routes, $application, $controller, $template] as $source) if ($source === false) throw new RuntimeException('Organisationsvertrag konnte nicht gelesen werden.');

foreach (['/api/ad-suite/admin/settings', '/api/ad-suite/admin/organization', '/api/ad-suite/admin/permissions'] as $contract) if (!str_contains($routes, $contract)) throw new RuntimeException("Admin-Route fehlt: {$contract}");
if (preg_match('/#\[[^\]]*NoAdminRequired/', $controller)) throw new RuntimeException('Admin-Controller ist für normale Nutzer*innen freigegeben.');
if (preg_match('/#\[[^\]]*NoCSRFRequired[^\]]*\]\s+public function save/', $controller)) throw new RuntimeException('Schreibender Admin-Endpunkt umgeht CSRF.');
foreach (['private function isAdmin()', '$this->groups->isAdmin(', 'Http::STATUS_FORBIDDEN', 'saveOrganization', 'savePermissions'] as $contract) if (!str_contains($controller, $contract)) throw new RuntimeException("Serverseitiger Admin-Vertrag fehlt: {$contract}");
foreach (['registerSection(IManager::SETTINGS_ADMIN', 'StandaloneProductAdminSection::class', 'StandaloneOrganizationAdmin::class'] as $contract) if (!str_contains($application, $contract)) throw new RuntimeException("Dynamische Adminregistrierung fehlt: {$contract}");
foreach (['id="orgsuite-admin"', 'id="orgs-organization-form"', 'id="orgs-permissions-form"', 'Bei einer Einzelinstallation'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Admin-UI-Vertrag fehlt: {$contract}");
foreach (["addScript('localbase', 'components/hierarchy-board')", "addScript('localbase', 'components/organization-editor')", "addStyle('localbase', 'organization-admin')"] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Admin-Assetvertrag fehlt: {$contract}");

echo "AdSuiteAdminContractTest: OK\n";
