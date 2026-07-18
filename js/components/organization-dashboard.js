(function() {
    'use strict';

    const clone = value => JSON.parse(JSON.stringify(value));

    /**
     * Zweck: Ordnet und klappt die persönlichen Blöcke der AD-Administration zugänglich ein und aus.
     * Zusammenspiel: organization-admin.js -> OrganizationDashboard -> persönlicher LocalBase-Layout-Endpunkt.
     * Vertrag: Verschoben werden nur direkte Dashboardkinder innerhalb desselben Scopes; fachliche Reihenfolgen bleiben unberührt.
     */
    class OrganizationDashboard {
        constructor({ root, onChange = () => {} }) {
            this.root = root;
            this.onChange = onChange;
            this.layout = { version: 1, scopes: {} };
            this.drag = null;
            root.addEventListener('click', event => this.onClick(event));
            root.addEventListener('dragstart', event => this.startDrag(event));
            root.addEventListener('dragover', event => this.overDrag(event));
            root.addEventListener('dragleave', event => this.leaveDrag(event));
            root.addEventListener('drop', event => this.dropDrag(event));
            root.addEventListener('dragend', () => this.clearDrag());
            root.addEventListener('invalid', event => this.revealInvalid(event.target), true);
        }

        set(layout) {
            this.layout = clone(layout || { version: 1, scopes: {} });
            this.applyLayout();
        }

        onClick(event) {
            const toggle = event.target instanceof Element ? event.target.closest('button[data-dashboard-toggle]') : null;
            if (toggle) return this.toggleWidget(toggle);
            const move = event.target instanceof Element ? event.target.closest('button[data-dashboard-move]') : null;
            if (move) this.moveWidget(move, Number(move.dataset.dashboardMove));
        }

        scopes() {
            return [...this.root.querySelectorAll('[data-dashboard-scope]')];
        }

        widgets(scope) {
            return [...scope.children].filter(child => child.matches('[data-dashboard-widget]'));
        }

        applyLayout() {
            this.scopes().forEach(scope => {
                const scopeLayout = this.layout.scopes?.[scope.dataset.dashboardScope] || { order: [], collapsed: [] };
                const widgets = this.widgets(scope);
                const byId = new Map(widgets.map(widget => [widget.dataset.widgetId, widget]));
                [...scopeLayout.order, ...widgets.map(widget => widget.dataset.widgetId)].forEach(widgetId => {
                    const widget = byId.get(widgetId);
                    if (widget) scope.append(widget);
                    byId.delete(widgetId);
                });
                this.widgets(scope).forEach(widget => this.setCollapsed(widget, scopeLayout.collapsed.includes(widget.dataset.widgetId)));
                this.updateControls(scope);
            });
        }

        collectLayout() {
            const scopes = {};
            this.scopes().forEach(scope => {
                const widgets = this.widgets(scope);
                scopes[scope.dataset.dashboardScope] = {
                    order: widgets.map(widget => widget.dataset.widgetId),
                    collapsed: widgets.filter(widget => widget.querySelector('[data-dashboard-toggle]')?.getAttribute('aria-expanded') === 'false').map(widget => widget.dataset.widgetId),
                };
            });
            return { version: 1, scopes };
        }

        toggleWidget(button) {
            const widget = button.closest('[data-dashboard-widget]');
            if (!widget) return;
            const collapsed = button.getAttribute('aria-expanded') === 'true';
            this.setCollapsed(widget, collapsed);
            this.announce(`${this.widgetLabel(widget)} wurde ${collapsed ? 'eingeklappt' : 'ausgeklappt'}.`);
            this.changed();
        }

        setCollapsed(widget, collapsed) {
            const button = widget.querySelector('[data-dashboard-toggle]');
            const content = widget.querySelector('[data-dashboard-content]');
            if (!button || !content) return;
            button.setAttribute('aria-expanded', String(!collapsed));
            content.hidden = collapsed;
            widget.classList.toggle('is-dashboard-collapsed', collapsed);
        }

        moveWidget(button, offset) {
            const widget = button.closest('[data-dashboard-widget]');
            const scope = widget?.parentElement;
            if (!widget || !scope?.matches('[data-dashboard-scope]')) return;
            const widgets = this.widgets(scope);
            const target = widgets[widgets.indexOf(widget) + offset];
            if (!target) return;
            scope.insertBefore(widget, offset < 0 ? target : target.nextSibling);
            this.updateControls(scope);
            this.announce(`${this.widgetLabel(widget)} wurde an Position ${this.widgets(scope).indexOf(widget) + 1} verschoben.`);
            this.changed();
            button.focus();
        }

        startDrag(event) {
            const handle = event.target instanceof Element ? event.target.closest('[data-dashboard-handle]') : null;
            const widget = handle?.closest('[data-dashboard-widget]');
            const scope = widget?.parentElement;
            if (!handle || !widget || !scope?.matches('[data-dashboard-scope]') || !event.dataTransfer) return;
            this.drag = { widget, scope, handle };
            widget.classList.add('is-dashboard-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', `dashboard:${scope.dataset.dashboardScope}:${widget.dataset.widgetId}`);
        }

        overDrag(event) {
            const target = event.target instanceof Element ? this.dragTarget(event.target) : null;
            if (!target || !this.drag || target === this.drag.widget || target.parentElement !== this.drag.scope) return;
            event.preventDefault();
            this.root.querySelectorAll('.is-dashboard-over').forEach(widget => widget.classList.remove('is-dashboard-over'));
            target.classList.add('is-dashboard-over');
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
        }

        leaveDrag(event) {
            const target = event.target instanceof Element ? this.dragTarget(event.target) : null;
            if (target && !target.contains(event.relatedTarget)) target.classList.remove('is-dashboard-over');
        }

        dropDrag(event) {
            const target = event.target instanceof Element ? this.dragTarget(event.target) : null;
            if (!target || !this.drag || target === this.drag.widget || target.parentElement !== this.drag.scope) return;
            event.preventDefault();
            const bounds = target.getBoundingClientRect();
            const horizontal = Math.abs(event.clientX - (bounds.left + bounds.width / 2)) > Math.abs(event.clientY - (bounds.top + bounds.height / 2));
            const before = horizontal ? event.clientX < bounds.left + bounds.width / 2 : event.clientY < bounds.top + bounds.height / 2;
            this.drag.scope.insertBefore(this.drag.widget, before ? target : target.nextSibling);
            const { widget, scope, handle } = this.drag;
            this.clearDrag();
            this.updateControls(scope);
            this.announce(`${this.widgetLabel(widget)} wurde an Position ${this.widgets(scope).indexOf(widget) + 1} verschoben.`);
            this.changed();
            handle.focus();
        }

        dragTarget(element) {
            if (!this.drag) return null;
            let target = element?.closest('[data-dashboard-widget]') || null;
            while (target && target.parentElement !== this.drag.scope) target = target.parentElement?.closest('[data-dashboard-widget]') || null;
            return target;
        }

        revealInvalid(element) {
            let widget = element?.closest('[data-dashboard-widget]') || null;
            let revealed = false;
            while (widget) {
                if (widget.querySelector('[data-dashboard-toggle]')?.getAttribute('aria-expanded') === 'false') {
                    this.setCollapsed(widget, false);
                    revealed = true;
                }
                widget = widget.parentElement?.closest('[data-dashboard-widget]') || null;
            }
            if (!revealed) return;
            this.announce('Der Block mit dem ungültigen Pflichtfeld wurde geöffnet.');
            this.changed();
        }

        updateControls(scope) {
            const widgets = this.widgets(scope);
            widgets.forEach((widget, index) => {
                const previous = widget.querySelector('[data-dashboard-move="-1"]');
                const next = widget.querySelector('[data-dashboard-move="1"]');
                if (previous) previous.disabled = index === 0;
                if (next) next.disabled = index === widgets.length - 1;
            });
        }

        clearDrag() {
            this.drag = null;
            this.root.querySelectorAll('.is-dashboard-dragging,.is-dashboard-over').forEach(widget => widget.classList.remove('is-dashboard-dragging', 'is-dashboard-over'));
        }

        changed() {
            this.layout = this.collectLayout();
            this.onChange(clone(this.layout));
        }

        widgetLabel(widget) {
            return widget.querySelector('[data-dashboard-title]')?.textContent?.trim() || widget.dataset.widgetId;
        }

        announce(message) {
            const feedback = this.root.querySelector('[data-dashboard-feedback]');
            if (feedback) feedback.textContent = message;
        }
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.components = window.LocalBase.components || {};
    window.LocalBase.components.OrganizationDashboard = OrganizationDashboard;
})();
