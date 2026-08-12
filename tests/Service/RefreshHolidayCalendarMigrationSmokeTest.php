<?php

declare(strict_types=1);

namespace OCP\BackgroundJob {
    interface IJobList {
        public function has(string $job, $argument): bool;
        public function add(string $job, $argument = null): void;
    }
}

namespace OCP\Migration {
    interface IOutput {}
    abstract class SimpleMigrationStep {}
}

namespace {

    use OCA\LocalBase\BackgroundJob\RefreshHolidayCalendarJob;
    use OCA\LocalBase\Migration\Version000001Date202607220001;
    use OCP\BackgroundJob\IJobList;
    use OCP\Migration\IOutput;

    $jobs = new class implements IJobList {
        public array $entries = [];
        public function has(string $job, $argument): bool { return isset($this->entries[$job]); }
        public function add(string $job, $argument = null): void { $this->entries[$job] = $argument; }
    };
    $migration = new Version000001Date202607220001($jobs);
    $migration->postSchemaChange(new class implements IOutput {}, static fn() => null, []);
    $migration->postSchemaChange(new class implements IOutput {}, static fn() => null, []);
    if (array_keys($jobs->entries) !== [RefreshHolidayCalendarJob::class]) {
        throw new RuntimeException('Der gemeinsame Kalenderjob wird bei Updates nicht idempotent registriert.');
    }

    echo "RefreshHolidayCalendarMigrationSmokeTest: OK\n";
}
