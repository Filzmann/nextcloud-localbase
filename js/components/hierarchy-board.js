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
            this.areas = {};
            this.positions = [];
            this.hierarchy = {};
            this.diagramOrder = [];
            this.draggedRole = '';
            this.draggedNode = null;
            this.connectionFrame = null;
            container.addEventListener('click', event => this.onClick(event));
            container.addEventListener('dragstart', event => this.startDrag(event));
            container.addEventListener('dragover', event => this.overTarget(event));
            container.addEventListener('dragleave', event => this.leaveTarget(event));
            container.addEventListener('drop', event => this.dropRole(event));
            container.addEventListener('dragend', () => this.clearDrag());
            window.addEventListener?.('resize', () => this.scheduleConnections());
        }

        set(roles, hierarchy, areas = {}, positions = [], diagramOrder = []) {
            this.roles = clone(roles); this.hierarchy = clone(hierarchy); this.areas = clone(areas); this.positions = clone(positions); this.diagramOrder = clone(diagramOrder); this.render();
        }
        get() { return clone(this.hierarchy); }
        getDiagramOrder() { return this.diagramNodes(this.sortedRoleKeys()).map(node => node.id); }

        onClick(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
            if (!button) return;
            if (button.dataset.action === 'move-node-left') this.moveDiagramNode(button.closest('[data-diagram-node]')?.dataset.diagramNode, -1);
            if (button.dataset.action === 'move-node-right') this.moveDiagramNode(button.closest('[data-diagram-node]')?.dataset.diagramNode, 1);
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
            const nodes = this.diagramNodes(roles.map(([key]) => key));
            const roleLevels = this.levels(roles.map(([key]) => key));
            this.container.querySelector('[data-hierarchy-board]').innerHTML = `
                <div class="orgs-diagram" data-hierarchy-diagram>
                    <svg class="orgs-diagram-links" data-hierarchy-svg aria-hidden="true"><defs><marker id="orgs-arrowhead" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z"></path></marker></defs><g data-hierarchy-links></g></svg>
                    <div class="orgs-diagram-levels">${roleLevels.map((level, index) => `
                        <section class="orgs-organigram-level" aria-label="Hierarchieebene ${index + 1}"><span class="orgs-level-label">Ebene ${index + 1}</span><div class="orgs-level-nodes">
                            ${this.levelNodes(level, nodes).map((node, nodeIndex, levelNodes) => this.card(node, index, nodeIndex, levelNodes.length)).join('')}
                        </div></section>`).join('')}
                    </div>
                </div>
                <section class="orgs-connections" aria-labelledby="orgs-connections-heading"><h4 id="orgs-connections-heading">Direkte Verbindungen</h4>${this.connectionList(connections)}</section>`;
            this.container.querySelector('[data-hierarchy-feedback]').textContent = message;
            this.scheduleConnections();
        }

        card(node, levelIndex = 0, nodeIndex = 0, nodeCount = 1) {
            const role = this.roles[node.roleKey];
            const area = node.areaKey ? this.areas[node.areaKey] : null;
            const person = this.positionText(node.roleKey, node.areaKey);
            const position = this.position(node.roleKey, node.areaKey);
            const personClass = (position?.displayNames || []).length === 0 ? ' orgs-card-person--vacant' : (position?.displayNames || []).length > 1 ? ' orgs-card-person--multiple' : '';
            const areaLabel = area ? `<span class="orgs-card-area">Bereich ${esc(area.label)}</span>` : '';
            const personMarkup = role.singleOccupant ? `<span class="orgs-card-person${personClass}">${esc(person)}</span>` : '';
            const label = `${role.label}${area ? `, Bereich ${area.label}` : ''}`;
            return `<article class="orgs-card" data-manager-key="${esc(node.roleKey)}" data-diagram-node="${esc(node.id)}" data-diagram-level="${levelIndex}"><div class="orgs-card-position"><button type="button" data-action="move-node-left" aria-label="${esc(label)} nach links verschieben" ${nodeIndex === 0 ? 'disabled' : ''}>←</button><button type="button" class="orgs-position-handle" draggable="true" data-position-node="${esc(node.id)}" aria-label="${esc(label)} waagerecht per Drag-and-drop anordnen"><span aria-hidden="true">↔</span></button><button type="button" data-action="move-node-right" aria-label="${esc(label)} nach rechts verschieben" ${nodeIndex === nodeCount - 1 ? 'disabled' : ''}>→</button></div><button type="button" class="orgs-drag-role" draggable="true" data-drag-role="${esc(node.roleKey)}" aria-label="${esc(label)} ziehen, um die Rolle einer Leitung zuzuordnen"><span aria-hidden="true">⠿</span><strong>${esc(role.label)}</strong><code>${esc(node.roleKey)}</code></button>${areaLabel}${personMarkup}</article>`;
        }

        diagramNodes(roleKeys) {
            // Spiegelvertrag: lib/Organization/AdOrganizationDefinition.php validiert genau diese Rollen- bzw. Rolle::Bereich-Knoten-IDs.
            const areas = Object.entries(this.areas).sort(([, a], [, b]) => Number(a.sortOrder) - Number(b.sortOrder));
            return this.orderDiagramNodes(roleKeys.flatMap(roleKey => {
                const role = this.roles[roleKey];
                if (!role?.areaScoped || !areas.length) return [{ id: roleKey, roleKey, areaKey: null }];
                return areas.map(([areaKey]) => ({ id: `${roleKey}::${areaKey}`, roleKey, areaKey }));
            }));
        }

        sortedRoleKeys() { return Object.entries(this.roles).sort(([, a], [, b]) => Number(a.sortOrder) - Number(b.sortOrder)).map(([key]) => key); }

        orderDiagramNodes(nodes) {
            const configured = new Map(this.diagramOrder.map((id, index) => [id, index]));
            const fallback = new Map(nodes.map((node, index) => [node.id, index]));
            return [...nodes].sort((a, b) => {
                const aOrder = configured.has(a.id) ? configured.get(a.id) : configured.size + fallback.get(a.id);
                const bOrder = configured.has(b.id) ? configured.get(b.id) : configured.size + fallback.get(b.id);
                return aOrder - bOrder;
            });
        }

        levelNodes(roleKeys, nodes) {
            const roles = new Set(roleKeys);
            return this.orderDiagramNodes(nodes.filter(node => roles.has(node.roleKey)));
        }

        diagramEdges(nodes) {
            const nodesByRole = this.nodesByRole(nodes);
            const result = [];
            for (const [manager, targets] of Object.entries(this.hierarchy)) for (const target of targets) {
                for (const source of nodesByRole.get(manager) || []) for (const destination of nodesByRole.get(target) || []) {
                    if (source.areaKey && destination.areaKey && source.areaKey !== destination.areaKey) continue;
                    result.push({ source: source.id, destination: destination.id });
                }
            }
            return result;
        }

        nodesByRole(nodes) {
            const result = new Map();
            for (const node of nodes) result.set(node.roleKey, [...(result.get(node.roleKey) || []), node]);
            return result;
        }

        position(roleKey, areaKey) {
            return this.positions.find(position => position.roleKey === roleKey && (position.areaKey || null) === (areaKey || null));
        }

        positionText(roleKey, areaKey) {
            const names = this.position(roleKey, areaKey)?.displayNames || [];
            if (!names.length) return 'Nicht besetzt';
            if (names.length === 1) return names[0];
            return `Mehrfach besetzt: ${names.join(', ')}`;
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
            const cards = new Map([...diagram.querySelectorAll('[data-diagram-node]')].map(card => [card.dataset.diagramNode, card]));
            const roleKeys = Object.entries(this.roles).sort(([, a], [, b]) => Number(a.sortOrder) - Number(b.sortOrder)).map(([key]) => key);
            for (const edge of this.diagramEdges(this.diagramNodes(roleKeys))) {
                const source = cards.get(edge.source);
                const destination = cards.get(edge.destination);
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
            const positionSource = event.target instanceof Element ? event.target.closest('[data-position-node]') : null;
            if (positionSource && event.dataTransfer) {
                const card = positionSource.closest('[data-diagram-node]');
                this.draggedNode = { id: positionSource.dataset.positionNode, level: card?.dataset.diagramLevel || '' };
                event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', `position:${this.draggedNode.id}`); positionSource.classList.add('is-position-dragging');
                return;
            }
            const source = event.target instanceof Element ? event.target.closest('[data-drag-role]') : null;
            if (!source || !event.dataTransfer) return;
            this.draggedRole = source.dataset.dragRole;
            event.dataTransfer.effectAllowed = 'link'; event.dataTransfer.setData('text/plain', this.draggedRole); source.classList.add('is-dragging');
        }

        overTarget(event) {
            const target = event.target instanceof Element ? event.target.closest('[data-manager-key]') : null;
            if (this.draggedNode) {
                if (!target || target.dataset.diagramLevel !== this.draggedNode.level || target.dataset.diagramNode === this.draggedNode.id) return;
                event.preventDefault();
                this.container.querySelectorAll('.is-position-before,.is-position-after').forEach(element => element.classList.remove('is-position-before', 'is-position-after'));
                const bounds = target.getBoundingClientRect();
                target.classList.add(event.clientX < bounds.left + bounds.width / 2 ? 'is-position-before' : 'is-position-after');
                if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
                return;
            }
            if (!target || !this.draggedRole || target.dataset.managerKey === this.draggedRole) return;
            event.preventDefault(); target.classList.add('is-drag-over');
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'link';
        }

        dropRole(event) {
            const target = event.target instanceof Element ? event.target.closest('[data-manager-key]') : null;
            if (!target) return;
            if (this.draggedNode) {
                if (target.dataset.diagramLevel !== this.draggedNode.level || target.dataset.diagramNode === this.draggedNode.id) return this.clearDrag();
                event.preventDefault();
                const sourceId = this.draggedNode.id;
                const before = target.classList.contains('is-position-before');
                this.applyDiagramOrderMove(sourceId, target.dataset.diagramNode, before);
                this.clearDrag();
                return this.render('Die Links-rechts-Anordnung wurde rein visuell geändert. Bitte speichere die Organisation.');
            }
            event.preventDefault();
            const role = event.dataTransfer?.getData('text/plain') || this.draggedRole;
            this.clearDrag(); this.addEdge(target.dataset.managerKey, role);
        }

        clearDrag() {
            this.draggedRole = '';
            this.draggedNode = null;
            this.container.querySelectorAll('.is-dragging,.is-drag-over,.is-position-dragging,.is-position-before,.is-position-after').forEach(element => element.classList.remove('is-dragging', 'is-drag-over', 'is-position-dragging', 'is-position-before', 'is-position-after'));
        }

        leaveTarget(event) {
            const target = event.target instanceof Element ? event.target.closest('[data-manager-key]') : null;
            target?.classList.remove('is-drag-over', 'is-position-before', 'is-position-after');
        }

        applyDiagramOrderMove(sourceId, targetId, before) {
            const order = this.getDiagramOrder().filter(id => id !== sourceId);
            const targetIndex = order.indexOf(targetId);
            if (targetIndex < 0) return;
            order.splice(targetIndex + (before ? 0 : 1), 0, sourceId);
            this.diagramOrder = order;
        }

        moveDiagramNode(nodeId, offset) {
            if (!nodeId) return;
            const card = [...this.container.querySelectorAll('[data-diagram-node]')].find(item => item.dataset.diagramNode === nodeId);
            if (!card) return;
            const levelCards = [...this.container.querySelectorAll(`[data-diagram-level="${card.dataset.diagramLevel}"]`)];
            const index = levelCards.indexOf(card);
            const target = levelCards[index + offset];
            if (!target) return;
            this.applyDiagramOrderMove(nodeId, target.dataset.diagramNode, offset < 0);
            this.render('Die Links-rechts-Anordnung wurde rein visuell geändert. Bitte speichere die Organisation.');
        }
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.components = window.LocalBase.components || {};
    window.LocalBase.components.HierarchyBoard = HierarchyBoard;
})();
