const assert = require('assert');

const {
    FakeButton,
    FakeClassList,
    FakeElement,
    createElementMap,
    installDocument,
} = require('./helpers/fake-dom.js');

const classList = new FakeClassList(['one']);
classList.add('two');
classList.toggle('one', false);
classList.toggle('three', true);
assert.strictEqual(classList.contains('one'), false);
assert.strictEqual(classList.has('two'), true);
assert.strictEqual(classList.toString(), 'two three');

const input = new FakeElement('input');
input.addEventListener('change', () => 'changed');
input.setAttribute('aria-label', 'Name');
input.focus();
input.select();
input.queryResults.set('.single', new FakeElement('single'));
input.queryAllResults.set('.many', [new FakeElement('many')]);

assert.strictEqual(input.listeners.change(), 'changed');
assert.strictEqual(input.getAttribute('aria-label'), 'Name');
assert.strictEqual(input.getAttribute('missing'), null);
assert.strictEqual(input.focused, true);
assert.strictEqual(input.selected, true);
assert.strictEqual(input.querySelector('.single').id, 'single');
assert.strictEqual(input.querySelectorAll('.many').length, 1);

const actionButton = new FakeButton({ action: 'save' });
const viewButton = new FakeButton({ view: 'list' });
assert.strictEqual(actionButton.closest('button[data-action]'), actionButton);
assert.strictEqual(actionButton.closest('button[data-view]'), null);
assert.strictEqual(viewButton.closest('button[data-view]'), viewButton);

const elements = createElementMap(['root', 'notice']);
const document = installDocument(elements, {
    querySelector(selector) {
        return selector === '.root' ? elements.get('root') : null;
    },
    querySelectorAll(selector) {
        return selector === '.notice' ? [elements.get('notice')] : [];
    },
});

assert.strictEqual(document.getElementById('root'), elements.get('root'));
assert.strictEqual(document.querySelector('.root'), elements.get('root'));
assert.deepStrictEqual(document.querySelectorAll('.notice'), [elements.get('notice')]);
document.addEventListener('click', () => 'clicked');
assert.strictEqual(document.listeners.click(), 'clicked');

console.log('LocalBase fake DOM helper smoke test passed.');
