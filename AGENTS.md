# AGENTS.md - LocalBase

## Projekt

Nextcloud-Hilfsapp `localbase` fuer gemeinsame lokale Basisbausteine eigener Nextcloud-Apps.

Nextcloud-App-ID:

    localbase

## Zweck

LocalBase enthaelt nur app-uebergreifende, fachlich neutrale Basisbausteine, die in mindestens zwei eigenen Apps semantisch identisch gebraucht werden.

Aktuell enthalten:

- PHP-Modelltrait `OCA\LocalBase\Model\ModelApiTrait`.
- PHP-Gruppenhelfer `OCA\LocalBase\Service\GroupProvisioningService` zum idempotenten Anlegen beliebiger Nextcloud-Gruppen.
- JavaScript-Basisklasse `window.LocalBase.models.Model`.
- JavaScript-API-Client `window.LocalBase.api.ApiClient`.
- JavaScript-UI-Primitives `window.LocalBase.ui.byId` und `window.LocalBase.ui.esc`.

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

Wichtige lokale Pruefungen:

    php -l lib/AppInfo/Application.php
    php -l lib/Model/ModelApiTrait.php
    php -l lib/Service/GroupProvisioningService.php
    php tests/Service/GroupProvisioningServiceSmokeTest.php
    node --check js/api/api-client.js
    node --check js/models/model.js
    node --check js/ui/ui.js
    node tests/js/api-client-smoke.js
    node tests/js/ui-smoke.js

## DDEV

Die gemeinsame lokale Nextcloud-DDEV-Umgebung liegt ausserhalb dieses Repos:

    ~/projects/br-nextcloud-apps/nextcloud-dev

Die App wird nach Nextcloud gemountet unter:

    /var/www/html/html/custom_apps/localbase
