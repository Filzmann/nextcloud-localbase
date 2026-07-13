# AGENTS.md - LocalBase

## Projekt

Nextcloud-Hilfsapp `localbase` fuer gemeinsame lokale Basisbausteine eigener Nextcloud-Apps.

Nextcloud-App-ID:

    localbase

## Zweck

LocalBase enthält app-übergreifende Basisbausteine, die in mindestens zwei eigenen Apps semantisch identisch gebraucht werden. Der ausdrücklich gemeinsame AD-Organisationsvertrag ist fachlich nicht neutral, gehört aber bewusst hierher, weil Kalender, Urlaub und Assistenzplanung exakt dieselben Gruppen-, Bereichs- und Hierarchieregeln verwenden müssen.

Aktuell enthalten:

- PHP-API-Responder `OCA\LocalBase\Controller\ApiResponder` fuer einheitliche JSON-Fehlerantworten in Controllern.
- PHP-Modelltrait `OCA\LocalBase\Model\ModelApiTrait`.
- PHP-Logger `OCA\LocalBase\Service\AppLogger` fuer sichere, skalare Log-Kontexte mit App-ID und optionaler User-ID.
- PHP-Gruppenhelfer `OCA\LocalBase\Service\GroupProvisioningService` zum idempotenten Anlegen beliebiger Nextcloud-Gruppen.
- Neutraler Kalendervertrag `AbsenceQueryEvent`/`AbsenceInterval` fuer optionale, read-only Abwesenheitsprovider. `planned` liefert `U?` ohne Blockade, `approved` liefert `U` mit Blockade.
- `AdOrganizationDefinition`, `AdOrganizationSettingsService`, `AdOrganizationHierarchy` und `AdOrganizationPermissionPolicy` bilden die konfigurierbaren gemeinsamen AD-Gruppen, Anzeigenamen, Bereiche, Teamansichten, Hierarchie und Peer-Grenzen fuer Kalender, Urlaub und Assistenzplanung ab.
- Rollen und Bereiche werden über stabile semantische Schlüssel referenziert; konfigurierbare Nextcloud-Gruppen-IDs oder Anzeigenamen dürfen nicht als Fachschlüssel in App-Code dupliziert werden.
- Die Definition wird zentral in der LocalBase-App-Konfiguration gespeichert und ausschließlich über die administrative Organisationsansicht im Einstellungs-Tab des AD Kalenders bearbeitet.
- Ungültige Referenzen, doppelte Gruppen-IDs und Hierarchiezyklen werden beim Speichern abgelehnt. Eine ungültige persistierte Definition fällt beim Lesen sicher auf die geprüfte Standarddefinition zurück.
- `ScheduleConflictQueryEvent` liefert vor genehmigten Abwesenheiten read-only Konflikte aus optional aktivierten Planungsapps; Provider loeschen oder aendern dabei keine Daten.
- JavaScript-Basisklasse `window.LocalBase.models.Model`.
- JavaScript-API-Client `window.LocalBase.api.ApiClient`.
- JavaScript-Repository-Basis `window.LocalBase.repositories.Repository`.
- JavaScript-UI-Primitives `window.LocalBase.ui.byId`, `window.LocalBase.ui.esc`, `window.LocalBase.ui.errorMessage` und `window.LocalBase.ui.Notice`.
- Test-Helper fuer app-uebergreifend gleiche, dependency-arme Fakes, Fixtures und Assertions.

## Git- und Arbeitsregeln

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository fuer die Hilfsapp `localbase`.
- Keine Commits, kein Push und kein Deployment ohne ausdrueckliche Freigabe durch Simon.
- Vor Commits immer `git status --short`, `git diff --stat` und `git diff --name-only` zeigen.
- Nicht `git add .` verwenden; Dateien gezielt stagen.
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
