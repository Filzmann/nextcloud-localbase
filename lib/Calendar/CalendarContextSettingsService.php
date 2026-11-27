<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use OCA\LocalBase\AppInfo\AppId;
use OCP\IAppConfig;
use OCP\IConfig;

/**
 * Zweck: Persistiert den gemeinsamen Kalenderkontext in der LocalBase-AppConfig.
 * Vertrag: Ungültige Alt- oder Fremdwerte fallen lesend auf den Bestandsdefault zurück; nur validierte Werte werden geschrieben.
 * Die fachliche Zeitzone folgt immer der Instanz-Zeitzone aus den Nextcloud-Systemeinstellungen (config.php 'timezone', Fallback UTC).
 */
final class CalendarContextSettingsService {
    private const KEY = 'calendar_context';

    public function __construct(private IAppConfig $config, private IConfig $systemConfig) {
    }

    public function context(): CalendarContext {
        $timezone = $this->instanceTimezone();
        $raw = $this->config->getValueString(AppId::VALUE, self::KEY, '');
        if ($raw === '') return CalendarContext::defaults($timezone);
        try {
            $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
            return CalendarContext::get([...(is_array($data) ? $data : []), 'timezone' => $timezone]);
        } catch (\Throwable) {
            return CalendarContext::defaults($timezone);
        }
    }

    public function save(array $data): CalendarContext {
        $context = CalendarContext::get([...$data, 'timezone' => $this->instanceTimezone()]);
        $this->config->setValueString(
            AppId::VALUE,
            self::KEY,
            json_encode($context->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
        return $context;
    }

    /** Instanz-Zeitzone aus den Nextcloud-Systemeinstellungen (config.php 'timezone'); ungültig oder leer fällt auf UTC zurück. */
    private function instanceTimezone(): string {
        $tz = trim($this->systemConfig->getSystemValueString('timezone', 'UTC'));
        return $tz === 'UTC' || in_array($tz, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true) ? $tz : 'UTC';
    }
}
