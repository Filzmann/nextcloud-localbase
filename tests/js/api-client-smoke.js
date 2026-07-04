const assert = require('assert');

global.window = {
    LocalBase: {},
    fetch: () => Promise.reject(new Error('unexpected fetch call'))
};

require('../../js/api/api-client.js');

const { ApiClient } = global.window.LocalBase.api;

async function run() {
    const seen = {};
    const client = new ApiClient({
        appId: 'demo',
        generateUrl: (path) => `/generated${path}`,
        requestToken: () => 'token-123',
        fetcher: async (url, options) => {
            seen.url = url;
            seen.options = options;

            return {
                ok: true,
                status: 200,
                text: async () => JSON.stringify({ ok: true, value: 42 })
            };
        }
    });

    const data = await client.request('/api/state', { method: 'POST', headers: { 'X-Test': '1' } });
    assert.deepStrictEqual(data, { ok: true, value: 42 });
    assert.strictEqual(seen.url, '/generated/apps/demo/api/state');
    assert.strictEqual(seen.options.headers.requesttoken, 'token-123');
    assert.strictEqual(seen.options.headers['Content-Type'], 'application/json');
    assert.strictEqual(seen.options.headers['X-Test'], '1');
    assert.strictEqual(client.encode('a b'), 'a%20b');

    const failingClient = new ApiClient({
        appId: 'demo',
        generateUrl: (path) => path,
        requestToken: () => 'token-123',
        errorMessage: (body, status) => body.message || `Status ${status}`,
        fetcher: async () => ({
            ok: false,
            status: 422,
            text: async () => JSON.stringify({ message: 'Nicht gut' })
        })
    });

    await assert.rejects(
        () => failingClient.request('/api/fail'),
        (error) => error.message === 'Nicht gut'
            && error.status === 422
            && error.data.message === 'Nicht gut'
    );
}

run()
    .then(() => {
        console.log('LocalBase ApiClient smoke tests passed');
    })
    .catch((error) => {
        console.error(error);
        process.exit(1);
    });
