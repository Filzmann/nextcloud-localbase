# AGENTS.md - LocalBase

## Projekt

Test-Harness und leere Kompatibilitäts-Stage (`localbase`) für die lokalen FLZ- und
BR-Nextcloud-Apps. Nextcloud-App-ID: `localbase`.

Die priorisierte Planung und offene Entscheidungen stehen in `ROADMAP.md`;
verbindliche Regeln bleiben in dieser Datei.

## Zweck

Seit dem vollständigen Code-Rückbau am 28. November 2026 (Freigabe Simon,
`LB-RUNTIME-RETIREMENT`) enthält dieses Repository **keine Laufzeit-Pilotkomponenten
mehr**. Die ehemaligen öffentlichen Verträge liegen in den zuständigen Apps:
`OCA\FlzOrganization\PublicApi\V1\*` (Organisation, Kalender, Feiertage),
`OCA\FlzPermissionMatrix\PublicApi\V1\*` (BR-Rollen),
`OCA\FlzDataProtection\PublicApi\V1\*` (Datenschutz-Registry), gemeinsam benötigte
PHP-/JS-Hilfen als app-lokal gebündelte Kategorie-A-Bibliothek (`vendor/flz-shared`).

Verbleibender Zweck:

- Zentraler PHP-Test-Runner und Assertions (`tests/Support/PhpTestRunner.php`,
  `tests/Support/assertions.php`), eingebunden über `OCA\LocalBase\Tests\...` im
  jeweiligen App-Bootstrap (test-only).
- JavaScript-Testrunner und DOM-Fake (`tests/Support/js-runner.mjs`,
  `tests/js/helpers/fake-dom.js`) für Verbraucher-Smokes.
- Coverage-Tooling (`tests/coverage/`: phpcov/c8, `merge-clover.php`) für die
  CI-Coverage-Gates aller Apps.
- Koordinierter Verbraucher-Vertragslauf im eigenen CI-Workflow
  (`consumer-contracts` in `.github/workflows/tests.yml`).
- Registrierungsfreie App-Hülle (`appinfo/info.xml`), damit die wegwerfbare
  DDEV-Compat-Stage die App weiter installieren kann.

`tests/Support/PilotRemovalContractTest.php` sichert diesen Zustand: Pilot-Pfade
dürfen nicht zurückkehren, das Harness muss vollständig bleiben.

## Repository und gemeinsamer Arbeitsablauf

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository.
- Diese Datei und lokal referenzierte Skills bilden bei einem direkten Start in diesem Repository die vollständige Repository-Steuerung.
- Fuer den allgemeinen Git-/Sandboxrahmen gilt der lokal mitgefuehrte Skill `work-in-nextcloud-app`. Oeffentliche Cross-App-Vertraege unterliegen dessen Stop-Regel und brauchen einen ausdrücklich beauftragten, aus jedem betroffenen Repository geprüften Cross-App-Lauf.
- Aenderungen am Harness muessen app-uebergreifend neutral, test-only und dependency-arm bleiben.
- Keine Fachlogik aus BRTop, BRStunden, FilzPlaner oder anderen Fachapps hierher verschieben; Fachverträge gehoeren in die zustaendige Kategorie-B-App.

## Tests

- Bei Aenderungen am PHP-Harness `php tests/run.php` ausfuehren.
- Bei Aenderungen am JS-Harness `node tests/run-js.mjs` ausfuehren.
- Nach Harness-Aenderungen die betroffenen App-Contract-/Smoke-Tests laufen lassen
  (der eigene `consumer-contracts`-CI-Lauf macht das koordiniert).
- PHPUnit/Vitest/Jest erst einfuehren, wenn die einfachen Testlaeufer, Assertion-Helfer, Mocks oder Fixtures selbst spuerbar dupliziert werden.

## DDEV

Die gemeinsame Nextcloud-DDEV-Umgebung wird aus dem dokumentierten
Parent-Unterverzeichnis `nextcloud-dev` gesteuert. Bei einem eigenständigen
Checkout ist der lokale DDEV-Pfad zuerst anhand der realen Umgebung zu
ermitteln.

Die App wird nach Nextcloud gemountet unter:

    /var/www/html/html/custom_apps/localbase

## Dokumentenverantwortung

- `README.md` beschreibt ausschließlich den aktuellen nutzbaren Stand, Betrieb,
  Tests und den Dokumentationsindex.
- `ROADMAP.md` enthält ausschließlich offene, zurückgestellte oder
  freigabepflichtige Arbeit und Entscheidungen.
- `CHANGELOG.md` dokumentiert erledigte Änderungen releasebezogen; erledigte
  Checklisten verbleiben nicht in der Roadmap.
- `docs/` enthält das Staging-Migrations-Runbook; `AGENTS.md` enthält ausschließlich
  verbindliche Arbeits- und Prüfregeln.

## Parent-Governance-Vertrag: 2

- Die für dieses Subrepository anwendbaren Regeln des Parent-Workspaces sind
  verbindlich. Dazu gehören insbesondere app-übergreifende ADRs und
  öffentliche Verträge, Repositorygrenzen sowie Workspace-, Delivery- und
  Release-Gates.
- Diese lokale `AGENTS.md` und die lokalen Skills bleiben die vollständige,
  ohne Parent-Checkout arbeitsfähige Repository-Steuerung. Die anwendbaren
  Parent-Regeln werden dafür hier oder in den lokalen Skills mitgeführt.
- Repository-lokale Regeln dürfen Parent-Verträge konkretisieren und verschärfen,
  aber nicht abschwächen oder umgehen.
- Bei einem Widerspruch gilt bis zur Klärung die strengere Regel. Die Arbeit
  stoppt, bis die kanonische Quelle bestimmt, die Regelprojektionen
  synchronisiert und eine erforderliche Entscheidung dokumentiert ist.
- Ist der Parent-Workspace nicht verfügbar, bleibt die lokale Steuerung
  wirksam. Vor Cross-App-, Release- oder Delivery-Arbeit muss ein vermuteter
  neuerer Parent-Stand oder eine Regelungslücke zuerst gegen den Parent
  geprüft werden.

### Entwicklungsphase und Kompatibilitätsbedarf

Entscheidung vom 5. September 2026: Das Gesamtprojekt befindet sich vollständig
in der Entwicklung. Es gibt kein PROD, keinen produktiven Datenbestand und
keinen bereits betriebenen Bestand mit zu erhaltendem Upgradepfad. STAGING
ist eine wegwerfbare Entwicklungs- und Integrationsumgebung und darf im
konkret beauftragten Reinstall vollständig neu aufgebaut werden. Wenige
externe Testnutzer ändern diese Einordnung nicht.

Vor einer Datenmigration, Legacy-Unterstützung, Compatibility Layer,
Deprecated API, Dual-Read/Dual-Write, einem Altschema-Fallback, Übergangsformat
oder der Unterstützung historischer Entwicklungsstände wird geprüft:

1. Wurde der betroffene Zustand jemals produktiv eingesetzt?
2. Benötigen reale Daten oder Nutzer seine Erhaltung?
3. Gibt es einen anderen konkreten technischen Erhaltungsgrund, insbesondere
   einen geltenden Plattform- oder externen API-Vertrag?

Sind alle relevanten Antworten nein, ist die saubere Breaking-Change-/
Reinstall-Lösung der Standard. Frühere rein interne Entwicklungsstände
begründen weder Abwärtskompatibilität noch eine Deprecationfrist.
Entwicklungsschemata dürfen durch ein kanonisches Installationsschema ersetzt,
alte interne APIs und Konfigurationsformate samt ausschließlich dafür
benötigten Adaptern und Tests entfernt werden. Architekturqualität und der
saubere Zielzustand haben Vorrang. Nextclouds nötige Installationsmigrationen
bleiben erhalten; ein Verzeichnisname `Migration` beweist keine Altlast.

Breaking Changes werden im selben Änderungskontext vollständig durchgezogen:
betroffene Provider, Consumer, standardisierte APIs, Vertragsversionen,
Metadaten, Tests und Dokumentation müssen zusammenpassen. Unterstützte
Nextcloud-/openDesk-Plattformverträge, externe Standards, Autorisierung und
Datenschutz gelten unverändert. Fehlende oder inkompatible optionale Provider
bleiben kontrolliert sichtbar. Ein Reinstall erlaubt keine privaten
Fremdtabellenzugriffe oder parallel erfundenen Plattformmechanismen.

Vor destruktiver Arbeit werden die tatsächlich benötigten externen
Testidentitäten, Gruppen, Rollen und nicht reproduzierbaren Testdaten gezielt
gesichert oder über bestehende native Setup-Strukturen reproduzierbar gemacht.
Echte Personen- und Zugangsdaten bleiben außerhalb von Git. Diese begrenzte
Sicherung begründet keine allgemeine Legacy-Unterstützung. Ein Reinstall
bleibt ein normaler unterstützter Entwicklungsweg; der vorhandene
Compatibility-Workflow besitzt den Fresh-Install-Nachweis, dessen aktueller
Belegstatus in `docs/workspace.md` beschrieben ist.

Diese Phase endet ausschließlich durch einen ausdrücklich dokumentierten,
von Simon freigegebenen **Production-Readiness-/Production-Freeze-Entscheid**.
Ein Release Candidate, eine Versionsnummer, ein Staging-Deployment oder ein
externer Testzugang lösen den Wechsel nicht aus. Der Entscheid wird in dieser
kanonischen Lifecycle-Quelle mit Datum, Geltungsbereich und betroffenem
Versions-/Datenstand festgehalten und in die lokale Steuerung projiziert.
Dann werden Upgradepfade, Datenbankmigrationen, Persistenz, Backup/Restore,
Rollback, Release-/API-Kompatibilitätszusagen, Deployment-/Freigabeprozess und
PROD→STAGING/COPY-Strategie neu bewertet. Eine vollständige PROD-Governance
wird jetzt nicht vorweggenommen.

Diese Regel entscheidet den Kompatibilitätsbedarf, erweitert aber keinen
Repository-Schreibauftrag und ersetzt keine Freigabe für eine konkrete
destruktive Aktion. Lokale Regelprojektionen folgen dem bestehenden
`docs/parent-governance-contract.md`; ein unsynchronisierter Einzel-Checkout
darf keinen abweichenden Phasenstand stillschweigend annehmen.

### Prüfaufwand

- Vor einem neuen Test, Scan, Linter, Architektur- oder Systemcheck wird
  geprüft, welcher bestehende Check dieselbe Eigenschaft bereits nachweist.
  Diesen erweitern oder sein nachweislich passendes Ergebnis wiederverwenden;
  ein zusätzlicher Check braucht eine benannte zusätzliche Fehlerklasse oder
  Vertrauensgrenze. Gleicher Input, gleiche Prüfung, gleiche Fehlerklasse und
  gleiche Phase begründen keinen zweiten Lauf. Gestaffelte Unit-, Contract-
  und Runtime-Nachweise bleiben erhalten. Die dokumentierten lokalen
  Prüfeinstiege bestimmen Umfang und Ergebnisgültigkeit.
