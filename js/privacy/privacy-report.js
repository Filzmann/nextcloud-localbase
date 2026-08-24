(function() {
    'use strict';

    const root = document.getElementById('localbase-privacy');
    if (!root) return;
    const client = new window.LocalBase.api.ApiClient({ appId: 'localbase' });
    const status = document.getElementById('lb-privacy-status');
    const completeness = document.getElementById('lb-privacy-completeness');
    const apps = document.getElementById('lb-privacy-apps');
    const article15 = document.getElementById('lb-privacy-article15');
    const download = document.getElementById('lb-privacy-download');
    let downloadableReport = null;

    function element(name, text, className = '') {
        const node = document.createElement(name);
        node.textContent = String(text ?? '');
        if (className) node.className = className;
        return node;
    }

    function sharedValue(items, field) {
        if (!items.length) return null;
        const first = String(items[0]?.[field] || '').trim();
        return first !== '' && items.every(item => String(item?.[field] || '').trim() === first) ? first : null;
    }

    function appendProcessing(card, processing) {
        if (!processing) return;
        const section = document.createElement('section');
        section.className = 'lb-privacy-processing';
        section.append(element('h4', 'Weitere Angaben zur Verarbeitung in dieser App'));
        const list = document.createElement('ul');
        for (const [label, value] of [
            ['Empfänger*innen', processing.recipients],
            ['Herkunft der Daten', processing.source],
            ['Drittlandübermittlung', processing.thirdCountryTransfers],
            ['Automatisierte Entscheidungen', processing.automatedDecisionMaking],
        ]) {
            const item = document.createElement('li');
            item.append(element('strong', label + ': '), document.createTextNode(Array.isArray(value) ? value.join('; ') : String(value || '')));
            list.append(item);
        }
        section.append(list);
        card.append(section);
    }

    function appendItems(card, provider) {
        const items = provider.items || [];
        if (!items.length) {
            card.append(element('p', provider.status === 'failed' ? 'Diese App konnte für die Auskunft nicht erreicht werden.' : 'Für dich sind in dieser App keine Datensätze vorhanden.', 'lb-privacy-empty'));
            return;
        }
        const groups = new Map();
        for (const item of items) {
            const type = item.dataType || item.label;
            if (!groups.has(type)) groups.set(type, []);
            groups.get(type).push(item);
        }
        for (const [type, typeItems] of groups) {
            const section = document.createElement('section');
            section.className = 'lb-privacy-data-type';
            section.append(element('h4', type));
            const purpose = sharedValue(typeItems, 'purpose');
            const retention = sharedValue(typeItems, 'retention');
            const common = document.createElement('div');
            common.className = 'lb-privacy-common';
            if (purpose) common.append(element('p', 'Grund der Speicherung: ' + purpose));
            if (retention) common.append(element('p', 'Aufbewahrt bis: ' + retention));
            if (common.children.length) section.append(common);

            const fields = [];
            for (const item of typeItems) for (const field of Object.keys(item.attributes || {})) if (!fields.includes(field)) fields.push(field);
            const hasNote = typeItems.some(item => item.thirdPartyNote);
            const headers = [...fields, ...(hasNote ? ['Hinweis'] : []), ...(!purpose ? ['Grund der Speicherung'] : []), ...(!retention ? ['Aufbewahrt bis'] : [])];
            const wrap = document.createElement('div');
            wrap.className = 'lb-privacy-table-wrap';
            const table = document.createElement('table');
            const caption = element('caption', `${type}: gespeicherte Datenfelder`);
            const head = document.createElement('thead');
            const headRow = document.createElement('tr');
            for (const label of headers) { const th = element('th', label); th.scope = 'col'; headRow.append(th); }
            head.append(headRow);
            const body = document.createElement('tbody');
            for (const item of typeItems) {
                const row = document.createElement('tr');
                for (const field of fields) row.append(element('td', item.attributes?.[field] ?? '—'));
                if (hasNote) row.append(element('td', item.thirdPartyNote || '—', item.thirdPartyNote ? 'lb-privacy-third-party' : ''));
                if (!purpose) row.append(element('td', item.purpose));
                if (!retention) row.append(element('td', item.retention));
                body.append(row);
            }
            table.append(caption, head, body); wrap.append(table); section.append(wrap); card.append(section);
        }
    }

    function render(report, retention = false) {
        apps.replaceChildren(); article15.replaceChildren();
        completeness.replaceChildren(element('p', report.complete ? 'Die beteiligten Apps haben vollständig geantwortet.' : 'Die Auskunft ist unvollständig. Hinweise stehen bei der betroffenen App.'));
        if (!retention) {
            downloadableReport = report; download.disabled = false;
            article15.append(element('p', 'Du kannst insbesondere Berichtigung verlangen sowie – soweit die gesetzlichen Voraussetzungen vorliegen – Löschung, Einschränkung oder Widerspruch. Du kannst dich außerdem bei einer Datenschutzaufsichtsbehörde beschweren.'));
            article15.append(element('p', report.article15?.contactNote || ''));
            if (report.coverage) {
                const coverage = document.createElement('section');
                coverage.className = 'lb-privacy-coverage';
                coverage.append(element('h3', 'Umfang dieser Auskunft'));
                coverage.append(element('p', report.coverage.scopeNotice || ''));
                coverage.append(element('h4', 'Aktuell noch nicht implementierte Datenabrufe'));
                const missing = document.createElement('ul');
                for (const source of report.coverage.notImplemented || []) missing.append(element('li', source));
                coverage.append(missing);
                completeness.append(coverage);
            }
        }
        for (const provider of report.providers || []) {
            const card = document.createElement('article');
            card.className = 'lb-privacy-app-card';
            card.append(element('h3', provider.name || provider.appId));
            if (retention) {
                const candidates = provider.candidates || [];
                card.append(element('p', candidates.length === 0 ? 'Keine Datensätze für eine administrative Prüfung vorgemerkt.' : `${candidates.length} Datensatz/Datensätze sind zur administrativen Prüfung vorgemerkt.`));
            } else { appendProcessing(card, provider.processing); appendItems(card, provider); }
            apps.append(card);
        }
        status.textContent = retention ? 'Dry Run geladen; es wurden keine Daten verändert.' : 'Auskunft geladen.';
    }

    async function load(path, retention = false) {
        status.textContent = 'Daten werden geladen.';
        if (!retention) { downloadableReport = null; download.disabled = true; }
        try { render(await client.request(path), retention); }
        catch (error) { apps.replaceChildren(); completeness.replaceChildren(); status.textContent = error?.message || 'Die Auskunft konnte nicht geladen werden.'; status.focus(); }
    }

    download.addEventListener('click', () => { if (downloadableReport) window.LocalBase.privacy.PrivacyReportDownload.download(downloadableReport); });
    if (root.dataset.privacyMode === 'self') { void load('/api/privacy/self'); return; }
    const form = document.getElementById('lb-privacy-subject-form');
    const subject = document.getElementById('lb-privacy-subject');
    form.addEventListener('submit', event => { event.preventDefault(); void load(`/api/privacy/admin/${encodeURIComponent(subject.value.trim())}`); });
    document.getElementById('lb-privacy-retention').addEventListener('click', () => { if (subject.reportValidity()) void load(`/api/privacy/admin/${encodeURIComponent(subject.value.trim())}/retention-preview`, true); });
}());
