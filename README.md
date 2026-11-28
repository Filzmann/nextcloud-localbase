# LocalBase

Test-Harness und leere Kompatibilitäts-Stage für die lokalen FLZ- und BR-Nextcloud-Apps.

LocalBase ist nach dem abgeschlossenen Runtime-Rückbau (28. November 2026,
Freigabe Simon) **keine Laufzeit-App mit Pilotkomponenten mehr**. Alle ehemaligen
Fachverträge liegen heute in den zuständigen Kategorie-B-Apps
(`flz_organization`, `flz_permission_matrix`, `flz_data_protection`) bzw. als
app-lokal gebündelte Kategorie-A-Bibliothek (`vendor/flz-shared` in den Apps).

Verbleibender Inhalt dieses Repositories:

- `tests/Support/PhpTestRunner.php` und `tests/Support/assertions.php` — zentraler
  PHP-Test-Runner und Assertions, den alle App-Repositories testweise über ihren
  Bootstrap einbinden (`OCA\LocalBase\Tests\...`).
- `tests/Support/js-runner.mjs` und `tests/js/helpers/fake-dom.js` — JavaScript-Testrunner
  und DOM-Fake für Verbraucher-Smokes.
- `tests/coverage/` — Coverage-Tooling (phpcov/c8, `merge-clover.php`), das die
  CI-Coverage-Gates aller Apps verwendet.
- `appinfo/info.xml` — minimale, registrierungsfreie App-Hülle, damit die
  Entwicklungs-Compat-Stage (DDEV) die App weiter installieren kann.
- `.github/workflows/tests.yml` — eigener Harness-Testlauf plus koordinierter
  Verbraucher-Vertragslauf (`consumer-contracts`), der alle Fachapps gegen den
  jeweiligen Branch/MAIN-Stand prüft.

Es gibt keine Navigation, keine Settings, keine Routen, keine Hintergrundjobs und
keine eigenen Datenbanktabellen mehr. Frühere Installationen (rein interne
Entwicklungsstände, kein Produktivbestand) können bei Bedarf die verwaiste Tabelle
über einen Neuaufbau der wegwerfbaren DDEV-Instanz entfernen.

## Tests

    php tests/run.php
    node tests/run-js.mjs

Dokumentation: `docs/prefixless-organization-group-migration.md` (Staging-Migrations-Runbook),
`ROADMAP.md` (offene Punkte), `CHANGELOG.md` (Historie), `AGENTS.md` (verbindliche Regeln).
