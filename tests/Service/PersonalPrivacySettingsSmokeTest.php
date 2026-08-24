<?php

declare(strict_types=1);

namespace OCP {
    interface IURLGenerator { public function linkToRoute(string $routeName,array $arguments=[]):string; public function imagePath(string $appId,string $file):string; }
}
namespace OCP\Settings {
    interface ISettings { public function getForm(); public function getSection():string; public function getPriority():int; }
    interface IIconSection { public function getIcon():string; public function getID():string; public function getName():string; public function getPriority():int; }
}
namespace OCP\AppFramework\Http {
    final class TemplateResponse {
        public function __construct(public string $appName,public string $templateName,public array $params=[]){}
    }
}

namespace {
    use OCA\LocalBase\Settings\PersonalPrivacy;
    use OCA\LocalBase\Settings\PersonalPrivacySection;

    $url=new class implements OCP\IURLGenerator {
        public function linkToRoute(string $routeName,array $arguments=[]):string{return '/route/'.$routeName;}
        public function imagePath(string $appId,string $file):string{return '/img/'.$appId.'/'.$file;}
    };
    $settings=new PersonalPrivacy($url);
    $form=$settings->getForm();
    if($settings->getSection()!=='localbase_privacy'||$settings->getPriority()!==10)throw new RuntimeException('Persönliche Datenschutzeinstellungen sind falsch einsortiert.');
    if($form->templateName!=='privacy-personal'||$form->params['privacyUrl']!=='/route/localbase.privacy_page.selfService')throw new RuntimeException('Persönliche Datenschutzeinstellungen verweisen nicht auf die kanonische Auskunft.');
    $section=new PersonalPrivacySection($url);
    if($section->getID()!=='localbase_privacy'||$section->getName()!=='Datenschutz'||!str_ends_with($section->getIcon(),'/privacy.svg'))throw new RuntimeException('Persönlicher Datenschutzabschnitt fehlt oder ist unverständlich benannt.');
    echo "LocalBase personal privacy settings smoke passed\n";
}
