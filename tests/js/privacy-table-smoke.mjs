import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const sourceUrl = new URL('../../js/privacy/privacy-report.js', import.meta.url);
const source = readFileSync(sourceUrl, 'utf8');
class Node {
    constructor(name = '') { this.name = name; this.children = []; this.dataset = {}; this.textContent = ''; this.className = ''; this.disabled = false; }
    append(...nodes) { this.children.push(...nodes); }
    replaceChildren(...nodes) { this.children = [...nodes]; }
    addEventListener() {}
    focus() {}
}
const nodes = new Map();
for (const id of ['localbase-privacy','lb-privacy-status','lb-privacy-completeness','lb-privacy-apps','lb-privacy-article15','lb-privacy-download']) nodes.set(id, new Node());
nodes.get('localbase-privacy').dataset.privacyMode = 'self';
const document = {
    getElementById: id => nodes.get(id) || null,
    createElement: name => new Node(name),
    createTextNode: text => Object.assign(new Node('#text'), { textContent: String(text) }),
};
const report = {
    complete: true,
    coverage: {
        scopeNotice: 'Dieser Download umfasst noch nicht die gesamte Nextcloud-Instanz.',
        notImplemented: ['Dateien und Freigaben', 'Talk und weitere Apps'],
    },
    article15: { rights: [], contactNote: '' },
    providers: [{ name: 'AD Raumplaner', status: 'complete', processing: {}, items: [
        { category:'booking', dataType:'Raumbuchung', label:'Raumbuchung', summary:'12. August', attributes:{Raum:'A',Titel:'Termin 1'}, purpose:'Raumplanung', retention:'Bis 1. September', sectionTitle:'Buchungen' },
        { category:'booking', dataType:'Raumbuchung', label:'Raumbuchung', summary:'13. August', attributes:{Raum:'B',Titel:'Termin 2'}, purpose:'Raumplanung', retention:'Bis 1. September', sectionTitle:'Buchungen' },
    ] }, { name: 'Gemischte App', status: 'complete', processing: {}, items: [
        { dataType:'Gemischter Typ', summary:'A', attributes:{Feld:'Wert A'}, purpose:'Grund A', retention:'Frist A' },
        { dataType:'Gemischter Typ', summary:'B', attributes:{Feld:'Wert B'}, purpose:'Grund B', retention:'Frist B' },
    ] }],
};
const window = { LocalBase: { api: { ApiClient: class { async request() { return report; } } }, privacy: { PrivacyReportDownload: { download() {} } } } };
vm.runInNewContext(source, { window, document, console, encodeURIComponent, setTimeout }, { filename: fileURLToPath(sourceUrl) });
await new Promise(resolve => setTimeout(resolve, 0));

const flatten = node => [node.textContent, ...node.children.flatMap(flatten)].filter(Boolean);
const firstCardText = flatten(nodes.get('lb-privacy-apps').children[0]);
for (const expected of ['AD Raumplaner','Weitere Angaben zur Verarbeitung in dieser App','Grund der Speicherung: Raumplanung','Aufbewahrt bis: Bis 1. September','Raumbuchung','Raum','Titel','A','Termin 1']) {
    if (!firstCardText.includes(expected)) throw new Error(`Tabellarische Auskunft fehlt: ${expected}`);
}
const pageText = flatten(nodes.get('lb-privacy-completeness'));
for (const expected of ['Dieser Download umfasst noch nicht die gesamte Nextcloud-Instanz.', 'Aktuell noch nicht implementierte Datenabrufe', 'Dateien und Freigaben', 'Talk und weitere Apps']) {
    if (!pageText.includes(expected)) throw new Error(`Vollständigkeitshinweis fehlt: ${expected}`);
}
for (const obsolete of ['Datentyp','Datensatz','Grund der Speicherung','Aufbewahrt bis']) if (firstCardText.filter(value => value === obsolete).length) throw new Error(`Vorgezogene oder redundante Tabellenspalte ist noch vorhanden: ${obsolete}`);
const secondCardText = flatten(nodes.get('lb-privacy-apps').children[1]);
for (const expected of ['Feld','Grund der Speicherung','Aufbewahrt bis','Grund A','Grund B','Frist A','Frist B']) {
    if (!secondCardText.includes(expected)) throw new Error(`Unterschiedliche Tabellenwerte fehlen: ${expected}`);
}
console.log('LocalBase privacy table smoke passed');
