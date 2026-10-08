<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

use OCA\LocalBase\AppInfo\Application;
use OCP\IAppConfig;

/**
 * Zweck: Persistiert die kanonische FLZ-Organisationsdefinition als gemeinsame App-Einstellung.
 * Zusammenspiel: Administrative Fachapp -> SettingsService -> IAppConfig; alle Verbraucher laden denselben validierten Vertrag.
 * Vertrag: Ungültige gespeicherte Daten fallen sicher auf die geprüften Defaults zurück; Schreibversuche mit ungültigen Daten schlagen fehl.
 */
final class FlzOrganizationSettingsService {
    private const KEY = 'flz_organization_definition';

    public function __construct(private IAppConfig $config) {}

    public function definition(): FlzOrganizationDefinition {
        return $this->state()['definition'];
    }

    /** @return array{definition: FlzOrganizationDefinition, valid: bool, persisted: bool} */
    public function state(): array {
        $raw = $this->config->getValueString(Application::APP_ID, self::KEY, '');
        if ($raw === '') return ['definition' => FlzOrganizationDefinition::defaults(), 'valid' => false, 'persisted' => false];
        try {
            $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
            $definition = FlzOrganizationDefinition::get(is_array($data) ? $data : []);
            if ((int)($data['version'] ?? 1) < 3) {
                $this->config->setValueString(Application::APP_ID, self::KEY, json_encode($definition->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            }
            return ['definition' => $definition, 'valid' => true, 'persisted' => true];
        } catch (\Throwable) {
            return ['definition' => FlzOrganizationDefinition::defaults(), 'valid' => false, 'persisted' => true];
        }
    }

    public function save(array $data): FlzOrganizationDefinition {
        $definition = FlzOrganizationDefinition::get($data);
        $encoded = json_encode($definition->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->config->setValueString(Application::APP_ID, self::KEY, $encoded);
        return $definition;
    }
}
