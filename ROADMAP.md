# Roadmap – LocalBase

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## App-lokale Migrationsaufgabe

### LB-PERSONAL-DATA-PILOT-RETIREMENT – verbliebenen Art.-15-Piloten kontrolliert zurückbauen

- Rückbau der alten LocalBase-Self-Service-/Adminoberfläche und ihrer internen
  PersonalData-Klassen erst nach einem grünen Runtime- und Rückbaunachweis
  planen und freigeben. Ein Datenfallback über fremde Speicher bleibt verboten.
- Die im app-lokalen Processing-Katalog sichtbaren Entscheidungen zu
  Rechtsgrundlage und betrieblichem Backupdurchgriff benötigen vor einer
  produktiven Verarbeitung eine fachliche Freigabe.

## Aktueller Fokus

- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Bestehende gemeinsame Modelle, API-, UI-, Organisations-, Integrations- und Testverträge klein, dependency-arm und stabil halten.
- Öffentliche Verträge mit den betroffenen Consumer-Apps auf einem realitätsnahen Staging und durch Contract-Tests absichern.
- Den Organisationseditor mit realen Gruppenbesetzungen und großen Organisationsstrukturen visuell und fachlich abnehmen.

## Geplante Erweiterungen

- Die zustandslosen, semantisch gemeinsam benötigten Hilfen werden gemäß
  Parent-ADR 0001 perspektivisch als app-lokal gebündelte Kategorie-A-Bibliothek
  ausgeliefert. Die bestehende Kategorie-B-Laufzeitapp wird erst nach
  kontrollierter Migration ihrer persistierenden Organisations-, Kalender-,
  Admin- und Jobanteile zurückgebaut.
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

## Bewusst zurückgestellt – niedrigste Priorität

### LB-L10N – app-lokale LocalBase-Texte lokalisieren

Status seit 17. September 2026: Die Umsetzung beginnt erst nach allen höher
priorisierten Roadmap-Aufgaben und einer erneuten ausdrücklichen Freigabe des
Root-Vorhabens `ZM-06`. Neue Funktionen und Codeänderungen berücksichtigen
die spätere Lokalisierbarkeit an den jeweils berührten Stellen, lösen aber
keine flächige Umstellung oder Übersetzungsimplementierung aus.

Bei der späteren Umsetzung wechseln nur von LocalBase selbst gerenderte
sichtbare Texte, Datumsnamen, Pluralformen und Platzhalter auf
Nextcloud-l10n; konfigurierte Eigennamen, technische Schlüssel, API-Werte
und Organisationsdaten bleiben unverändert.
