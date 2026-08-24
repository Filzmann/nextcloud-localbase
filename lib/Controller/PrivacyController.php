<?php

declare(strict_types=1);

namespace OCA\LocalBase\Controller;

use OCA\LocalBase\AppInfo\AppId;
use OCA\LocalBase\Privacy\PersonalDataAggregator;
use OCA\LocalBase\Privacy\PersonalDataRequest;
use OCA\LocalBase\Privacy\PersonalDataSubject;
use OCA\LocalBase\Privacy\RetentionPreviewAggregator;
use OCA\LocalBase\Privacy\RetentionPreviewRequest;
use OCA\LocalBase\Service\PrivacyAccessService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

final class PrivacyController extends Controller {
    public function __construct(
        IRequest $request,
        private IUserSession $session,
        private PersonalDataAggregator $personalData,
        private RetentionPreviewAggregator $retention,
        private PrivacyAccessService $access,
    ) {
        parent::__construct(AppId::VALUE, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function selfService(): JSONResponse {
        $uid = $this->session->getUser()?->getUID();
        if ($uid === null || $uid === '') return new JSONResponse(['message' => 'Authentifizierung erforderlich.'], Http::STATUS_UNAUTHORIZED);
        return new JSONResponse($this->personalData->collect(new PersonalDataRequest(
            new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, $uid),
            'de',
            PersonalDataRequest::PURPOSE_SELF_SERVICE,
        )));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function adminReport(string $subjectUid): JSONResponse {
        if (!$this->access->canReadAdminReports()) return new JSONResponse(['message' => 'Keine Berechtigung.'], Http::STATUS_FORBIDDEN);
        try {
            $report = $this->personalData->collect(new PersonalDataRequest(
                new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, $subjectUid),
                'de',
                PersonalDataRequest::PURPOSE_ADMIN_REPORT,
            ));
            $this->access->logAdminAccess('privacy.admin_report', $subjectUid);
            return new JSONResponse($report);
        } catch (Throwable) {
            return new JSONResponse(['message' => 'Subject ist ungültig.'], 400);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function retentionPreview(string $subjectUid): JSONResponse {
        if (!$this->access->canReadAdminReports()) return new JSONResponse(['message' => 'Keine Berechtigung.'], Http::STATUS_FORBIDDEN);
        try {
            $preview = $this->retention->preview(new RetentionPreviewRequest(
                new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, $subjectUid),
            ));
            $this->access->logAdminAccess('privacy.retention_preview', $subjectUid);
            return new JSONResponse($preview);
        } catch (Throwable) {
            return new JSONResponse(['message' => 'Subject ist ungültig.'], 400);
        }
    }

}
