import { spawnSync } from 'node:child_process';
import { cpSync, mkdtempSync, readdirSync, rmSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, relative } from 'node:path';

export function collectJsFiles(root, directory) {
    const path = join(root, directory);
    const files = [];

    function walk(currentPath) {
        for (const entry of readdirSync(currentPath)) {
            const entryPath = join(currentPath, entry);
            const stats = statSync(entryPath);

            if (stats.isDirectory()) {
                walk(entryPath);
                continue;
            }

            if (stats.isFile() && entryPath.endsWith('.js')) {
                files.push(relative(root, entryPath));
            }
        }
    }

    walk(path);
    files.sort();

    return files;
}

export function runCommand(root, command, args) {
    console.log(`> ${[command, ...args].join(' ')}`);
    const result = spawnSync(command, args, {
        cwd: root,
        stdio: 'inherit',
    });

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}

export function runJavaScriptSuite(options) {
    const sourceDirectories = options.sourceDirectories || ['js'];
    const testFiles = options.testFiles || [];

    for (const directory of sourceDirectories) {
        for (const file of collectJsFiles(options.root, directory)) {
            runCommand(options.root, 'node', ['--check', file]);
        }
    }

    const commonJsTests = testFiles.filter((file) => file.endsWith('.js'));
    const moduleTests = testFiles.filter((file) => !file.endsWith('.js'));
    if (commonJsTests.length > 0) {
        const isolatedRoot = mkdtempSync(join(tmpdir(), 'localbase-js-tests.'));
        try {
            cpSync(join(options.root, 'js'), join(isolatedRoot, 'js'), { recursive: true });
            cpSync(join(options.root, 'tests', 'js'), join(isolatedRoot, 'tests', 'js'), { recursive: true });
            for (const file of commonJsTests) {
                runCommand(isolatedRoot, 'node', [file]);
            }
        } finally {
            rmSync(isolatedRoot, { recursive: true, force: true });
        }
    }

    for (const file of moduleTests) {
        runCommand(options.root, 'node', [file]);
    }

    if (options.successMessage) {
        console.log(options.successMessage);
    }
}
