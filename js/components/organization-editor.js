(function() {
    'use strict';

    const esc = window.LocalBase.ui.esc;
    const clone = value => JSON.parse(JSON.stringify(value));

    /**
     * Zweck: Bearbeitet allgemeine AD-Organisationsfelder, Rollen, Bereiche und Urlaubsansichten.
     * Zusammenspiel: admin.js -> OrganizationEditor -> HierarchyBoard; LocalBase validiert den gesammelten Payload serverseitig.
     */
    class OrganizationEditor {
        constructor({ container, form, onSave }) {
            this.container = container; this.form = form; this.onSave = onSave; this.definition = null;
            this.positions = [];
            this.sortDrag = null;
            this.editingRoleKey = null;
            this.hierarchyBoard = new window.LocalBase.components.HierarchyBoard({ container, onEditRole: roleKey => this.openRoleEditor(roleKey) });
            this.exporter = new window.LocalBase.components.OrganizationExporter({ container, board: this.hierarchyBoard });
            form.addEventListener('submit', event => { event.preventDefault(); if (this.definition) this.onSave(this.collect()); });
            container.addEventListener('click', event => this.onClick(event));
            container.addEventListener('input', event => this.onInput(event));
            container.addEventListener('dragstart', event => this.startSort(event));
            container.addEventListener('dragover', event => this.overSort(event));
            container.addEventListener('dragleave', event => event.target instanceof Element && event.target.closest('[data-sort-key]')?.classList.remove('is-sort-over'));
            container.addEventListener('drop', event => this.dropSort(event));
            container.addEventListener('dragend', () => this.clearSort());
        }

        set(definition, positions = []) { this.definition = clone(definition); this.positions = clone(positions); this.editingRoleKey = null; this.render(); }

        onClick(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
            if (!button) return;
            if (button.dataset.action === 'add-team') this.addTeam();
            if (button.dataset.action === 'remove-team') button.closest('[data-organization-team]')?.remove();
            if (button.dataset.action === 'toggle-card') this.toggleCard(button);
            if (button.dataset.action === 'close-role-editor') this.closeRoleEditor();
            if (button.dataset.action === 'move-up') this.moveSortRow(button, -1);
            if (button.dataset.action === 'move-down') this.moveSortRow(button, 1);
        }

        onInput(event) {
            const input = event.target instanceof Element ? event.target : null;
            if (!input) return;
            if (input.matches('[data-role-editor-field]')) return this.updateRoleFromPanel(input);
            const areaCard = input.closest('[data-area-key]');
            if (areaCard && input.matches('[data-field="label"],[data-field="groupId"]')) {
                this.definition.areas[areaCard.dataset.areaKey][input.dataset.field] = input.value;
                if (input.dataset.field === 'label') areaCard.querySelector('[data-card-label]').textContent = input.value || areaCard.dataset.areaKey;
                this.refreshBoard();
            }
            const teamCard = input.closest('[data-organization-team]');
            if (teamCard && input.matches('[data-field="label"]')) teamCard.querySelector('[data-card-label]').textContent = input.value || 'Unbenannte Urlaubsansicht';
            if (teamCard && input.matches('[data-field="id"]')) teamCard.querySelector('[data-card-key]').textContent = input.value || 'ohne-id';
        }

        render() {
            const data = this.definition;
            const roles = Object.entries(data.roles).sort(([, a], [, b]) => Number(a.sortOrder) - Number(b.sortOrder));
            const areas = Object.entries(data.areas).sort(([, a], [, b]) => Number(a.sortOrder) - Number(b.sortOrder));
            this.container.innerHTML = `
                <fieldset class="orgs-general"><legend>Allgemein</legend>
                    <label>Präfix der Assistenzteams <input data-organization-field="teamGroupPrefix" value="${esc(data.teamGroupPrefix)}" required></label>
                    <label>Anzeigename der Assistenzteams <input data-organization-field="teamLabelPrefix" value="${esc(data.teamLabelPrefix)}" required></label>
                    <label>Maximale Kürzellänge <input data-organization-field="teamCodeMaxLength" type="number" min="1" max="64" value="${esc(data.teamCodeMaxLength)}" required></label>
                    <label>Titel des Leitungsblocks <input data-organization-field="staffBlockLabel" value="${esc(data.staffBlockLabel)}" required></label>
                </fieldset>
                <fieldset class="orgs-hierarchy"><legend>Direkte Hierarchie</legend>
                    <p>Bearbeite Gruppeneinstellungen über den Edit-Stift einer Diagrammkarte. Ziehe eine unterstellte Rolle am Rollengriff auf die Karte ihrer Leitung. Der separate waagerechte Positionsgriff ordnet Karten derselben Ebene rein visuell von links nach rechts; dafür stehen zusätzlich Pfeiltasten bereit. Verbindungen zwischen Bereichsrollen gelten automatisch in jedem jeweils gleichen Bereich.</p>
                    <div class="orgs-hierarchy-toolbar" aria-label="Hierarchieverbindung per Tastatur anlegen"><label>Leitung <select data-hierarchy-manager>${this.roleOptions(roles)}</select></label><label>Unterstellte Rolle <select data-hierarchy-target>${this.roleOptions(roles)}</select></label><button type="button" data-action="add-edge">Zuordnen</button></div>
                    <p class="orgs-feedback" data-hierarchy-feedback role="status" aria-live="polite"></p>${this.exporter.markup()}<div class="orgs-diagram-workspace" data-diagram-workspace><div class="orgs-organigram" data-hierarchy-board></div><aside class="orgs-role-editor" data-role-editor aria-labelledby="orgs-role-editor-heading" hidden></aside></div>
                </fieldset>
                <p class="orgs-feedback" data-organization-sort-feedback role="status" aria-live="polite"></p>
                <section class="orgs-compact-section" aria-labelledby="orgs-role-order-heading"><h4 id="orgs-role-order-heading">Fachliche Gruppenreihenfolge</h4><p>Diese Reihenfolge steuert Kalender und Gruppenlisten. Sie bleibt von der Links-rechts-Anordnung im Organigramm getrennt.</p><ol class="orgs-compact-list orgs-role-order" data-sort-list="roles">${roles.map(([key, role]) => this.roleOrderItem(key, role)).join('')}</ol></section>
                <section class="orgs-compact-section" aria-labelledby="orgs-area-heading"><h4 id="orgs-area-heading">Bürobereiche</h4><p>Öffne nur den Bereich, den du bearbeiten möchtest.</p><div class="orgs-compact-list" data-sort-list="areas">${areas.map(([key, area]) => this.areaCard(key, area)).join('')}</div></section>
                <section class="orgs-compact-section" aria-labelledby="orgs-team-heading"><h4 id="orgs-team-heading">Teamansichten im Urlaubsplaner</h4><p>Die Karten legen fest, welche Rollen und Bereiche gemeinsam erscheinen.</p><div class="orgs-compact-list" data-organization-teams>${(data.organizationTeams || []).map(team => this.teamCard(team)).join('')}</div><button type="button" data-action="add-team">Urlaubsansicht hinzufügen</button></section>`;
            this.hierarchyBoard.set(data.roles, data.hierarchy || {}, data.areas, this.positions, data.diagramOrder || []);
            this.updateSortControls('roles');
            this.updateSortControls('areas');
        }

        roleOrderItem(key, role) {
            return `<li class="orgs-order-item" data-role-key="${esc(key)}" data-sort-key="${esc(key)}" data-sort-kind="roles"><span class="orgs-setting-title"><strong data-role-order-label="${esc(key)}">${esc(role.label)}</strong><code>${esc(key)}</code></span>${this.sortControls(role.label, role.sortOrder)}</li>`;
        }

        areaCard(key, area) {
            const panelId = `orgs-area-${key}`;
            return `<article class="orgs-setting-card" data-setting-card data-area-key="${esc(key)}" data-sort-key="${esc(key)}" data-sort-kind="areas"><header><button type="button" class="orgs-setting-toggle" data-action="toggle-card" aria-expanded="false" aria-controls="${esc(panelId)}"><span aria-hidden="true">▸</span><span class="orgs-setting-title"><strong data-card-label>${esc(area.label)}</strong><code>${esc(key)}</code></span></button>${this.sortControls(area.label, area.sortOrder)}</header><div id="${esc(panelId)}" class="orgs-setting-panel" data-setting-panel hidden><label>Anzeigename <input data-field="label" value="${esc(area.label)}" required></label><details><summary>Technische Zuordnung</summary><label>Nextcloud-Gruppen-ID <input data-field="groupId" value="${esc(area.groupId)}" required></label></details></div></article>`;
        }

        teamCard(team, expanded = false) {
            const panelId = `orgs-team-${team.id}`;
            return `<article class="orgs-setting-card" data-setting-card data-organization-team><header><button type="button" class="orgs-setting-toggle" data-action="toggle-card" aria-expanded="${expanded ? 'true' : 'false'}" aria-controls="${esc(panelId)}"><span aria-hidden="true">▸</span><span class="orgs-setting-title"><strong data-card-label>${esc(team.label)}</strong><code data-card-key>${esc(team.id)}</code></span></button><button type="button" data-action="remove-team" aria-label="Urlaubsansicht ${esc(team.label)} entfernen">Entfernen</button></header><div id="${esc(panelId)}" class="orgs-setting-panel" data-setting-panel ${expanded ? '' : 'hidden'}><label>ID <input data-field="id" value="${esc(team.id)}" required pattern="[a-z][a-z0-9_-]*"></label><label>Anzeigename <input data-field="label" value="${esc(team.label)}" required></label><label>Rollenschlüssel <span class="orgs-field-help">Kommagetrennte Rollen, die in dieser Ansicht erscheinen.</span><input data-field="roles" value="${esc((team.roles || []).join(', '))}" required></label><label>Bereichsschlüssel <span class="orgs-field-help">Optional kommagetrennte Bürobereiche.</span><input data-field="areas" value="${esc((team.areas || []).join(', '))}"></label><label>Reihenfolge <input data-field="sortOrder" type="number" value="${esc(team.sortOrder)}"></label></div></article>`;
        }

        sortControls(label, sortOrder) {
            return `<span class="orgs-sort-controls"><input data-field="sortOrder" type="hidden" value="${esc(sortOrder)}"><button type="button" class="orgs-sort-handle" draggable="true" data-sort-handle aria-label="${esc(label)} per Drag-and-drop sortieren"><span aria-hidden="true">⠿</span></button><button type="button" data-action="move-up" aria-label="${esc(label)} nach oben verschieben">↑</button><button type="button" data-action="move-down" aria-label="${esc(label)} nach unten verschieben">↓</button></span>`;
        }
        roleOptions(roles) { return roles.map(([key, role]) => `<option value="${esc(key)}">${esc(role.label)}</option>`).join(''); }

        roleEditorMarkup(roleKey) {
            const role = this.definition.roles[roleKey];
            if (!role) return '';
            const option = (field, label, help) => `<label class="orgs-role-option"><input type="checkbox" data-role-editor-field="${field}" ${role[field] ? 'checked' : ''}><span><strong>${esc(label)}</strong><span class="orgs-field-help">${esc(help)}</span></span></label>`;
            return `<div class="orgs-role-editor-header"><h4 id="orgs-role-editor-heading" tabindex="-1"><span data-role-editor-heading-text>${esc(role.label)}</span> bearbeiten</h4><button type="button" data-action="close-role-editor" aria-label="Rollenbearbeitung schließen">×</button></div><p data-role-scope-help>Diese Einstellung gilt für alle Karten dieser Rolle${role.areaScoped ? ' in sämtlichen Bereichen' : ''}.</p><label>Anzeigename <input data-role-editor-field="label" value="${esc(role.label)}" required></label><fieldset><legend>Wirkung der Rolle</legend>${option('calendarVisible', 'Im Kalender anzeigen', 'Rolle steht als Filter und Kalendergruppe zur Verfügung.')}${option('areaScoped', 'Mit Bürobereichen verbinden', 'Mitglieder werden zusätzlich über Bürobereiche zugeordnet.')}${option('managementAreaScoped', 'Leitung nur im gemeinsamen Bereich', 'Leitungsrechte gelten nur in gemeinsamen Bereichen.')}${option('peerEnabled', 'Gleichrangige Freigabe ermöglichen', 'Gleichrangige dürfen nach Freigabe gegenseitig bearbeiten.')}${option('staffBlock', 'Im Leitungsblock anzeigen', 'Gemeinsamer Block für Geschäftsführung, Leitungen und Stabsstellen.')}${option('singleOccupant', 'Als Einzelposition behandeln', 'Genau eine Person; bei Bereichsrollen je Bereich.')}</fieldset><details><summary>Technische Zuordnung</summary><p class="orgs-field-help">Änderungen verschieben keine bestehenden Nextcloud-Mitgliedschaften.</p><label>Nextcloud-Gruppen-ID <input data-role-editor-field="groupId" value="${esc(role.groupId)}" required></label><span class="orgs-technical-key">Fachlicher Schlüssel: <code>${esc(roleKey)}</code></span></details>`;
        }

        openRoleEditor(roleKey) {
            if (!this.definition.roles[roleKey]) return;
            this.editingRoleKey = roleKey;
            const panel = this.container.querySelector('[data-role-editor]');
            panel.innerHTML = this.roleEditorMarkup(roleKey);
            panel.hidden = false;
            this.container.querySelector('[data-diagram-workspace]').classList.add('is-editing');
            panel.querySelector('[data-role-editor-field="label"]')?.focus();
        }

        closeRoleEditor() {
            const roleKey = this.editingRoleKey;
            this.editingRoleKey = null;
            const panel = this.container.querySelector('[data-role-editor]');
            panel.hidden = true;
            panel.replaceChildren();
            this.container.querySelector('[data-diagram-workspace]').classList.remove('is-editing');
            if (roleKey) this.hierarchyBoard.focusRoleEdit(roleKey);
        }

        updateRoleFromPanel(input) {
            const role = this.definition.roles[this.editingRoleKey];
            if (!role) return;
            const field = input.dataset.roleEditorField;
            role[field] = input.type === 'checkbox' ? input.checked : input.value;
            if (field === 'label') {
                const label = input.value || this.editingRoleKey;
                this.container.querySelector('[data-role-editor-heading-text]').textContent = label;
                const orderItem = this.container.querySelector(`[data-role-order-label="${this.editingRoleKey}"]`)?.closest('[data-role-key]');
                if (orderItem) {
                    orderItem.querySelector('[data-role-order-label]').textContent = label;
                    orderItem.querySelector('[data-sort-handle]').setAttribute('aria-label', `${label} per Drag-and-drop sortieren`);
                    orderItem.querySelector('[data-action="move-up"]').setAttribute('aria-label', `${label} nach oben verschieben`);
                    orderItem.querySelector('[data-action="move-down"]').setAttribute('aria-label', `${label} nach unten verschieben`);
                }
            }
            if (field === 'areaScoped') this.container.querySelector('[data-role-scope-help]').textContent = `Diese Einstellung gilt für alle Karten dieser Rolle${role.areaScoped ? ' in sämtlichen Bereichen' : ''}.`;
            this.refreshBoard();
        }

        refreshBoard() {
            const hierarchy = this.hierarchyBoard.get();
            const diagramOrder = this.hierarchyBoard.getDiagramOrder().filter(nodeId => this.isValidDiagramNode(nodeId, this.definition.roles, this.definition.areas));
            this.hierarchyBoard.set(this.definition.roles, hierarchy, this.definition.areas, this.positions, diagramOrder);
        }

        toggleCard(button) {
            const card = button.closest('[data-setting-card]');
            const panel = card?.querySelector('[data-setting-panel]');
            if (!card || !panel) return;
            const expanded = button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', String(expanded));
            panel.hidden = !expanded;
        }

        collect() {
            this.syncSortOrders('roles');
            this.syncSortOrders('areas');
            const data = clone(this.definition);
            for (const field of ['teamGroupPrefix', 'teamLabelPrefix', 'staffBlockLabel']) data[field] = this.container.querySelector(`[data-organization-field="${field}"]`).value.trim();
            data.teamCodeMaxLength = Number(this.container.querySelector('[data-organization-field="teamCodeMaxLength"]').value);
            for (const role of Object.values(data.roles)) for (const field of ['groupId', 'label']) role[field] = role[field].trim();
            this.container.querySelectorAll('[data-area-key]').forEach(row => {
                const area = data.areas[row.dataset.areaKey];
                for (const field of ['groupId', 'label']) area[field] = row.querySelector(`[data-field="${field}"]`).value.trim();
                area.sortOrder = Number(row.querySelector('[data-field="sortOrder"]').value);
            });
            data.hierarchy = this.hierarchyBoard.get();
            data.diagramOrder = this.hierarchyBoard.getDiagramOrder().filter(nodeId => this.isValidDiagramNode(nodeId, data.roles, data.areas));
            data.organizationTeams = [...this.container.querySelectorAll('[data-organization-team]')].map(row => ({ id: row.querySelector('[data-field="id"]').value.trim(), label: row.querySelector('[data-field="label"]').value.trim(), roles: this.list(row.querySelector('[data-field="roles"]').value), areas: this.list(row.querySelector('[data-field="areas"]').value), sortOrder: Number(row.querySelector('[data-field="sortOrder"]').value) }));
            return data;
        }

        startSort(event) {
            const handle = event.target instanceof Element ? event.target.closest('[data-sort-handle]') : null;
            const row = handle?.closest('[data-sort-key]');
            if (!row || !event.dataTransfer) return;
            this.sortDrag = { kind: row.dataset.sortKind, key: row.dataset.sortKey };
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', `sort:${this.sortDrag.kind}:${this.sortDrag.key}`);
            row.classList.add('is-sorting');
        }

        overSort(event) {
            const row = event.target instanceof Element ? event.target.closest('[data-sort-key]') : null;
            if (!row || !this.sortDrag || row.dataset.sortKind !== this.sortDrag.kind || row.dataset.sortKey === this.sortDrag.key) return;
            event.preventDefault();
            row.classList.add('is-sort-over');
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
        }

        dropSort(event) {
            const target = event.target instanceof Element ? event.target.closest('[data-sort-key]') : null;
            if (!target || !this.sortDrag || target.dataset.sortKind !== this.sortDrag.kind) return;
            const list = target.closest('[data-sort-list]');
            const source = [...list.querySelectorAll('[data-sort-key]')].find(row => row.dataset.sortKey === this.sortDrag.key);
            if (!source || source === target) return this.clearSort();
            event.preventDefault();
            const bounds = target.getBoundingClientRect();
            const insertBefore = event.clientY < bounds.top + bounds.height / 2;
            list.insertBefore(source, insertBefore ? target : target.nextSibling);
            const kind = this.sortDrag.kind;
            this.clearSort();
            this.syncSortOrders(kind);
            this.announceSort(source);
        }

        moveSortRow(button, offset) {
            const row = button.closest('[data-sort-key]');
            const list = row?.closest('[data-sort-list]');
            if (!row || !list) return;
            const rows = [...list.querySelectorAll('[data-sort-key]')];
            const index = rows.indexOf(row);
            const target = rows[index + offset];
            if (!target) return;
            if (offset < 0) list.insertBefore(row, target);
            else list.insertBefore(target, row);
            this.syncSortOrders(row.dataset.sortKind);
            this.announceSort(row);
            button.focus();
        }

        syncSortOrders(kind) {
            const list = this.container.querySelector(`[data-sort-list="${kind}"]`);
            if (!list) return;
            const rows = [...list.querySelectorAll('[data-sort-key]')];
            this.applyOrder(kind, rows.map(row => row.dataset.sortKey));
            rows.forEach(row => { row.querySelector('[data-field="sortOrder"]').value = this.definition[kind][row.dataset.sortKey].sortOrder; });
            this.updateSortControls(kind);
            if (kind === 'roles' || kind === 'areas') this.hierarchyBoard.set(this.definition.roles, this.hierarchyBoard.get(), this.definition.areas, this.positions, this.hierarchyBoard.getDiagramOrder());
        }

        applyOrder(kind, orderedKeys) {
            const collection = this.definition?.[kind];
            if (!collection) return;
            orderedKeys.forEach((key, index) => { if (collection[key]) collection[key].sortOrder = (index + 1) * 10; });
        }

        updateSortControls(kind) {
            const rows = [...this.container.querySelectorAll(`[data-sort-list="${kind}"] [data-sort-key]`)];
            rows.forEach((row, index) => {
                row.querySelector('[data-action="move-up"]').disabled = index === 0;
                row.querySelector('[data-action="move-down"]').disabled = index === rows.length - 1;
            });
        }

        announceSort(row) {
            const label = row.querySelector('[data-field="label"]')?.value || row.querySelector('[data-role-order-label]')?.textContent || row.dataset.sortKey;
            this.container.querySelector('[data-organization-sort-feedback]').textContent = `${label} wurde an Position ${[...row.parentElement.children].indexOf(row) + 1} verschoben.`;
        }

        clearSort() {
            this.sortDrag = null;
            this.container.querySelectorAll('.is-sorting,.is-sort-over').forEach(element => element.classList.remove('is-sorting', 'is-sort-over'));
        }

        isValidDiagramNode(nodeId, roles, areas) {
            const [roleKey, areaKey = null, extra = null] = String(nodeId).split('::');
            if (extra !== null || !roles[roleKey]) return false;
            return areaKey === null ? !roles[roleKey].areaScoped : Boolean(roles[roleKey].areaScoped && areas[areaKey]);
        }

        addTeam() {
            const rows = [...this.container.querySelectorAll('[data-organization-team]')];
            const ids = new Set(rows.map(row => row.querySelector('[data-field="id"]').value));
            let number = rows.length + 1; while (ids.has(`view-${number}`)) number += 1;
            const sortOrder = Math.max(0, ...rows.map(row => Number(row.querySelector('[data-field="sortOrder"]').value) || 0)) + 10;
            this.container.querySelector('[data-organization-teams]').insertAdjacentHTML('beforeend', this.teamCard({ id: `view-${number}`, label: 'Neue Urlaubsansicht', roles: [], areas: [], sortOrder }, true));
            this.container.querySelector('[data-organization-team]:last-child [data-field="label"]')?.focus();
        }
        list(value) { return [...new Set(value.split(',').map(item => item.trim()).filter(Boolean))]; }
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.components = window.LocalBase.components || {};
    window.LocalBase.components.OrganizationEditor = OrganizationEditor;
})();
