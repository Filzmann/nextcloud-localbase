# Öffentliche Verträge von LocalBase

Diese Datei dokumentiert den geltenden Ist-Stand der gemeinsamen
LocalBase-Verträge. Neue gemeinsame Verträge entstehen erst nach dem in
`AGENTS.md` beschriebenen Cross-App-Stop und mit Provider-/Consumer-Tests.

## Grundbausteine

LocalBase stellt `ApiResponder`, `ModelApiTrait`, `AppLogger`,
`GroupProvisioningService`, die JavaScript-Bausteine `ApiClient`,
`Repository`, `Model`, `Notice` und kleine UI-Primitives bereit. Gemeinsame
Test-Helper bleiben test-only, fachlich neutral und dependency-arm; der
`PhpTestRunner` sammelt dependency-arme PHP-Smokes deterministisch und führt
sie isoliert aus.

## Kalender- und Abwesenheitsverträge

`AbsenceQueryEvent` und `AbsenceInterval` bilden optionale read-only
Abwesenheitsprovider ab. `planned` liefert `U?` ohne Blockade, `approved`
liefert `U` mit Blockade. `ScheduleConflictQueryEvent` liefert vor genehmigten
Abwesenheiten read-only Konflikte aus optionalen Planungsapps; Provider
löschen oder verändern keine Daten.

`CalendarContext` und `CalendarContextSettingsService` definieren Land,
ISO-3166-2-Region und fachliche IANA-Zeitzone organisationsweit. `DE`,
`DE-BE` und `Europe/Berlin` bleiben Bestandsdefaults. Persönliche
Nextcloud-Zeitzonen beeinflussen ausschließlich individuelle Anzeigen.

`HolidayCalendarService` liefert Schulferien und gesetzliche Feiertage als
validierten read-only Jahresvertrag. `OpenHolidaysClient` ist der einzige
Provideradapter; `HolidayCalendarCacheStore` hält regionsgebundene
Jahresstände in LocalBase-AppConfig. Ein täglicher Hintergrundjob aktualisiert
das laufende und die zwei folgenden Jahre. Bei Providerfehlern bleibt ein
vorhandener Stand `stale`; Erstabrufe werden sicher als `unavailable`
ausgewiesen und nach kurzer Sperrfrist erneut versucht.

## AD-Organisationsvertrag

`AdOrganizationDefinition`, `AdOrganizationSettingsService`,
`AdOrganizationHierarchy` und `AdOrganizationPermissionPolicy` bilden
konfigurierbare Gruppen, Anzeigenamen, Bereiche, Ansichten, Hierarchie und
Peergrenzen ab. Rollen und Bereiche werden über stabile semantische Schlüssel
referenziert; konfigurierbare Gruppen-IDs oder Anzeigenamen sind keine
Fachschlüssel.

Die gemeinsame Reihenfolge umfasst unter anderem Fahrzeugverwaltung nach IT,
Empfang nach Sekretariat sowie im Pflegebereich stellvertretende PDL,
Büroorganisation Pflege und PFK. Der Organisationsvertrag Version 2 ergänzt
diese Rollen, Kanten und Urlaubsansichten additiv. Bestehende Werte bleiben
erhalten; Gruppen-ID-Kollisionen, ungültige Referenzen und Hierarchiezyklen
werden abgelehnt. Eine ungültige gespeicherte Definition fällt sicher auf die
geprüfte Standarddefinition zurück.

Version 3 trennt die bisherigen Funktionen unterhalb `finance_lead` in die
stabilen Schlüssel `finance` und `payroll`. Beim Upgrade bleibt die bestehende
Gruppen-ID von `finance` erhalten; `payroll` wird additiv ergänzt. Beide
Rollen bleiben im bisherigen Hierarchie- und Organisationsblock.
Aus Sicherheitsgründen wird die Mitgliedschaft der bisherigen kombinierten
Gruppe nicht automatisch zu `payroll` kopiert: Die Bestandsgruppe wird
`finance` zugeordnet und ihr unveränderter Standardtitel fachlich zu
„Finanzen“ normalisiert. Administrator*innen verschieben Lohn-Mitarbeitende
anschließend bewusst in die neue konfigurierte Lohn-Gruppe. Bis dahin erhält
niemand aus der alten kombinierten Gruppe Zugriff auf Vertragsstammdaten.
Für bestehende Hierarchie-Consumer bleibt die frühere technische Gruppen-ID
`ad-Finanzen-Lohn` als reiner `finance`-Alias lesbar; dieser Alias erteilt
ausdrücklich niemals die neue `payroll`-Rolle.

`AdOrganizationSnapshotService` veröffentlicht Rollen und Bereiche ohne
Mitgliederlisten oder Fachrechte. Der unveränderliche Snapshot enthält
Vertragsversion, Definitionsversion, Gültigkeitsstatus und Prüfsumme. Eine
fehlende, beschädigte oder nur aus Defaults rekonstruierte Persistenz erzeugt
einen ungültigen, leeren Snapshot, aus dem Consumer keine Freigabe ableiten
dürfen.

`AdSuiteAdminSettingsService` speichert app-übergreifende Peerfreigaben
semantisch nach Rollen. Die Organisationsdefinition und diese Freigaben liegen
zentral in LocalBase-AppConfig. Bei Einzelinstallation erscheinen sie im
Adminabschnitt des Fachprodukts, ab zwei Produkten im OrgSuite-Adminabschnitt.

## Organisationseditor und persönliche Darstellung

Fachliche Rolleneinstellungen werden in einem zugänglichen Seitenpanel
bearbeitet. Technische Gruppen-IDs bleiben eingeklappt; Rollenreihenfolge,
Bereiche und Urlaubsansichten bleiben getrennte fachliche Einstellungen.

`diagramOrder` speichert ausschließlich die visuelle Links-rechts-Anordnung
von Organigrammkarten innerhalb ihrer Hierarchieebene. Sie verändert weder
Rollen-/Bereichsreihenfolgen noch Kalender, Rechte oder Hierarchiekanten. Das
Organigramm bleibt automatisch nach Hierarchieebenen angeordnet; freie
X-/Y-Positionen gehören nicht zum Vertrag.

Haupt-, Organisations- und Rechteblöcke können eingeklappt und per
Drag-and-drop oder Tastatur verschoben werden. Reihenfolge und Einklappzustand
sind persönliche UI-Präferenzen in `IUserConfig`. Der persönliche Zoom reicht
in 10-Prozent-Schritten von 50 bis 150 Prozent; der verschobene Ausschnitt
bleibt flüchtig. Diese Werte verändern keine fachlichen Ordnungen, Rechte oder
Exportgrößen.

Draw.io-, PNG- und PDF-Export arbeiten ausschließlich clientseitig mit dem
sichtbaren Stand und ohne Serverablage oder externe Exportdienste.
Zugeordnete Nutzer*innen werden nur nach ausdrücklicher, standardmäßig
deaktivierter Auswahl aufgenommen.

## Optionale Integration und Navigation

`IntegrationCapabilityQueryEvent`, `AdIntegrationCapabilities` und
`IntegrationCapabilityService` beschreiben optionale Cross-App-Fähigkeiten.
Ein leerer Snapshot ist ein zulässiger Standalone-Zustand und erweitert keine
Berechtigungen.

`StandaloneAppNavigationService` registriert Fachapp-Einstiege nur ohne
aktive OrgSuite. `AdProductSuiteService` und dynamische Settings-Adapter
platzieren die gemeinsame Organisationsverwaltung bei Einzelinstallation im
Fachprodukt. OrgSuite bindet den vollständig in LocalBase liegenden
Organisationseditor ab zwei Produkten lediglich als Adminadapter ein.
