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

`GroupProvisioningService::assertRoleMembersBelongTo()` prüft eine allgemeine
native Nextcloud-Gruppenhierarchie read-only. Fehlende Gruppen oder Mitglieder
einer Rollengruppe außerhalb der geforderten Basisgruppe werden abgelehnt;
die Prüfung legt keine Mitgliedschaften an und nennt in Fehlern keine
Konto-UIDs.

## BR-Gruppenvertrag

`BrGroupDefinition` und `BrGroupSettingsService` sind die kanonische,
versionierte Quelle für die drei getrennten semantischen Gruppen `member`,
`chair` und `deputy`. Technische Nextcloud-Gruppen-IDs bleiben konfigurierbar,
müssen eindeutig sein und werden zentral in der LocalBase-AppConfig
persistiert. Ein fehlender oder beschädigter persistierter Vertrag liefert
nur nicht freigabefähige Defaults; Consumer verwenden ausschließlich
`validatedDefinition()`.

Vor Initialisierung oder Speicherung müssen alle referenzierten nativen
Gruppen existieren. Jedes Mitglied von Vorsitz oder Stellvertretung muss
zugleich Mitglied der allgemeinen BR-Gruppe sein. Widersprüche, fehlende
Gruppen und veraltete Revisionen werden ohne AppConfig- oder
Mitgliedschaftsänderung abgelehnt. Die einmalige Übernahme einer bisherigen
Mitgliedergruppe ist nur zulässig, solange noch kein persistierter Vertrag
existiert; beschädigte Bestandswerte werden dabei nicht überschrieben.

## Lokale Demokonten

`DemoAccountProvisioningService` ist die kanonische Provisionierung für die
app-spezifischen AD-Demo-Packs. Neu erzeugte lokale Demokonten erhalten ihre
UID als initiales Passwort; bei einem bereits eindeutig für dasselbe
Demo-Pack registrierten Konto wird dieser Zustand bei erneuter Provisionierung
wiederhergestellt. Das ist ein bewusst schwacher, ausschließlich für lokale
Test- und Demokonten bestimmter Zugang und kein Produktionsvertrag.

Fremde Konten, Konten mit geändertem Benutzer-Backend sowie Konten ohne
änderbares Passwort oder Anzeigenamen werden im Preflight abgewiesen. Dabei
werden weder Gruppen angelegt noch Mitgliedschaften oder Passwörter verändert.
Die Provisionierung übernimmt insbesondere keine LDAP- oder sonstigen
externen Konten.

## Kalender- und Abwesenheitsverträge

`AbsenceEmployeeDiscoveryEvent`, `AbsenceQueryEvent` und `AbsenceInterval`
bilden optionale read-only Abwesenheitsprovider ab. Die Discovery ist an einen
halboffenen Zeitraum gebunden und aggregiert ausschließlich normalisierte
Konto-UIDs; leere und nicht-stringförmige Providerwerte werden verworfen, und
ohne Provider bleibt sie leer. `planned` liefert `U?` ohne Blockade,
`approved` liefert `U` mit Blockade. `ScheduleConflictQueryEvent` liefert
read-only Konflikte aus optionalen Planungsapps. Eine Abfrage kann ihre
validierte `requesterAppId` angeben; jeder Konflikt kann seine validierte
`sourceAppId` tragen. Das Event schließt Konflikte derselben Source zentral
aus, damit bidirektionale Provider ihre eigenen Einträge nicht zurückmelden.
Leere IDs halten bestehende Consumer und Provider rückwärtskompatibel. Typen
bleiben auf `shift` und `appointment` begrenzt, Zeiträume sind halboffen und
Labels enthalten ausschließlich knappe, nicht vertrauliche Anzeigenamen.
Provider löschen oder verändern keine Daten. Ohne registrierten Provider
bleibt die Konfliktmenge leer; Consumer greifen niemals auf Tabellen oder
interne Services einer anderen Fachapp zu.

`CalendarContext` und `CalendarContextSettingsService` definieren Land,
ISO-3166-2-Region und fachliche IANA-Zeitzone organisationsweit. `DE`,
`DE-BE` und `Europe/Berlin` bleiben Bestandsdefaults. Persönliche
Nextcloud-Zeitzonen beeinflussen ausschließlich individuelle Anzeigen.

`HolidayCalendarService` liefert Schulferien und gesetzliche Feiertage als
validierten read-only Jahresvertrag Version 1. Consumer werten neben der
Vertragsversion zwingend `cacheStatus` aus: `fresh` und `current` sind aktuell,
`stale` bleibt mit sichtbarer Aktualitätseinschränkung nutzbar und
`unavailable` darf niemals als leere, konfliktfreie Kalenderlage interpretiert
werden. `OpenHolidaysClient` ist der einzige
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

Version 4 ergänzt Rollen und Bereichen additiv um ein Kalenderkürzel. Die
Standarddefinition verwendet `BO`, `EB`, `PFK`, `BO-Pflege` und `IT` sowie
`NO`, `W` und `S`; alle übrigen Einträge fallen auf ihren Anzeigenamen zurück.
Bestehende Gruppen-IDs, Anzeigenamen, Reihenfolgen und Rechte bleiben dabei
unverändert.

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
