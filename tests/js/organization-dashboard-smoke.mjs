import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../js/components/organization-dashboard.js', import.meta.url), 'utf8');
const template = readFileSync(new URL('../../templates/organization-admin.php', import.meta.url), 'utf8');
const editor = readFileSync(new URL('../../js/components/organization-editor.js', import.meta.url), 'utf8');
const admin = readFileSync(new URL('../../js/admin/organization-admin.js', import.meta.url), 'utf8');
const css = readFileSync(new URL('../../css/organization-admin.css', import.meta.url), 'utf8');

for (const contract of ['class OrganizationDashboard', 'data-dashboard-scope', 'data-dashboard-widget', 'data-dashboard-toggle', 'data-dashboard-handle', 'data-dashboard-move', 'aria-expanded', 'collectLayout(', 'applyLayout(', 'moveWidget(']) {
    if (!source.includes(contract)) throw new Error(`Persönlicher Dashboardvertrag fehlt: ${contract}`);
}
for (const widget of ['directory', 'organization', 'permissions', 'calendar-permissions', 'vacation-permissions']) if (!template.includes(`data-widget-id="${widget}"`)) throw new Error(`Statischer Dashboardblock fehlt: ${widget}`);
for (const widget of ['general', 'hierarchy', 'role-order', 'areas', 'vacation-views']) if (!editor.includes(`dashboardWidget('${widget}'`)) throw new Error(`Organisations-Dashboardblock fehlt: ${widget}`);
for (const contract of ["components/organization-dashboard", '/api/ad-suite/admin/layout', 'dashboardLayout', 'saveDashboardLayout']) if (!template.includes(contract) && !admin.includes(contract)) throw new Error(`Dashboardanbindung fehlt: ${contract}`);
for (const contract of ['.orgs-dashboard-grid', '.orgs-dashboard-widget', '.orgs-dashboard-header', '.is-dashboard-dragging', '.is-dashboard-over']) if (!css.includes(contract)) throw new Error(`Dashboardlayout fehlt: ${contract}`);

const context = { window: { LocalBase: { components: {} } }, Element: class {} };
vm.createContext(context);
vm.runInContext(source, context);
const Dashboard = context.window.LocalBase.components.OrganizationDashboard;
const dashboard = Object.create(Dashboard.prototype);
let changed = 0;
dashboard.onChange = () => { changed += 1; };
dashboard.root = { querySelectorAll: () => [], querySelector: () => null };
const content = { hidden: false };
const classList = { toggle() {} };
let toggle;
const widget = { dataset: { widgetId: 'directory' }, classList, querySelector: selector => selector === '[data-dashboard-content]' ? content : selector === '[data-dashboard-toggle]' ? toggle : null };
toggle = {
    getAttribute: () => 'true',
    setAttribute(name, value) { this[name] = value; },
    closest: () => widget,
};
dashboard.toggleWidget(toggle);
if (!content.hidden || toggle['aria-expanded'] !== 'false' || changed !== 1) throw new Error('Dashboardblock wird nicht zugänglich eingeklappt oder gespeichert.');

const createWidget = id => {
    const state = { expanded: 'true' };
    const panel = { hidden: false };
    const classes = new Set();
    const moves = {
        '-1': { disabled: false, focus() { this.focused = true; } },
        '1': { disabled: false, focus() { this.focused = true; } },
    };
    const collapse = { getAttribute: () => state.expanded, setAttribute: (_name, value) => { state.expanded = value; } };
    const item = {
        dataset: { widgetId: id },
        classList: { toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name), add: name => classes.add(name), remove: (...names) => names.forEach(name => classes.delete(name)) },
        matches: selector => selector === '[data-dashboard-widget]',
        querySelector(selector) {
            if (selector === '[data-dashboard-toggle]') return collapse;
            if (selector === '[data-dashboard-content]') return panel;
            if (selector === '[data-dashboard-title]') return { textContent: id };
            const move = selector.match(/data-dashboard-move="(-?\d+)"/);
            return move ? moves[move[1]] : null;
        },
    };
    collapse.closest = () => item;
    Object.defineProperty(item, 'nextSibling', { get: () => {
        const index = item.parentElement.children.indexOf(item);
        return item.parentElement.children[index + 1] || null;
    } });
    return { item, state, panel, moves };
};
const first = createWidget('first');
const second = createWidget('second');
const third = createWidget('third');
const scope = {
    dataset: { dashboardScope: 'test' },
    children: [first.item, second.item, third.item],
    matches: selector => selector === '[data-dashboard-scope]',
    closest: () => null,
    append(item) { this.insertBefore(item, null); },
    insertBefore(item, reference) {
        this.children = this.children.filter(candidate => candidate !== item);
        const index = reference ? this.children.indexOf(reference) : this.children.length;
        this.children.splice(index, 0, item);
    },
};
scope.children.forEach(item => { item.parentElement = scope; });
dashboard.root = {
    querySelectorAll: selector => selector === '[data-dashboard-scope]' ? [scope] : [],
    querySelector: () => null,
};
dashboard.layout = { version: 1, scopes: { test: { order: ['third', 'first', 'second'], collapsed: ['first'] } } };
dashboard.applyLayout();
if (scope.children.map(item => item.dataset.widgetId).join(',') !== 'third,first,second' || !first.panel.hidden) throw new Error('Persönliche Blockreihenfolge oder Einklappzustand wird nicht angewendet.');
const collected = dashboard.collectLayout();
if (collected.scopes.test.order.join(',') !== 'third,first,second' || collected.scopes.test.collapsed.join(',') !== 'first') throw new Error('Dashboardzustand wird nicht vollständig gesammelt.');
const moveDown = first.moves['1'];
moveDown.closest = () => first.item;
dashboard.moveWidget(moveDown, 1);
if (scope.children.map(item => item.dataset.widgetId).join(',') !== 'third,second,first' || !moveDown.focused) throw new Error('Tastaturäquivalente Blockverschiebung funktioniert nicht oder verliert den Fokus.');
const outerWidget = { parentElement: scope };
const nestedScope = { closest: selector => selector === '[data-dashboard-widget]' ? outerWidget : null };
const nestedWidget = { parentElement: nestedScope };
dashboard.drag = { scope };
if (dashboard.dragTarget({ closest: () => nestedWidget }) !== outerWidget) throw new Error('Ein äußerer Dashboardblock kann nicht über verschachtelten Blockinhalten abgelegt werden.');
first.state.expanded = 'false';
first.panel.hidden = true;
dashboard.revealInvalid({ closest: () => first.item });
if (first.panel.hidden || first.state.expanded !== 'true') throw new Error('Ein eingeklappter Block mit ungültigem Pflichtfeld wird nicht automatisch geöffnet.');

console.log('LocalBase organization dashboard smoke passed');
