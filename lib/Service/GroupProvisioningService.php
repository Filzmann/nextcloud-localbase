<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use OCP\IGroupManager;

class GroupProvisioningService {
    public function __construct(
        private IGroupManager $groupManager
    ) {
    }

    /**
     * @param array<int, string> $groupNames
     * @return array<int, string>
     */
    public function ensureGroups(array $groupNames): array {
        $created = [];

        foreach ($groupNames as $groupName) {
            if ($this->groupManager->groupExists($groupName)) {
                continue;
            }

            $group = $this->groupManager->createGroup($groupName);
            if ($group === null && !$this->groupManager->groupExists($groupName)) {
                throw new \RuntimeException('Nextcloud-Gruppe ' . $groupName . ' konnte nicht angelegt werden.');
            }

            if ($group !== null) {
                $created[] = $groupName;
            }
        }

        return $created;
    }
}
