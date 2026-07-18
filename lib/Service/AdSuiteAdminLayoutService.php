<?php

declare(strict_types=1);

namespace OCA\LocalBase\Service;

use InvalidArgumentException;
use OCA\LocalBase\AppInfo\Application;
use OCP\Config\IUserConfig;
use Psr\Log\LoggerInterface;

/**
 * Zweck: Speichert die rein persönliche Anordnung der gemeinsamen AD-Adminblöcke je Nextcloud-Konto.
 * Zusammenspiel: AdSuiteAdminApiController -> IUserConfig; fachliche Organisations- und Rechtewerte bleiben unberührt.
 * Vertrag: Ausschließlich bekannte Scopes und Block-IDs werden angenommen; neue Standardblöcke werden vorhandenen Layouts angehängt.
 */
final class AdSuiteAdminLayoutService {
    private const CONFIG_KEY = 'ad_suite_admin_dashboard_layout';
    private const VERSION = 1;
    private const BLOCKS = [
        'main' => ['directory', 'organization', 'permissions'],
        'organization' => ['general', 'hierarchy', 'role-order', 'areas', 'vacation-views'],
        'permissions' => ['calendar-permissions', 'vacation-permissions'],
    ];

    public function __construct(
        private IUserConfig $config,
        private LoggerInterface $logger,
    ) {
    }

    public function layout(string $userId): array {
        try {
            $stored = $this->config->getValueArray($userId, Application::APP_ID, self::CONFIG_KEY, [], true);
            return $stored === [] ? $this->defaultLayout() : $this->normalize($stored);
        } catch (\Throwable $error) {
            $this->logger->warning('Persönliches AD-Adminlayout ist ungültig; Standardlayout wird verwendet.', ['exception' => $error]);
            return $this->defaultLayout();
        }
    }

    public function save(string $userId, array $layout): array {
        $normalized = $this->normalize($layout);
        $this->config->setValueArray($userId, Application::APP_ID, self::CONFIG_KEY, $normalized, true);
        return $normalized;
    }

    private function defaultLayout(): array {
        return [
            'version' => self::VERSION,
            'scopes' => array_map(static fn(array $order): array => ['order' => $order, 'collapsed' => []], self::BLOCKS),
        ];
    }

    private function normalize(array $layout): array {
        if (($layout['version'] ?? self::VERSION) !== self::VERSION || !isset($layout['scopes']) || !is_array($layout['scopes'])) {
            throw new InvalidArgumentException('Das persönliche Adminlayout ist ungültig.');
        }
        if (array_diff(array_keys($layout), ['version', 'scopes']) !== []) throw new InvalidArgumentException('Das persönliche Adminlayout enthält unbekannte Felder.');
        if (array_diff(array_keys($layout['scopes']), array_keys(self::BLOCKS)) !== []) throw new InvalidArgumentException('Das persönliche Adminlayout enthält einen unbekannten Bereich.');

        $scopes = [];
        foreach (self::BLOCKS as $scope => $allowed) {
            $candidate = $layout['scopes'][$scope] ?? ['order' => [], 'collapsed' => []];
            if (!is_array($candidate) || array_diff(array_keys($candidate), ['order', 'collapsed']) !== []) throw new InvalidArgumentException('Das persönliche Adminlayout enthält ungültige Bereichsdaten.');
            $order = $this->validatedList($candidate['order'] ?? [], $allowed);
            $collapsed = $this->validatedList($candidate['collapsed'] ?? [], $allowed);
            $scopes[$scope] = [
                'order' => array_merge($order, array_values(array_diff($allowed, $order))),
                'collapsed' => $collapsed,
            ];
        }
        return ['version' => self::VERSION, 'scopes' => $scopes];
    }

    private function validatedList(mixed $value, array $allowed): array {
        if (!is_array($value) || !array_is_list($value)) throw new InvalidArgumentException('Das persönliche Adminlayout enthält keine gültige Blockliste.');
        $validated = [];
        foreach ($value as $blockId) {
            if (!is_string($blockId) || !in_array($blockId, $allowed, true) || in_array($blockId, $validated, true)) {
                throw new InvalidArgumentException('Das persönliche Adminlayout enthält einen unbekannten oder doppelten Block.');
            }
            $validated[] = $blockId;
        }
        return $validated;
    }
}
