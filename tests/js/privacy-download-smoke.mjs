import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const sourceUrl = new URL('../../js/privacy/privacy-download.js', import.meta.url);
const source = readFileSync(sourceUrl, 'utf8');
const actions = [];
class FakeBlob { constructor(parts, options) { this.parts = parts; this.type = options.type; } }
const document = {
    body: { append(node) { actions.push(['append', node]); } },
    createElement(name) {
        if (name !== 'a') throw new Error('Nur ein Download-Link ist erlaubt.');
        return { click() { actions.push(['click']); }, remove() { actions.push(['remove']); } };
    },
};
const FakeURL = {
    createObjectURL(blob) { actions.push(['create', blob]); return 'blob:report'; },
    revokeObjectURL(url) { actions.push(['revoke', url]); },
};
const window = {};
vm.runInNewContext(source, { window, document, URL: FakeURL, Blob: FakeBlob }, { filename: fileURLToPath(sourceUrl) });

const report = {
    subject: { id: 'user-17' },
    coverage: {
        scopeNotice: 'Dieser Download umfasst noch nicht die gesamte Nextcloud-Instanz.',
        notImplemented: ['Dateien und Freigaben', 'Talk und weitere Apps'],
    },
    article15: {
        generatedAt: '2026-08-12T10:00:00+00:00',
        rights: ['Berichtigung verlangen', 'Beschwerde einreichen'],
        contactNote: 'Bitte wende dich an die Datenschutzstelle.',
    },
    providers: [{
        appId: 'adroom',
        status: 'complete',
        processing: {
            recipients: ['Keine regelmäßige Weitergabe.'],
            source: 'Von dir bei der Buchung angegeben.',
            thirdCountryTransfers: 'Keine.',
            automatedDecisionMaking: 'Keine.',
        },
        items: [{
            dataType: 'Raumbuchung',
            sectionTitle: 'Folgende Raumbuchungen sind mit deinen Daten gespeichert:',
            summary: '12. August 2026, 10:00–11:00 Uhr – Besprechung 1',
            purpose: 'Organisation der Raumnutzung',
            retention: 'Bis 11. September 2026, danach administrative Prüfung',
            attributes: { Titel: 'Gemeinsamer Termin' },
            thirdPartyNote: 'An diesem Termin waren weitere Personen beteiligt. Ihre Namen werden hier nicht genannt.',
        }],
    }],
};

const pdf = window.LocalBase.privacy.PrivacyReportDownload.buildPdf(report);
for (const expected of ['%PDF-1.4', 'Auskunft zu deinen personenbezogenen Daten', 'Erstellt am 12.08.26', 'Weitere Angaben zur Verarbeitung in dieser App', 'Raumbuchung', 'Titel', 'Grund der Speicherung:', 'Aufbewahrt bis:', 'weitere Personen beteiligt', 'Deine Rechte', 'Dieser Download umfasst noch nicht die gesamte Nextcloud-Instanz.', 'Aktuell noch nicht implementierte Datenabrufe', 'Dateien und Freigaben', 'Talk und weitere Apps']) {
    if (!pdf.includes(expected)) throw new Error(`Menschenlesbarer PDF-Inhalt fehlt: ${expected}`);
}
for (const obsolete of ['Datentyp', 'Datensatz']) if (pdf.includes(obsolete)) throw new Error(`PDF enthält noch die redundante Tabellenspalte ${obsolete}.`);
if ((pdf.match(/ re [BS]/g) || []).length < 3) throw new Error('PDF zeichnet keine echten Tabellenzellen.');
if (pdf.match(/Organisation der Raumnutzung/g)?.length !== 1 || pdf.match(/Bis 11\. September 2026/g)?.length !== 1) throw new Error('Appweit identische Werte werden im PDF nicht platzsparend vor die Tabelle gezogen.');
if (pdf.includes('passwordHash') || pdf.includes('Andere Person')) throw new Error('PDF enthält geheime oder fremde personenbezogene Angaben.');
const xrefOffset = Number(pdf.match(/startxref\n(\d+)/)?.[1]);
if (!pdf.slice(xrefOffset).startsWith('xref')) throw new Error('PDF besitzt keine gültige Querverweistabelle.');

const longReport = JSON.parse(JSON.stringify(report));
longReport.providers[0].items = Array.from({ length: 180 }, (_, index) => ({
    ...report.providers[0].items[0],
    attributes: { Titel: `Datensatz ${index + 1} mit einem ausreichend langen menschenlesbaren Titel für den Seitenumbruch` },
}));
const longPdf = window.LocalBase.privacy.PrivacyReportDownload.buildPdf(longReport);
const pageCount = Number(longPdf.match(/\/Type \/Pages \/Kids \[[^\]]+\] \/Count (\d+)/)?.[1]);
if (pageCount < 2 || !longPdf.includes('Datensatz 180')) throw new Error('Lange Auskunft wird nicht vollständig über mehrere PDF-Seiten umbrochen.');

window.LocalBase.privacy.PrivacyReportDownload.download(report);
const anchor = actions.find(([name]) => name === 'append')[1];
if (anchor.href !== 'blob:report' || anchor.download !== 'datenauskunft-12.08.26.pdf') throw new Error('PDF besitzt keinen lokal formatierten stabilen Dateinamen oder Blob-Link.');
const blob = actions.find(([name]) => name === 'create')[1];
if (blob.type !== 'application/pdf' || !blob.parts.join('').startsWith('%PDF-1.4')) throw new Error('Download enthält kein PDF.');
if (!actions.some(([name]) => name === 'click') || !actions.some(([name]) => name === 'revoke')) throw new Error('Download wird nicht ausgelöst oder Blob-Link nicht freigegeben.');

for (const invalid of [null, {}, { providers: [] }]) {
    try {
        window.LocalBase.privacy.PrivacyReportDownload.download(invalid);
        throw new Error('Unvollständiger Bericht wurde heruntergeladen.');
    } catch (error) {
        if (error.message === 'Unvollständiger Bericht wurde heruntergeladen.') throw error;
    }
}

console.log('LocalBase privacy PDF download smoke passed');
