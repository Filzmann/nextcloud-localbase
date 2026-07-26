<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use OCA\LocalBase\AppInfo\Application;
use OCP\IAppConfig;

/**
 * Zweck: Persistiert den gemeinsamen Kalenderkontext in der LocalBase-AppConfig.
 * Vertrag: Ungültige Alt- oder Fremdwerte fallen lesend auf den Berlin-Bestandsdefault zurück; nur validierte Werte werden geschrieben.
 */
final class CalendarContextSettingsService {
    private const KEY = 'calendar_context';

    public function __construct(private IAppConfig $config) {
    }

    public function context(): CalendarContext {
        $raw = $this->config->getValueString(Application::APP_ID, self::KEY, '');
        if ($raw === '') return CalendarContext::defaults();
        try {
            $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
            return CalendarContext::get(is_array($data) ? $data : []);
        } catch (\Throwable) {
            return CalendarContext::defaults();
        }
    }

    public function save(array $data): CalendarContext {
        $context = CalendarContext::get($data);
        $this->config->setValueString(
            Application::APP_ID,
            self::KEY,
            json_encode($context->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
        return $context;
    }
}
