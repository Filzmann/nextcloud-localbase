# LocalBase

Gemeinsame Basisbausteine für die lokalen AD- und BR-Nextcloud-Apps. LocalBase besitzt keine eigene Navigation und wird als technische Infrastruktur mit den AD-Fachprodukten ausgeliefert.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
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
