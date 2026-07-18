<?php

declare(strict_types=1);

namespace OCP\Config {
    interface IUserConfig {
        public function getValueArray(string $userId, string $app, string $key, array $default = [], bool $lazy = false): array;
        public function setValueArray(string $userId, string $app, string $key, array $value, bool $lazy = false, int $flags = 0): bool;
    }
}

namespace OCA\LocalBase\AppInfo { final class Application { public const APP_ID = 'localbase'; } }
namespace Psr\Log { interface LoggerInterface { public function warning(string $message, array $context = []): void; } }

namespace {
    require_once __DIR__ . '/../../lib/Service/AdSuiteAdminLayoutService.php';

    use OCA\LocalBase\Service\AdSuiteAdminLayoutService;
    use OCP\Config\IUserConfig;
    use Psr\Log\LoggerInterface;

    $config = new class implements IUserConfig {
        public array $values = [];
        public function getValueArray(string $userId, string $app, string $key, array $default = [], bool $lazy = false): array {
            return $this->values[$userId][$app][$key] ?? $default;
        }
        public function setValueArray(string $userId, string $app, string $key, array $value, bool $lazy = false, int $flags = 0): bool {
            $this->values[$userId][$app][$key] = $value;
            return true;
        }
    };
    $logger = new class implements LoggerInterface { public array $warnings = []; public function warning(string $message, array $context = []): void { $this->warnings[] = [$message, $context]; } };
    $service = new AdSuiteAdminLayoutService($config, $logger);
    $default = $service->layout('admin-a');
    if (($default['version'] ?? null) !== 1) throw new RuntimeException('Persönliches Adminlayout besitzt keine Vertragsversion.');
    if (($default['scopes']['main']['order'] ?? []) !== ['directory', 'organization', 'permissions']) throw new RuntimeException('Hauptblöcke fehlen im Standardlayout.');
    if (($default['scopes']['organization']['order'] ?? []) !== ['general', 'hierarchy', 'role-order', 'areas', 'vacation-views']) throw new RuntimeException('Organisationsblöcke fehlen im Standardlayout.');
    if (($default['scopes']['permissions']['order'] ?? []) !== ['calendar-permissions', 'vacation-permissions']) throw new RuntimeException('Rechteblöcke fehlen im Standardlayout.');

    $saved = $service->save('admin-a', [
        'version' => 1,
        'scopes' => [
            'main' => ['order' => ['permissions', 'directory', 'organization'], 'collapsed' => ['directory']],
            'organization' => ['order' => ['hierarchy', 'general'], 'collapsed' => ['general']],
            'permissions' => ['order' => ['vacation-permissions'], 'collapsed' => []],
        ],
    ]);
    if (($saved['scopes']['main']['order'][0] ?? '') !== 'permissions' || ($saved['scopes']['main']['collapsed'] ?? []) !== ['directory']) throw new RuntimeException('Persönliche Hauptansicht wird nicht gespeichert.');
    if (($saved['scopes']['organization']['order'] ?? []) !== ['hierarchy', 'general', 'role-order', 'areas', 'vacation-views']) throw new RuntimeException('Neue oder ausgelassene Blöcke werden nicht sicher ergänzt.');
    if ($service->layout('admin-b') !== $default) throw new RuntimeException('Layouts verschiedener Admins sind nicht getrennt.');
    $config->values['admin-c']['localbase']['ad_suite_admin_dashboard_layout'] = ['scopes' => ['main' => ['order' => ['unknown'], 'collapsed' => []]]];
    if ($service->layout('admin-c') !== $default || $logger->warnings === []) throw new RuntimeException('Ungültiges gespeichertes Layout fällt nicht protokolliert auf den Standard zurück.');

    foreach ([
        ['scopes' => ['unknown' => ['order' => [], 'collapsed' => []]]],
        ['scopes' => ['main' => ['order' => ['unknown'], 'collapsed' => []]]],
        ['scopes' => ['main' => ['order' => ['directory', 'directory'], 'collapsed' => []]]],
        ['scopes' => ['main' => ['order' => [], 'collapsed' => 'directory']]],
    ] as $invalid) {
        try {
            $service->save('admin-a', $invalid);
            throw new RuntimeException('Ungültiges persönliches Adminlayout wurde gespeichert.');
        } catch (InvalidArgumentException) {
        }
    }

    echo "AdSuiteAdminLayoutServiceSmokeTest: OK\n";
}
