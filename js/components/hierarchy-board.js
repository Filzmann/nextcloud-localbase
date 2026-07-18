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
            this.connectionFrame = null;
            container.addEventListener('click', event => this.onClick(event));
            container.addEventListener('dragstart', event => this.startDrag(event));
            container.addEventListener('dragover', event => this.overTarget(event));
            container.addEventListener('dragleave', event => event.target instanceof Element && event.target.closest('[data-manager-key]')?.classList.remove('is-drag-over'));
            container.addEventListener('drop', event => this.dropRole(event));
            container.addEventListener('dragend', () => this.clearDrag());
            window.addEventListener?.('resize', () => this.scheduleConnections());
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
            const connections = this.connections(roleMap);
            this.container.querySelector('[data-hierarchy-board]').innerHTML = `
                <div class="orgs-diagram" data-hierarchy-diagram>
                    <svg class="orgs-diagram-links" data-hierarchy-svg aria-hidden="true"><defs><marker id="orgs-arrowhead" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z"></path></marker></defs><g data-hierarchy-links></g></svg>
                    <div class="orgs-diagram-levels">${this.levels(roles.map(([key]) => key)).map((level, index) => `
                        <section class="orgs-organigram-level" aria-label="Hierarchieebene ${index + 1}"><span class="orgs-level-label">Ebene ${index + 1}</span><div class="orgs-level-nodes">
                            ${level.map(key => this.card(key, roleMap[key])).join('')}
                        </div></section>`).join('')}
                    </div>
                </div>
                <section class="orgs-connections" aria-labelledby="orgs-connections-heading"><h4 id="orgs-connections-heading">Direkte Verbindungen</h4>${this.connectionList(connections)}</section>`;
            this.container.querySelector('[data-hierarchy-feedback]').textContent = message;
            this.scheduleConnections();
        }

        card(key, role) {
            return `<article class="orgs-card" data-manager-key="${esc(key)}" data-diagram-role="${esc(key)}"><button type="button" class="orgs-drag-role" draggable="true" data-drag-role="${esc(key)}" aria-label="${esc(role.label)} ziehen, um die Rolle einer Leitung zuzuordnen"><span aria-hidden="true">⠿</span><strong>${esc(role.label)}</strong><code>${esc(key)}</code></button></article>`;
        }

        connections(roleMap) {
            const result = [];
            for (const [manager, targets] of Object.entries(this.hierarchy)) for (const target of targets) result.push({
                manager,
                managerLabel: roleMap[manager]?.label || manager,
                target,
                targetLabel: roleMap[target]?.label || target,
            });
            return result;
        }

        connectionList(connections) {
            if (!connections.length) return '<p class="orgs-empty">Keine direkten Unterstellungen.</p>';
            return `<ul class="orgs-connection-list">${connections.map(edge => `<li><span>${esc(edge.managerLabel)} <span aria-hidden="true">→</span><span class="hidden-visually"> leitet </span> ${esc(edge.targetLabel)}</span><button type="button" data-action="remove-edge" data-manager-key="${esc(edge.manager)}" data-target-key="${esc(edge.target)}" aria-label="Unterstellung ${esc(edge.targetLabel)} unter ${esc(edge.managerLabel)} entfernen">Entfernen</button></li>`).join('')}</ul>`;
        }

        scheduleConnections() {
            if (this.connectionFrame !== null && typeof window.cancelAnimationFrame === 'function') window.cancelAnimationFrame(this.connectionFrame);
            if (typeof window.requestAnimationFrame !== 'function') return this.drawConnections();
            this.connectionFrame = window.requestAnimationFrame(() => { this.connectionFrame = null; this.drawConnections(); });
        }

        drawConnections() {
            const diagram = this.container.querySelector('[data-hierarchy-diagram]');
            const svg = diagram?.querySelector('[data-hierarchy-svg]');
            const links = svg?.querySelector('[data-hierarchy-links]');
            if (!diagram || !svg || !links) return;
            links.replaceChildren();
            const bounds = diagram.getBoundingClientRect();
            const width = Math.max(diagram.scrollWidth, diagram.clientWidth);
            const height = Math.max(diagram.scrollHeight, diagram.clientHeight);
            svg.setAttribute('width', String(width));
            svg.setAttribute('height', String(height));
            svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            const cards = new Map([...diagram.querySelectorAll('[data-diagram-role]')].map(card => [card.dataset.diagramRole, card]));
            for (const [manager, targets] of Object.entries(this.hierarchy)) for (const target of targets) {
                const source = cards.get(manager);
                const destination = cards.get(target);
                if (!source || !destination) continue;
                const from = source.getBoundingClientRect();
                const to = destination.getBoundingClientRect();
                const fromX = from.left - bounds.left + from.width / 2;
                const fromY = from.bottom - bounds.top;
                const toX = to.left - bounds.left + to.width / 2;
                const toY = to.top - bounds.top - 4;
                const middleY = fromY + Math.max(20, (toY - fromY) / 2);
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', `M ${fromX} ${fromY} C ${fromX} ${middleY}, ${toX} ${middleY}, ${toX} ${toY}`);
                path.setAttribute('class', 'orgs-diagram-link');
                path.setAttribute('marker-end', 'url(#orgs-arrowhead)');
                links.append(path);
            }
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
