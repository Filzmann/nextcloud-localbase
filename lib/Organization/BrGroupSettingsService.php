<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

use OCA\LocalBase\AppInfo\AppId;
use OCA\LocalBase\Service\GroupProvisioningService;
use OCP\IAppConfig;

/** Canonical, versioned BR group mapping shared by all BR apps. */
final class BrGroupSettingsService {
    private const KEY = 'br_group_definition';

    public function __construct(
        private IAppConfig $config,
        private GroupProvisioningService $groups,
    ) {}

    /** @return array{definition:BrGroupDefinition,valid:bool,persisted:bool} */
    public function state(): array {
        $raw = $this->config->getValueString(AppId::VALUE, self::KEY, '');
        if ($raw === '') {
            return ['definition' => BrGroupDefinition::defaults(), 'valid' => false, 'persisted' => false];
        }
        try {
            $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
            return ['definition' => BrGroupDefinition::get(is_array($data) ? $data : []), 'valid' => true, 'persisted' => true];
        } catch (\Throwable) {
            return ['definition' => BrGroupDefinition::defaults(), 'valid' => false, 'persisted' => true];
        }
    }

    public function validatedDefinition(): BrGroupDefinition {
        $state = $this->state();
        if (!$state['valid']) {
            throw new \DomainException('Der gemeinsame BR-Gruppenvertrag fehlt oder ist ungültig.');
        }
        $this->assertMembershipContract($state['definition']);
        return $state['definition'];
    }

    public function initializeFromLegacyMemberGroup(string $memberGroup): BrGroupDefinition {
        $state = $this->state();
        if ($state['persisted']) {
            if (!$state['valid']) throw new \DomainException('Ein ungültiger persistierter BR-Gruppenvertrag wird nicht automatisch überschrieben.');
            return $state['definition'];
        }
        $defaults = BrGroupDefinition::defaults()->groups();
        $defaults[BrGroupDefinition::MEMBER] = trim($memberGroup) !== '' ? trim($memberGroup) : $defaults[BrGroupDefinition::MEMBER];
        return $this->save($defaults, 0);
    }

    /** @param array<string,mixed> $groups */
    public function save(array $groups, int $expectedRevision): BrGroupDefinition {
        $state = $this->state();
        $currentRevision = $state['valid'] ? $state['definition']->revision() : 0;
        if ($currentRevision !== $expectedRevision) {
            throw new \RuntimeException('Der BR-Gruppenvertrag wurde zwischenzeitlich geändert.');
        }
        $definition = BrGroupDefinition::fromGroups($groups, $currentRevision + 1);
        $this->assertMembershipContract($definition);
        $encoded = json_encode($definition->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->config->setValueString(AppId::VALUE, self::KEY, $encoded);
        return $definition;
    }

    private function assertMembershipContract(BrGroupDefinition $definition): void {
        $this->groups->assertRoleMembersBelongTo(
            $definition->groupId(BrGroupDefinition::MEMBER),
            [
                $definition->groupId(BrGroupDefinition::CHAIR),
                $definition->groupId(BrGroupDefinition::DEPUTY),
            ],
        );
    }
}
