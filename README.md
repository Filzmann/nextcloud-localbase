# LocalBase

Gemeinsame Basisbausteine für die lokalen AD- und BR-Nextcloud-Apps. LocalBase besitzt keine eigene Navigation und wird als technische Infrastruktur mit den AD-Fachprodukten ausgeliefert.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
- App-ID und Installationsordner: `localbase`

## Installation

Das Releasearchiv nach `custom_apps/` entpacken und als HTTP-Benutzer aktivieren:

```bash
sudo -u www-data php occ app:enable localbase
```

Auf Staging- und Zielsystemen wird LocalBase nicht als separates Fachprodukt installiert, sondern automatisch durch den geprüften Produktinstaller. Die vollständige Installationsreihenfolge und Prüfschritte stehen im öffentlichen [AD-Suite-Projekt](https://github.com/Filzmann/ad-suite).
