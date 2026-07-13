<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/PhpTestRunner.php';

use OCA\LocalBase\Tests\Support\PhpTestRunner;

PhpTestRunner::run(
    root: dirname(__DIR__),
    lintDirectories: ['appinfo', 'lib', 'tests'],
    testDirectories: ['tests/Controller', 'tests/Model', 'tests/Service', 'tests/Support'],
    testSuffixes: ['Test.php'],
    successMessage: 'LocalBase PHP tests passed',
);
