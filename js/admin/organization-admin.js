(function() {
    'use strict';

    const client = new window.LocalBase.api.ApiClient({
        appId: 'localbase',
        errorMessage: (data, status) => data?.error || `HTTP ${status}`,
    });
    const notice = new window.LocalBase.ui.Notice('orgs-admin-notice', { baseClass: 'orgs-notice', typeClassPrefix: 'orgs-notice--' });
    const organizationForm = document.getElementById('orgs-organization-form');
    const permissionsForm = document.getElementById('orgs-permissions-form');
    const dashboard = new window.LocalBase.components.OrganizationDashboard({
        root: document.getElementById('orgsuite-admin'),
        onChange: saveDashboardLayout,
    });
    let layoutSave = Promise.resolve();
    const editor = new window.LocalBase.components.OrganizationEditor({
        container: document.getElementById('orgs-organization-editor'),
        form: organizationForm,
        onSave: saveOrganization,
    });

    function renderCheckboxes(containerId, values, options) {
        const container = document.getElementById(containerId);
        const labels = new Map((options || []).map(option => [option.groupId, option.label]));
        container.replaceChildren(...Object.entries(values || {}).map(([groupId, enabled]) => {
            const label = document.createElement('label');
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = groupId;
            input.checked = Boolean(enabled);
            label.append(input, document.createTextNode(` ${labels.get(groupId) || groupId}`));
            return label;
        }));
    }

    function collect(containerId) {
        return Object.fromEntries([...document.getElementById(containerId).querySelectorAll('input[type="checkbox"]')].map(input => [input.name, input.checked]));
    }

    function renderDirectoryStatus(directory) {
        const status = document.getElementById('orgs-directory-status');
        const groups = document.getElementById('orgs-directory-groups');
        const summary = directory?.compatible
            ? 'Alle konfigurierten Gruppen sind über Nextcloud erreichbar.'
            : 'Mindestens eine konfigurierte Gruppe fehlt in Nextcloud.';
        const demo = directory?.demoWritable
            ? ' Organisations-Demo-Packs dürfen Mitgliedschaften vorbereiten.'
            : ' Read-only Gruppen bleiben produktiv nutzbar, können aber nicht durch Demo-Packs verändert werden.';
        status.textContent = summary + demo;
        groups.replaceChildren(...(directory?.groups || []).map(item => {
            const row = document.createElement('tr');
            const values = [
                item.kind === 'role' ? 'Rolle' : 'Bereich',
                item.label,
                item.groupId,
                item.exists ? (item.backendNames || []).join(', ') || 'Nextcloud' : '–',
                !item.exists ? 'Fehlt' : item.membershipWritable ? 'Vorhanden, beschreibbar' : 'Vorhanden, read-only',
            ];
            values.forEach((value, index) => {
                const cell = document.createElement(index === 1 ? 'th' : 'td');
                if (index === 1) cell.scope = 'row';
                cell.textContent = value;
                row.append(cell);
            });
            return row;
        }));
    }

    async function load() {
        try {
            const data = await client.request('/api/ad-suite/admin/settings');
            editor.set(data.organization, data.directory?.positions || []);
            renderCheckboxes('orgs-calendar-peer-settings', data.calendarPeerEditing, data.calendarPeerOptions);
            renderCheckboxes('orgs-vacation-peer-settings', data.vacationPeerApproval, data.vacationPeerOptions);
            renderDirectoryStatus(data.directory);
            dashboard.set(data.dashboardLayout);
            notice.clear();
        } catch (error) {
            notice.error(error);
            organizationForm.querySelector('button[type="submit"]').disabled = true;
            permissionsForm.querySelector('button[type="submit"]').disabled = true;
        }
    }

    async function saveOrganization(organization) {
        try {
            await client.request('/api/ad-suite/admin/organization', { method: 'PUT', body: JSON.stringify({ organization }) });
            await load();
            notice.success('AD-Organisation gespeichert.');
        } catch (error) {
            notice.error(error);
        }
    }

    function saveDashboardLayout(layout) {
        layoutSave = layoutSave.then(async () => {
            try {
                await client.request('/api/ad-suite/admin/layout', { method: 'PUT', body: JSON.stringify({ layout }) });
            } catch (error) {
                notice.error(error);
            }
        });
    }

    permissionsForm.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            await client.request('/api/ad-suite/admin/permissions', {
                method: 'PUT',
                body: JSON.stringify({
                    calendarPeerEditing: collect('orgs-calendar-peer-settings'),
                    vacationPeerApproval: collect('orgs-vacation-peer-settings'),
                }),
            });
            await load();
            notice.success('Organisationsweite Rechte gespeichert.');
        } catch (error) {
            notice.error(error);
        }
    });

    load();
})();
