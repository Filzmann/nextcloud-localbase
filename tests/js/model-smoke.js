const assert = require('assert');

global.window = {};

require('../../js/models/model.js');

const { Model } = window.LocalBase.models;

class TestModel extends Model {
    constructor(data = {}) {
        super();
        this.data = data;
    }

    toArray() {
        return this.data;
    }
}

assert.strictEqual(TestModel.get(null), null);
assert.strictEqual(TestModel.get(undefined), null);

const existing = new TestModel({ id: 1 });
assert.strictEqual(TestModel.get(existing), existing);
assert.deepStrictEqual(TestModel.get({ id: 2 }).toArray(), { id: 2 });

const all = TestModel.get_all([
    { id: 3 },
    null,
    existing,
]);
assert.strictEqual(all.length, 2);
assert.deepStrictEqual(all.map(item => item.toArray()), [{ id: 3 }, { id: 1 }]);
assert.deepStrictEqual(TestModel.get_all('invalid'), []);
assert.deepStrictEqual(existing.to_array(), { id: 1 });

assert.throws(
    () => new Model().toArray(),
    /muss toArray implementieren/
);
assert.throws(
    () => existing.save(),
    /kann nicht direkt gespeichert werden/
);

console.log('LocalBase model smoke test passed.');
