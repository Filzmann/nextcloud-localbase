<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('localbase', 'privacy/privacy-download');
\OCP\Util::addScript('localbase', 'privacy/privacy-report');
\OCP\Util::addStyle('localbase', 'privacy');
$mode = ($_['mode'] ?? 'self') === 'admin' ? 'admin' : 'self';
?>
<main id="localbase-privacy" class="lb-privacy" data-privacy-mode="<?php p($mode); ?>">
    <header>
        <h1><?php p($mode === 'admin' ? 'Datenschutz-Auskunft' : 'Meine Daten'); ?></h1>
        <p><?php p($mode === 'admin' ? 'Berechtigte Auskunft und Retention-Vorschau ohne Berichtskopie.' : 'Flüchtige Auskunft über die Daten der angemeldeten Person.'); ?></p>
    </header>
    <?php if ($mode === 'admin'): ?>
        <form id="lb-privacy-subject-form">
            <label for="lb-privacy-subject">Nextcloud-UID</label>
            <input id="lb-privacy-subject" name="subjectUid" maxlength="255" required>
            <button type="submit">Auskunft laden</button>
            <button type="button" id="lb-privacy-retention">Dry Run anzeigen</button>
        </form>
    <?php endif; ?>
    <button type="button" id="lb-privacy-download" disabled>Auskunft als PDF herunterladen</button>
    <div id="lb-privacy-status" role="status" aria-live="polite">Daten werden geladen.</div>
    <section aria-labelledby="lb-privacy-article15-title">
        <h2 id="lb-privacy-article15-title">Angaben nach Art. 15 DSGVO</h2>
        <div id="lb-privacy-article15" class="lb-privacy-rights"></div>
    </section>
    <section aria-labelledby="lb-privacy-apps-title">
        <h2 id="lb-privacy-apps-title">Deine Daten nach App</h2>
        <div id="lb-privacy-completeness"></div>
        <div id="lb-privacy-apps" class="lb-privacy-apps"></div>
    </section>
</main>
