import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const boardSource = readFileSync(new URL('../../js/components/hierarchy-board.js', import.meta.url), 'utf8');
const exporterSource = readFileSync(new URL('../../js/components/organization-exporter.js', import.meta.url), 'utf8');
const editorSource = readFileSync(new URL('../../js/components/organization-editor.js', import.meta.url), 'utf8');
const template = readFileSync(new URL('../../templates/organization-admin.php', import.meta.url), 'utf8');

for (const contract of ['class OrganizationExporter', 'Draw.io', 'PNG', 'PDF', 'Zugeordnete Nutzer*innen einbeziehen', 'data-organization-export-people', 'canvas.toBlob', 'window.open', 'downloadBlob(', 'toDrawio(', 'toSvg(', 'printHtml(']) {
    if (!exporterSource.includes(contract)) throw new Error(`Organigramm-Exportvertrag fehlt: ${contract}`);
}
if (!boardSource.includes('exportSnapshot(includePeople = false)')) throw new Error('Das Organigramm stellt keinen datensparsamen Export-Snapshot bereit.');
if (!editorSource.includes('this.exporter.markup()') || !template.includes("components/organization-exporter")) throw new Error('Der Export ist nicht in den gemeinsamen Organisationseditor eingebunden.');

const context = {
    window: { LocalBase: { ui: { esc: value => String(value ?? '') } } },
    JSON, Set, Map, Math, Object, Element: class {}, Blob: class {}, URL: {}, Image: class {}, XMLSerializer: class {},
};
runInNewContext(boardSource, context);
runInNewContext(exporterSource, context);

const board = Object.create(context.window.LocalBase.components.HierarchyBoard.prototype);
board.roles = {
    gf: { label: 'Geschäftsführung & Organisation', sortOrder: 10, areaScoped: false, singleOccupant: true },
    office: { label: 'Büro', sortOrder: 20, areaScoped: true, singleOccupant: false },
};
board.areas = { west: { label: 'West', sortOrder: 10 }, south: { label: 'Süd', sortOrder: 20 } };
board.hierarchy = { gf: ['office'] };
board.diagramOrder = ['gf', 'office::south', 'office::west'];
board.positions = [{ roleKey: 'gf', areaKey: null, displayNames: ['Gina Führung'] }];

const anonymousSnapshot = board.exportSnapshot(false);
if (JSON.stringify(anonymousSnapshot).includes('Gina Führung')) throw new Error('Der Standardexport enthält trotz deaktivierter Namensoption personenbezogene Daten.');
const namedSnapshot = board.exportSnapshot(true);
if (!JSON.stringify(namedSnapshot).includes('Gina Führung')) throw new Error('Der ausdrücklich aktivierte Export enthält die zugeordnete Einzelperson nicht.');

const exporter = Object.create(context.window.LocalBase.components.OrganizationExporter.prototype);
const exportMarkup = exporter.markup();
if (!exportMarkup.includes('role="group"') || /data-organization-export-people[^>]*checked/.test(exportMarkup)) throw new Error('Die Exportauswahl ist nicht als zugängliche Gruppe oder nicht datensparsam vorbelegt.');
const layout = exporter.layout(namedSnapshot);
const top = layout.nodes.find(node => node.id === 'gf');
const officeNodes = layout.nodes.filter(node => node.roleKey === 'office');
if (!top || officeNodes.length !== 2 || top.x <= 0 || top.x === officeNodes[0].x) throw new Error('Der Export bildet Hierarchieebenen und die verteilte Links-rechts-Anordnung nicht ab.');

const drawio = exporter.toDrawio(layout);
if (!drawio.includes('<mxGraphModel') || !drawio.includes('vertex="1"') || !drawio.includes('edge="1"') || !drawio.includes('source="node-') || !drawio.includes('Gina Führung')) throw new Error('Der Draw.io-Export ist nicht als bearbeitbares Diagramm aufgebaut.');
if (drawio.includes('Geschäftsführung & Organisation')) throw new Error('Der Draw.io-Export escaped XML-Sonderzeichen nicht.');

const svg = exporter.toSvg(layout);
if (!svg.includes('<svg') || !svg.includes('<rect') || !svg.includes('<path') || !svg.includes('Gina Führung') || svg.includes('Geschäftsführung & Organisation')) throw new Error('Der gemeinsame Vektorexport ist unvollständig oder unsicher escaped.');
const printable = exporter.printHtml(svg);
if (!printable.includes('@page') || !printable.includes('size: landscape') || !printable.includes(svg)) throw new Error('Die PDF-Druckansicht ist nicht auf einen skalierbaren Querformat-Export ausgelegt.');

let snapshotIncludedPeople = null;
let downloadedFilename = '';
const feedback = { textContent: '' };
const checkbox = { checked: false };
const actionButton = new context.Element();
actionButton.dataset = { action: 'export-drawio' };
actionButton.disabled = false;
actionButton.closest = () => actionButton;
const workflow = Object.create(context.window.LocalBase.components.OrganizationExporter.prototype);
workflow.container = { querySelector: selector => selector.includes('people') ? checkbox : feedback };
workflow.board = { exportSnapshot: includePeople => { snapshotIncludedPeople = includePeople; return anonymousSnapshot; } };
workflow.downloadBlob = (_blob, filename) => { downloadedFilename = filename; };
await workflow.onClick({ target: actionButton });
if (snapshotIncludedPeople !== false || downloadedFilename !== 'ad-organigramm.drawio' || actionButton.disabled || feedback.textContent !== 'Das Organigramm wurde exportiert.') throw new Error('Der Draw.io-Download respektiert den datensparsamen Standard oder den Statusvertrag nicht.');

let printed = false;
let printMarkup = '';
context.window.open = () => ({
    opener: {},
    focus() {},
    print() { printed = true; },
    addEventListener: (_type, callback) => callback(),
    document: { open() {}, write(value) { printMarkup = value; }, close() {} },
});
exporter.exportPdf(layout);
if (!printed || !printMarkup.includes('<svg')) throw new Error('Der PDF-Export öffnet die Vektor-Druckansicht nicht.');

let pngFilename = '';
let revokedSvg = false;
const canvas = {
    width: 0,
    height: 0,
    getContext: () => ({ fillStyle: '', fillRect() {}, drawImage() {} }),
    toBlob: callback => callback(new context.Blob()),
};
context.URL.createObjectURL = () => 'blob:organigramm';
context.URL.revokeObjectURL = () => { revokedSvg = true; };
context.Image = class {
    constructor() { this.listeners = {}; }
    addEventListener(type, callback) { this.listeners[type] = callback; }
    set src(_value) { this.listeners.load(); }
};
context.document = { createElement: tag => tag === 'canvas' ? canvas : {}, body: { append() {} } };
exporter.downloadBlob = (_blob, filename) => { pngFilename = filename; };
await exporter.exportPng(layout);
if (pngFilename !== 'ad-organigramm.png' || canvas.width !== layout.width * 2 || canvas.height !== layout.height * 2 || !revokedSvg) throw new Error('Der PNG-Export ist nicht hochauflösend oder räumt seine temporäre URL nicht auf.');

console.log('LocalBase organization exporter smoke passed');
