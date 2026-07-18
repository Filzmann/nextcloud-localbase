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
            this.hierarchyBoard = new window.LocalBase.components.HierarchyBoard({ container });
            this.exporter = new window.LocalBase.components.OrganizationExporter({ container, board: this.hierarchyBoard });
            form.addEventListener('submit', event => { event.preventDefault(); if (this.definition) this.onSave(this.collect()); });
            container.addEventListener('click', event => this.onClick(event));
            container.addEventListener('dragstart', event => this.startSort(event));
            container.addEventListener('dragover', event => this.overSort(event));
            container.addEventListener('dragleave', event => event.target instanceof Element && event.target.closest('[data-sort-key]')?.classList.remove('is-sort-over'));
            container.addEventListener('drop', event => this.dropSort(event));
            container.addEventListener('dragend', () => this.clearSort());
        }

        set(definition, positions = []) { this.definition = clone(definition); this.positions = clone(positions); this.render(); }

        onClick(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
            if (!button) return;
            if (button.dataset.action === 'add-team') this.addTeam();
            if (button.dataset.action === 'remove-team') button.closest('tr')?.remove();
            if (button.dataset.action === 'move-up') this.moveSortRow(button, -1);
            if (button.dataset.action === 'move-down') this.moveSortRow(button, 1);
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
                <p class="orgs-feedback" data-organization-sort-feedback role="status" aria-live="polite"></p>
                <div class="orgs-table-wrap"><table class="orgs-table"><caption>Fachrollen und Nextcloud-Gruppen</caption><thead><tr><th scope="col">Rolle</th><th scope="col">Gruppen-ID</th><th scope="col">Anzeigename</th>${this.columnHeader('Kalender', 'Rolle steht als Filter und Kalendergruppe zur Verfügung.')}${this.columnHeader('Bereich', 'Mitglieder werden zusätzlich über Bürobereiche zugeordnet.')}${this.columnHeader('Leitung je Bereich', 'Leitungsrechte gelten nur in gemeinsamen Bereichen.')}${this.columnHeader('Peer-fähig', 'Gleichrangige dürfen nach Freigabe gegenseitig bearbeiten.')}${this.columnHeader('Leitungsblock', 'Gemeinsamer Block für Geschäftsführung, Leitungen und Stabsstellen.')}${this.columnHeader('Einzelposition', 'Genau eine Person; bei Bereichsrollen je Bereich.')}<th scope="col">Reihenfolge</th></tr></thead><tbody data-sort-list="roles">${roles.map(([key, role]) => this.roleRow(key, role)).join('')}</tbody></table></div>
                <div class="orgs-table-wrap"><table class="orgs-table"><caption>Bürobereiche</caption><thead><tr><th>Bereich</th><th>Gruppen-ID</th><th>Anzeigename</th><th>Reihenfolge</th></tr></thead><tbody data-sort-list="areas">${areas.map(([key, area]) => this.areaRow(key, area)).join('')}</tbody></table></div>
                <fieldset class="orgs-hierarchy"><legend>Direkte Hierarchie</legend>
                    <p>Ziehe eine unterstellte Rolle am Rollengriff auf die Karte ihrer Leitung. Der separate waagerechte Positionsgriff ordnet Karten derselben Ebene rein visuell von links nach rechts; dafür stehen zusätzlich Pfeiltasten bereit. Verbindungen zwischen Bereichsrollen gelten automatisch in jedem jeweils gleichen Bereich.</p>
                    <div class="orgs-hierarchy-toolbar" aria-label="Hierarchieverbindung per Tastatur anlegen"><label>Leitung <select data-hierarchy-manager>${this.roleOptions(roles)}</select></label><label>Unterstellte Rolle <select data-hierarchy-target>${this.roleOptions(roles)}</select></label><button type="button" data-action="add-edge">Zuordnen</button></div>
                    <p class="orgs-feedback" data-hierarchy-feedback role="status" aria-live="polite"></p>${this.exporter.markup()}<div class="orgs-organigram" data-hierarchy-board></div>
                </fieldset>
                <div class="orgs-table-wrap"><table class="orgs-table"><caption>Teamansichten im Urlaubsplaner</caption><thead><tr><th>ID</th><th>Anzeigename</th><th>Rollen</th><th>Bereiche</th><th>Reihenfolge</th><th>Aktion</th></tr></thead><tbody data-organization-teams>${(data.organizationTeams || []).map(team => this.teamRow(team)).join('')}</tbody></table></div>
                <button type="button" data-action="add-team">Urlaubsansicht hinzufügen</button>`;
            this.hierarchyBoard.set(data.roles, data.hierarchy || {}, data.areas, this.positions, data.diagramOrder || []);
            this.updateSortControls('roles');
            this.updateSortControls('areas');
        }

        roleRow(key, role) {
            const check = (field, label) => `<input data-field="${field}" type="checkbox" ${role[field] ? 'checked' : ''} aria-label="${esc(label)}">`;
            return `<tr data-role-key="${esc(key)}" data-sort-key="${esc(key)}" data-sort-kind="roles"><th scope="row"><code>${esc(key)}</code></th><td><input data-field="groupId" value="${esc(role.groupId)}" aria-label="Gruppen-ID ${esc(role.label)}" required></td><td><input data-field="label" value="${esc(role.label)}" aria-label="Anzeigename ${esc(key)}" required></td><td>${check('calendarVisible', `${role.label} im Kalender sichtbar`)}</td><td>${check('areaScoped', `${role.label} ist bereichsgebunden`)}</td><td>${check('managementAreaScoped', `Leitungsrecht von ${role.label} ist bereichsgebunden`)}</td><td>${check('peerEnabled', `Peer-Recht für ${role.label} konfigurierbar`)}</td><td>${check('staffBlock', `${role.label} im Leitungsblock`)}</td><td>${check('singleOccupant', `${role.label} ist eine Einzelposition`)}</td>${this.sortCell(role.label, role.sortOrder)}</tr>`;
        }

        columnHeader(label, help) { return `<th scope="col"><span>${esc(label)}</span><span class="orgs-column-help">${esc(help)}</span></th>`; }

        areaRow(key, area) { return `<tr data-area-key="${esc(key)}" data-sort-key="${esc(key)}" data-sort-kind="areas"><th scope="row"><code>${esc(key)}</code></th><td><input data-field="groupId" value="${esc(area.groupId)}" aria-label="Gruppen-ID ${esc(area.label)}" required></td><td><input data-field="label" value="${esc(area.label)}" aria-label="Anzeigename ${esc(key)}" required></td>${this.sortCell(area.label, area.sortOrder)}</tr>`; }
        sortCell(label, sortOrder) {
            return `<td class="orgs-sort-cell"><input data-field="sortOrder" type="hidden" value="${esc(sortOrder)}"><button type="button" class="orgs-sort-handle" draggable="true" data-sort-handle aria-label="${esc(label)} per Drag-and-drop sortieren"><span aria-hidden="true">⠿</span></button><button type="button" data-action="move-up" aria-label="${esc(label)} nach oben verschieben">↑</button><button type="button" data-action="move-down" aria-label="${esc(label)} nach unten verschieben">↓</button></td>`;
        }
        roleOptions(roles) { return roles.map(([key, role]) => `<option value="${esc(key)}">${esc(role.label)}</option>`).join(''); }

        collect() {
            this.syncSortOrders('roles');
            this.syncSortOrders('areas');
            const data = clone(this.definition);
            for (const field of ['teamGroupPrefix', 'teamLabelPrefix', 'staffBlockLabel']) data[field] = this.container.querySelector(`[data-organization-field="${field}"]`).value.trim();
            data.teamCodeMaxLength = Number(this.container.querySelector('[data-organization-field="teamCodeMaxLength"]').value);
            this.container.querySelectorAll('[data-role-key]').forEach(row => {
                const role = data.roles[row.dataset.roleKey];
                for (const field of ['groupId', 'label']) role[field] = row.querySelector(`[data-field="${field}"]`).value.trim();
                for (const field of ['calendarVisible', 'areaScoped', 'managementAreaScoped', 'peerEnabled', 'staffBlock', 'singleOccupant']) role[field] = row.querySelector(`[data-field="${field}"]`).checked;
                role.sortOrder = Number(row.querySelector('[data-field="sortOrder"]').value);
            });
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
            const label = row.querySelector('[data-field="label"]')?.value || row.dataset.sortKey;
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
            this.container.querySelector('[data-organization-teams]').insertAdjacentHTML('beforeend', this.teamRow({ id: `view-${number}`, label: 'Neue Urlaubsansicht', roles: [], areas: [], sortOrder }));
        }

        teamRow(team) { return `<tr data-organization-team><td><input data-field="id" value="${esc(team.id)}" aria-label="ID der Urlaubsansicht" required pattern="[a-z][a-z0-9_-]*"></td><td><input data-field="label" value="${esc(team.label)}" aria-label="Anzeigename der Urlaubsansicht ${esc(team.id)}" required></td><td><input data-field="roles" value="${esc((team.roles || []).join(', '))}" aria-label="Rollenschlüssel der Urlaubsansicht ${esc(team.id)}" required></td><td><input data-field="areas" value="${esc((team.areas || []).join(', '))}" aria-label="Bereichsschlüssel der Urlaubsansicht ${esc(team.id)}"></td><td><input data-field="sortOrder" type="number" value="${esc(team.sortOrder)}" aria-label="Reihenfolge der Urlaubsansicht ${esc(team.id)}"></td><td><button type="button" data-action="remove-team" aria-label="Urlaubsansicht ${esc(team.label)} entfernen">Entfernen</button></td></tr>`; }
        list(value) { return [...new Set(value.split(',').map(item => item.trim()).filter(Boolean))]; }
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.components = window.LocalBase.components || {};
    window.LocalBase.components.OrganizationEditor = OrganizationEditor;
})();
