<?php

declare(strict_types=1);

namespace OCA\LocalBase\Calendar;

use OCA\LocalBase\AppInfo\Application;
use OCP\IAppConfig;

/** Speichert kleine normalisierte Jahresstände regionsgebunden in der LocalBase-AppConfig-Datenbank. */
final class HolidayCalendarCacheStore {
    public function __construct(private IAppConfig $config) {}

    public function get(int $year, CalendarContext $context): ?HolidayCalendar {
        $raw = $this->config->getValueString(Application::APP_ID, $this->key($year, $context), '');
        if ($raw === '') return null;
        try {
            $data = json_decode($raw, true, 128, JSON_THROW_ON_ERROR);
            $calendar = HolidayCalendar::get(is_array($data) ? $data : []);
            return $calendar->year() === $year && $calendar->context()->toArray() === $context->toArray() ? $calendar : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function save(HolidayCalendar $calendar): void {
        $this->config->setValueString(
            Application::APP_ID,
            $this->key($calendar->year(), $calendar->context()),
            json_encode($calendar->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }

    private function key(int $year, CalendarContext $context): string {
        $region = $context->countryCode() . '|' . $context->subdivisionCode();
        return 'holiday_calendar_' . substr(hash('sha256', $region), 0, 20) . '_' . $year;
    }
}
