import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const editorSource = readFileSync(new URL('../../js/components/organization-editor.js', import.meta.url), 'utf8');
const hierarchySource = readFileSync(new URL('../../js/components/hierarchy-board.js', import.meta.url), 'utf8');
const adminSource = readFileSync(new URL('../../js/admin/organization-admin.js', import.meta.url), 'utf8');
const css = readFileSync(new URL('../../css/organization-admin.css', import.meta.url), 'utf8');

for (const contract of ['class OrganizationEditor', 'Direkte Hierarchie', 'Fachrollen und Nextcloud-Gruppen', 'data-organization-teams', 'this.hierarchyBoard.get()', 'data-sort-list="roles"', 'data-sort-list="areas"', 'moveSortRow(', 'syncSortOrders(']) {
    if (!editorSource.includes(contract)) throw new Error(`Organisationseditor-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class HierarchyBoard', 'draggable="true"', 'addEdge(manager, target)', 'Diese Verbindung würde einen Hierarchiezyklus erzeugen.', 'levels(roleKeys)', 'data-hierarchy-links', 'drawConnections()', "createElementNS('http://www.w3.org/2000/svg', 'path')", 'orgs-connection-list']) if (!hierarchySource.includes(contract)) throw new Error(`Organigramm-Vertrag fehlt: ${contract}`);
if (hierarchySource.includes('Keine direkt unterstellte Rolle') || hierarchySource.includes('class="orgs-edges"')) throw new Error('Unterstellte Rollen stehen weiterhin textlastig innerhalb der Diagrammknoten.');
for (const contract of ['/api/ad-suite/admin/settings', '/api/ad-suite/admin/organization', '/api/ad-suite/admin/permissions', 'calendarPeerEditing', 'vacationPeerApproval', 'renderDirectoryStatus', 'orgs-directory-groups']) {
    if (!adminSource.includes(contract)) throw new Error(`Admin-Frontendvertrag fehlt: ${contract}`);
}
const template = readFileSync(new URL('../../templates/organization-admin.php', import.meta.url), 'utf8');
for (const contract of ['orgs-directory-status', 'orgs-directory-groups', 'Verzeichnis- und LDAP-Kompatibilität']) if (!template.includes(contract)) throw new Error(`Verzeichnisdiagnose-Markup fehlt: ${contract}`);
for (const contract of ['width: 100%', 'max-width: none', 'overflow-x: auto', '.orgs-organigram', '.orgs-card.is-drag-over', '.orgs-sort-handle', '.orgs-diagram-links', 'background-image:']) {
    if (!css.includes(contract)) throw new Error(`Admin-Layoutvertrag fehlt: ${contract}`);
}

const context = { window: { LocalBase: { ui: { esc: value => String(value ?? '') } } }, JSON, Set, Math, Object, Element: class {} };
runInNewContext(hierarchySource, context);
runInNewContext(editorSource, context);
const board = Object.create(context.window.LocalBase.components.HierarchyBoard.prototype);
board.hierarchy = { gf: ['pdl'], pdl: ['pfk'] };
if (JSON.stringify(board.levels(['gf', 'pdl', 'pfk'])) !== JSON.stringify([['gf'], ['pdl'], ['pfk']])) throw new Error('Organigramm-Ebenen werden nicht aus der Hierarchie abgeleitet.');
if (!board.reaches('gf', 'pfk') || board.reaches('pfk', 'gf')) throw new Error('Clientseitige Zyklusprüfung ist fehlerhaft.');

const editor = Object.create(context.window.LocalBase.components.OrganizationEditor.prototype);
editor.definition = { roles: { first: { sortOrder: 20 }, second: { sortOrder: 10 } }, areas: { west: { sortOrder: 20 }, east: { sortOrder: 10 } } };
editor.applyOrder('roles', ['first', 'second']);
editor.applyOrder('areas', ['west', 'east']);
if (editor.definition.roles.first.sortOrder !== 10 || editor.definition.roles.second.sortOrder !== 20) throw new Error('Rollenreihenfolge wird nach Drag-and-drop nicht kanonisch nummeriert.');
if (editor.definition.areas.west.sortOrder !== 10 || editor.definition.areas.east.sortOrder !== 20) throw new Error('Bereichsreihenfolge wird nach Drag-and-drop nicht kanonisch nummeriert.');

console.log('LocalBase organization admin smoke passed');
