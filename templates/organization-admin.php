<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('localbase', 'ui/ui');
\OCP\Util::addScript('localbase', 'components/hierarchy-board');
\OCP\Util::addScript('localbase', 'components/organization-exporter');
\OCP\Util::addScript('localbase', 'components/organization-editor');
\OCP\Util::addScript('localbase', 'admin/organization-admin');
\OCP\Util::addStyle('localbase', 'organization-admin');
?>
<section id="orgsuite-admin" class="section orgs-admin" aria-labelledby="orgs-admin-heading">
    <h2 id="orgs-admin-heading">AD-Organisation</h2>
    <p>Organisationsweite Einstellungen sind ausschließlich im Nextcloud-Adminbereich änderbar. Bei einer Einzelinstallation erscheinen sie unter der Fachapp, ab zwei AD-Produkten in der OrgSuite.</p>
    <div id="orgs-admin-notice" class="orgs-notice" role="status" aria-live="polite" aria-atomic="true" hidden></div>

    <section class="orgs-panel" aria-labelledby="orgs-directory-heading">
        <h3 id="orgs-directory-heading">Verzeichnis- und LDAP-Kompatibilität</h3>
        <p>Die Fachapps verwenden ausschließlich die von Nextcloud bereitgestellten Konten und Gruppen. Read-only LDAP-Gruppen sind für produktive Rechte zulässig; nur Demo-Packs dürfen deren Mitgliedschaften nicht verändern.</p>
        <p id="orgs-directory-status" role="status" aria-live="polite">Verzeichnisstatus wird geprüft.</p>
        <div class="orgs-table-wrap">
            <table class="orgs-table">
                <caption>Konfigurierte Organisationsgruppen und Nextcloud-Backends</caption>
                <thead><tr><th>Typ</th><th>Bezeichnung</th><th>Gruppen-ID</th><th>Backend</th><th>Status</th></tr></thead>
                <tbody id="orgs-directory-groups"></tbody>
            </table>
        </div>
    </section>

    <section class="orgs-panel" aria-labelledby="orgs-organization-heading">
        <h3 id="orgs-organization-heading">AD-Organisation</h3>
        <p>Diese Konfiguration steuert Gruppen, sichtbare Namen, Bereiche, Hierarchie und Urlaubsansichten in den AD-Fachapps.</p>
        <p><strong>Wichtig:</strong> Änderungen an Gruppen-IDs verschieben keine bestehenden Nextcloud-Mitgliedschaften. Zielgruppen und Mitgliedschaften müssen vor der Umstellung vorbereitet werden.</p>
        <form id="orgs-organization-form">
            <div id="orgs-organization-editor"></div>
            <button type="submit" class="primary">Organisation speichern</button>
        </form>
    </section>

    <section class="orgs-panel" aria-labelledby="orgs-permissions-heading">
        <h3 id="orgs-permissions-heading">Zusätzliche Rechte innerhalb von Fachgruppen</h3>
        <form id="orgs-permissions-form">
            <fieldset>
                <legend>AD Kalender</legend>
                <p>Aktivierte Kolleg*innen dürfen innerhalb derselben Fachgruppe Kalenderdaten bearbeiten; Büro- und EB-Rechte bleiben auf gemeinsame Bürobereiche begrenzt.</p>
                <div id="orgs-calendar-peer-settings" class="orgs-checkbox-grid"></div>
            </fieldset>
            <fieldset>
                <legend>AD Urlaub</legend>
                <p>Aktivierte Kolleg*innen dürfen innerhalb derselben Fachgruppe geplante Urlaube genehmigen. Eigene Genehmigungen bleiben gesperrt.</p>
                <div id="orgs-vacation-peer-settings" class="orgs-checkbox-grid"></div>
            </fieldset>
            <button type="submit" class="primary">Rechte speichern</button>
        </form>
    </section>
</section>
