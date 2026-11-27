(function() {
    const translate = typeof t === 'function' ? t : (_app, text, params) => Object.entries(params || {}).reduce((s, [k, v]) => s.split('{' + k + '}').join(String(v)), text);
    class Model {
        static get(data) {
            if (data == null) {
                return null;
            }

            return data instanceof this ? data : new this(data || {});
        }

        static get_all(items = []) {
            if (!Array.isArray(items)) {
                return [];
            }

            return items.map(item => this.get(item)).filter(Boolean);
        }

        toArray() {
            throw new Error(`${this.constructor.name} ${translate('localbase', 'muss toArray implementieren.')}`);
        }

        to_array() {
            return this.toArray();
        }

        save() {
            throw new Error(`${this.constructor.name} ${translate('localbase', 'kann nicht direkt gespeichert werden.')}`);
        }
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.models = window.LocalBase.models || {};
    window.LocalBase.models.Model = Model;
})();
