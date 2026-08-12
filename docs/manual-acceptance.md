# Manuelles Abnahmeformular – LocalBase

Dieses Formular dokumentiert die manuelle Abnahme der von LocalBase
bereitgestellten Infrastruktur-, Administrations- und Cross-App-Verträge auf
einem realitätsnahen Staging-System. LocalBase besitzt keine eigene
Fachnavigation; sichtbare Prüfungen erfolgen im jeweils vorgesehenen
Adminadapter oder in einer ausdrücklich genannten Consumer-App.

Pro Prüffall wird genau ein Ergebnis markiert. Keine Passwörter, Tokens,
personenbezogenen Echtdaten, vollständigen Mitgliederlisten oder internen
Systemkennungen eintragen. Ausschließlich neutrale Testkonten und synthetische
Organisationsdaten verwenden.

## Kopfdaten

| Feld | Eintrag |
|---|---|
| Datum und Uhrzeit | |
| Prüfer*in | |
| Umgebung und URL | |
| LocalBase-Version | |
| Nextcloud-Version | |
| Browser und Version | |
| Fenstergröße / Zoom | |
| Neutrale Admin- und Nichtadmin-Konten | |
| Aktive Consumer-Apps | |
| Ausgangskonfiguration gesichert unter | |

Ergebniskennzeichnung: `[ ] erfolgreich` / `[ ] nicht erfolgreich` /
`[ ] nicht geprüft`. Bei „nicht erfolgreich“ oder „nicht geprüft“ ist eine
Begründung verpflichtend. Veränderte Testkonfigurationen werden nach der
Abnahme auf den dokumentierten Ausgangsstand zurückgeführt.

## A. Installation und Administrationsort

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| A1 | Technische Infrastruktur-App | App-Status und Nextcloud-Navigation mit aktivem LocalBase prüfen. | LocalBase ist aktiv, besitzt aber keinen eigenen Fach- oder Haupteinstieg. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A2 | Einzelprodukt-Adminadapter | Genau ein unterstütztes AD-Fachprodukt zusammen mit LocalBase aktivieren und dessen Adminbereich öffnen. | Die gemeinsame Organisationsverwaltung erscheint beim Fachprodukt; OrgSuite ist dafür nicht erforderlich. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A3 | Mehrprodukt-Adminadapter | Mindestens zwei AD-Fachprodukte und OrgSuite aktivieren und den Nextcloud-Adminbereich öffnen. | Die gemeinsame Organisationsverwaltung erscheint über OrgSuite und nicht mehrfach in den Fachapps. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A4 | Nichtadmin-Deny | Als Nichtadmin den Adminbereich und einen direkten administrativen Lese- sowie Schreibaufruf versuchen. | Der Zugriff wird serverseitig verweigert; keine Konfiguration ändert sich. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A5 | CSRF-Schutz | Einen schreibenden Adminaufruf mit Sitzung, aber ohne gültiges Requesttoken wiederholen. | Der Request wird abgewiesen und der vorherige Konfigurationsstand bleibt vollständig erhalten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## B. Organisation, Rechte und persönliche Darstellung

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| B1 | Rollen, Bereiche und Reihenfolge | Synthetische Gruppen-IDs, sichtbare Namen, Bürobereiche und Rollenreihenfolge ändern, speichern und neu laden. Anschließend die zentralen Kalenderkürzel in einem Consumer prüfen. | Der vollständige gültige Stand bleibt erhalten; `BO`, `EB`, `PFK`, `BO-Pflege`, `IT`, `NO`, `W` und `S` werden von sichtbaren Consumer-Apps einheitlich verwendet. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B2 | Hierarchie und Peer-Freigabe | Eine neutrale Hierarchiekante und eine Peer-Freigabe ändern und mit passenden Testkonten in einem Consumer positiv sowie negativ prüfen. | Nur die konfigurierte Hierarchie beziehungsweise Freigabe wirkt; Bereichsgrenzen und deny by default bleiben erhalten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B3 | Ungültige Definition | Doppelte Gruppen-ID, unbekannte Referenz und Hierarchiezyklus nacheinander absenden. | Jeder ungültige Stand wird verständlich abgewiesen; die letzte gültige Definition bleibt unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B4 | Sichere Leserückfallebene | In einer dafür vorbereiteten Testinstallation einen ungültigen persistierten Organisationsstand lesen. | Die Oberfläche fällt erkennbar auf die geprüfte Standarddefinition zurück und leitet aus dem ungültigen Stand keine Freigabe ab. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B5 | Diagrammordnung ohne Fachwirkung | Karten derselben Ebene horizontal umordnen und danach Rollenliste, Kalenderreihenfolge und Rechte prüfen. | Nur die visuelle Links-rechts-Anordnung ändert sich; fachliche Reihenfolge und Rechte bleiben gleich. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B6 | Persönliches Dashboardlayout | Blöcke per Tastatur und Drag-and-drop verschieben, einklappen und mit einem zweiten Admin-Konto vergleichen. | Reihenfolge und Einklappzustand bleiben je Konto getrennt; neue beziehungsweise unbekannte Blöcke beschädigen das Layout nicht. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B7 | Persönlicher Zoom | Organigrammzoom zwischen 50 und 150 Prozent ändern und mit einem zweiten Gerät oder Browserfenster desselben Kontos prüfen. | Zoom wird in 10-Prozent-Schritten gespeichert; der verschobene Ausschnitt bleibt flüchtig und die fachliche Struktur unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B8 | Tastatur, Fokus und große Struktur | Seitenpanel, Karten, Listen, Blöcke und Organigramm mit Tastatur bedienen; viele synthetische Rollen und Bereiche anzeigen. | Fokus bleibt sichtbar, Funktionen sind erreichbar und die Oberfläche bleibt ohne Tastaturfalle oder unkontrolliertes Seitenscrollen nutzbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## C. Kalenderkontext und Ferien-/Feiertagsvertrag

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| C1 | Bestandsdefaults | Kalenderkontext ohne bewusst gespeicherte Abweichung öffnen. | `DE`, `DE-BE` und `Europe/Berlin` erscheinen als geprüfte Bestandsdefaults. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C2 | Gültiger Kontextwechsel | Auf eine freigegebene synthetische Testregion und passende IANA-Zeitzone wechseln und Consumer neu laden. | Der normalisierte Kontext bleibt gespeichert und Ferien, Feiertage sowie fachliche Datumsgrenzen folgen ihm. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C3 | Ungültiger Kontext | Leeres Land, unpassende Region und ungültige Zeitzone absenden. | Ungültige Kombinationen werden abgewiesen; es entsteht keine Teilkonfiguration. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C4 | Aktueller Jahresstand | Ein noch nicht geladener, für den Test freigegebener Jahresstand abrufen und danach erneut öffnen. | Validierte Ferien und gesetzliche Feiertage werden geliefert; die Wiederholung nutzt den regionsgebundenen Cache. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C5 | Veralteter Cache | Mit vorhandenem gültigem Cache den Provider in einer isolierten Testumgebung vorübergehend unerreichbar machen. | Der letzte gültige Stand bleibt verfügbar und wird sichtbar als veraltet gekennzeichnet. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C6 | Erstabruf ohne Provider | Für eine nicht gecachte Testkombination den Providerausfall simulieren. | Der Stand wird sicher als nicht verfügbar gemeldet und nicht als leere, aber gültige Feiertagsliste ausgegeben. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## D. Öffentliche Verträge und Standalone-Zustände

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| D1 | Abwesenheitsvertrag | Für einen begrenzten halboffenen Zeitraum neutrale Konten mit `planned` und `approved` über AD Urlaub entdecken und in AD Kalender lesen. | Die Discovery liefert ausschließlich die passenden normalisierten Konto-UIDs; `planned` erscheint als `U?` ohne Blockade und `approved` als `U` mit der vereinbarten Blockade. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D2 | Fehlender Abwesenheitsprovider | Consumer ohne AD Urlaub öffnen. | Der leere Providerzustand ist gültig und blockiert die Consumer-App nicht. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D3 | Konfliktabfrage | Einen genehmigungsrelevanten synthetischen Konflikt zwischen AD Urlaub und AD Kalender prüfen. | Der read-only Vertrag meldet Konflikte, verändert oder löscht aber keine Daten in einer App. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D4 | Capability-Snapshot | Consumer mit und ohne optionale Provider öffnen und die angebotenen Integrationen vergleichen. | Fähigkeiten entsprechen den aktiven Providern; ein leerer Snapshot bleibt zulässig und erweitert niemals Rechte. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D5 | Standalone-Navigation | Ein AD-Fachprodukt ohne OrgSuite und anschließend mit aktiver OrgSuite öffnen. | LocalBase liefert genau den vorgesehenen Einzel- beziehungsweise Suite-Einstieg, ohne Fachrechte zu beeinflussen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D6 | Consumer-Isolation bei Fehler | Einen optionalen Provider gezielt fehlschlagen lassen und eine unabhängige Consumer-Funktion ausführen. | Der Providerfehler bleibt isoliert; führende Fachdaten und unabhängige Consumer-Funktionen bleiben erhalten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## E. Organigrammexport und Datenschutz

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| E1 | Draw.io, PNG und PDF | Den sichtbaren synthetischen Organigrammstand in alle drei Formate exportieren und lokal öffnen. | Draw.io bleibt editierbar, PNG lesbar und PDF vektorbasiert; lange Inhalte bleiben innerhalb der Karten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E2 | Mitglieder standardmäßig aus | Export ohne aktivierte Mitgliederauswahl prüfen. | Zugeordnete Konten erscheinen nicht im Export. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E3 | Bewusste Mitgliederauswahl | Mitgliederausgabe ausdrücklich aktivieren und erneut exportieren. | Nur die aktuell sichtbaren synthetischen Zuordnungen werden aufgenommen; die Auswahl erweitert keine Rechte. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E4 | Lokaler Exportweg | Während des Exports Browsernetzwerk und Nextcloud-Dateien prüfen. | Die Erzeugung erfolgt clientseitig als Download; es gibt keine Serverablage und keinen externen Exportdienst. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E5 | Datensparsame Abnahme | Formular, Screenshots und Exporte prüfen. | Keine Secrets, realen Mitgliederlisten oder unnötigen technischen Kennungen wurden dokumentiert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## Wiederherstellung

| Feld | Eintrag |
|---|---|
| Wiederhergestellter Kalenderkontext | |
| Wiederhergestellte Organisationsdefinition | |
| Wiederhergestellte Peer-Freigaben | |
| Verbliebene synthetische Testdaten | |

## Abschlussentscheidung

| Feld | Eintrag |
|---|---|
| Anzahl erfolgreich | |
| Anzahl nicht erfolgreich | |
| Anzahl nicht geprüft | |
| Kritische Abweichungen / Ticketreferenzen | |
| Erneute Prüfung erforderlich bis | |
| Gesamtentscheidung | [ ] abgenommen [ ] mit Auflagen abgenommen [ ] nicht abgenommen |
| Begründung der Gesamtentscheidung | |
| Name / Datum | |
