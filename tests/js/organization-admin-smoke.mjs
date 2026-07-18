import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const editorSource = readFileSync(new URL('../../js/components/organization-editor.js', import.meta.url), 'utf8');
const hierarchySource = readFileSync(new URL('../../js/components/hierarchy-board.js', import.meta.url), 'utf8');
const adminSource = readFileSync(new URL('../../js/admin/organization-admin.js', import.meta.url), 'utf8');
const css = readFileSync(new URL('../../css/organization-admin.css', import.meta.url), 'utf8');

for (const contract of ['class OrganizationEditor', 'Direkte Hierarchie', 'Fachrollen und Nextcloud-Gruppen', 'data-organization-teams', 'this.hierarchyBoard.get()', 'this.hierarchyBoard.getDiagramOrder()', 'data-sort-list="roles"', 'data-sort-list="areas"', 'moveSortRow(', 'syncSortOrders(', 'singleOccupant', 'Genau eine Person; bei Bereichsrollen je Bereich.', 'Rolle steht als Filter und Kalendergruppe zur Verfügung.', 'Leitungsrechte gelten nur in gemeinsamen Bereichen.', 'Gleichrangige dürfen nach Freigabe gegenseitig bearbeiten.', 'Gemeinsamer Block für Geschäftsführung, Leitungen und Stabsstellen.']) {
    if (!editorSource.includes(contract)) throw new Error(`Organisationseditor-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class HierarchyBoard', 'draggable="true"', 'data-position-node', 'data-diagram-level-list', 'data-action="move-node-left"', 'data-action="move-node-right"', 'getDiagramOrder()', 'insertionTarget(', 'applyDiagramOrderMove(', 'addEdge(manager, target)', 'Diese Verbindung würde einen Hierarchiezyklus erzeugen.', 'levels(roleKeys)', 'diagramNodes(roleKeys)', 'diagramEdges(nodes)', 'positionText(roleKey, areaKey)', 'data-hierarchy-links', 'drawConnections()', "createElementNS('http://www.w3.org/2000/svg', 'path')", 'orgs-connection-list', 'orgs-card-person']) if (!hierarchySource.includes(contract)) throw new Error(`Organigramm-Vertrag fehlt: ${contract}`);
if (hierarchySource.includes('Keine direkt unterstellte Rolle') || hierarchySource.includes('class="orgs-edges"')) throw new Error('Unterstellte Rollen stehen weiterhin textlastig innerhalb der Diagrammknoten.');
for (const contract of ['/api/ad-suite/admin/settings', '/api/ad-suite/admin/organization', '/api/ad-suite/admin/permissions', 'calendarPeerEditing', 'vacationPeerApproval', 'renderDirectoryStatus', 'orgs-directory-groups', 'data.directory?.positions || []']) {
    if (!adminSource.includes(contract)) throw new Error(`Admin-Frontendvertrag fehlt: ${contract}`);
}
const template = readFileSync(new URL('../../templates/organization-admin.php', import.meta.url), 'utf8');
for (const contract of ['orgs-directory-status', 'orgs-directory-groups', 'Verzeichnis- und LDAP-Kompatibilität']) if (!template.includes(contract)) throw new Error(`Verzeichnisdiagnose-Markup fehlt: ${contract}`);
for (const contract of ['width: 100%', 'max-width: none', 'overflow-x: auto', '.orgs-organigram', '.orgs-export', '.orgs-card.is-drag-over', '.orgs-card.is-position-before', '.orgs-card.is-position-after', '.orgs-position-handle', '.orgs-sort-handle', '.orgs-diagram-links', '.orgs-column-help', '.orgs-card-person', 'background-image:']) {
    if (!css.includes(contract)) throw new Error(`Admin-Layoutvertrag fehlt: ${contract}`);
}
if (!/\.orgs-level-nodes\s*\{[^}]*justify-content:\s*space-evenly/.test(css)) throw new Error('Wenige Organigrammkarten nutzen die verfügbare Ebenenbreite nicht gleichmäßig.');

const context = { window: { LocalBase: { ui: { esc: value => String(value ?? '') } } }, JSON, Set, Math, Object, Element: class {} };
runInNewContext(hierarchySource, context);
runInNewContext(editorSource, context);
const board = Object.create(context.window.LocalBase.components.HierarchyBoard.prototype);
board.hierarchy = { gf: ['pdl'], pdl: ['pfk'] };
if (JSON.stringify(board.levels(['gf', 'pdl', 'pfk'])) !== JSON.stringify([['gf'], ['pdl'], ['pfk']])) throw new Error('Organigramm-Ebenen werden nicht aus der Hierarchie abgeleitet.');
if (!board.reaches('gf', 'pfk') || board.reaches('pfk', 'gf')) throw new Error('Clientseitige Zyklusprüfung ist fehlerhaft.');
board.roles = {
    gf: { label: 'Geschäftsführung', sortOrder: 10, areaScoped: false, singleOccupant: true },
    bl: { label: 'Büroleitung', sortOrder: 20, areaScoped: true, singleOccupant: true },
    office: { label: 'Büro', sortOrder: 30, areaScoped: true, singleOccupant: false },
};
board.areas = { west: { label: 'West', sortOrder: 10 }, south: { label: 'Süd', sortOrder: 20 } };
board.hierarchy = { gf: ['bl'], bl: ['office'] };
board.diagramOrder = ['bl::south', 'bl::west'];
board.positions = [
    { roleKey: 'gf', areaKey: null, displayNames: ['Gina Führung'] },
    { roleKey: 'bl', areaKey: 'west', displayNames: ['Berta West'] },
    { roleKey: 'bl', areaKey: 'south', displayNames: [] },
];
const diagramNodes = board.diagramNodes(['gf', 'bl', 'office']);
if (diagramNodes.filter(node => node.roleKey === 'bl').map(node => node.id).join(',') !== 'bl::south,bl::west') throw new Error('Visuelle Links-rechts-Ordnung wird nicht unabhängig von der Bereichsreihenfolge angewendet.');
if (board.diagramEdges(diagramNodes).length !== 4) throw new Error('Hierarchiepfeile werden nicht passend je Bereich aufgefächert.');
if (board.positionText('bl', 'west') !== 'Berta West' || board.positionText('bl', 'south') !== 'Nicht besetzt') throw new Error('Besetzung von Einzelpositionen wird im Organigramm nicht verständlich dargestellt.');
const occupiedCard = board.card(diagramNodes.find(node => node.id === 'bl::west'));
if (!occupiedCard.includes('orgs-card-person') || !occupiedCard.includes('Berta West') || !occupiedCard.includes('Bereich West')) throw new Error('Bereich und zugeordnete Person fehlen in der Diagrammkarte.');
board.diagramOrder = diagramNodes.map(node => node.id);
board.applyDiagramOrderMove('bl::west', 'bl::south', true);
if (board.diagramOrder.indexOf('bl::west') > board.diagramOrder.indexOf('bl::south')) throw new Error('Horizontales Drag-and-drop verschiebt einen Diagrammknoten nicht nach links.');
const gapTarget = board.insertionTarget([
    { id: 'left', left: 0, width: 100 },
    { id: 'dragged', left: 170, width: 100 },
    { id: 'right', left: 400, width: 100 },
], 320, 'dragged');
if (gapTarget?.targetId !== 'right' || gapTarget?.before !== true) throw new Error('Eine in den Zwischenraum gezogene Karte landet nicht zwischen ihren Nachbarkarten.');
const endTarget = board.insertionTarget([{ id: 'left', left: 0, width: 100 }, { id: 'right', left: 200, width: 100 }], 400, 'left');
if (endTarget?.targetId !== 'right' || endTarget?.before !== false) throw new Error('Eine rechts abgelegte Karte landet nicht am Ende ihrer Hierarchieebene.');
if (board.insertionTarget([{ id: 'only', left: 0, width: 100 }], 50, 'only') !== null) throw new Error('Eine einzelne Karte erzeugt eine ungültige Ablageposition.');
const rightCard = { dataset: { diagramNode: 'right' }, getBoundingClientRect: () => ({ left: 400, width: 100 }) };
const positionedGap = board.positionTarget({ querySelectorAll: () => [rightCard] }, 320, 'dragged');
if (positionedGap?.card !== rightCard || positionedGap?.targetId !== 'right' || !positionedGap?.before) throw new Error('Der visuelle Zwischenraum wird nicht auf die benachbarte Diagrammkarte abgebildet.');

const editor = Object.create(context.window.LocalBase.components.OrganizationEditor.prototype);
editor.definition = { roles: { first: { sortOrder: 20 }, second: { sortOrder: 10 } }, areas: { west: { sortOrder: 20 }, east: { sortOrder: 10 } } };
editor.applyOrder('roles', ['first', 'second']);
editor.applyOrder('areas', ['west', 'east']);
if (editor.definition.roles.first.sortOrder !== 10 || editor.definition.roles.second.sortOrder !== 20) throw new Error('Rollenreihenfolge wird nach Drag-and-drop nicht kanonisch nummeriert.');
if (editor.definition.areas.west.sortOrder !== 10 || editor.definition.areas.east.sortOrder !== 20) throw new Error('Bereichsreihenfolge wird nach Drag-and-drop nicht kanonisch nummeriert.');

console.log('LocalBase organization admin smoke passed');
