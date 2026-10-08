# AGENTS.md - LocalBase

## Projekt

Nextcloud-Hilfsapp `localbase` fuer gemeinsame lokale Basisbausteine eigener Nextcloud-Apps.

Nextcloud-App-ID:

    localbase

Die priorisierte Produktplanung und offene Entscheidungen stehen in `ROADMAP.md`; verbindliche Fach-, Sicherheits- und Architekturregeln bleiben in dieser Datei.
Der ausführliche Katalog öffentlicher Verträge steht in
`docs/architecture.md`; diese Datei hält die bei jeder Arbeit benötigten
Cross-App-Grenzen und Prüfungen.

## Zweck

LocalBase enthält app-übergreifende Basisbausteine, die in mindestens zwei eigenen Apps semantisch identisch gebraucht werden. Der ausdrücklich gemeinsame FLZ-Organisationsvertrag ist fachlich nicht neutral, gehört aber bewusst hierher, weil Kalender, Urlaub und Assistenzplanung exakt dieselben Gruppen-, Bereichs- und Hierarchieregeln verwenden müssen.

Aktuell enthalten:

- Öffentliche Privacy-Verträge für Nextcloud-User-Subjects,
  `PersonalDataProvider`, feste Provider-Registry-Snapshots und fehlerisolierte
  Aggregation. Self-Service bindet die Session-UID; die Admin-Auskunft verlangt
  die explizit konfigurierte Nextcloud-Gruppe `privacy_admin_group` und bleibt
  ohne Konfiguration deny by default.
- Die flüchtige Self-Service- und Admin-Grundansicht persistiert keine
  Berichtskopie. Sie weist die Betroffenenrechte einmal im Kopf aus und
  zeigt je App weitere Verarbeitungsangaben vor den Datentabellen und gliedert
  danach nach Datentyp. Der Tabellenkopf besteht aus den freigegebenen
  Datenfeldern. Innerhalb eines Datentyps identische Zwecke oder
  Aufbewahrungsaussagen stehen einmal vor der Tabelle; nur unterschiedliche
  Werte bleiben zusätzliche Tabellenspalten.
  Menschenlesbare Datumsangaben verwenden die deutsche Kurzform `TT.MM.JJ`;
  Uhrzeiten werden bei Bedarf als `HH:MM Uhr` ergänzt.
  Derselbe Stand
  kann clientseitig als mehrseitiges PDF heruntergeladen werden; geheime
  Anmeldewerte und Identitäten geschützter Drittpersonen bleiben ausgeschlossen.
  Der app-eigene Nextcloud-Kontoprovider liest Konto, sämtliche nichtleeren
  Nextcloud-Profilfelder einschließlich ihrer Sichtbarkeit und Bestätigung
  sowie die eigenen Gruppenzuordnungen ausschließlich über öffentliche
  Nextcloud-User-, Account- und GroupManager-APIs. Der Bericht weist außerdem
  sichtbar darauf hin, dass er noch nicht die gesamte Instanz abdeckt, und
  benennt die noch nicht implementierten Datenabrufe. Normale Konten erreichen dieselbe kanonische
  Self-Service-Seite über den `Datenschutz`-Eintrag im rechten
  Nextcloud-Benutzermenü und über den persönlichen Einstellungsbereich.
  Der historische Retention-Pilot wurde nach grüner Lifecycle- und
  Rückbaumatrix entfernt; LocalBase koordiniert keine Retention-Ausführung,
  automatische Löschung oder Lifecycle-Provider.
- PHP-API-Responder `OCA\LocalBase\Controller\ApiResponder` fuer einheitliche JSON-Fehlerantworten in Controllern.
- PHP-Modelltrait `OCA\LocalBase\Model\ModelApiTrait`.
- PHP-Logger `OCA\LocalBase\Service\AppLogger` fuer sichere, skalare Log-Kontexte mit App-ID und optionaler User-ID.
- PHP-Gruppenhelfer `OCA\LocalBase\Service\GroupProvisioningService` zum
  idempotenten Anlegen beliebiger Nextcloud-Gruppen und zur read-only Prüfung,
  dass Mitglieder definierter Rollengruppen zugleich einer Basisgruppe
  angehören. Widersprüche werden ohne automatische Mitgliedschaftsänderung
  abgelehnt.
- `BrGroupDefinition` und `BrGroupSettingsService` bilden den versionierten,
  zentral persistierten BR-Gruppenvertrag mit den getrennten semantischen
  Schlüsseln `member`, `chair` und `deputy`. Ein fehlender, ungültiger oder
  hinsichtlich der nativen Nextcloud-Mitgliedschaften widersprüchlicher
  Vertrag ist für Consumer nicht freigabefähig. Vorsitz und Stellvertretung
  müssen immer auch der Mitgliedergruppe angehören.
- `DemoAccountProvisioningService` erzeugt ausschließlich explizit
  registrierte lokale Test- und Demokonten. Für diese Konten gilt
  Benutzername = Passwort; eine erneute Provisionierung stellt diesen Zustand
  nur für dasselbe registrierte Demo-Pack wieder her. Fremde, externe oder
  nicht passwortänderbare Konten werden vor jeder Mutation abgewiesen. Dieser
  bewusst schwache Zugang darf nicht für echte oder produktive Konten
  verwendet werden.
- Neutraler Kalendervertrag `AbsenceEmployeeDiscoveryEvent`/`AbsenceQueryEvent`/`AbsenceInterval` fuer optionale, read-only Abwesenheitsprovider. Die Discovery bleibt auf einen halboffenen Zeitraum begrenzt, liefert ausschließlich normalisierte Konto-UIDs und bleibt ohne Provider leer. `planned` liefert `U?` ohne Blockade, `approved` liefert `U` mit Blockade.
- `CalendarContext` und `CalendarContextSettingsService` definieren Land, ISO-3166-2-Region und fachliche IANA-Zeitzone organisationsweit. `DE`, `DE-BE` und `Europe/Berlin` bleiben Bestandsdefaults. Persönliche Nextcloud-Zeitzonen dürfen ausschließlich individuelle Terminanzeigen beeinflussen. Der Kontext ist im gemeinsamen FLZ-Adminbereich änderbar und wird bei bestehenden persönlichen Dashboardlayouts additiv eingeblendet.
- `HolidayCalendarService` liefert Schulferien und gesetzliche Feiertage als validierten, read-only Jahresvertrag Version 1 für den gemeinsamen Kalenderkontext. Consumer prüfen Version und `cacheStatus`; `stale` bleibt nur mit sichtbarer Aktualitätseinschränkung nutzbar, `unavailable` ist niemals eine konfliktfreie Leerliste. `OpenHolidaysClient` ist der einzige externe Provideradapter; `HolidayCalendarCacheStore` hält regionsgebundene Jahresstände in der LocalBase-AppConfig. Ein täglicher Hintergrundjob aktualisiert das laufende und die zwei folgenden Jahre. Bei Providerfehlern bleibt ein vorhandener Stand als `stale` verfügbar, Erstabrufe werden sicher als `unavailable` ausgewiesen und nach kurzer Sperrfrist erneut versucht.
- `FlzOrganizationDefinition`, `FlzOrganizationSettingsService`, `FlzOrganizationHierarchy` und `FlzOrganizationPermissionPolicy` bilden die konfigurierbaren gemeinsamen FLZ-Gruppen, Anzeigenamen, Bereiche, Teamansichten, Hierarchie und Peer-Grenzen fuer Kalender, Urlaub und Assistenzplanung ab.
- `FlzSuiteAdminSettingsService` speichert app-übergreifend verwendete Peer-Freigaben semantisch nach Rollen und stellt sie Filzmann Kalender, Filzmann Urlaubsplanung und der administrativen OrgSuite-Oberfläche gemeinsam bereit.
- Rollen und Bereiche werden über stabile semantische Schlüssel referenziert; konfigurierbare Nextcloud-Gruppen-IDs oder Anzeigenamen dürfen nicht als Fachschlüssel in App-Code dupliziert werden.
- Die initiale Reihenfolge umfasst Fahrzeugverwaltung nach IT, Empfang nach Sekretariat sowie im Pflegebereich stellvertretende PDL, Büroorganisation Pflege und Pflegefachkraft. Für den Bürobereich bleibt Büroleitung, stellvertretende Büroleitung, Einsatzbegleitung und Büromitarbeiter*innen maßgeblich. Die im Adminbereich gespeicherte Reihenfolge bleibt für alle Verbraucher verbindlich.
- Organisationsvertrag Version 2 ergänzt bestehende Version-1-Einstellungen additiv um `deputy_pdl`, `care_office`, `fleet_management` und `reception`, die freigegebenen Hierarchiekanten sowie Urlaubsansichten. Bestehende Werte und Kanten bleiben erhalten; Gruppen-ID-Kollisionen und Zyklen werden abgelehnt.
- Organisationsvertrag Version 3 trennt `finance` und `payroll` additiv unter `finance_lead`; Version 4 ergänzt die betriebsweit festgelegten Kalenderkürzel `BO`, `EB`, `PFK`, `BO-Pflege`, `IT`, `NO`, `W` und `S`. Die bestehende Finanzgruppen-ID bleibt erhalten. Der read-only `FlzOrganizationSnapshot` enthält nur Rollen und Bereiche, keine Mitgliederlisten, und ist bei fehlender oder ungültiger Persistenz nicht freigabefähig.
- `diagramOrder` speichert davon getrennt ausschließlich die globale Links-rechts-Anordnung der Organigrammkarten innerhalb ihrer Hierarchieebene. Beim horizontalen Drag-and-drop bestimmt der Zwischenraum zwischen zwei Karten die neue Einfügeposition. Diese visuelle Anordnung verändert weder Rollen-/Bereichsreihenfolgen noch Kalender, Rechte oder Hierarchiekanten.
- Das Organigramm bleibt automatisch nach Hierarchieebenen angeordnet; freie X-/Y-Knotenpositionen sind kein Bestandteil des Organisationsvertrags. Karten derselben Ebene stehen waagerecht nebeneinander und verwenden innerhalb definierter Mindest-/Maximalgrenzen nur ihre benötigte Breite; sie brechen nicht in scheinbare zusätzliche Hierarchiezeilen um. Der persönliche Zoom wird in 10-Prozent-Schritten von 50 bis 150 Prozent über `IUserConfig` geräteübergreifend gespeichert. Der verschobene Ausschnitt bleibt wegen unterschiedlicher Viewportgrößen flüchtig. Zoom und Ausschnitt verändern weder Hierarchie und Diagrammordnung noch die logische Größe der Exporte.
- Fachliche Rolleneinstellungen werden über den Edit-Stift der Diagrammkarten in einem zugänglichen Seitenpanel bearbeitet und gelten für alle Diagrammkarten derselben semantischen Rolle. Technische Gruppen-IDs bleiben dort eingeklappt; die für Kalender und Gruppenlisten verbindliche Rollenreihenfolge bleibt als eigene kompakte Drag-and-drop-Liste sichtbar. Bürobereiche und Urlaubsansichten werden als aufklappbare Einstellungskarten dargestellt.
- Hauptblöcke, Organisationsabschnitte und Rechteblöcke der FLZ-Administration sind unabhängig einklappbar und per Drag-and-drop sowie Tastatur verschiebbar. Die Organisationsabschnitte erscheinen als eigenständige Cards in einer ungerahmten Sammlung und nicht gemeinsam in einer äußeren Organisations-Card; die Card mit dem Organigramm belegt dabei immer die volle verfügbare Grid-Breite. Reihenfolge und Einklappzustand sind ausschließlich persönliche UI-Präferenzen, werden über Nextclouds native `IUserConfig` je Konto gespeichert und verändern weder fachliche Reihenfolgen noch Organisations- oder Rechtewerte. Der serverseitige Layoutvertrag akzeptiert nur bekannte Scopes und Block-IDs und ergänzt neue Standardblöcke rückwärtskompatibel.
- Der Organigrammexport arbeitet ausschließlich clientseitig mit dem aktuell sichtbaren Stand. Draw.io enthält editierbare Knoten und Kanten, PNG wird hochauflösend gerendert und PDF als skalierbares Vektordokument erzeugt; alle drei Formate lösen unmittelbar einen Dateidownload aus. Lange Karteninhalte werden in PNG und PDF innerhalb der Karten umgebrochen und nötigenfalls passend verkleinert. Zugeordnete Nutzer*innen werden nur nach ausdrücklicher, standardmäßig deaktivierter Auswahl aufgenommen; es gibt weder Serverablage noch externe Exportdienste.
- Die gemeinsame Organisationsdefinition und app-übergreifende Freigaben werden zentral in der LocalBase-App-Konfiguration gespeichert. Bei einer Einzelinstallation erscheinen sie im Adminabschnitt des Fachprodukts, ab zwei Produkten im Adminabschnitt der OrgSuite. Rein app-spezifische Admin-Einstellungen verbleiben bei der jeweiligen Fachapp.
- Organisationsansichten für den Urlaubsplan sind dynamisch konfigurierbare Rollen-/Bereichsschnitte. Büro Nordost, West und Süd bleiben eigenständige Ansichten, auch wenn eine Leitung mehrere Bereiche führt.
- Die Pflegeansicht beginnt mit der globalen Einzelposition stellvertretende PDL, gefolgt von Büroorganisation Pflege und Pflegefachkräften. Fahrzeugverwaltung und Empfang besitzen eigene globale Organisationsansichten.
- Ungültige Referenzen, doppelte Gruppen-IDs und Hierarchiezyklen werden beim Speichern abgelehnt. Eine ungültige persistierte Definition fällt beim Lesen sicher auf die geprüfte Standarddefinition zurück.
- `ScheduleConflictQueryEvent` liefert vor genehmigten Abwesenheiten read-only Konflikte aus optional aktivierten Planungsapps; Provider loeschen oder aendern dabei keine Daten.
- `IntegrationCapabilityQueryEvent`, `FlzIntegrationCapabilities` und `IntegrationCapabilityService` beschreiben optionale Cross-App-Fähigkeiten. Ein leerer Snapshot ist ein zulässiger Standalone-Zustand und erweitert niemals Berechtigungen.
- `FlzProductCatalog` liest den versionierten FLZ-Produktkatalog als kanonische
  Quelle für Produkt-IDs, Reihenfolge, Routen sowie getrennte Menü-,
  Standalone- und Bundle-Eigenschaften. Ungültige oder fehlende Katalogdaten
  erweitern weder Navigation noch Berechtigungen. Menüfähige
  Entwicklungsprodukte dürfen über explizit falsche Bundle-Flags von
  Release-Artefakten ausgeschlossen bleiben; Delivery-Code verwendet dafür
  ausschließlich die gefilterte Bundle-Produktmenge.
- `StandaloneAppNavigationService` registriert katalogisierte
  Fachapp-Einstiege nur ohne aktive OrgSuite. `FlzProductSuiteService` und die
  dynamischen Settings-Adapter platzieren die gemeinsame
  Organisationsverwaltung bei einer Einzelinstallation unter deren
  Fachprodukt.
- Organisationseditor, Admin-API und Persistenz des gemeinsamen FLZ-Vertrags liegen vollständig in LocalBase. OrgSuite bindet diese Oberfläche ab zwei Produkten nur als Adminadapter ein.
- JavaScript-Basisklasse `window.LocalBase.models.Model`.
- JavaScript-API-Client `window.LocalBase.api.ApiClient`.
- JavaScript-Repository-Basis `window.LocalBase.repositories.Repository`.
- JavaScript-UI-Primitives `window.LocalBase.ui.byId`, `window.LocalBase.ui.esc`, `window.LocalBase.ui.errorMessage` und `window.LocalBase.ui.Notice`.
- Test-Helper fuer app-uebergreifend gleiche, dependency-arme Fakes, Fixtures und Assertions.
- Test-only PHP-Runner `PhpTestRunner`, der Lint- und Smoke-Tests deterministisch sammelt und jeden Test isoliert in einem eigenen PHP-Prozess ausführt.

## Repository und gemeinsamer Arbeitsablauf

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository fuer die Hilfsapp `localbase`.
- Diese Datei und lokal referenzierte Skills bilden bei einem direkten Start in diesem Repository die vollständige Repository-Steuerung.
- Fuer den allgemeinen Git-/Sandboxrahmen gilt der lokal mitgefuehrte Skill `work-in-nextcloud-app`. Oeffentliche LocalBase- oder Cross-App-Vertraege unterliegen dessen Stop-Regel und brauchen einen ausdrücklich beauftragten, aus jedem betroffenen Repository geprüften Cross-App-Lauf.
- Aenderungen muessen app-uebergreifend neutral bleiben.
- Keine Fachlogik aus BRTop, BRStunden oder FlzPlaner hierher verschieben.

## Architekturregeln

- LocalBase bleibt klein und dependency-arm.
- Gemeinsamer Code wird erst hierher verschoben, wenn er in mindestens zwei Apps semantisch gleich gebraucht wird.
- Modelle/DTOs werden in PHP und JavaScript einheitlich angefasst: `get(...)`, `get_all([...])`, `toArray()` und `save()`.
- Nicht persistierbare DTOs duerfen `save()` bewusst mit klarer Fehlermeldung blockieren.
- Neue `fromApi`-/`toApi`-Kompatibilitaetsaliase werden nicht eingefuehrt.
- Gemeinsame API-Helfer kapseln `fetch`, `OC.generateUrl`, JSON-Parsing, CSRF-Token und Fehlerobjekte; App-spezifische Module bleiben nur duenne Adapter.
- Gemeinsame UI-Helfer oder Komponenten werden nur aufgenommen, wenn mindestens zwei Apps dieselbe Semantik, dieselben Zustaende, dieselben Events und dieselben Accessibility-Anforderungen teilen.
- LocalBase darf kleine UI-Primitives wie Escaping, Notices, Button-Helfer oder Formatierer bereitstellen; fachliche Komponenten und app-spezifisches Markup bleiben in den App-Repos.

## Tests

LocalBase ist Multiplikator-Code. Oeffentliche Vertraege muessen bei Aenderungen durch passende Tests abgesichert werden, bevor darauf aufbauende Apps weiter refaktoriert werden.

- Tests sind Teil der Architekturarbeit und kein optionaler Nachtrag. Neue oeffentliche LocalBase-Vertraege bekommen eigene Tests, bevor Apps darauf migriert werden.
- Vor groesseren Refactorings zuerst Charakterisierungstests fuer das bestehende gewuenschte Verhalten schreiben oder aktualisieren.
- Gemeinsame Test-Helper gehoeren zu den LocalBase-Testvertraegen: Sie bleiben test-only, fachlich neutral, dependency-arm und werden in LocalBase selbst getestet.
- Apps sollen gemeinsame Test-Helper nutzen, wenn dadurch echte Setup-Duplizierung verschwindet, ohne dass die Lesbarkeit des einzelnen Tests leidet.
- Bei Aenderungen an PHP-Bausteinen `php tests/run.php` ausfuehren.
- Bei Aenderungen an JavaScript-Bausteinen `node tests/run-js.mjs` ausfuehren.
- Bei Aenderungen an Controller-/DI-nahen Klassen zusaetzlich einen gezielten DDEV-DI-Check ausfuehren.
- Nach LocalBase-Aenderungen die betroffenen App-Contract-/Smoke-Tests laufen lassen.
- PHPUnit/Vitest/Jest erst einfuehren, wenn die einfachen Testlaeufer, Assertion-Helfer, Mocks oder Fixtures selbst spuerbar dupliziert werden.

Wichtige lokale Pruefungen:

    php tests/run.php
    node tests/run-js.mjs

Einzelne Checks, die durch die Testlaeufer gebuendelt werden:

    php -l lib/AppInfo/Application.php
    php -l lib/Controller/ApiResponder.php
    php -l lib/Model/ModelApiTrait.php
    php -l lib/Service/AppLogger.php
    php -l lib/Service/GroupProvisioningService.php
    php tests/Controller/ApiResponderSmokeTest.php
    php tests/Service/AppLoggerSmokeTest.php
    php tests/Service/GroupProvisioningServiceSmokeTest.php
    node --check js/api/api-client.js
    node --check js/models/model.js
    node --check js/repositories/repository.js
    node --check js/ui/ui.js
    node tests/js/api-client-smoke.js
    node tests/js/repository-smoke.js
    node tests/js/ui-smoke.js

## DDEV

Die gemeinsame Nextcloud-DDEV-Umgebung wird aus dem dokumentierten
Parent-Unterverzeichnis `nextcloud-dev` gesteuert. Bei einem eigenständigen
Checkout ist der lokale DDEV-Pfad zuerst anhand der realen Umgebung zu
ermitteln.

Die App wird nach Nextcloud gemountet unter:

    /var/www/html/html/custom_apps/localbase

## Dokumentenverantwortung

- `README.md` beschreibt ausschließlich den aktuellen nutzbaren Stand,
  Installation, Betrieb, Tests und den Dokumentationsindex.
- `ROADMAP.md` enthält ausschließlich offene, zurückgestellte oder
  freigabepflichtige Arbeit und Entscheidungen.
- `CHANGELOG.md` dokumentiert erledigte Änderungen releasebezogen; erledigte
  Checklisten verbleiben nicht in der Roadmap.
- `docs/architecture.md` ist die ausführliche Quelle für geltende fachliche
  und technische Architekturverträge.
- `docs/manual-acceptance.md` enthält wiederholbare manuelle Prüfungen und
  keine Produktplanung.
- `AGENTS.md` enthält ausschließlich verbindliche Arbeits-, Sicherheits-,
  Architektur- und Prüfregeln. Zusätzliche Dokumente werden in `README.md`
  mit eindeutiger Zuständigkeit eingeordnet.

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
