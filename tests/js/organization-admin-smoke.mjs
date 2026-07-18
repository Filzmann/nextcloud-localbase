import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const editorSource = readFileSync(new URL('../../js/components/organization-editor.js', import.meta.url), 'utf8');
const hierarchySource = readFileSync(new URL('../../js/components/hierarchy-board.js', import.meta.url), 'utf8');
const adminSource = readFileSync(new URL('../../js/admin/organization-admin.js', import.meta.url), 'utf8');
const css = readFileSync(new URL('../../css/organization-admin.css', import.meta.url), 'utf8');

for (const contract of ['class OrganizationEditor', 'Direkte Hierarchie', 'Fachrollen und Nextcloud-Gruppen', 'data-organization-teams', 'this.hierarchyBoard.get()', 'data-sort-list="roles"', 'data-sort-list="areas"', 'moveSortRow(', 'syncSortOrders(', 'singleOccupant', 'Genau eine Person; bei Bereichsrollen je Bereich.', 'Rolle steht als Filter und Kalendergruppe zur Verfügung.', 'Leitungsrechte gelten nur in gemeinsamen Bereichen.', 'Gleichrangige dürfen nach Freigabe gegenseitig bearbeiten.', 'Gemeinsamer Block für Geschäftsführung, Leitungen und Stabsstellen.']) {
    if (!editorSource.includes(contract)) throw new Error(`Organisationseditor-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class HierarchyBoard', 'draggable="true"', 'addEdge(manager, target)', 'Diese Verbindung würde einen Hierarchiezyklus erzeugen.', 'levels(roleKeys)', 'diagramNodes(roleKeys)', 'diagramEdges(nodes)', 'positionText(roleKey, areaKey)', 'data-hierarchy-links', 'drawConnections()', "createElementNS('http://www.w3.org/2000/svg', 'path')", 'orgs-connection-list', 'orgs-card-person']) if (!hierarchySource.includes(contract)) throw new Error(`Organigramm-Vertrag fehlt: ${contract}`);
if (hierarchySource.includes('Keine direkt unterstellte Rolle') || hierarchySource.includes('class="orgs-edges"')) throw new Error('Unterstellte Rollen stehen weiterhin textlastig innerhalb der Diagrammknoten.');
for (const contract of ['/api/ad-suite/admin/settings', '/api/ad-suite/admin/organization', '/api/ad-suite/admin/permissions', 'calendarPeerEditing', 'vacationPeerApproval', 'renderDirectoryStatus', 'orgs-directory-groups', 'data.directory?.positions || []']) {
    if (!adminSource.includes(contract)) throw new Error(`Admin-Frontendvertrag fehlt: ${contract}`);
}
const template = readFileSync(new URL('../../templates/organization-admin.php', import.meta.url), 'utf8');
for (const contract of ['orgs-directory-status', 'orgs-directory-groups', 'Verzeichnis- und LDAP-Kompatibilität']) if (!template.includes(contract)) throw new Error(`Verzeichnisdiagnose-Markup fehlt: ${contract}`);
for (const contract of ['width: 100%', 'max-width: none', 'overflow-x: auto', '.orgs-organigram', '.orgs-card.is-drag-over', '.orgs-sort-handle', '.orgs-diagram-links', '.orgs-column-help', '.orgs-card-person', 'background-image:']) {
    if (!css.includes(contract)) throw new Error(`Admin-Layoutvertrag fehlt: ${contract}`);
}

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
board.positions = [
    { roleKey: 'gf', areaKey: null, displayNames: ['Gina Führung'] },
    { roleKey: 'bl', areaKey: 'west', displayNames: ['Berta West'] },
    { roleKey: 'bl', areaKey: 'south', displayNames: [] },
];
const diagramNodes = board.diagramNodes(['gf', 'bl', 'office']);
if (diagramNodes.map(node => node.id).join(',') !== 'gf,bl::west,bl::south,office::west,office::south') throw new Error('Bereichsrollen werden nicht in geordnete Bereichsknoten aufgefächert.');
if (board.diagramEdges(diagramNodes).length !== 4) throw new Error('Hierarchiepfeile werden nicht passend je Bereich aufgefächert.');
if (board.positionText('bl', 'west') !== 'Berta West' || board.positionText('bl', 'south') !== 'Nicht besetzt') throw new Error('Besetzung von Einzelpositionen wird im Organigramm nicht verständlich dargestellt.');
const occupiedCard = board.card(diagramNodes.find(node => node.id === 'bl::west'));
if (!occupiedCard.includes('orgs-card-person') || !occupiedCard.includes('Berta West') || !occupiedCard.includes('Bereich West')) throw new Error('Bereich und zugeordnete Person fehlen in der Diagrammkarte.');

const editor = Object.create(context.window.LocalBase.components.OrganizationEditor.prototype);
editor.definition = { roles: { first: { sortOrder: 20 }, second: { sortOrder: 10 } }, areas: { west: { sortOrder: 20 }, east: { sortOrder: 10 } } };
editor.applyOrder('roles', ['first', 'second']);
editor.applyOrder('areas', ['west', 'east']);
if (editor.definition.roles.first.sortOrder !== 10 || editor.definition.roles.second.sortOrder !== 20) throw new Error('Rollenreihenfolge wird nach Drag-and-drop nicht kanonisch nummeriert.');
if (editor.definition.areas.west.sortOrder !== 10 || editor.definition.areas.east.sortOrder !== 20) throw new Error('Bereichsreihenfolge wird nach Drag-and-drop nicht kanonisch nummeriert.');

console.log('LocalBase organization admin smoke passed');
