<?php

declare(strict_types=1);

namespace OCA\LocalBase\Controller;

use InvalidArgumentException;
use OCA\LocalBase\AppInfo\Application;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;
use OCA\LocalBase\Organization\AdSuiteAdminSettingsService;
use OCA\LocalBase\Service\AdSuiteAdminLayoutService;
use OCA\LocalBase\Service\OrganizationDirectoryStatusService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Zweck: Stellt die organisationsweiten Suite-Einstellungen ausschließlich Nextcloud-Admins bereit.
 * Zusammenspiel: Admin-UI -> AdSuiteAdminApiController -> LocalBase-Organisations- und Freigabeservices.
 * Vertrag: Keine Methode trägt NoAdminRequired; zusätzlich verweigert der Controller direkte Aufrufe ohne aktive Admin-Sitzung.
 */
final class AdSuiteAdminApiController extends Controller {
    public function __construct(
        IRequest $request,
        private IUserSession $session,
        private IGroupManager $groups,
        private AdOrganizationSettingsService $organization,
        private AdSuiteAdminSettingsService $adminSettings,
        private OrganizationDirectoryStatusService $directoryStatus,
        private AdSuiteAdminLayoutService $dashboardLayout,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    public function settings(): JSONResponse {
        if (!$this->isAdmin()) return $this->denied();
        return new JSONResponse([
            'organization' => $this->organization->definition()->toArray(),
            'calendarPeerEditing' => $this->adminSettings->calendarPeerEditing(),
            'calendarPeerOptions' => $this->adminSettings->calendarPeerOptions(),
            'vacationPeerApproval' => $this->adminSettings->vacationPeerApproval(),
            'vacationPeerOptions' => $this->adminSettings->vacationPeerOptions(),
            'directory' => $this->directoryStatus->status(),
            'dashboardLayout' => $this->dashboardLayout->layout($this->session->getUser()->getUID()),
        ]);
    }

    public function saveOrganization(array $organization): JSONResponse {
        if (!$this->isAdmin()) return $this->denied();
        try {
            return new JSONResponse(['organization' => $this->organization->save($organization)->toArray()]);
        } catch (InvalidArgumentException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('AD-Organisation konnte nicht gespeichert werden.', ['exception' => $error]);
            return new JSONResponse(['error' => 'Die AD-Organisation konnte nicht gespeichert werden.'], Http::STATUS_BAD_REQUEST);
        }
    }

    public function savePermissions(array $calendarPeerEditing, array $vacationPeerApproval): JSONResponse {
        if (!$this->isAdmin()) return $this->denied();
        return new JSONResponse([
            'calendarPeerEditing' => $this->adminSettings->saveCalendarPeerEditing($calendarPeerEditing),
            'vacationPeerApproval' => $this->adminSettings->saveVacationPeerApproval($vacationPeerApproval),
        ]);
    }

    public function saveLayout(array $layout): JSONResponse {
        if (!$this->isAdmin()) return $this->denied();
        try {
            return new JSONResponse(['dashboardLayout' => $this->dashboardLayout->save($this->session->getUser()->getUID(), $layout)]);
        } catch (InvalidArgumentException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('Persönliches AD-Adminlayout konnte nicht gespeichert werden.', ['exception' => $error]);
            return new JSONResponse(['error' => 'Das persönliche Adminlayout konnte nicht gespeichert werden.'], Http::STATUS_BAD_REQUEST);
        }
    }

    private function isAdmin(): bool {
        $user = $this->session->getUser();
        return $user !== null && $this->groups->isAdmin($user->getUID());
    }

    private function denied(): JSONResponse {
        return new JSONResponse(['error' => 'Keine Berechtigung.'], Http::STATUS_FORBIDDEN);
    }
}
