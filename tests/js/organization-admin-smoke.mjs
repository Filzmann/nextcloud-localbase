import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const editorSource = readFileSync(new URL('../../js/components/organization-editor.js', import.meta.url), 'utf8');
const hierarchySource = readFileSync(new URL('../../js/components/hierarchy-board.js', import.meta.url), 'utf8');
const adminSource = readFileSync(new URL('../../js/admin/organization-admin.js', import.meta.url), 'utf8');
const css = readFileSync(new URL('../../css/organization-admin.css', import.meta.url), 'utf8');

for (const contract of ['class OrganizationEditor', 'Direkte Hierarchie', 'Fachliche Gruppenreihenfolge', 'onZoomChange', 'setZoom(zoom, false)', 'data-role-editor', 'roleEditorMarkup(', 'openRoleEditor(', 'closeRoleEditor(', 'updateRoleFromPanel(', 'data-setting-card', 'data-action="toggle-card"', 'data-organization-teams', 'this.hierarchyBoard.get()', 'this.hierarchyBoard.getDiagramOrder()', 'data-sort-list="roles"', 'data-sort-list="areas"', 'moveSortRow(', 'syncSortOrders(', 'singleOccupant', 'Technische Zuordnung', 'gilt für alle Karten dieser Rolle', 'Genau eine Person; bei Bereichsrollen je Bereich.', 'Rolle steht als Filter und Kalendergruppe zur Verfügung.', 'Leitungsrechte gelten nur in gemeinsamen Bereichen.', 'Gleichrangige dürfen nach Freigabe gegenseitig bearbeiten.', 'Gemeinsamer Block für Geschäftsführung, Leitungen und Stabsstellen.']) {
    if (!editorSource.includes(contract)) throw new Error(`Organisationseditor-Vertrag fehlt: ${contract}`);
}
if (editorSource.includes('Fachrollen und Nextcloud-Gruppen') || editorSource.includes('columnHeader(') || editorSource.includes('roleRow(')) throw new Error('Die breite Rollen-Einstellungstabelle ist weiterhin vorhanden.');
for (const contract of ['class HierarchyBoard', 'onEditRole', 'onZoomChange', 'data-action="edit-role"', 'data-action="zoom-in"', 'data-action="zoom-out"', 'data-action="zoom-reset"', 'data-organigram-viewport', 'tabindex="0"', 'startPan(', 'movePan(', 'finishPan(', 'normalizeZoom(', 'setZoom(', 'draggable="true"', 'data-position-node', 'data-diagram-level-list', 'data-action="move-node-left"', 'data-action="move-node-right"', 'getDiagramOrder()', 'insertionTarget(', 'applyDiagramOrderMove(', 'addEdge(manager, target)', 'Diese Verbindung würde einen Hierarchiezyklus erzeugen.', 'levels(roleKeys)', 'diagramNodes(roleKeys)', 'diagramEdges(nodes)', 'positionText(roleKey, areaKey)', 'data-hierarchy-links', 'drawConnections()', "createElementNS('http://www.w3.org/2000/svg', 'path')", 'orgs-connection-list', 'orgs-card-person']) if (!hierarchySource.includes(contract)) throw new Error(`Organigramm-Vertrag fehlt: ${contract}`);
if (hierarchySource.includes('Keine direkt unterstellte Rolle') || hierarchySource.includes('class="orgs-edges"')) throw new Error('Unterstellte Rollen stehen weiterhin textlastig innerhalb der Diagrammknoten.');
for (const contract of ['/api/ad-suite/admin/settings', '/api/ad-suite/admin/organization', '/api/ad-suite/admin/permissions', 'calendarPeerEditing', 'vacationPeerApproval', 'renderDirectoryStatus', 'orgs-directory-groups', 'data.directory?.positions || []', 'setOrganigramZoom', 'data.dashboardLayout?.organigram?.zoom']) {
    if (!adminSource.includes(contract)) throw new Error(`Admin-Frontendvertrag fehlt: ${contract}`);
}
const template = readFileSync(new URL('../../templates/organization-admin.php', import.meta.url), 'utf8');
for (const contract of ['orgs-directory-status', 'orgs-directory-groups', 'Verzeichnis- und LDAP-Kompatibilität']) if (!template.includes(contract)) throw new Error(`Verzeichnisdiagnose-Markup fehlt: ${contract}`);
for (const contract of ['width: 100%', 'max-width: none', 'overflow-x: auto', '.orgs-organigram', '.orgs-organigram-toolbar', '.orgs-organigram.is-panning', '.orgs-diagram-workspace.is-editing', '.orgs-role-editor', '.orgs-compact-list', '.orgs-setting-card', '.orgs-export', '.orgs-card.is-drag-over', '.orgs-card.is-position-before', '.orgs-card.is-position-after', '.orgs-position-handle', '.orgs-sort-handle', '.orgs-diagram-links', '.orgs-card-person', 'background-image:']) {
    if (!css.includes(contract)) throw new Error(`Admin-Layoutvertrag fehlt: ${contract}`);
}
if (!/\.orgs-level-nodes\s*\{[^}]*justify-content:\s*space-evenly/.test(css)) throw new Error('Wenige Organigrammkarten nutzen die verfügbare Ebenenbreite nicht gleichmäßig.');
if (!/\.orgs-level-nodes\s*\{[^}]*flex-wrap:\s*nowrap/.test(css)) throw new Error('Karten derselben Hierarchieebene werden nicht stabil nebeneinander angeordnet.');
if (!/\.orgs-card\s*\{[^}]*width:\s*fit-content[^}]*min-width:\s*11rem[^}]*max-width:\s*15rem/.test(css)) throw new Error('Kurze Organigrammkarten nutzen weiterhin unnötig die volle Standardbreite.');

const context = { window: { LocalBase: { ui: { esc: value => String(value ?? '') } } }, JSON, Set, Math, Object, Element: class {} };
runInNewContext(hierarchySource, context);
runInNewContext(editorSource, context);
const board = Object.create(context.window.LocalBase.components.HierarchyBoard.prototype);
if (board.normalizeZoom(20) !== 50 || board.normalizeZoom(137) !== 140 || board.normalizeZoom(190) !== 150) throw new Error('Persönlicher Zoom wird nicht auf sichere Grenzen und Schritte normalisiert.');
let emittedZoom = null;
let appliedZoom = false;
board.zoom = 100;
board.onZoomChange = zoom => { emittedZoom = zoom; };
board.applyZoom = () => { appliedZoom = true; };
board.setZoom(130);
if (board.zoom !== 130 || emittedZoom !== 130 || !appliedZoom) throw new Error('Zoomänderung wird nicht angewendet und persönlich gespeichert.');
const viewport = { scrollLeft: 200, scrollTop: 100, classList: { add() {}, remove() {} }, setPointerCapture() {}, releasePointerCapture() {} };
board.beginPan(viewport, { clientX: 50, clientY: 60, pointerId: 7 });
let panPrevented = false;
board.movePan({ clientX: 80, clientY: 90, pointerId: 7, preventDefault: () => { panPrevented = true; } });
if (viewport.scrollLeft !== 170 || viewport.scrollTop !== 70 || !panPrevented) throw new Error('Zeiger-Pan verschiebt den hierarchischen Diagrammausschnitt nicht korrekt.');
board.finishPan({ pointerId: 7 });
if (board.pan !== null) throw new Error('Zeiger-Pan wird nicht sauber beendet.');
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
if (!occupiedCard.includes('orgs-card-person') || !occupiedCard.includes('Berta West') || !occupiedCard.includes('Bereich West') || !occupiedCard.includes('data-action="edit-role"')) throw new Error('Bereich, zugeordnete Person oder Edit-Stift fehlen in der Diagrammkarte.');
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
editor.definition = {
    roles: {
        first: { label: 'Erste Rolle', groupId: 'ad-Erste', sortOrder: 20, calendarVisible: true, areaScoped: false, managementAreaScoped: false, peerEnabled: true, staffBlock: false, singleOccupant: false },
        second: { label: 'Zweite Rolle', groupId: 'ad-Zweite', sortOrder: 10, calendarVisible: true, areaScoped: true, managementAreaScoped: true, peerEnabled: false, staffBlock: false, singleOccupant: true },
    },
    areas: { west: { label: 'West', groupId: 'ad-West', sortOrder: 20 }, east: { label: 'Ost', groupId: 'ad-Ost', sortOrder: 10 } },
};
editor.applyOrder('roles', ['first', 'second']);
editor.applyOrder('areas', ['west', 'east']);
if (editor.definition.roles.first.sortOrder !== 10 || editor.definition.roles.second.sortOrder !== 20) throw new Error('Rollenreihenfolge wird nach Drag-and-drop nicht kanonisch nummeriert.');
if (editor.definition.areas.west.sortOrder !== 10 || editor.definition.areas.east.sortOrder !== 20) throw new Error('Bereichsreihenfolge wird nach Drag-and-drop nicht kanonisch nummeriert.');
const rolePanel = editor.roleEditorMarkup('second');
if (!rolePanel.includes('Zweite Rolle') || !rolePanel.includes('ad-Zweite') || !rolePanel.includes('Technische Zuordnung') || !rolePanel.includes('gilt für alle Karten dieser Rolle')) throw new Error('Das seitliche Rollenpanel enthält nicht alle globalen Rolleninformationen.');
const roleOrder = editor.roleOrderItem('first', editor.definition.roles.first);
if (!roleOrder.includes('data-sort-kind="roles"') || !roleOrder.includes('Erste Rolle') || roleOrder.includes('data-field="groupId"')) throw new Error('Die kompakte fachliche Reihenfolge vermischt Sortierung und Rolleneinstellungen.');
const areaCard = editor.areaCard('west', editor.definition.areas.west);
if (!areaCard.includes('data-setting-card') || !areaCard.includes('data-action="toggle-card"') || !areaCard.includes('ad-West')) throw new Error('Bereiche werden nicht als kompakte aufklappbare Karten dargestellt.');
const teamCard = editor.teamCard({ id: 'pflege', label: 'Pflege', roles: ['first'], areas: [], sortOrder: 10 });
if (!teamCard.includes('data-organization-team') || !teamCard.includes('data-action="toggle-card"') || !teamCard.includes('Pflege')) throw new Error('Urlaubsansichten werden nicht als kompakte aufklappbare Karten dargestellt.');
let boardRefreshed = false;
editor.editingRoleKey = 'second';
editor.positions = [];
editor.hierarchyBoard = {
    get: () => ({ first: ['second'] }),
    getDiagramOrder: () => ['first', 'second'],
    set: () => { boardRefreshed = true; },
};
editor.container = { querySelector: selector => selector === '[data-role-scope-help]' ? { textContent: '' } : null };
editor.updateRoleFromPanel({ dataset: { roleEditorField: 'areaScoped' }, type: 'checkbox', checked: false });
if (editor.definition.roles.second.areaScoped !== false || !boardRefreshed) throw new Error('Eine Rollenänderung aus dem Seitenpanel aktualisiert nicht alle Diagrammkarten derselben Rolle.');
const settingPanel = { hidden: true };
let expanded = 'false';
const settingCard = { querySelector: () => settingPanel };
const toggle = { closest: () => settingCard, getAttribute: () => expanded, setAttribute: (_name, value) => { expanded = value; } };
editor.toggleCard(toggle);
if (expanded !== 'true' || settingPanel.hidden) throw new Error('Kompakte Einstellungskarten lassen sich nicht zugänglich aufklappen.');

console.log('LocalBase organization admin smoke passed');
