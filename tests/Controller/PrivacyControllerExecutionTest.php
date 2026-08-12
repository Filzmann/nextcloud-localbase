<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {}
    interface IUser { public function getUID(): string; }
    interface IUserSession { public function getUser(): ?IUser; }
    interface IGroupManager { public function isInGroup(string $uid, string $gid): bool; }
    interface IAppConfig { public function getValueString(string $appId, string $key, string $default = ''): string; }
}
namespace OCP\AppFramework { class Controller { public function __construct(string $appId, \OCP\IRequest $request) {} } final class Http { public const STATUS_UNAUTHORIZED=401; public const STATUS_FORBIDDEN=403; } }
namespace OCP\AppFramework\Http { final class JSONResponse { public function __construct(private array $data=[],private int $status=200){} public function getData():array{return $this->data;} public function getStatus():int{return $this->status;} } }
namespace OCP\AppFramework\Http\Attribute { #[\Attribute(\Attribute::TARGET_METHOD)] final class NoAdminRequired {} #[\Attribute(\Attribute::TARGET_METHOD)] final class NoCSRFRequired {} }
namespace OCP\EventDispatcher { class Event { public function __construct() {} } interface IEventDispatcher { public function dispatchTyped(object $event): object; } }
namespace Psr\Log { interface LoggerInterface { public function info(string|\Stringable $message, array $context=[]): void; } }

namespace {
    use OCA\LocalBase\Controller\PrivacyController;
    use OCA\LocalBase\Privacy\PersonalDataAggregator;
    use OCA\LocalBase\Privacy\RetentionPreviewAggregator;
    use OCA\LocalBase\Service\PrivacyAccessService;
    use OCP\AppFramework\Http;

    $user = new class implements OCP\IUser { public function getUID(): string { return 'self-user'; } };
    $session = new class($user) implements OCP\IUserSession { public function __construct(public ?OCP\IUser $user){} public function getUser(): ?OCP\IUser{return $this->user;} };
    $groups = new class implements OCP\IGroupManager { public bool $allowed=false; public function isInGroup(string $uid,string $gid):bool{return $this->allowed&&$uid==='self-user'&&$gid==='privacy-team';} };
    $config = new class implements OCP\IAppConfig { public string $group='privacy-team'; public function getValueString(string $appId,string $key,string $default=''):string{return $this->group;} };
    $events = new class implements OCP\EventDispatcher\IEventDispatcher { public function dispatchTyped(object $event):object{return $event;} };
    $logger = new class implements Psr\Log\LoggerInterface { public array $entries=[]; public function info(string|Stringable $message,array $context=[]):void{$this->entries[]=['message'=>(string)$message,'context'=>$context];} };
    $access = new PrivacyAccessService($session,$groups,$config,$logger);
    $controller = new PrivacyController(new class implements OCP\IRequest {},$session,new PersonalDataAggregator($events),new RetentionPreviewAggregator($events),$access);

    $self = $controller->selfService();
    if ($self->getStatus() !== 200 || $self->getData()['subject']['id'] !== 'self-user') throw new RuntimeException('Self-Service ist nicht an die Session gebunden.');
    if ($controller->adminReport('target-user')->getStatus() !== Http::STATUS_FORBIDDEN) throw new RuntimeException('Admin-Auskunft ist ohne Datenschutzgruppe erlaubt.');
    if ($controller->retentionPreview('target-user')->getStatus() !== Http::STATUS_FORBIDDEN) throw new RuntimeException('Retention-Preview ist ohne Datenschutzgruppe erlaubt.');

    $groups->allowed = true;
    if ($controller->adminReport('target-user')->getData()['subject']['id'] !== 'target-user') throw new RuntimeException('Berechtigte Admin-Auskunft verwendet nicht das validierte Zielsubject.');
    if ($controller->retentionPreview('target-user')->getData()['dryRun'] !== true) throw new RuntimeException('Admin-Retention ist kein Dry Run.');
    if (array_column($logger->entries, 'message') !== ['privacy.admin_report', 'privacy.retention_preview']) throw new RuntimeException('Erfolgreiche Admin-Abrufe werden nicht minimal protokolliert.');
    foreach ($logger->entries as $entry) {
        if ($entry['context'] !== ['actor_uid' => 'self-user', 'subject_uid' => 'target-user']) throw new RuntimeException('Admin-Protokoll enthält falsche oder unnötige Metadaten.');
    }

    $config->group = '';
    if ($controller->adminReport('target-user')->getStatus() !== Http::STATUS_FORBIDDEN) throw new RuntimeException('Fehlende Datenschutzgruppe ist nicht deny by default.');
    $session->user = null;
    if ($controller->selfService()->getStatus() !== Http::STATUS_UNAUTHORIZED) throw new RuntimeException('Anonymer Self-Service ist erlaubt.');

    echo "PrivacyControllerExecutionTest: OK\n";
}
