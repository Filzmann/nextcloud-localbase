# LocalBase

Gemeinsame Basisbausteine für die lokalen AD- und BR-Nextcloud-Apps. LocalBase besitzt keine eigene Navigation und wird als technische Infrastruktur mit den AD-Fachprodukten ausgeliefert.

Der öffentliche Laufzeitvertrag `OCA\\LocalBase\\PublicApi\\V1` stellt Rollen
und Bürobereiche als unveränderlichen, datensparsamen
Organisationssnapshot bereit. Vertragsversion, Definitionsversion und
Prüfsumme machen den Stand überprüfbar. Fehlende, ungültige oder inkompatible
Snapshots enthalten keine Zuordnungen und erteilen keine fachlichen Rechte.
Die Berechtigungsmatrix ist der erste migrierte Consumer; weitere Fachapps
folgen einzeln nach eigenen Contract- und Laufzeitnachweisen.

Die kanonische Organisationsdefinition Version 4 ergänzt die betriebsweit
festgelegten Kalenderkürzel für Rollen und Bürobereiche. Sie bleibt die
einzige persistierte Quelle, aus der der öffentliche Snapshot erzeugt wird.

Der öffentliche Planungskonfliktvertrag Version 1.0 verbindet optionale
Kalender- und Planungsprovider read-only. Requester- und Provider-App-ID
verhindern Eigenmeldungen; ohne Listener bleibt die Konfliktmenge leer.

## Staging-Kompatibilität

- Nextcloud 33 bis 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 33 bis 34 unterstützten Bereichs
- App-ID und Installationsordner: `localbase`

## Installation

Auf Staging- und Zielsystemen werden Nextcloud-Root, `custom_apps`, CLI-PHP und
Runtimebenutzer aus der realen Konfiguration ermittelt. Danach wird LocalBase
im vorgesehenen Runtimekontext aktiviert:

```bash
<RUNTIME-KONTEXT> <CLI-PHP> occ app:enable localbase
```

Auf Staging- und Zielsystemen wird LocalBase nicht als separates Fachprodukt installiert, sondern automatisch durch den geprüften Produktinstaller. Die vollständige Installationsreihenfolge und Prüfschritte stehen im öffentlichen [AD-Suite-Projekt](https://github.com/Filzmann/ad-suite).

## Roadmap

Geplante gemeinsame Bausteine und offene Architekturentscheidungen stehen in der [Roadmap](ROADMAP.md).

Für die manuelle Staging-Prüfung der Administrationsoberfläche und der
Cross-App-Verträge steht ein ausfüllbares
[Abnahmeformular](docs/manual-acceptance.md) bereit. Es berücksichtigt, dass
LocalBase keine eigene Fachnavigation besitzt.

## Dokumentation

- [Architektur](docs/architecture.md)
- [Manuelle Abnahme](docs/manual-acceptance.md)
- [Roadmap](ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Arbeitsregeln](AGENTS.md)
