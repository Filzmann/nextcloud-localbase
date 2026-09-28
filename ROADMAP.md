# Roadmap – LocalBase

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## App-lokale Migrationsaufgabe

### LB-PERSONAL-DATA-PILOT-RETIREMENT – verbliebenen Art.-15-Piloten kontrolliert zurückbauen

Status: Der LocalBase-Retention-Pilot wurde am 24. September 2026 nach grüner
Nextcloud-34-Lifecycle- und Rückbaumatrix physisch entfernt. Offen bleibt nur
der getrennte PersonalData-Pilot.

Die app-lokale Persistenzinventur ist abgeschlossen:

- `IUserConfig` speichert unter `ad_suite_admin_dashboard_layout` je UID nur
  Reihenfolge, Einklappzustand und Organigramm-Zoom des gemeinsamen
  AD-Adminbereichs; Freitext und Drittpersonenwerte sind nicht vorgesehen.
- `IAppConfig` speichert unter `demo_account_registry` die UIDs synthetischer
  Demokonten mit Owner-App-ID und Backendklasse. Die nativen Konten und
  Gruppenmitgliedschaften bleiben Eigentum von Nextcloud; die Registry ist ein
  eigener personenbezogener LocalBase-Nebenspeicher.
- Der verbleibende `NextcloudAccountPersonalDataProvider` liest ausschließlich
  native Kontoprofil- und Gruppendaten und ist keine Projektion der beiden
  LocalBase-eigenen Speicher. Organisations-, Kalender-, Capability- und
  Produktkatalogwerte enthalten nach dem Codeinventar keine kopierten
  Mitgliederlisten. OrgSuite persistiert als Adminadapter keine eigenen
  Personenwerte.

Der lazy registrierte Standalone-V1-Provider und der Processing-Metadata-Katalog
decken Adminlayout und Demo-Registry inzwischen subjectgebunden ab. Persönlicher
Reset, native UserConfig-Kontobindung, Kontolöschlistener und Bereinigung
verwaister Demo-Registrierungen sind testgestützt umgesetzt. Rechtsgrundlage und
betrieblicher Backupdurchgriff bleiben im Katalog sichtbar als
`PRIVACY-DECISION-REQUIRED` ausgewiesen. Offen ist damit nur noch der getrennt
zu planende Rückbau der alten LocalBase-Self-Service-/Adminoberfläche und ihrer
internen PersonalData-Klassen nach einem grünen Runtime- und Rückbaunachweis;
es gibt weiterhin keinen Datenfallback über fremde Speicher.

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
