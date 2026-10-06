# Changelog

## Unreleased

- Persönliche Adminlayoutwerte und synthetische Demo-Registrierungen über den
  Standalone-V1-Datenschutzvertrag samt Processing-Katalog subjectgebunden
  abgedeckt; Reset, Kontolöschung und sichere Bereinigung verwaister
  Demo-Registrierungen ergänzt.

- Veralteten LocalBase-Retention-Pilot nach grüner Nextcloud-34-Installations-,
  Update-, Deaktivierungs-, Entfernungs-, Neuinstallations- und Rückbaumatrix
  einschließlich Registry, DTOs, Aggregator, Endpoint und Dry-Run-UI entfernt;
  Retention wird ausschließlich durch `filzmann_data_protection` koordiniert.

- Öffentlichen Kategorie-B-Organisationssnapshot unter
  `OCA\\LocalBase\\PublicApi\\V1` mit unveränderlichem DTO,
  Versionskennung, eindeutigen Gruppenabbildungen und Prüfsumme eingeführt.
- Berechtigungsmatrix als ersten Consumer gegen den realen öffentlichen
  Vertrag sowie per Fresh Install mit und ohne LocalBase nachgewiesen;
  ungültige oder fehlende Provider liefern keine semantischen Zuordnungen.
- AD Recruitment als zweiten Consumer auf die öffentliche Organization-V1-
  Grenze umgestellt und die fehlenden, deaktivierten, alten, inkompatiblen,
  ungültigen und fehlerhaften Providerzustände per Consumer-Contract
  fail-closed belegt; der reale Lifecycle-Nachweis bleibt offen.
- Entwicklungsstand auf `0.12.0-dev.2` fortgeschrieben und als In-place-Update
  vom vorherigen Stand geprüft; der synthetische Organisationszustand bleibt
  erhalten und der Matrixconsumer bleibt verfügbar.
- Nextcloud-33-Unterstützung durch Fresh Install auf 33.0.7, Upgrade auf 34.0.2
  sowie DI-, Job-, API-, Rechte-, Asset- und Consumer-Smokes nachgewiesen.
- Dokumentations- und Steuerungsstruktur vereinheitlicht.
- Planungskonfliktvertrag als additive API Version 1.0 ausgewiesen; anfragende
  und liefernde App-IDs grenzen bidirektionale Eigenmeldungen zentral aus.

- AD BQ-Planer als navigierbares Standalone-Produkt im kanonischen Produktkatalog ergänzt; bis zur Release-Reife bleibt es aus Full-Suite- und Einzelprodukt-Bundles ausgeschlossen.
- Freigegebene Einzelbundle-Produkte sind über einen eigenen Katalogvertrag von noch nicht auslieferbaren Produkten getrennt.

## 0.11.0-rc.1

- Selbstbedienungs-Auskunft nach Art. 15 DSGVO mit intuitivem Nextcloud-Menü- und Einstellungszugang sowie mehrseitigem PDF-Download ergänzt.
- Personenbezogene Daten je App und Datentyp in menschenlesbaren Tabellen mit deutschen Kurzdatumswerten, Zweck, Aufbewahrung und weiteren Verarbeitungsangaben dargestellt.
- Nextcloud-Konto, sämtliche nichtleeren Profilfelder und eigene Gruppenzuordnungen aus den öffentlichen Nextcloud-APIs aufgenommen; Anmeldegeheimnisse und Drittpersonendaten bleiben ausgeschlossen.
- Noch nicht angebundene Datenquellen sichtbar ausgewiesen und die spätere Ausweitung auf die vollständige Nextcloud-Instanz in der Roadmap vorgemerkt.
- Öffentliche, fehlerisolierte PersonalData- und Retention-Providerverträge sowie geschützte Self-Service-, Admin- und Dry-Run-Endpunkte eingeführt.
- Organisationsvertrag Version 4 um zentrale Kalenderkürzel für Rollen und Bürobereiche ergänzt und bestehende Definitionen additiv migriert.

## 0.10.0-rc.2

- Gemeinsamen API-Client um den lokalisierten Nextcloud-Fehlervertrag mit stabilem `error`-Feld ergänzt; ältere `message`-Antworten bleiben kompatibel.

## 0.10.0-rc.1

- Organisationsvertrag Version 3 mit getrennten Rollen `finance` und `payroll` ergänzt; bestehende Finanzgruppen bleiben beim Upgrade erhalten.
- Datensparsamen, unveränderlichen Organisationssnapshot für Fachapp-Berechtigungen veröffentlicht.
- Fehlende oder ungültige Organisationspersistenz im Snapshot explizit als nicht freigabefähig markiert.
- Synthetische Demoorganisation um die getrennte Lohnrolle ergänzt.

## 0.9.0-rc.1

- Versionierten AD-Produktkatalog als kanonischen Providervertrag ergänzt.
- AD Recruitment als Standalone-, Menü-, Suite- und Einzelbundle-Produkt aufgenommen.
- Bestehende Standalone-Navigation auf Katalogroute und -reihenfolge umgestellt.

## 0.7.0-rc.1

- Optionale Capability-Verträge für eigenständig installierbare AD-Fachprodukte ergänzt.
- Organisationseditor und geschützte Admin-API vollständig nach LocalBase verschoben.
- Dynamische Adminplatzierung und Einzelprodukt-Navigation ohne aktive OrgSuite ergänzt.

## 0.6.3-rc.1

- Öffentliche Projekt-, Quellcode- und Fehlerkanäle ergänzt.
- Neutrale Assistenzteam-Beispiele für die öffentliche Auslieferung vereinheitlicht.

## 0.6.2-rc.1

- Erster reproduzierbarer Staging-Releasekandidat für Nextcloud 34 und PHP ab 8.3.
- Gemeinsame Organisations-, API-, Modell-, UI- und Kalenderverträge für die AD-Suite.
- Abgesicherte PHP- und JavaScript-Smoke-Tests für öffentliche LocalBase-Verträge.
