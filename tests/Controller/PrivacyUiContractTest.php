<?php

declare(strict_types=1);

$routes = file_get_contents(__DIR__ . '/../../appinfo/routes.php');
$template = file_get_contents(__DIR__ . '/../../templates/privacy.php');
$script = file_get_contents(__DIR__ . '/../../js/privacy/privacy-report.js');
$style = file_get_contents(__DIR__ . '/../../css/privacy.css');

foreach ([$routes, $template, $script, $style] as $source) if ($source === false) throw new RuntimeException('Privacy-UI-Quelle fehlt.');
foreach (['/privacy', '/privacy/admin', '/api/privacy/self'] as $contract) if (!str_contains($routes, $contract)) throw new RuntimeException("Privacy-Route fehlt: {$contract}");
foreach (['<main', '<h1', 'role="status"', 'aria-live="polite"', '<form', 'data-privacy-mode', 'lb-privacy-download', 'Auskunft als PDF herunterladen', 'lb-privacy-apps'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Privacy-UI-Vertrag fehlt: {$contract}");
foreach (['/api/privacy/self', '/api/privacy/admin/', 'textContent', 'encodeURIComponent', 'PrivacyReportDownload', 'article15', 'dataType', 'attributes', 'Weitere Angaben zur Verarbeitung in dieser App', 'Grund der Speicherung', 'Aufbewahrt bis', 'sharedValue', "createElement('table')", "th.scope = 'col'"] as $contract) if (!str_contains($script, $contract)) throw new RuntimeException("Privacy-Clientvertrag fehlt: {$contract}");
foreach (['<dl', 'lb-privacy-results'] as $obsolete) if (str_contains($template, $obsolete)) throw new RuntimeException("Technische Definitionslistendarstellung ist noch vorhanden: {$obsolete}");
foreach (['overflow-y:auto', ':focus-visible'] as $contract) if (!str_contains(str_replace(' ', '', $style), str_replace(' ', '', $contract))) throw new RuntimeException("Privacy-Layoutvertrag fehlt: {$contract}");
foreach (['innerHTML', 'NoCSRFRequired'] as $unsafe) if (str_contains($script, $unsafe)) throw new RuntimeException("Privacy-Client enthält unsicheren Vertrag: {$unsafe}");

foreach ([$routes, $template, $script] as $source) if (str_contains($source, 'retention-preview') || str_contains($source, 'lb-privacy-retention') || str_contains($source, 'Dry Run')) throw new RuntimeException('Der entfernte LocalBase-Retention-Pilot ist noch in Route oder UI erreichbar.');
foreach (['RetentionProvider.php', 'RetentionProviderRegistryEvent.php', 'RetentionPreviewAggregator.php', 'RetentionPreviewCandidate.php', 'RetentionPreviewPage.php', 'RetentionPreviewRequest.php'] as $obsolete) if (is_file(__DIR__ . '/../../lib/Privacy/' . $obsolete)) throw new RuntimeException("Veralteter LocalBase-Retention-Vertrag ist noch vorhanden: {$obsolete}");

echo "PrivacyUiContractTest: OK\n";
