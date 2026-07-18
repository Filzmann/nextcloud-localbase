<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {}
    interface IUser { public function getUID(): string; }
    interface IUserSession { public function getUser(): ?IUser; }
    interface IGroupManager { public function isAdmin(string $uid): bool; }
}
namespace OCP\AppFramework {
    class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} }
    class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_FORBIDDEN = 403; }
}
namespace OCP\AppFramework\Http {
    class JSONResponse {
        public function __construct(private array $data = [], private int $status = 200) {}
        public function getData(): array { return $this->data; }
        public function getStatus(): int { return $this->status; }
    }
}
namespace Psr\Log { interface LoggerInterface { public function error(string $message, array $context = []): void; } }
namespace OCA\LocalBase\AppInfo { final class Application { public const APP_ID = 'localbase'; } }
namespace OCA\LocalBase\Organization {
    class Definition { public function __construct(private array $data) {} public function toArray(): array { return $this->data; } }
    class AdOrganizationSettingsService {
        public bool $invalid = false;
        public bool $failure = false;
        public function definition(): Definition { return new Definition(['roles' => ['buero']]); }
        public function save(array $organization): Definition {
            if ($this->invalid) throw new \InvalidArgumentException('Ungültige Organisation.');
            if ($this->failure) throw new \RuntimeException('Intern');
            return new Definition($organization);
        }
    }
    class AdSuiteAdminSettingsService {
        public function calendarPeerEditing(): array { return ['ad-Buero' => true]; }
        public function calendarPeerOptions(): array { return ['ad-Buero']; }
        public function vacationPeerApproval(): array { return ['ad-PFK' => false]; }
        public function vacationPeerOptions(): array { return ['ad-PFK']; }
        public function saveCalendarPeerEditing(array $value): array { return $value; }
        public function saveVacationPeerApproval(array $value): array { return $value; }
    }
}

namespace OCA\LocalBase\Service {
    class OrganizationDirectoryStatusService {
        public function status(): array { return ['compatible' => true, 'demoWritable' => false, 'groups' => [['groupId' => 'ad-Buero', 'exists' => true]], 'positions' => [['roleKey' => 'gf_as', 'areaKey' => null, 'displayNames' => ['Gina Führung']]]]; }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/Controller/AdSuiteAdminApiController.php';

    use OCA\LocalBase\Organization\AdOrganizationSettingsService;
    use OCA\LocalBase\Organization\AdSuiteAdminSettingsService;
    use OCA\LocalBase\Controller\AdSuiteAdminApiController;
    use OCA\LocalBase\Service\OrganizationDirectoryStatusService;
    use OCP\IGroupManager;
    use OCP\IRequest;
    use OCP\IUser;
    use OCP\IUserSession;
    use Psr\Log\LoggerInterface;

    $request = new class implements IRequest {};
    $user = new class implements IUser { public function getUID(): string { return 'admin'; } };
    $session = new class($user) implements IUserSession { public function __construct(private ?IUser $user) {} public function getUser(): ?IUser { return $this->user; } };
    $groups = new class implements IGroupManager { public bool $admin = false; public function isAdmin(string $uid): bool { return $this->admin; } };
    $organization = new AdOrganizationSettingsService();
    $settings = new AdSuiteAdminSettingsService();
    $logger = new class implements LoggerInterface { public array $errors = []; public function error(string $message, array $context = []): void { $this->errors[] = [$message, $context]; } };
    $directory = new OrganizationDirectoryStatusService();
    $controller = new AdSuiteAdminApiController($request, $session, $groups, $organization, $settings, $directory, $logger);
    if ($controller->settings()->getStatus() !== 403) throw new RuntimeException('Nicht-Admin kann Einstellungen lesen.');
    if ($controller->saveOrganization([])->getStatus() !== 403 || $controller->savePermissions([], [])->getStatus() !== 403) throw new RuntimeException('Nicht-Admin kann Einstellungen schreiben.');
    $groups->admin = true;
    $data = $controller->settings()->getData();
    if (($data['organization']['roles'][0] ?? '') !== 'buero' || !isset($data['calendarPeerOptions'], $data['vacationPeerOptions']) || ($data['directory']['compatible'] ?? null) !== true || ($data['directory']['positions'][0]['displayNames'] ?? []) !== ['Gina Führung']) throw new RuntimeException('Admin-Einstellungen sind unvollständig.');
    if ($controller->saveOrganization(['roles' => ['pfk']])->getData()['organization']['roles'][0] !== 'pfk') throw new RuntimeException('Organisation wird nicht gespeichert.');
    $organization->invalid = true;
    if ($controller->saveOrganization([])->getStatus() !== 400) throw new RuntimeException('Validierungsfehler erhält keinen Status 400.');
    $organization->invalid = false;
    $organization->failure = true;
    if ($controller->saveOrganization([])->getStatus() !== 400 || $logger->errors === []) throw new RuntimeException('Interner Fehler wird nicht sicher behandelt.');
    $permissions = $controller->savePermissions(['ad-Buero' => false], ['ad-PFK' => true])->getData();
    if ($permissions['calendarPeerEditing']['ad-Buero'] !== false || $permissions['vacationPeerApproval']['ad-PFK'] !== true) throw new RuntimeException('Freigaben werden nicht gespeichert.');

    echo "LocalBase AD-Suite admin controller execution tests passed\n";
}
