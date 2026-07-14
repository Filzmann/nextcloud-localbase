<?php

declare(strict_types=1);

namespace OCA\LocalBase\Tests\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Zweck: Führt dependency-arme PHP-Smoke-Tests app-übergreifend in getrennten Prozessen aus.
 * Vertrag: Testdateien teilen keinen PHP-Prozess und können deshalb eigene Fakes ohne Reihenfolgeabhängigkeit definieren.
 */
final class PhpTestRunner {
    /**
     * @param list<string> $lintDirectories
     * @param list<string> $testDirectories
     * @param list<string> $testSuffixes
     */
    public static function run(string $root, array $lintDirectories, array $testDirectories, array $testSuffixes, string $successMessage): void {
        foreach (self::collect($root, $lintDirectories, static fn(string $path): bool => str_ends_with($path, '.php')) as $file) {
            self::execute($root, [PHP_BINARY, '-l', self::relativePath($root, $file)]);
        }

        foreach (self::collect($root, $testDirectories, static fn(string $path): bool => self::hasSuffix($path, $testSuffixes)) as $file) {
            self::execute($root, [PHP_BINARY, self::relativePath($root, $file)]);
        }

        echo $successMessage . PHP_EOL;
    }

    /** @return list<string> */
    public static function collect(string $root, array $directories, callable $accept): array {
        $files = [];
        foreach ($directories as $directory) {
            $path = $root . '/' . trim((string)$directory, '/');
            if (!is_dir($path)) continue;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (str_contains(str_replace('\\', '/', $file->getPathname()), '/vendor/')) continue;
                if ($file->isFile() && $accept($file->getPathname())) $files[] = $file->getPathname();
            }
        }
        sort($files);
        return array_values(array_unique($files));
    }

    private static function hasSuffix(string $path, array $suffixes): bool {
        foreach ($suffixes as $suffix) if (str_ends_with($path, (string)$suffix)) return true;
        return false;
    }

    private static function relativePath(string $root, string $path): string {
        return str_replace(rtrim($root, '/') . '/', '', $path);
    }

    private static function execute(string $root, array $command): void {
        $command = self::withOptionalCoverage($root, $command);
        $display = implode(' ', array_map('escapeshellarg', $command));
        echo '> ' . $display . PHP_EOL;
        $previousDirectory = getcwd();
        if (!chdir($root)) throw new \RuntimeException("Testwurzel ist nicht erreichbar: {$root}");
        try {
            passthru($display, $exitCode);
        } finally {
            if ($previousDirectory !== false) chdir($previousDirectory);
        }
        if ($exitCode !== 0) exit($exitCode);
    }

    /**
     * Zweck: Misst einzelne isolierte Testprozesse, ohne den normalen schnellen Testlauf mit einer Abhängigkeit zu belasten.
     * Vertrag: Lint-Prozesse bleiben unverändert; Coverage wird nur bei vollständig gesetzter Testumgebung aktiviert.
     *
     * @param list<string> $command
     * @return list<string>
     */
    public static function withOptionalCoverage(string $root, array $command): array {
        $tool = trim((string)getenv('PHP_COVERAGE_COMMAND'));
        $outputDirectory = trim((string)getenv('PHP_COVERAGE_OUTPUT_DIR'));
        if ($tool === '' || $outputDirectory === '' || ($command[0] ?? '') !== PHP_BINARY || ($command[1] ?? '') === '-l') {
            return $command;
        }
        if (!is_file($tool) || !is_executable($tool)) throw new \RuntimeException("Coverage-Werkzeug ist nicht ausführbar: {$tool}");
        if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
            throw new \RuntimeException("Coverage-Ausgabe konnte nicht angelegt werden: {$outputDirectory}");
        }

        $script = (string)($command[1] ?? 'test');
        $report = rtrim($outputDirectory, '/') . '/' . hash('sha256', $root . '/' . $script) . '.xml';
        return [
            $tool,
            'execute',
            '--clover',
            $report,
            '--include',
            $root . '/lib',
            '--add-uncovered',
            $script,
        ];
    }
}
