const assert = require('assert');

const elements = new Map([
    ['root', { id: 'root' }]
]);

global.window = {};
global.document = {
    getElementById(id) {
        return elements.get(id) || null;
    }
};

require('../../js/ui/ui.js');

assert.strictEqual(window.LocalBase.ui.byId('root'), elements.get('root'));
assert.strictEqual(window.LocalBase.ui.byId('missing'), null);
assert.strictEqual(
    window.LocalBase.ui.esc('<strong title="x">Tom & Jerry\'s</strong>'),
    '&lt;strong title=&quot;x&quot;&gt;Tom &amp; Jerry&#039;s&lt;/strong&gt;'
);

console.log('LocalBase UI smoke test passed.');
