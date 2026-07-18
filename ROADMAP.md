# Roadmap – LocalBase

Diese Datei bündelt geplante Erweiterungen und offene Architekturentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Aktueller Fokus

- Bestehende gemeinsame Modelle, API-, UI-, Organisations-, Integrations- und Testverträge klein, dependency-arm und stabil halten.
- Öffentliche Verträge mit den betroffenen Consumer-Apps auf einem realitätsnahen Staging und durch Contract-Tests absichern.
- Den Organisationseditor mit realen Gruppenbesetzungen und großen Organisationsstrukturen visuell und fachlich abnehmen.

## Umgesetzt

- Rollen und Bereiche lassen sich im Adminbereich per Drag-and-drop oder gleichwertig per Tastatur sortieren. Drag-and-drop im Organigramm darf außerdem direkte Hierarchiebeziehungen ändern; die serverseitige Zyklusprüfung bleibt verbindlich.
- Das Organigramm verwendet kompakte Diagrammknoten, Positionierung sowie gerichtete Pfeile. Bereichsrollen werden je Bürobereich aufgefächert; Verbindungen zwischen zwei Bereichsrollen gelten jeweils innerhalb desselben Bereichs.
- Rollen können ausdrücklich als Einzelposition markiert werden. Organisationsweite Einzelpositionen sowie bereichsbezogene BL-/StvBL-Positionen zeigen ihre Nextcloud-Gruppenbesetzung im Diagramm; fehlende und mehrfache Besetzungen werden sichtbar diagnostiziert.
- Die Gruppenoptionen im Organisationseditor enthalten sichtbare Erklärungen ihrer fachlichen Wirkung.

## Geplante Erweiterungen

- Neue gemeinsame Bausteine werden erst aufgenommen, wenn mindestens zwei Apps dieselbe Semantik und einen gemeinsam testbaren Vertrag benötigen.
- Die geplante Kalendersynchronisation bleibt zunächst eine AD-Kalender-Anforderung. Ein gemeinsamer LocalBase-Vertrag wird erst nach einem zweiten semantisch gleichen Bedarf bewertet.
- Test-Helper werden nur bei konkret nachgewiesener app-übergreifender Duplizierung ergänzt.

## Vor der Umsetzung zu klären

- Provider und Consumer, exakter öffentlicher Vertrag sowie Verhalten bei fehlenden Apps.
- Rechte-, Datenschutz-, Versions- und Rückwärtskompatibilitätsfolgen.
- Contract-Tests in LocalBase und in jeder betroffenen Consumer-App.

### Vor einem weiteren Ausbau des Organigramms zu klären

- Bedarf und Vertrag für manuelle Knotenpositionierung, Zoom und eine Druckansicht.
- Verhalten und responsive Anordnung bei Organisationen, die deutlich größer als die aktuelle AD-Struktur sind.
- Weiterführende Screenreader-Navigation zwischen Diagrammknoten und Verbindungen über die vorhandene textliche Alternative hinaus.
- Welche Teile der Reihenfolge und Diagrammanordnung fachliche Organisationsdaten sind und welche lediglich persönliche oder globale Darstellungspräferenzen darstellen.
