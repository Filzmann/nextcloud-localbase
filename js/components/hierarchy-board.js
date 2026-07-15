(function() {
    'use strict';

    const esc = window.LocalBase.ui.esc;
    const clone = value => JSON.parse(JSON.stringify(value));

    /**
     * Zweck: Rendert und bearbeitet direkte Organisationskanten per Tastatur und Drag-and-drop.
     * Vertrag: Die clientseitige Zyklusprüfung dient der Rückmeldung; LocalBase validiert den Payload serverseitig erneut.
     */
    class HierarchyBoard {
        constructor({ container }) {
            this.container = container;
            this.roles = {};
            this.hierarchy = {};
            this.draggedRole = '';
            container.addEventListener('click', event => this.onClick(event));
            container.addEventListener('dragstart', event => this.startDrag(event));
            container.addEventListener('dragover', event => this.overTarget(event));
            container.addEventListener('dragleave', event => event.target instanceof Element && event.target.closest('[data-manager-key]')?.classList.remove('is-drag-over'));
            container.addEventListener('drop', event => this.dropRole(event));
            container.addEventListener('dragend', () => this.clearDrag());
        }

        set(roles, hierarchy) { this.roles = clone(roles); this.hierarchy = clone(hierarchy); this.render(); }
        get() { return clone(this.hierarchy); }

        onClick(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
            if (!button) return;
            if (button.dataset.action === 'remove-edge') this.removeEdge(button.dataset.managerKey, button.dataset.targetKey);
            if (button.dataset.action === 'add-edge') this.addEdge(
                this.container.querySelector('[data-hierarchy-manager]').value,
                this.container.querySelector('[data-hierarchy-target]').value,
            );
        }

        render(message = '') {
            const roles = Object.entries(this.roles).sort(([, a], [, b]) => Number(a.sortOrder) - Number(b.sortOrder));
            const roleMap = Object.fromEntries(roles);
            this.container.querySelector('[data-hierarchy-board]').innerHTML = this.levels(roles.map(([key]) => key)).map((level, index) => `
                <section class="orgs-organigram-level" aria-label="Hierarchieebene ${index + 1}"><span class="orgs-level-label">Ebene ${index + 1}</span><div class="orgs-level-nodes">
                    ${level.map(key => this.card(key, roleMap[key], roleMap)).join('')}
                </div></section>`).join('');
            this.container.querySelector('[data-hierarchy-feedback]').textContent = message;
        }

        card(key, role, roleMap) {
            const children = (this.hierarchy[key] || []).map(target => `<span class="orgs-edge"><span>${esc(roleMap[target]?.label || target)}</span><button type="button" data-action="remove-edge" data-manager-key="${esc(key)}" data-target-key="${esc(target)}" aria-label="Unterstellung ${esc(roleMap[target]?.label || target)} unter ${esc(role.label)} entfernen">×</button></span>`).join('') || '<span class="orgs-empty">Keine direkt unterstellte Rolle</span>';
            return `<article class="orgs-card" data-manager-key="${esc(key)}"><header><button type="button" class="orgs-drag-role" draggable="true" data-drag-role="${esc(key)}" aria-label="${esc(role.label)} ziehen, um die Rolle einer Leitung zuzuordnen"><span aria-hidden="true">⠿</span> ${esc(role.label)}</button><code>${esc(key)}</code></header><div class="orgs-edges" aria-label="Direkt unterstellt">${children}</div></article>`;
        }

        levels(roleKeys) {
            const levels = Object.fromEntries(roleKeys.map(key => [key, 0]));
            for (let pass = 0; pass < roleKeys.length; pass += 1) for (const [manager, targets] of Object.entries(this.hierarchy)) for (const target of targets) if (manager in levels && target in levels) levels[target] = Math.max(levels[target], levels[manager] + 1);
            const result = [];
            for (const key of roleKeys) (result[levels[key]] ||= []).push(key);
            return result.filter(Boolean);
        }

        addEdge(manager, target) {
            if (!this.roles[manager] || !this.roles[target]) return this.render('Bitte wähle zwei gültige Rollen.');
            if (manager === target) return this.render('Eine Rolle kann sich nicht selbst unterstellt sein.');
            if ((this.hierarchy[manager] || []).includes(target)) return this.render('Diese direkte Unterstellung besteht bereits.');
            if (this.reaches(target, manager)) return this.render('Diese Verbindung würde einen Hierarchiezyklus erzeugen.');
            this.hierarchy[manager] = [...(this.hierarchy[manager] || []), target];
            this.render(`${this.roles[target].label} ist jetzt ${this.roles[manager].label} direkt unterstellt.`);
        }

        removeEdge(manager, target) {
            this.hierarchy[manager] = (this.hierarchy[manager] || []).filter(key => key !== target);
            if (!this.hierarchy[manager].length) delete this.hierarchy[manager];
            this.render('Direkte Unterstellung entfernt.');
        }

        reaches(start, goal, visited = new Set()) {
            if (start === goal) return true;
            if (visited.has(start)) return false;
            visited.add(start);
            return (this.hierarchy[start] || []).some(target => this.reaches(target, goal, visited));
        }

        startDrag(event) {
            const source = event.target instanceof Element ? event.target.closest('[data-drag-role]') : null;
            if (!source || !event.dataTransfer) return;
            this.draggedRole = source.dataset.dragRole;
            event.dataTransfer.effectAllowed = 'link'; event.dataTransfer.setData('text/plain', this.draggedRole); source.classList.add('is-dragging');
        }

        overTarget(event) {
            const target = event.target instanceof Element ? event.target.closest('[data-manager-key]') : null;
            if (!target || !this.draggedRole || target.dataset.managerKey === this.draggedRole) return;
            event.preventDefault(); target.classList.add('is-drag-over');
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'link';
        }

        dropRole(event) {
            const target = event.target instanceof Element ? event.target.closest('[data-manager-key]') : null;
            if (!target) return;
            event.preventDefault();
            const role = event.dataTransfer?.getData('text/plain') || this.draggedRole;
            this.clearDrag(); this.addEdge(target.dataset.managerKey, role);
        }

        clearDrag() {
            this.draggedRole = '';
            this.container.querySelectorAll('.is-dragging,.is-drag-over').forEach(element => element.classList.remove('is-dragging', 'is-drag-over'));
        }
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.components = window.LocalBase.components || {};
    window.LocalBase.components.HierarchyBoard = HierarchyBoard;
})();
