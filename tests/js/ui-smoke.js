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
assert.strictEqual(
    window.LocalBase.ui.errorMessage(new Error('Kaputt'), 'Fallback'),
    'Kaputt'
);
assert.strictEqual(
    window.LocalBase.ui.errorMessage({ data: { message: 'API kaputt' }, message: 'HTTP 400' }, 'Fallback'),
    'API kaputt'
);
assert.strictEqual(
    window.LocalBase.ui.errorMessage('', 'Fallback'),
    'Fallback'
);
assert.strictEqual(
    window.LocalBase.ui.errorMessage(null, 'Fallback'),
    'Fallback'
);

const noticeElement = { textContent: '', hidden: true, className: 'notice' };
elements.set('notice', noticeElement);

const typedNotice = new window.LocalBase.ui.Notice('notice', {
    baseClass: 'notice',
    typeClassPrefix: 'notice-'
});

typedNotice.show('Gespeichert', 'success');
assert.strictEqual(noticeElement.textContent, 'Gespeichert');
assert.strictEqual(noticeElement.hidden, false);
assert.strictEqual(noticeElement.className, 'notice notice-success');

typedNotice.clear();
assert.strictEqual(noticeElement.textContent, '');
assert.strictEqual(noticeElement.hidden, true);
assert.strictEqual(noticeElement.className, 'notice');

console.log('LocalBase UI smoke test passed.');
