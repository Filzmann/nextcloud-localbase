<?php

declare(strict_types=1);

namespace OCA\LocalBase\Organization;

use OCA\LocalBase\AppInfo\Application;
use OCP\IAppConfig;

/**
 * Zweck: Persistiert die kanonische AD-Organisationsdefinition als gemeinsame App-Einstellung.
 * Zusammenspiel: Administrative Fachapp -> SettingsService -> IAppConfig; alle Verbraucher laden denselben validierten Vertrag.
 * Vertrag: Ungültige gespeicherte Daten fallen sicher auf die geprüften Defaults zurück; Schreibversuche mit ungültigen Daten schlagen fehl.
 */
final class AdOrganizationSettingsService {
    private const KEY = 'ad_organization_definition';

    public function __construct(private IAppConfig $config) {}

    public function definition(): AdOrganizationDefinition {
        $raw = $this->config->getValueString(Application::APP_ID, self::KEY, '');
        if ($raw === '') return AdOrganizationDefinition::defaults();
        try {
            $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
            $definition = AdOrganizationDefinition::get(is_array($data) ? $data : []);
            if ((int)($data['version'] ?? 1) < 2) {
                $this->config->setValueString(Application::APP_ID, self::KEY, json_encode($definition->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            }
            return $definition;
        } catch (\Throwable) {
            return AdOrganizationDefinition::defaults();
        }
    }

    public function save(array $data): AdOrganizationDefinition {
        $definition = AdOrganizationDefinition::get($data);
        $encoded = json_encode($definition->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->config->setValueString(Application::APP_ID, self::KEY, $encoded);
        return $definition;
    }
}
