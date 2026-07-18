import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runJavaScriptSuite } from './Support/js-runner.mjs';

const root = dirname(dirname(fileURLToPath(import.meta.url)));

runJavaScriptSuite({
    root,
    testFiles: [
        'tests/js/api-client-smoke.js',
        'tests/js/fake-dom-smoke.js',
        'tests/js/model-smoke.js',
        'tests/js/organization-admin-smoke.mjs',
        'tests/js/organization-exporter-smoke.mjs',
        'tests/js/repository-smoke.js',
        'tests/js/ui-smoke.js',
    ],
    successMessage: 'LocalBase JavaScript tests passed',
});
