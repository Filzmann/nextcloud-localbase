# LocalBase

Gemeinsame Basisbausteine für die lokalen AD- und BR-Nextcloud-Apps. LocalBase besitzt keine eigene Navigation und muss vor OrgSuite und den Fachapps aktiviert werden.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
- App-ID und Installationsordner: `localbase`

## Installation

Das Releasearchiv nach `custom_apps/` entpacken und als HTTP-Benutzer aktivieren:

```bash
sudo -u www-data php occ app:enable localbase
```

Die vollständige Suite-Installationsreihenfolge und Prüfschritte stehen in der gemeinsamen Staging-Release-Dokumentation des Parent-Workspaces.
