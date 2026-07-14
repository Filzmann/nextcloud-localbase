<?php

declare(strict_types=1);

require_once __DIR__ . '/PhpTestRunner.php';

use OCA\LocalBase\Tests\Support\PhpTestRunner;

$root = dirname(__DIR__, 2);
$originalCoverageCommand = getenv('PHP_COVERAGE_COMMAND');
$originalCoverageOutputDirectory = getenv('PHP_COVERAGE_OUTPUT_DIR');
putenv('PHP_COVERAGE_COMMAND');
putenv('PHP_COVERAGE_OUTPUT_DIR');
$files = PhpTestRunner::collect($root, ['tests/Support'], static fn(string $path): bool => str_ends_with($path, 'SmokeTest.php'));
$names = array_map('basename', $files);
if (!in_array('PhpTestRunnerSmokeTest.php', $names, true) || !in_array('AssertionsSmokeTest.php', $names, true)) {
    throw new RuntimeException('PHP-Test-Runner sammelt rekursive Smoke-Tests nicht deterministisch ein.');
}
if ($names !== array_values(array_unique($names))) throw new RuntimeException('PHP-Test-Runner liefert Testdateien mehrfach.');

$vendorFixture = $root . '/tests/.runner-smoke/vendor/example';
$projectFixture = $root . '/tests/.runner-smoke/project';
mkdir($vendorFixture, 0775, true);
mkdir($projectFixture, 0775, true);
file_put_contents($vendorFixture . '/Ignored.php', '<?php');
file_put_contents($projectFixture . '/Included.php', '<?php');
$collected = PhpTestRunner::collect($root, ['tests/.runner-smoke'], static fn(string $path): bool => str_ends_with($path, '.php'));
if ($collected !== [$projectFixture . '/Included.php']) {
    throw new RuntimeException('PHP-Test-Runner darf Fremdabhängigkeiten unter vendor nicht sammeln.');
}

$normalCommand = [PHP_BINARY, 'tests/example.php'];
if (PhpTestRunner::withOptionalCoverage($root, $normalCommand) !== $normalCommand) {
    throw new RuntimeException('Coverage darf ohne explizite Umgebung nicht aktiv sein.');
}
$temporaryDirectory = sys_get_temp_dir() . '/localbase-coverage-' . getmypid();
$fakeTool = $temporaryDirectory . '/phpcov';
if (!mkdir($temporaryDirectory, 0775, true) || file_put_contents($fakeTool, "#!/bin/sh\n") === false || !chmod($fakeTool, 0755)) {
    throw new RuntimeException('Coverage-Testumgebung konnte nicht angelegt werden.');
}
putenv("PHP_COVERAGE_COMMAND={$fakeTool}");
putenv("PHP_COVERAGE_OUTPUT_DIR={$temporaryDirectory}/reports");
$coverageCommand = PhpTestRunner::withOptionalCoverage($root, $normalCommand);
putenv('PHP_COVERAGE_COMMAND');
putenv('PHP_COVERAGE_OUTPUT_DIR');
if (($coverageCommand[0] ?? '') !== $fakeTool || !in_array('--clover', $coverageCommand, true) || !in_array($root . '/lib', $coverageCommand, true)) {
    throw new RuntimeException('PHP-Test-Runner reicht Test und Quellfilter nicht korrekt an PHPCOV weiter.');
}
unlink($fakeTool);
rmdir($temporaryDirectory . '/reports');
rmdir($temporaryDirectory);
unlink($vendorFixture . '/Ignored.php');
unlink($projectFixture . '/Included.php');
rmdir($vendorFixture);
rmdir(dirname($vendorFixture));
rmdir($projectFixture);
rmdir(dirname($projectFixture));
if ($originalCoverageCommand !== false) putenv('PHP_COVERAGE_COMMAND=' . $originalCoverageCommand);
if ($originalCoverageOutputDirectory !== false) putenv('PHP_COVERAGE_OUTPUT_DIR=' . $originalCoverageOutputDirectory);

echo "LocalBase PHP runner smoke test passed\n";
