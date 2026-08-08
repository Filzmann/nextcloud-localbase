# Changelog

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
