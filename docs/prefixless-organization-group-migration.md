# Migration auf präfixlose Organisationsgruppen

## Ziel und Geltungsbereich

Organisationsgruppen verwenden künftig verständliche, global eindeutige
Gruppen-IDs ohne herkunftsbezogene Präfixe wie `flz-` oder `ad-`. Die
stabilen semantischen Rollen- und Bereichsschlüssel des LocalBase-Vertrags
bleiben unverändert und sind weiterhin die fachliche Quelle. Fachrechte
werden nicht aus dem Namen einer Gruppe abgeleitet.

Seit der testgetriebenen Defaultumstellung verwendet
`FlzOrganizationDefinition::defaults()` dieselbe unten dokumentierte
Abbildung als ausführbaren Fresh-Install-Vertrag. Demofixtures, Peerfreigaben
und öffentliche V1-Snapshots leiten ihre Gruppen ausschließlich aus dieser
Definition ab. Es gibt keinen ausführbaren Präfixfallback und keinen
kombinierten Finanz-/Lohn-Alias mehr. Persistierte Instanzen werden nicht
stillschweigend umgeschrieben, sondern ausschließlich mit dem kontrollierten
Lauf dieses Dokuments umgestellt.

Die Regel `Benutzername = Passwort` gilt ausschließlich für registrierte
synthetische Konten in der lokalen DDEV-Instanz. Sie darf weder auf Staging
noch auf andere Umgebungen übertragen werden.

## Kanonische Abbildung

| Semantischer Schlüssel | Bisherige Gruppe | Zielgruppe |
| --- | --- | --- |
| `gf_as` | `flz-GF-AS` | `GF-AS` |
| `pdl` | `flz-PDL` | `PDL` |
| `staff_hr` | `flz-Stab-HR` | `Stab-HR` |
| `staff_qmb` | `flz-Stab-QMB` | `Stab-QMB` |
| `gf_digi` | `flz-GF-Digi` | `GF-Digi` |
| `assistant_gf_digi` | `flz-AsdGF-Digi` | `AsdGF-Digi` |
| `finance_lead` | `flz-Leitung-Finanzen-Lohn` | `Leitung-Finanzen-Lohn` |
| `finance` | `flz-Finanzen` | `Finanzen` |
| `payroll` | `flz-Lohn` | `Lohn` |
| `it` | `flz-IT` | `IT` |
| `fleet_management` | `flz-Fahrzeugverwaltung` | `Fahrzeugverwaltung` |
| `secretariat` | `flz-Sekretariat` | `Sekretariat` |
| `reception` | `flz-Empfang` | `Empfang` |
| `bl` | `flz-BL` | `BL` |
| `deputy_bl` | `flz-StvBL` | `StvBL` |
| `office` | `flz-Buero` | `Buero` |
| `deputy_pdl` | `flz-StvPDL` | `StvPDL` |
| `care_office` | `flz-Bueroorganisation-Pflege` | `Bueroorganisation-Pflege` |
| `eb` | `flz-EB` | `EB` |
| `pfk` | `flz-PFK` | `PFK` |
| `northeast` | `flz-Bereich-Nordost` | `Bereich-Nordost` |
| `west` | `flz-Bereich-West` | `Bereich-West` |
| `south` | `flz-Bereich-Sued` | `Bereich-Sued` |

Assistenzteams folgen derselben Regel dynamisch:
`flz-ASN-<gültiges Teamkürzel>` wird zu `ASN-<gültiges Teamkürzel>`. In der
lokalen Instanz waren dies `A`, `B` und `C`. Historische `ad-*`-Gruppen sind
keine zweite Organisationsquelle und werden nicht automatisch als zusätzliche
Rollen oder Teams übernommen.

## Lokaler Migrationsnachweis vom 2. November 2026

Ausgangslage war Nextcloud 34.0.2.1 ohne offenen Datenbank-Upgradebedarf. Die
Organisationsdefinition Version 4 war nicht persistiert und wurde deshalb aus
den historischen `flz-*`-Defaults rekonstruiert.

- 26 kanonische Rollen-, Bereichs- und Teamgruppen wurden ohne Zielkollision
  auf die obige Abbildung aufgelöst.
- Alle Mitgliedschaften der kanonischen `flz-*`-Quellgruppen wurden in die
  Zielgruppen kopiert; die anschließende Vollprüfung fand keine fehlende
  Mitgliedschaft.
- Die Definition wurde ausschließlich über
  `FlzOrganizationSettingsService::save()` validiert gespeichert. Der
  öffentliche Organisationssnapshot Version 1.0 war danach gültig und hatte
  die Prüfsumme
  `5d373ef1eb81f525260620e69d265a08f9b1ef51c0ed2547e713f2b686fd21b6`.
- Der Organisationsverzeichnisstatus war kompatibel und alle 23 Rollen- und
  Bereichsgruppen waren vorhanden, schreibbar und demo-provisionierbar. Auch
  der Urlaubsautorisierungssnapshot blieb gültig.
- PDL→PFK und BL→Büro im gemeinsamen Bereich wurden erlaubt; Empfang→Sekretariat
  und BL→Büro in einem fremden Bereich wurden verweigert.
- Drei repräsentative lokale synthetische Konten aus Pflegeleitung,
  Assistenzteam und Raumplaner wurden nach erneuter Provisionierung mit der
  lokalen Regel `Benutzername = Passwort` erfolgreich per WebDAV angemeldet.
- Vor der Contract-Phase wurden exakt 38 `ad-*`-Gruppen und 26 `flz-*`-Gruppen
  fixiert. Ihre sortierten JSON-Listen hatten die SHA-256-Werte
  `df3620a47c2e61cbafcf679a6c29bddcfe4d81438c36cd5c0039fa5b5bffd189`
  beziehungsweise
  `da5e302ced85094481160b999d2cec1e4a65c0054e2021ef4901a13bd1c961ea`.
- Anschließend wurden genau diese 64 Präfixgruppen sowie 38 registrierte
  `ad-demo-*`-Konten mit geprüftem `flz-demo-*`-Gegenstück entfernt. Andere
  Konten blieben erhalten. Im Abschlussstand existierten weder Präfixgruppen
  noch `ad-demo-*`-Konten oder zugehörige Registereinträge; 38 registrierte
  `flz-demo-*`-Konten blieben bestehen.

## Vorgemerkter Staging-Lauf

Staging wird nicht durch den lokalen Nachweis verändert. Ein späterer Lauf
braucht einen eigenen ausdrücklichen Auftrag und verwendet ausschließlich die
tatsächlichen Staging-Pfade, Runtimebenutzer und Zugangsmittel.

### 1. Preflight

1. Nextcloud-Status, LocalBase-Version, offenen Upgradebedarf und die reale
   `apps_paths`-Konfiguration feststellen.
2. Die persistierte Organisationsdefinition über
   `FlzOrganizationSettingsService::state()` oder die geschützte Admin-API
   lesen. Eine ungültige Definition ist ein Stop-Grund.
3. Quellgruppen, Zielgruppen, Backend, Schreibbarkeit und Mitgliedszahlen über
   native Nextcloud-Gruppen-APIs inventarisieren. Mitgliedschaften werden
   datensparsam gesichert; Passwörter oder Tokens gehören nicht in Bericht
   oder Repository.
4. Die Zielabbildung aus den semantischen Rollen, Bereichen und gültigen
   ASN-Teamkürzeln ableiten. Fehlende Quellen, doppelte Ziel-IDs, bereits
   fachlich anders belegte Zielgruppen oder unbekannte Präfixgruppen stoppen
   den Lauf.
5. Vorhandene nichtsynthetische Personen in einer zu entfernenden Gruppe
   müssen einer eindeutigen Zielgruppe zugeordnet und dort geprüft sein. Ein
   `ad-demo-*`-Konto darf nur entfernt werden, wenn es tatsächlich synthetisch
   registriert ist und sein vorgesehenes Gegenstück existiert. Auf Staging
   wird niemals `Benutzername = Passwort` gesetzt.

### 2. Expand und kontrolliertes Umschalten

1. Zielgruppen nativ anlegen, ohne Quellgruppen zu verändern.
2. Kanonische Mitgliedschaften vollständig kopieren und Quell-/Zielmengen
   vergleichen.
3. Rollen-, Bereichs- und Teamgruppen in einer vollständigen Definition
   ändern und ausschließlich über `FlzOrganizationSettingsService::save()`
   oder die geschützte LocalBase-Admin-API speichern. Rohe AppConfig-Schreibzugriffe
   sind nicht zulässig.
4. Die Provider- und Consumerprojektionen neu lesen; während der
   Umschaltphase dürfen fehlende oder ungültige Snapshots keine Rechte
   erteilen.

### 3. Verifikation vor Contract

- `OrganizationSnapshotService` und
  `VacationAuthorizationSnapshotService` sind gültig und enthalten keine
  `flz-`- oder `ad-`-Gruppen-ID.
- Der Organisationsverzeichnisstatus meldet alle konfigurierten Gruppen als
  vorhanden und mit dem erwarteten Backend.
- Mindestens ein erlaubter und ein verweigerter Hierarchie-/Bereichsfall
  funktionieren serverseitig; die betroffenen Consumer-Smokes sind grün.
- Für jede Quellgruppe ist die erforderliche Mitgliedsmenge in der Zielgruppe
  enthalten. Es gibt keine unbeabsichtigte zusätzliche Mitgliedschaft.
- Repräsentative Staging-Konten melden sich mit den dort vorgesehenen, geheim
  gehaltenen Zugangsmitteln an; Zugangsdaten werden nicht ausgegeben.

### 4. Contract

Die zu löschenden Quellgruppen werden unmittelbar vor der Löschung erneut als
exakte Liste samt Anzahl und Prüfsumme fixiert. Nur diese Liste wird gelöscht.
Alte synthetische Konten und ihre Registry-Einträge werden erst danach und nur
unter den Preflight-Bedingungen entfernt. Abschließend müssen Gruppen-,
Konten- und Registersuche null unerwartete Präfixreste liefern; Status,
Snapshots, Berechtigungsfälle, Assets und sichtbare Adminoberfläche werden
erneut geprüft.

### Stop- und Rückbaubedingungen

Vor der Contract-Phase wird bei jeder Kollision, unklaren Zuordnung,
schreibgeschützten Gruppe, fehlenden Identität, ungültigen Projektion,
fehlgeschlagenen Rechteprüfung oder abweichenden fixierten Quellliste
gestoppt. Bis dahin besteht der Rückbau darin, die gesicherte bisherige
Definition über denselben Service wieder zu speichern und nur nachweislich
neu angelegte Zielgruppen zu entfernen.

Nach der Contract-Phase werden Präfixgruppen bei notwendigem Rückbau aus der
gesicherten inversen Abbildung neu angelegt, Mitgliedschaften aus den
präfixlosen Zielgruppen zurückkopiert und erst dann die frühere Definition
validiert gespeichert. In der gegenwärtigen Entwicklungsphase ist bei einer
wegwerfbaren Staging-Instanz ein sauberer Reinstall mit reproduzierbarer
synthetischer Konfiguration dem Wiederaufbau überholter Demoidentitäten
vorzuziehen. Ein Rückbau darf weder echte Konten löschen noch die lokale
Schwachpasswortregel auf Staging übertragen.

## Staging-Nachweis vom 28. November 2026 (Freigabe Simon, kontrollierter Lauf)

Nach Abschluss des LocalBase-Rückbaus (PR nextcloud-localbase#12) wurde der
Staging-Zielkontext (DDEV-Instanz nextcloud-dev, Nextcloud 34.0.2.1) nach diesem
Runbook geprüft:

- Preflight: `occ status` ohne offenen Upgradebedarf (localbase 0.13.0,
  flzroom 0.13.1-rc.1 nach `occ upgrade`); die persistierte
  Organisationsdefinition in `flz_organization` (AppConfig) ist gültig und
  enthält keine `flz-`- oder `ad-`-Gruppen-IDs.
- Inventur: `occ group:list --output=json` liefert 31 Gruppen, davon **0** mit
  `flz-`- oder `ad-`-Präfix (SHA-256 der leeren sortierten Präfixliste:
  `4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945`); keine
  Nicht-Demo-Mitgliedschaft in Präfixgruppen. Die 38 registrierten synthetischen
  `flz-demo-*`-Konten bleiben bestehen und sind prefixlosen Gruppen
  (`ASN-A/B/C`, Rollen-/Bereichsgruppen) zugeordnet.
- Consumer-Nachweis: Delivery-Gate nach dem Rückbau erneut RC=0
  (28.11.2026 14:18 UTC, `br-compat-gate/work/delivery-gate.log`), inklusive
  HTTP-/CSRF-Smokes und Paketbau aller FLZ-Produkte.
- Contract-Phase: keine Präfixgruppen vorhanden — keine Löschung nötig. Es
  verblieben keine unerwarteten Präfixreste in Gruppen-, Konten- und
  Registersuche (Demo-Konten tragen das registrierte `flz-demo-*`-Namensschema,
  sind aber keiner Präfixgruppe zugeordnet).
