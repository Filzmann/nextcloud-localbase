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
            this.hierarchyBoard = new window.LocalBase.components.HierarchyBoard({ container });
            form.addEventListener('submit', event => { event.preventDefault(); if (this.definition) this.onSave(this.collect()); });
            container.addEventListener('click', event => this.onClick(event));
        }

        set(definition) { this.definition = clone(definition); this.render(); }

        onClick(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
            if (!button) return;
            if (button.dataset.action === 'add-team') this.addTeam();
            if (button.dataset.action === 'remove-team') button.closest('tr')?.remove();
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
                <div class="orgs-table-wrap"><table class="orgs-table"><caption>Fachrollen und Nextcloud-Gruppen</caption><thead><tr><th>Rolle</th><th>Gruppen-ID</th><th>Anzeigename</th><th>Kalender</th><th>Bereich</th><th>Leitung je Bereich</th><th>Peer-fähig</th><th>Leitungsblock</th><th>Reihenfolge</th></tr></thead><tbody>${roles.map(([key, role]) => this.roleRow(key, role)).join('')}</tbody></table></div>
                <div class="orgs-table-wrap"><table class="orgs-table"><caption>Bürobereiche</caption><thead><tr><th>Bereich</th><th>Gruppen-ID</th><th>Anzeigename</th><th>Reihenfolge</th></tr></thead><tbody>${areas.map(([key, area]) => this.areaRow(key, area)).join('')}</tbody></table></div>
                <fieldset class="orgs-hierarchy"><legend>Direkte Hierarchie</legend>
                    <p>Ziehe eine unterstellte Rolle auf die Karte ihrer Leitung. Für die Tastatur steht dieselbe Zuordnung über die Auswahlfelder bereit.</p>
                    <div class="orgs-hierarchy-toolbar" aria-label="Hierarchieverbindung per Tastatur anlegen"><label>Leitung <select data-hierarchy-manager>${this.roleOptions(roles)}</select></label><label>Unterstellte Rolle <select data-hierarchy-target>${this.roleOptions(roles)}</select></label><button type="button" data-action="add-edge">Zuordnen</button></div>
                    <p class="orgs-feedback" data-hierarchy-feedback role="status" aria-live="polite"></p><div class="orgs-organigram" data-hierarchy-board></div>
                </fieldset>
                <div class="orgs-table-wrap"><table class="orgs-table"><caption>Teamansichten im Urlaubsplaner</caption><thead><tr><th>ID</th><th>Anzeigename</th><th>Rollen</th><th>Bereiche</th><th>Reihenfolge</th><th>Aktion</th></tr></thead><tbody data-organization-teams>${(data.organizationTeams || []).map(team => this.teamRow(team)).join('')}</tbody></table></div>
                <button type="button" data-action="add-team">Urlaubsansicht hinzufügen</button>`;
            this.hierarchyBoard.set(data.roles, data.hierarchy || {});
        }

        roleRow(key, role) {
            const check = (field, label) => `<input data-field="${field}" type="checkbox" ${role[field] ? 'checked' : ''} aria-label="${esc(label)}">`;
            return `<tr data-role-key="${esc(key)}"><th scope="row"><code>${esc(key)}</code></th><td><input data-field="groupId" value="${esc(role.groupId)}" aria-label="Gruppen-ID ${esc(role.label)}" required></td><td><input data-field="label" value="${esc(role.label)}" aria-label="Anzeigename ${esc(key)}" required></td><td>${check('calendarVisible', `${role.label} im Kalender sichtbar`)}</td><td>${check('areaScoped', `${role.label} ist bereichsgebunden`)}</td><td>${check('managementAreaScoped', `Leitungsrecht von ${role.label} ist bereichsgebunden`)}</td><td>${check('peerEnabled', `Peer-Recht für ${role.label} konfigurierbar`)}</td><td>${check('staffBlock', `${role.label} im Leitungsblock`)}</td><td><input data-field="sortOrder" type="number" value="${esc(role.sortOrder)}" aria-label="Reihenfolge ${esc(role.label)}"></td></tr>`;
        }

        areaRow(key, area) { return `<tr data-area-key="${esc(key)}"><th scope="row"><code>${esc(key)}</code></th><td><input data-field="groupId" value="${esc(area.groupId)}" aria-label="Gruppen-ID ${esc(area.label)}" required></td><td><input data-field="label" value="${esc(area.label)}" aria-label="Anzeigename ${esc(key)}" required></td><td><input data-field="sortOrder" type="number" value="${esc(area.sortOrder)}" aria-label="Reihenfolge ${esc(area.label)}"></td></tr>`; }
        roleOptions(roles) { return roles.map(([key, role]) => `<option value="${esc(key)}">${esc(role.label)}</option>`).join(''); }

        collect() {
            const data = clone(this.definition);
            for (const field of ['teamGroupPrefix', 'teamLabelPrefix', 'staffBlockLabel']) data[field] = this.container.querySelector(`[data-organization-field="${field}"]`).value.trim();
            data.teamCodeMaxLength = Number(this.container.querySelector('[data-organization-field="teamCodeMaxLength"]').value);
            this.container.querySelectorAll('[data-role-key]').forEach(row => {
                const role = data.roles[row.dataset.roleKey];
                for (const field of ['groupId', 'label']) role[field] = row.querySelector(`[data-field="${field}"]`).value.trim();
                for (const field of ['calendarVisible', 'areaScoped', 'managementAreaScoped', 'peerEnabled', 'staffBlock']) role[field] = row.querySelector(`[data-field="${field}"]`).checked;
                role.sortOrder = Number(row.querySelector('[data-field="sortOrder"]').value);
            });
            this.container.querySelectorAll('[data-area-key]').forEach(row => {
                const area = data.areas[row.dataset.areaKey];
                for (const field of ['groupId', 'label']) area[field] = row.querySelector(`[data-field="${field}"]`).value.trim();
                area.sortOrder = Number(row.querySelector('[data-field="sortOrder"]').value);
            });
            data.hierarchy = this.hierarchyBoard.get();
            data.organizationTeams = [...this.container.querySelectorAll('[data-organization-team]')].map(row => ({ id: row.querySelector('[data-field="id"]').value.trim(), label: row.querySelector('[data-field="label"]').value.trim(), roles: this.list(row.querySelector('[data-field="roles"]').value), areas: this.list(row.querySelector('[data-field="areas"]').value), sortOrder: Number(row.querySelector('[data-field="sortOrder"]').value) }));
            return data;
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
