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

    const defaultSeen = {};
    global.OC = {
        requestToken: 'default-token',
        generateUrl(path) {
            return `/oc${path}`;
        }
    };
    global.window.fetch = async (url, options) => {
        defaultSeen.url = url;
        defaultSeen.options = options;

        return {
            ok: true,
            status: 200,
            text: async () => JSON.stringify({ defaultClient: true })
        };
    };

    const defaultClient = new ApiClient({ appId: 'demo' });
    assert.deepStrictEqual(await defaultClient.request('/api/default'), { defaultClient: true });
    assert.strictEqual(defaultSeen.url, '/oc/apps/demo/api/default');
    assert.strictEqual(defaultSeen.options.headers.requesttoken, 'default-token');

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

    const emptyResponseClient = new ApiClient({
        basePath: '/custom/base',
        generateUrl: (path) => path,
        requestToken: () => 'token-456',
        fetcher: async (url) => {
            assert.strictEqual(url, '/custom/base/api/empty');

            return {
                ok: true,
                status: 204,
                text: async () => ''
            };
        }
    });
    assert.deepStrictEqual(await emptyResponseClient.request('/api/empty'), {});

    const rawResponseClient = new ApiClient({
        appId: 'demo',
        generateUrl: (path) => path,
        requestToken: () => 'token-789',
        fetcher: async () => ({
            ok: true,
            status: 200,
            text: async () => '<html>Not JSON</html>'
        })
    });
    assert.deepStrictEqual(
        await rawResponseClient.request('/api/raw'),
        { raw: '<html>Not JSON</html>' }
    );

    const defaultErrorClient = new ApiClient({
        appId: 'demo',
        generateUrl: (path) => path,
        requestToken: () => 'token-999',
        fetcher: async () => ({
            ok: false,
            status: 500,
            text: async () => ''
        })
    });
    await assert.rejects(
        () => defaultErrorClient.request('/api/fail-empty'),
        (error) => error.message === 'HTTP 500'
            && error.status === 500
            && Object.keys(error.data).length === 0
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
