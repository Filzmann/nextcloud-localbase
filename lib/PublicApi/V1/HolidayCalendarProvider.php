<?php

declare(strict_types=1);

namespace OCA\LocalBase\PublicApi\V1;

use OCA\LocalBase\Calendar\HolidayCalendarService;

/** Öffentlicher read-only V1-Einstieg; Cache und externe Quelle bleiben vollständig LocalBase-owned. */
final class HolidayCalendarProvider {
    public function __construct(private HolidayCalendarService $calendars) {}

    public function calendar(int $year): HolidayCalendarSnapshot {
        return HolidayCalendarSnapshot::fromCalendar($this->calendars->forYear($year));
    }
}
