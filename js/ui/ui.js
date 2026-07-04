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

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.ui = window.LocalBase.ui || {};
    window.LocalBase.ui.byId = byId;
    window.LocalBase.ui.esc = esc;
})();
