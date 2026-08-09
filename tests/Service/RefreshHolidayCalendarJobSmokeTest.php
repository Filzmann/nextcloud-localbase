<?php

declare(strict_types=1);

namespace OCP\AppFramework\Utility { interface ITimeFactory { public function getTime(): int; } }
namespace OCP\BackgroundJob {
    interface IJob { public const TIME_INSENSITIVE = 1; }
    abstract class TimedJob {
        public int $interval = 0;
        public int $sensitivity = 0;
        public bool $parallel = true;
        public function __construct(protected \OCP\AppFramework\Utility\ITimeFactory $time) {}
        protected function setInterval(int $interval): void { $this->interval = $interval; }
        protected function setTimeSensitivity(int $sensitivity): void { $this->sensitivity = $sensitivity; }
        protected function setAllowParallelRuns(bool $parallel): void { $this->parallel = $parallel; }
        abstract protected function run($argument): void;
    }
}
namespace OCA\LocalBase\Calendar {
    class Context { public function timezone(): \DateTimeZone { return new \DateTimeZone('Europe/Berlin'); } }
    class CalendarContextSettingsService { public function context(): Context { return new Context(); } }
    class HolidayCalendarService {
        public array $calls = [];
        public function forYear(int $year, bool $forceRefresh = false): void { $this->calls[] = [$year, $forceRefresh]; }
    }
}

namespace {

    use OCA\LocalBase\BackgroundJob\RefreshHolidayCalendarJob;
    use OCA\LocalBase\Calendar\CalendarContextSettingsService;
    use OCA\LocalBase\Calendar\HolidayCalendarService;
    use OCP\AppFramework\Utility\ITimeFactory;
    use OCP\BackgroundJob\IJob;

    $clock = new class implements ITimeFactory {
        public function getTime(): int { return strtotime('2026-12-31T23:30:00Z'); }
    };
    $holidays = new HolidayCalendarService();
    $job = new RefreshHolidayCalendarJob($clock, new CalendarContextSettingsService(), $holidays);
    if ($job->interval !== 24 * 3600 || $job->sensitivity !== IJob::TIME_INSENSITIVE || $job->parallel !== false) {
        throw new RuntimeException('Der gemeinsame Kalenderjob besitzt nicht den sicheren Zeitvertrag.');
    }
    $run = new ReflectionMethod($job, 'run');
    $run->invoke($job, null);
    if ($holidays->calls !== [[2027, true], [2028, true], [2029, true]]) {
        throw new RuntimeException('Der gemeinsame Kalenderjob verwendet nicht die Fachzeitzone und zwei Folgejahre.');
    }

    $info = file_get_contents(__DIR__ . '/../../appinfo/info.xml');
    if ($info === false || !str_contains($info, 'OCA\\LocalBase\\BackgroundJob\\RefreshHolidayCalendarJob')) {
        throw new RuntimeException('Der gemeinsame Kalenderjob ist nicht in der App registriert.');
    }
    echo "RefreshHolidayCalendarJobSmokeTest: OK\n";
}
