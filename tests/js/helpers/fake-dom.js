class FakeClassList {
    constructor(initialValues = []) {
        this.values = new Set(initialValues);
    }

    add(name) {
        this.values.add(name);
    }

    remove(name) {
        this.values.delete(name);
    }

    toggle(name, enabled) {
        if (enabled) {
            this.add(name);
        } else {
            this.remove(name);
        }
    }

    contains(name) {
        return this.values.has(name);
    }

    has(name) {
        return this.contains(name);
    }

    toString() {
        return Array.from(this.values).join(' ');
    }
}

class FakeElement {
    constructor(id = '') {
        this.id = id;
        this.value = '';
        this.checked = false;
        this.hidden = false;
        this.disabled = false;
        this.innerHTML = '';
        this.textContent = '';
        this.className = '';
        this.scrollTop = 0;
        this.dataset = {};
        this.listeners = {};
        this.attributes = {};
        this.classList = new FakeClassList();
        this.focused = false;
        this.selected = false;
        this.queryResults = new Map();
        this.queryAllResults = new Map();
    }

    addEventListener(type, listener) {
        this.listeners[type] = listener;
    }

    setAttribute(name, value) {
        this.attributes[name] = String(value);
    }

    getAttribute(name) {
        return Object.prototype.hasOwnProperty.call(this.attributes, name)
            ? this.attributes[name]
            : null;
    }

    focus() {
        this.focused = true;
    }

    select() {
        this.selected = true;
    }

    closest() {
        return null;
    }

    querySelector(selector) {
        return this.queryResults.get(selector) || null;
    }

    querySelectorAll(selector) {
        return this.queryAllResults.get(selector) || [];
    }
}

class FakeButton extends FakeElement {
    constructor(dataset = {}, id = '') {
        super(id);
        this.dataset = { ...dataset };
    }

    closest(selector) {
        if (selector === 'button[data-action]' && this.dataset.action) {
            return this;
        }

        if (selector === 'button[data-view]' && this.dataset.view) {
            return this;
        }

        return super.closest(selector);
    }
}

function createElementMap(ids) {
    return new Map(ids.map(id => [id, new FakeElement(id)]));
}

function installDocument(elements, options = {}) {
    const listeners = {};
    const documentElement = options.documentElement || new FakeElement('document-element');
    const body = options.body || new FakeElement('body');

    global.document = {
        documentElement,
        body,
        listeners,
        getElementById(id) {
            return elements.get(id) || null;
        },
        querySelector(selector) {
            if (options.querySelector) {
                return options.querySelector(selector);
            }

            return null;
        },
        querySelectorAll(selector) {
            if (options.querySelectorAll) {
                return options.querySelectorAll(selector);
            }

            return [];
        },
        addEventListener(type, listener) {
            listeners[type] = listener;
        },
    };

    return global.document;
}

module.exports = {
    FakeButton,
    FakeClassList,
    FakeElement,
    createElementMap,
    installDocument,
};
