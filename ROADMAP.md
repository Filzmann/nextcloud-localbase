# Roadmap – LocalBase

Diese Datei bündelt geplante Erweiterungen und offene Architekturentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Freigegebene Umsetzungsaufgaben

### LB-BR-GROUPS – Gemeinsamen BR-Gruppenvertrag bereitstellen

Status: bereit nach Klärung der Mitgliedschaftsinvariante

- Konfigurierbare semantische Schlüssel für BR-Mitglieder, Vorsitz und
  Stellvertretung bereitstellen; die drei Bedeutungen bleiben getrennt.
- Bestehende Gruppennamen additiv übernehmen. Der Provider benennt oder
  löscht keine Gruppen und verändert keine Mitgliedschaften.
- Fehlende, doppelte oder widersprüchliche Gruppenbezüge sicher ablehnen.
- Vor Implementierung entscheiden, ob Vorsitz und Stellvertretung zwingend
  zugleich Mitglieder der allgemeinen BR-Gruppe sein müssen.
- Provider-, Migrations- und Deny-Tests gemeinsam mit
  `BRT-BR-GROUPS` und `BRS-BR-GROUPS` abnehmen.

## Zukunftsplanung – nicht freigegeben

### LB-L10N – LocalBase-Oberflächen vollständig lokalisieren

Status: später, nicht freigegeben; Pilot-App, Reihenfolge und Rohtext-Gate
werden vor jeder Umsetzung appübergreifend separat freigegeben

- Nur von LocalBase selbst gerenderte sichtbare Texte, Meldungen,
  Datumsnamen, Pluralformen und Platzhalter auf Nextcloud-l10n umstellen.
- Konfigurierte Eigennamen, technische Schlüssel, API-Werte und
  Organisationsdaten unverändert lassen.
- Deutsche Ausgabe, eine weitere Locale, Fallback, Pluralformen,
  Platzhalter und Escaping in PHP und JavaScript testen.
- Erst nach vollständiger Migration einen Rohtext-Check für LocalBase
  verbindlich schalten.

## Aktueller Fokus

- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Bestehende gemeinsame Modelle, API-, UI-, Organisations-, Integrations- und Testverträge klein, dependency-arm und stabil halten.
- Öffentliche Verträge mit den betroffenen Consumer-Apps auf einem realitätsnahen Staging und durch Contract-Tests absichern.
- Den Organisationseditor mit realen Gruppenbesetzungen und großen Organisationsstrukturen visuell und fachlich abnehmen.

## Geplante Erweiterungen

- Neue gemeinsame Bausteine werden erst aufgenommen, wenn mindestens zwei Apps dieselbe Semantik und einen gemeinsam testbaren Vertrag benötigen.
- Die geplante Kalendersynchronisation bleibt zunächst eine AD-Kalender-Anforderung. Ein gemeinsamer LocalBase-Vertrag wird erst nach einem zweiten semantisch gleichen Bedarf bewertet.
- Test-Helper werden nur bei konkret nachgewiesener app-übergreifender Duplizierung ergänzt.

## Vor der Umsetzung zu klären

- Provider und Consumer, exakter öffentlicher Vertrag sowie Verhalten bei fehlenden Apps.
- Rechte-, Datenschutz-, Versions- und Rückwärtskompatibilitätsfolgen.
- Contract-Tests in LocalBase und in jeder betroffenen Consumer-App.

### Vor einem weiteren Ausbau des Organigramms zu klären

- Bedarf und Vertrag für Suche oder einen temporären Zweigfokus bei Organisationen, die deutlich größer als die aktuelle AD-Struktur sind.
- Weiterführende Screenreader-Navigation zwischen Diagrammknoten und Verbindungen über die vorhandene textliche Alternative hinaus.
