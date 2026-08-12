<?php

declare(strict_types=1);

$root=dirname(__DIR__,2);
$info=file_get_contents($root.'/appinfo/info.xml');
$template=file_get_contents($root.'/templates/privacy-personal.php');
$icon=file_get_contents($root.'/img/privacy.svg');
if($info===false||$template===false||$icon===false)throw new RuntimeException('Datenschutz-Navigationsdatei fehlt.');
foreach(['<id>localbase_privacy</id>','<name>Datenschutz</name>','<route>localbase.privacy_page.selfService</route>','<type>settings</type>','<personal>OCA\\LocalBase\\Settings\\PersonalPrivacy</personal>','<personal-section>OCA\\LocalBase\\Settings\\PersonalPrivacySection</personal-section>'] as $contract)if(!str_contains($info,$contract))throw new RuntimeException("Datenschutz-Navigation fehlt: {$contract}");
foreach(['Datenschutz und eigene Daten','Eigene Daten anzeigen und herunterladen','privacyUrl'] as $contract)if(!str_contains($template,$contract))throw new RuntimeException("Persönlicher Datenschutz-Einstieg fehlt: {$contract}");
if(str_contains($info,'role="admin"'))throw new RuntimeException('Datenschutz-Menü ist fälschlich auf Admins begrenzt.');
echo "LocalBase privacy navigation contract passed\n";
