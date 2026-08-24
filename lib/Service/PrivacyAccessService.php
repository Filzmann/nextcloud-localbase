<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCA\LocalBase\AppInfo\AppId;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

final class PrivacyAccessService {
    private const ADMIN_GROUP_KEY = 'privacy_admin_group';

    public function __construct(
        private IUserSession $session,
        private IGroupManager $groups,
        private IAppConfig $config,
        private LoggerInterface $logger,
    ) {}

    public function canReadAdminReports(): bool {
        $uid = $this->session->getUser()?->getUID();
        $group = trim($this->config->getValueString(AppId::VALUE, self::ADMIN_GROUP_KEY, ''));
        return $uid !== null && $uid !== '' && $group !== '' && $this->groups->isInGroup($uid, $group);
    }

    public function logAdminAccess(string $event, string $subjectUid): void {
        $this->logger->info($event, [
            'actor_uid' => $this->session->getUser()?->getUID(),
            'subject_uid' => $subjectUid,
        ]);
    }
}
