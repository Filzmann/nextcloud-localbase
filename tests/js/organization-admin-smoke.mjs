import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const editorSource = readFileSync(new URL('../../js/components/organization-editor.js', import.meta.url), 'utf8');
const hierarchySource = readFileSync(new URL('../../js/components/hierarchy-board.js', import.meta.url), 'utf8');
const adminSource = readFileSync(new URL('../../js/admin/organization-admin.js', import.meta.url), 'utf8');
const css = readFileSync(new URL('../../css/organization-admin.css', import.meta.url), 'utf8');

for (const contract of ['class OrganizationEditor', 'Direkte Hierarchie', 'Fachrollen und Nextcloud-Gruppen', 'data-organization-teams', 'this.hierarchyBoard.get()']) {
    if (!editorSource.includes(contract)) throw new Error(`Organisationseditor-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class HierarchyBoard', 'draggable="true"', 'addEdge(manager, target)', 'Diese Verbindung würde einen Hierarchiezyklus erzeugen.', 'levels(roleKeys)']) if (!hierarchySource.includes(contract)) throw new Error(`Organigramm-Vertrag fehlt: ${contract}`);
for (const contract of ['/api/ad-suite/admin/settings', '/api/ad-suite/admin/organization', '/api/ad-suite/admin/permissions', 'calendarPeerEditing', 'vacationPeerApproval']) {
    if (!adminSource.includes(contract)) throw new Error(`Admin-Frontendvertrag fehlt: ${contract}`);
}
for (const contract of ['width: 100%', 'max-width: none', 'overflow-x: auto', '.orgs-organigram', '.orgs-card.is-drag-over']) {
    if (!css.includes(contract)) throw new Error(`Admin-Layoutvertrag fehlt: ${contract}`);
}

const context = { window: { LocalBase: { ui: { esc: value => String(value ?? '') } } }, JSON, Set, Math, Object, Element: class {} };
runInNewContext(hierarchySource, context);
runInNewContext(editorSource, context);
const board = Object.create(context.window.LocalBase.components.HierarchyBoard.prototype);
board.hierarchy = { gf: ['pdl'], pdl: ['pfk'] };
if (JSON.stringify(board.levels(['gf', 'pdl', 'pfk'])) !== JSON.stringify([['gf'], ['pdl'], ['pfk']])) throw new Error('Organigramm-Ebenen werden nicht aus der Hierarchie abgeleitet.');
if (!board.reaches('gf', 'pfk') || board.reaches('pfk', 'gf')) throw new Error('Clientseitige Zyklusprüfung ist fehlerhaft.');

console.log('LocalBase organization admin smoke passed');
