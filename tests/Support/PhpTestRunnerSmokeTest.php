<?php

declare(strict_types=1);

require_once __DIR__ . '/PhpTestRunner.php';

use OCA\LocalBase\Tests\Support\PhpTestRunner;

$root = dirname(__DIR__, 2);
$files = PhpTestRunner::collect($root, ['tests/Support'], static fn(string $path): bool => str_ends_with($path, 'SmokeTest.php'));
$names = array_map('basename', $files);
if (!in_array('PhpTestRunnerSmokeTest.php', $names, true) || !in_array('AssertionsSmokeTest.php', $names, true)) {
    throw new RuntimeException('PHP-Test-Runner sammelt rekursive Smoke-Tests nicht deterministisch ein.');
}
if ($names !== array_values(array_unique($names))) throw new RuntimeException('PHP-Test-Runner liefert Testdateien mehrfach.');

echo "LocalBase PHP runner smoke test passed\n";
