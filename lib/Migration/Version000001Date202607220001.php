<?php

declare(strict_types=1);

namespace OCA\LocalBase\Migration;

use Closure;
use OCA\LocalBase\BackgroundJob\RefreshHolidayCalendarJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Registriert den gemeinsamen Kalenderjob additiv auch bei bereits installierten LocalBase-Versionen. */
final class Version000001Date202607220001 extends SimpleMigrationStep {
    public function __construct(private IJobList $jobs) {}

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        if (!$this->jobs->has(RefreshHolidayCalendarJob::class, null)) {
            $this->jobs->add(RefreshHolidayCalendarJob::class);
        }
    }
}
