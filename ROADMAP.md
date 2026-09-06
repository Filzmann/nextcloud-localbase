# Roadmap – LocalBase

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## App-lokale Migrationsaufgabe

### LB-PRIVACY-PILOT-RETIREMENT – LocalBase-Pilot kontrolliert zurückbauen

Status: offen; die systemweite Reihenfolge und Freigabe wird im kanonischen
Root-Zukunftsplan geführt

- LocalBase bleibt während der Migration ein charakterisierter, aber nicht
  parallel kanonischer Rückfallstand. Die dauerhaft öffentliche
  Privacy-Runtime ist `filzmann_data_protection`.
- App-eigene persönliche UI-Werte, Demo-Registry und verbliebene
  Pilotprovider vollständig inventarisieren und ihre zulässige Projektion
  beziehungsweise begründete Nichtanwendbarkeit festhalten.
- Self-Service, Adminoberfläche, Registry und öffentliche Privacy-Klassen
  erst entfernen, wenn alle vorgesehenen Consumer migriert und Installation,
  Update, Deinstallation sowie Rückbau gemeinsam grün sind.
- Keine fremden Tabellen, Dateien, AppConfig-Werte oder internen Klassen als
  Coverage-Fallback lesen. Fehlende oder inkompatible Provider bleiben
  sichtbar unvollständig.

### LB-L10N – app-lokale LocalBase-Texte lokalisieren

Aktivierung ausschließlich nach Freigabe des systemweiten Root-Vorhabens
`ZM-06`. Nur von LocalBase selbst gerenderte sichtbare Texte, Datumsnamen,
Pluralformen und Platzhalter wechseln auf Nextcloud-l10n; konfigurierte
Eigennamen, technische Schlüssel, API-Werte und Organisationsdaten bleiben
unverändert.

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
