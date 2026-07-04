const assert = require('assert');

const calls = [];

global.window = {};

require('../../js/repositories/repository.js');

const api = {
    request(path, options = {}) {
        calls.push({ path, options });

        return { path, options };
    },
    encode(value) {
        return 'x' + encodeURIComponent(String(value));
    }
};

const { Repository } = window.LocalBase.repositories;
const repository = new Repository(api);

assert.deepStrictEqual(repository.request('/api/state'), { path: '/api/state', options: {} });
assert.deepStrictEqual(repository.post('/api/save', { ok: true }), {
    path: '/api/save',
    options: { method: 'POST', body: '{"ok":true}' }
});
assert.deepStrictEqual(repository.post('/api/empty'), {
    path: '/api/empty',
    options: { method: 'POST' }
});
assert.strictEqual(repository.encode('A B'), 'xA%20B');

const functionRepository = new Repository((path, options = {}) => ({ path, options }));
assert.deepStrictEqual(functionRepository.post('/api/function', { value: 1 }), {
    path: '/api/function',
    options: { method: 'POST', body: '{"value":1}' }
});
assert.strictEqual(functionRepository.encode('A B'), 'A%20B');

console.log('LocalBase repository smoke test passed.');
