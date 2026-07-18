# AGENTS.md - LocalBase

## Projekt

Nextcloud-Hilfsapp `localbase` fuer gemeinsame lokale Basisbausteine eigener Nextcloud-Apps.

Nextcloud-App-ID:

    localbase

Die priorisierte Produktplanung und offene Entscheidungen stehen in `ROADMAP.md`; verbindliche Fach-, Sicherheits- und Architekturregeln bleiben in dieser Datei.

## Zweck

LocalBase enthält app-übergreifende Basisbausteine, die in mindestens zwei eigenen Apps semantisch identisch gebraucht werden. Der ausdrücklich gemeinsame AD-Organisationsvertrag ist fachlich nicht neutral, gehört aber bewusst hierher, weil Kalender, Urlaub und Assistenzplanung exakt dieselben Gruppen-, Bereichs- und Hierarchieregeln verwenden müssen.

Aktuell enthalten:

- PHP-API-Responder `OCA\LocalBase\Controller\ApiResponder` fuer einheitliche JSON-Fehlerantworten in Controllern.
- PHP-Modelltrait `OCA\LocalBase\Model\ModelApiTrait`.
- PHP-Logger `OCA\LocalBase\Service\AppLogger` fuer sichere, skalare Log-Kontexte mit App-ID und optionaler User-ID.
- PHP-Gruppenhelfer `OCA\LocalBase\Service\GroupProvisioningService` zum idempotenten Anlegen beliebiger Nextcloud-Gruppen.
- Neutraler Kalendervertrag `AbsenceQueryEvent`/`AbsenceInterval` fuer optionale, read-only Abwesenheitsprovider. `planned` liefert `U?` ohne Blockade, `approved` liefert `U` mit Blockade.
- `AdOrganizationDefinition`, `AdOrganizationSettingsService`, `AdOrganizationHierarchy` und `AdOrganizationPermissionPolicy` bilden die konfigurierbaren gemeinsamen AD-Gruppen, Anzeigenamen, Bereiche, Teamansichten, Hierarchie und Peer-Grenzen fuer Kalender, Urlaub und Assistenzplanung ab.
- `AdSuiteAdminSettingsService` speichert app-übergreifend verwendete Peer-Freigaben semantisch nach Rollen und stellt sie AD Kalender, AD Urlaub und der administrativen OrgSuite-Oberfläche gemeinsam bereit.
- Rollen und Bereiche werden über stabile semantische Schlüssel referenziert; konfigurierbare Nextcloud-Gruppen-IDs oder Anzeigenamen dürfen nicht als Fachschlüssel in App-Code dupliziert werden.
- Die initiale Reihenfolge umfasst Fahrzeugverwaltung nach IT, Empfang nach Sekretariat sowie im Pflegebereich stellvertretende PDL, Büroorganisation Pflege und Pflegefachkraft. Für den Bürobereich bleibt Büroleitung, stellvertretende Büroleitung, Einsatzbegleitung und Büromitarbeiter*innen maßgeblich. Die im Adminbereich gespeicherte Reihenfolge bleibt für alle Verbraucher verbindlich.
- Organisationsvertrag Version 2 ergänzt bestehende Version-1-Einstellungen additiv um `deputy_pdl`, `care_office`, `fleet_management` und `reception`, die freigegebenen Hierarchiekanten sowie Urlaubsansichten. Bestehende Werte und Kanten bleiben erhalten; Gruppen-ID-Kollisionen und Zyklen werden abgelehnt.
- `diagramOrder` speichert davon getrennt ausschließlich die globale Links-rechts-Anordnung der Organigrammkarten innerhalb ihrer Hierarchieebene. Beim horizontalen Drag-and-drop bestimmt der Zwischenraum zwischen zwei Karten die neue Einfügeposition. Diese visuelle Anordnung verändert weder Rollen-/Bereichsreihenfolgen noch Kalender, Rechte oder Hierarchiekanten.
- Das Organigramm bleibt automatisch nach Hierarchieebenen angeordnet; freie X-/Y-Knotenpositionen sind kein Bestandteil des Organisationsvertrags. Der persönliche Zoom wird in 10-Prozent-Schritten von 50 bis 150 Prozent über `IUserConfig` geräteübergreifend gespeichert. Der verschobene Ausschnitt bleibt wegen unterschiedlicher Viewportgrößen flüchtig. Zoom und Ausschnitt verändern weder Hierarchie und Diagrammordnung noch die logische Größe der Exporte.
- Fachliche Rolleneinstellungen werden über den Edit-Stift der Diagrammkarten in einem zugänglichen Seitenpanel bearbeitet und gelten für alle Diagrammkarten derselben semantischen Rolle. Technische Gruppen-IDs bleiben dort eingeklappt; die für Kalender und Gruppenlisten verbindliche Rollenreihenfolge bleibt als eigene kompakte Drag-and-drop-Liste sichtbar. Bürobereiche und Urlaubsansichten werden als aufklappbare Einstellungskarten dargestellt.
- Hauptblöcke, Organisationsabschnitte und Rechteblöcke der AD-Administration sind unabhängig einklappbar und per Drag-and-drop sowie Tastatur verschiebbar. Reihenfolge und Einklappzustand sind ausschließlich persönliche UI-Präferenzen, werden über Nextclouds native `IUserConfig` je Konto gespeichert und verändern weder fachliche Reihenfolgen noch Organisations- oder Rechtewerte. Der serverseitige Layoutvertrag akzeptiert nur bekannte Scopes und Block-IDs und ergänzt neue Standardblöcke rückwärtskompatibel.
- Der Organigrammexport arbeitet ausschließlich clientseitig mit dem aktuell sichtbaren Stand. Draw.io enthält editierbare Knoten und Kanten, PNG wird hochauflösend gerendert und PDF über eine druckoptimierte Vektoransicht erzeugt. Zugeordnete Nutzer*innen werden nur nach ausdrücklicher, standardmäßig deaktivierter Auswahl aufgenommen; es gibt weder Serverablage noch externe Exportdienste.
- Die gemeinsame Organisationsdefinition und app-übergreifende Freigaben werden zentral in der LocalBase-App-Konfiguration gespeichert. Bei einer Einzelinstallation erscheinen sie im Adminabschnitt des Fachprodukts, ab zwei Produkten im Adminabschnitt der OrgSuite. Rein app-spezifische Admin-Einstellungen verbleiben bei der jeweiligen Fachapp.
- Organisationsansichten für den Urlaubsplan sind dynamisch konfigurierbare Rollen-/Bereichsschnitte. Büro Nordost, West und Süd bleiben eigenständige Ansichten, auch wenn eine Leitung mehrere Bereiche führt.
- Die Pflegeansicht beginnt mit der globalen Einzelposition stellvertretende PDL, gefolgt von Büroorganisation Pflege und Pflegefachkräften. Fahrzeugverwaltung und Empfang besitzen eigene globale Organisationsansichten.
- Ungültige Referenzen, doppelte Gruppen-IDs und Hierarchiezyklen werden beim Speichern abgelehnt. Eine ungültige persistierte Definition fällt beim Lesen sicher auf die geprüfte Standarddefinition zurück.
- `ScheduleConflictQueryEvent` liefert vor genehmigten Abwesenheiten read-only Konflikte aus optional aktivierten Planungsapps; Provider loeschen oder aendern dabei keine Daten.
- `IntegrationCapabilityQueryEvent`, `AdIntegrationCapabilities` und `IntegrationCapabilityService` beschreiben optionale Cross-App-Fähigkeiten. Ein leerer Snapshot ist ein zulässiger Standalone-Zustand und erweitert niemals Berechtigungen.
- `StandaloneAppNavigationService` registriert Fachapp-Einstiege nur ohne aktive OrgSuite. `AdProductSuiteService` und die dynamischen Settings-Adapter platzieren die gemeinsame Organisationsverwaltung bei einer Einzelinstallation unter deren Fachprodukt.
- Organisationseditor, Admin-API und Persistenz des gemeinsamen AD-Vertrags liegen vollständig in LocalBase. OrgSuite bindet diese Oberfläche ab zwei Produkten nur als Adminadapter ein.
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
- Keine Fachlogik aus BRTop, BRStunden oder AdPlaner hierher verschieben.

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

Die gemeinsame lokale Nextcloud-DDEV-Umgebung liegt ausserhalb dieses Repos:

    ~/projects/br-nextcloud-apps/nextcloud-dev

Die App wird nach Nextcloud gemountet unter:

    /var/www/html/html/custom_apps/localbase
