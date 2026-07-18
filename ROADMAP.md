# Roadmap – LocalBase

Diese Datei bündelt geplante Erweiterungen und offene Architekturentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Aktueller Fokus

- Bestehende gemeinsame Modelle, API-, UI-, Organisations-, Integrations- und Testverträge klein, dependency-arm und stabil halten.
- Öffentliche Verträge mit den betroffenen Consumer-Apps auf einem realitätsnahen Staging und durch Contract-Tests absichern.
- Den Organisationseditor mit realen Gruppenbesetzungen und großen Organisationsstrukturen visuell und fachlich abnehmen.

## Umgesetzt

- Rollen und Bereiche lassen sich im Adminbereich per Drag-and-drop oder gleichwertig per Tastatur sortieren. Drag-and-drop im Organigramm darf außerdem direkte Hierarchiebeziehungen ändern; die serverseitige Zyklusprüfung bleibt verbindlich.
- Das Organigramm verwendet kompakte Diagrammknoten, Positionierung sowie gerichtete Pfeile. Bereichsrollen werden je Bürobereich aufgefächert; Verbindungen zwischen zwei Bereichsrollen gelten jeweils innerhalb desselben Bereichs.
- Karten derselben Hierarchieebene lassen sich per Drag-and-drop einschließlich einer Ablage zwischen zwei Karten sowie über zugängliche Links-/Rechts-Schaltflächen global anordnen. Diese Diagrammordnung ist eine rein visuelle Organisationsdarstellung und bleibt technisch von der fachlichen Rollen-/Bereichsreihenfolge getrennt.
- Das aktuell sichtbare Organigramm lässt sich clientseitig als bearbeitbares Draw.io-Diagramm, hochauflösendes PNG und über eine druckoptimierte Vektoransicht als PDF exportieren. Die Aufnahme zugeordneter Nutzer*innen muss für jeden Export ausdrücklich aktiviert werden.
- Rollen können ausdrücklich als Einzelposition markiert werden. Organisationsweite Einzelpositionen sowie bereichsbezogene BL-/StvBL-Positionen zeigen ihre Nextcloud-Gruppenbesetzung im Diagramm; fehlende und mehrfache Besetzungen werden sichtbar diagnostiziert.
- Die Gruppenoptionen werden über einen Edit-Stift der Diagrammkarten in einem Seitenpanel mit sichtbaren Erklärungen ihrer fachlichen Wirkung bearbeitet. Technische Zuordnungen sind eingeklappt; die fachliche Reihenfolge bleibt separat kompakt sortierbar, Bereiche und Urlaubsansichten erscheinen als aufklappbare Karten.
- Alle Haupt-, Organisations- und Rechteblöcke der AD-Administration lassen sich wie Dashboard-Widgets unabhängig einklappen und per Drag-and-drop oder Tastatur verschieben. Die Organisationsbereiche stehen dabei als eigenständige Cards ohne gemeinsamen äußeren Kasten; das Organigramm nutzt stets die volle verfügbare Breite. Diese Ansicht wird über die native Nextcloud-Benutzerkonfiguration persönlich und geräteübergreifend gespeichert; fachliche Reihenfolgen und Rechte bleiben davon getrennt.
- Das Organigramm bleibt automatisch hierarchisch angeordnet. Kompakte Karten benötigen innerhalb einer Hierarchieebene nur ihre in Grenzen variable Inhaltsbreite und stehen platzsparend nebeneinander. Die Ansicht unterstützt einen zugänglichen persönlichen Zoom von 50 bis 150 Prozent sowie Scrollen und Zeiger-Pan des sichtbaren Ausschnitts. Nur der Zoom wird geräteübergreifend gespeichert; Exporte verwenden weiterhin die vollständige logische Diagrammgröße.
- Organisationsvertrag Version 2 ergänzt stellvertretende PDL, Büroorganisation Pflege, Fahrzeugverwaltung und Empfang additiv in bestehenden Konfigurationen. Stv. PDL führt Pflegefachkräfte und Büroorganisation Pflege; Fahrzeugverwaltung ist GF-Digi und Empfang dem Sekretariat unterstellt.

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
