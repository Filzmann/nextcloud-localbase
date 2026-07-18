# Roadmap – LocalBase

Diese Datei bündelt geplante Erweiterungen und offene Architekturentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Aktueller Fokus

- Bestehende gemeinsame Modelle, API-, UI-, Organisations-, Integrations- und Testverträge klein, dependency-arm und stabil halten.
- Öffentliche Verträge mit den betroffenen Consumer-Apps auf einem realitätsnahen Staging und durch Contract-Tests absichern.

## Geplante Erweiterungen

- Neue gemeinsame Bausteine werden erst aufgenommen, wenn mindestens zwei Apps dieselbe Semantik und einen gemeinsam testbaren Vertrag benötigen.
- Der Organisationseditor soll die fachliche Reihenfolge der Organisationsgruppen per Drag-and-drop pflegbar machen. Eine gleichwertige Tastaturbedienung muss dieselbe Reihenfolge ändern können.
- Die Organisationsstruktur soll als leicht erfassbares Organigramm dargestellt werden: kompakte Knoten statt textlastiger Kästen, Hierarchie und Verbindungen vorrangig über Position, Linien und gerichtete Pfeile sowie eine Diagrammdarstellung nach dem visuellen Prinzip gängiger Werkzeuge wie draw.io.
- Leitungspositionen, denen fachlich genau eine Person zugeordnet ist, zeigen diese Person direkt im zugehörigen Diagrammknoten. Die Darstellung erweitert keine Gruppen-, Rollen- oder Bearbeitungsrechte.
- Die geplante Kalendersynchronisation bleibt zunächst eine AD-Kalender-Anforderung. Ein gemeinsamer LocalBase-Vertrag wird erst nach einem zweiten semantisch gleichen Bedarf bewertet.
- Test-Helper werden nur bei konkret nachgewiesener app-übergreifender Duplizierung ergänzt.

## Vor der Umsetzung zu klären

- Provider und Consumer, exakter öffentlicher Vertrag sowie Verhalten bei fehlenden Apps.
- Rechte-, Datenschutz-, Versions- und Rückwärtskompatibilitätsfolgen.
- Contract-Tests in LocalBase und in jeder betroffenen Consumer-App.

### Vor dem Organigramm und der Sortierung zu klären

- Ob Drag-and-drop ausschließlich die Reihenfolge gleichrangiger Gruppen ändert oder auch Hierarchiebeziehungen verschieben darf; eine reine Darstellungsänderung darf Rechte und Unterstellungsverhältnisse nicht stillschweigend verändern.
- Welche Organisationselemente eigene Diagrammknoten erhalten und welche Beziehungen als Leitung, Unterstellung, Bereichszuordnung oder sonstige Verbindung dargestellt werden.
- Wie Mehrfachzuordnungen, bereichsübergreifende Leitungen und Positionen ohne beziehungsweise mit mehreren Mitgliedern angezeigt werden.
- Für welche Leitungspositionen die fachliche Ein-Personen-Regel gilt, wie sie validiert wird und welches Verhalten bei Abweichungen sicher und verständlich ist.
- Gewünschte Leserichtung, automatische Anordnung, manuelle Positionierung, Zoom, große Organisationen, responsive Darstellung und gegebenenfalls Druckansicht.
- Tastaturbedienung, Fokusreihenfolge, Screenreader-Alternative und textliche Kennzeichnung der Pfeilbedeutungen; Information darf nicht nur über Position, Form oder Farbe vermittelt werden.
- Welche Teile der Reihenfolge und Diagrammanordnung fachliche Organisationsdaten sind und welche lediglich persönliche oder globale Darstellungspräferenzen darstellen.
