# Changelog

## Unreleased

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
