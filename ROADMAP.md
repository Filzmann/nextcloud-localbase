# Roadmap – LocalBase

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Regeln in `AGENTS.md`.

## LocalBase-Runtime-Ersetzung — abgeschlossen

### LB-RUNTIME-RETIREMENT – vollständiger Code-Rückbau erledigt

- Erledigt 28. November 2026 (Freigabe Simon): Zuerst war LocalBase aus
  FLZ-Produktkatalog, Installer und allen Bundles entfernt worden; danach wurden
  am selben Tag alle Laufzeit-Pilotkomponenten aus dem Repository entfernt
  (`lib/`, `appinfo/routes.php`, `templates/`, `js/`-Pilot-Assets, `css/`,
  `img/`, `l10n/`, `resources/`, Pilot-Tests). `appinfo/info.xml` ist eine
  registrierungsfreie Hülle (0.13.0).
- Verbleibende Rolle: Test-Harness (`tests/Support/PhpTestRunner.php`,
  Assertions, `js-runner.mjs`, `tests/js/helpers/fake-dom.js`,
  `tests/coverage/`-Tooling) und leere Compat-Stage-App für DDEV/Staging-Mirror.
  Der koordinierte Verbraucher-Vertragslauf in `.github/workflows/tests.yml`
  prüft alle Fachapps gegen den jeweiligen Stand.
- `tests/Support/PilotRemovalContractTest.php` sichert den Rückbauzustand
  (Pilot-Pfade entfernt, Harness vollständig vorhanden).

### LB-PERSONAL-DATA-PILOT-RETIREMENT – erledigt

- Der Art.-15-Pilot (Self-Service-/Adminoberfläche, interne PersonalData-Klassen,
  `resources/privacy-processing.json`) wurde mit dem vollständigen Rückbau
  entfernt, nachdem der Runtime-Nachweis grün war (alle Consumer auf
  `OCA\FlzOrganization\PublicApi\V1\*` / `OCA\FlzPermissionMatrix\PublicApi\V1\*`
  / `flz_data_protection` migriert, Delivery-Gate grün). Es gab keinen
  Datenfallback über fremde Speicher; Datenschutz-Projektionen der Fachapps
  liegen bei `flz_data_protection` bzw. app-lokal.

## App-lokale Migrationsaufgabe

### LB-PREFIXLESS-GROUP-STAGING – präfixlose Organisationsgruppen auf Staging migrieren

- Der lokale DDEV-Zustand, die ausführbaren Standarddefinitionen, Demofixtures,
  Providerprojektionen und Tests verwenden bereits präfixlose Rollen-,
  Bereichs- und ASN-Teamgruppen. Nachweis und späterer Staging-Ablauf stehen in
  [`docs/prefixless-organization-group-migration.md`](docs/prefixless-organization-group-migration.md).
- Offen bleiben der Fresh-Install-/Consumer-Nachweis im vollständigen
  Staging-Zielkontext und die kontrollierte Instanzmigration nach Runbook.
  Dieser Lauf braucht eine eigene ausdrückliche Freigabe. Die lokale Regel
  `Benutzername = Passwort` für synthetische Konten ist dort verboten.

## Bewusst zurückgestellt – niedrigste Priorität

### LB-L10N – app-lokale LocalBase-Texte lokalisieren

Status seit 17. September 2026: gegenstandslos nach dem Code-Rückbau —
LocalBase rendert keine sichtbaren Texte mehr. Das Root-Vorhaben `ZM-06`
betrifft nur noch die Fachapps.
