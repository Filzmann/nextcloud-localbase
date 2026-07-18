<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('localbase', 'ui/ui');
\OCP\Util::addScript('localbase', 'components/hierarchy-board');
\OCP\Util::addScript('localbase', 'components/organization-exporter');
\OCP\Util::addScript('localbase', 'components/organization-editor');
\OCP\Util::addScript('localbase', 'components/organization-dashboard');
\OCP\Util::addScript('localbase', 'admin/organization-admin');
\OCP\Util::addStyle('localbase', 'organization-admin');
?>
<section id="orgsuite-admin" class="section orgs-admin" aria-labelledby="orgs-admin-heading">
    <h2 id="orgs-admin-heading">AD-Organisation</h2>
    <p>Organisationsweite Einstellungen sind ausschließlich im Nextcloud-Adminbereich änderbar. Bei einer Einzelinstallation erscheinen sie unter der Fachapp, ab zwei AD-Produkten in der OrgSuite.</p>
    <div id="orgs-admin-notice" class="orgs-notice" role="status" aria-live="polite" aria-atomic="true" hidden></div>

    <p class="orgs-feedback" data-dashboard-feedback role="status" aria-live="polite"></p>
    <div class="orgs-dashboard-grid" data-dashboard-scope="main">
        <section class="orgs-panel orgs-dashboard-widget" data-dashboard-widget data-widget-id="directory" aria-labelledby="orgs-directory-heading">
            <header class="orgs-dashboard-header"><h3 id="orgs-directory-heading" data-dashboard-title>Verzeichnis- und LDAP-Kompatibilität</h3><div class="orgs-dashboard-actions"><button type="button" data-dashboard-move="-1" aria-label="Verzeichnis- und LDAP-Kompatibilität eine Position zurück verschieben">↑</button><button type="button" data-dashboard-handle draggable="true" aria-label="Verzeichnis- und LDAP-Kompatibilität per Drag-and-drop verschieben">⠿</button><button type="button" data-dashboard-move="1" aria-label="Verzeichnis- und LDAP-Kompatibilität eine Position weiter verschieben">↓</button><button type="button" data-dashboard-toggle aria-expanded="true" aria-controls="orgs-directory-content" aria-label="Verzeichnis- und LDAP-Kompatibilität ein- oder ausklappen"><span aria-hidden="true">▾</span></button></div></header>
            <div id="orgs-directory-content" data-dashboard-content>
                <p>Die Fachapps verwenden ausschließlich die von Nextcloud bereitgestellten Konten und Gruppen. Read-only LDAP-Gruppen sind für produktive Rechte zulässig; nur Demo-Packs dürfen deren Mitgliedschaften nicht verändern.</p>
                <p id="orgs-directory-status" role="status" aria-live="polite">Verzeichnisstatus wird geprüft.</p>
                <div class="orgs-table-wrap"><table class="orgs-table"><caption>Konfigurierte Organisationsgruppen und Nextcloud-Backends</caption><thead><tr><th>Typ</th><th>Bezeichnung</th><th>Gruppen-ID</th><th>Backend</th><th>Status</th></tr></thead><tbody id="orgs-directory-groups"></tbody></table></div>
            </div>
        </section>

        <section class="orgs-panel orgs-dashboard-widget orgs-dashboard-collection" data-dashboard-widget data-widget-id="organization" aria-labelledby="orgs-organization-heading">
            <header class="orgs-dashboard-header"><h3 id="orgs-organization-heading" data-dashboard-title>AD-Organisation</h3><div class="orgs-dashboard-actions"><button type="button" data-dashboard-move="-1" aria-label="AD-Organisation eine Position zurück verschieben">↑</button><button type="button" data-dashboard-handle draggable="true" aria-label="AD-Organisation per Drag-and-drop verschieben">⠿</button><button type="button" data-dashboard-move="1" aria-label="AD-Organisation eine Position weiter verschieben">↓</button><button type="button" data-dashboard-toggle aria-expanded="true" aria-controls="orgs-organization-content" aria-label="AD-Organisation ein- oder ausklappen"><span aria-hidden="true">▾</span></button></div></header>
            <div id="orgs-organization-content" data-dashboard-content>
                <p>Diese Konfiguration steuert Gruppen, sichtbare Namen, Bereiche, Hierarchie und Urlaubsansichten in den AD-Fachapps.</p>
                <p><strong>Wichtig:</strong> Änderungen an Gruppen-IDs verschieben keine bestehenden Nextcloud-Mitgliedschaften. Zielgruppen und Mitgliedschaften müssen vor der Umstellung vorbereitet werden.</p>
                <form id="orgs-organization-form"><div id="orgs-organization-editor"></div><button type="submit" class="primary">Organisation speichern</button></form>
            </div>
        </section>

        <section class="orgs-panel orgs-dashboard-widget" data-dashboard-widget data-widget-id="permissions" aria-labelledby="orgs-permissions-heading">
            <header class="orgs-dashboard-header"><h3 id="orgs-permissions-heading" data-dashboard-title>Zusätzliche Rechte innerhalb von Fachgruppen</h3><div class="orgs-dashboard-actions"><button type="button" data-dashboard-move="-1" aria-label="Zusätzliche Rechte eine Position zurück verschieben">↑</button><button type="button" data-dashboard-handle draggable="true" aria-label="Zusätzliche Rechte per Drag-and-drop verschieben">⠿</button><button type="button" data-dashboard-move="1" aria-label="Zusätzliche Rechte eine Position weiter verschieben">↓</button><button type="button" data-dashboard-toggle aria-expanded="true" aria-controls="orgs-permissions-content" aria-label="Zusätzliche Rechte ein- oder ausklappen"><span aria-hidden="true">▾</span></button></div></header>
            <div id="orgs-permissions-content" data-dashboard-content>
                <form id="orgs-permissions-form">
                    <div class="orgs-dashboard-grid" data-dashboard-scope="permissions">
                        <section class="orgs-dashboard-widget" data-dashboard-widget data-widget-id="calendar-permissions" aria-labelledby="orgs-calendar-permissions-heading">
                            <header class="orgs-dashboard-header"><h4 id="orgs-calendar-permissions-heading" data-dashboard-title>AD Kalender</h4><div class="orgs-dashboard-actions"><button type="button" data-dashboard-move="-1" aria-label="AD Kalender eine Position zurück verschieben">↑</button><button type="button" data-dashboard-handle draggable="true" aria-label="AD Kalender per Drag-and-drop verschieben">⠿</button><button type="button" data-dashboard-move="1" aria-label="AD Kalender eine Position weiter verschieben">↓</button><button type="button" data-dashboard-toggle aria-expanded="true" aria-controls="orgs-calendar-permissions-content" aria-label="AD Kalender ein- oder ausklappen"><span aria-hidden="true">▾</span></button></div></header>
                            <div id="orgs-calendar-permissions-content" class="orgs-dashboard-content" data-dashboard-content><p>Aktivierte Kolleg*innen dürfen innerhalb derselben Fachgruppe Kalenderdaten bearbeiten; Büro- und EB-Rechte bleiben auf gemeinsame Bürobereiche begrenzt.</p><div id="orgs-calendar-peer-settings" class="orgs-checkbox-grid"></div></div>
                        </section>
                        <section class="orgs-dashboard-widget" data-dashboard-widget data-widget-id="vacation-permissions" aria-labelledby="orgs-vacation-permissions-heading">
                            <header class="orgs-dashboard-header"><h4 id="orgs-vacation-permissions-heading" data-dashboard-title>AD Urlaub</h4><div class="orgs-dashboard-actions"><button type="button" data-dashboard-move="-1" aria-label="AD Urlaub eine Position zurück verschieben">↑</button><button type="button" data-dashboard-handle draggable="true" aria-label="AD Urlaub per Drag-and-drop verschieben">⠿</button><button type="button" data-dashboard-move="1" aria-label="AD Urlaub eine Position weiter verschieben">↓</button><button type="button" data-dashboard-toggle aria-expanded="true" aria-controls="orgs-vacation-permissions-content" aria-label="AD Urlaub ein- oder ausklappen"><span aria-hidden="true">▾</span></button></div></header>
                            <div id="orgs-vacation-permissions-content" class="orgs-dashboard-content" data-dashboard-content><p>Aktivierte Kolleg*innen dürfen innerhalb derselben Fachgruppe geplante Urlaube genehmigen. Eigene Genehmigungen bleiben gesperrt.</p><div id="orgs-vacation-peer-settings" class="orgs-checkbox-grid"></div></div>
                        </section>
                    </div>
                    <button type="submit" class="primary">Rechte speichern</button>
                </form>
            </div>
        </section>
    </div>
</section>
