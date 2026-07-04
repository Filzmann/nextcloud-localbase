(function() {
    function byId(id) {
        return document.getElementById(id);
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function textOrNull(value) {
        const text = String(value || '').trim();

        return text === '' ? null : text;
    }

    function errorMessage(error, fallback = 'Die Aktion konnte nicht ausgefuehrt werden.') {
        if (typeof error === 'string') {
            return textOrNull(error) || fallback;
        }

        if (error && error.data) {
            const dataMessage = textOrNull(error.data.message);
            if (dataMessage) {
                return dataMessage;
            }
        }

        if (error) {
            const message = textOrNull(error.message);
            if (message) {
                return message;
            }
        }

        return fallback;
    }

    class Notice {
        constructor(target, options = {}) {
            this.target = target;
            this.baseClass = options.baseClass || '';
            this.typeClassPrefix = options.typeClassPrefix || '';
        }

        element() {
            return typeof this.target === 'string' ? byId(this.target) : this.target;
        }

        show(message, type = 'info') {
            const element = this.element();
            if (!element) {
                return;
            }

            const text = String(message || '');
            element.textContent = text;
            element.hidden = text === '';

            if (this.baseClass !== '') {
                element.className = this.typeClassPrefix && text !== ''
                    ? this.baseClass + ' ' + this.typeClassPrefix + type
                    : this.baseClass;
            }
        }

        clear() {
            this.show('');
        }
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.ui = window.LocalBase.ui || {};
    window.LocalBase.ui.Notice = Notice;
    window.LocalBase.ui.byId = byId;
    window.LocalBase.ui.errorMessage = errorMessage;
    window.LocalBase.ui.esc = esc;
})();
