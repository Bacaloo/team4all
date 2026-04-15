# team4all

## App-Bilder

### Beschreibung / Branding
![Team4All Beschreibung](img/app-description.svg)

### Menü-Logo (Nextcloud Navigation)
![Team4All Menü-Logo](img/app-menu.svg)

Teamarbeit für alle im Unternehmen

## Pflegehinweise

Die Sichtbarkeit und Nutzbarkeit der App ist standardmäßig für alle angemeldeten Nextcloud-Nutzer freigegeben.
- Zusätzliche Einschränkungen sollen künftig app-intern und funktionsbezogen umgesetzt werden, nicht über die bloße Mitgliedschaft in der Kontengruppe `Team4All`.
- Die Kontengruppe `Team4All` bleibt für Provisioning und technische Zielstrukturen relevant.
- Solange die Gruppe noch nicht existiert, darf ein Administrator die App einmalig aufrufen. Dabei wird die Gruppe `Team4All` angelegt und der angemeldete Administrator als erstes Mitglied hinzugefügt.
- Team4All wertet für den aktuell angemeldeten Nutzer alle in Nextcloud sichtbaren Adressbücher aus, also eigene und freigegebene Adressbücher.
- In Team4All sichtbar und bearbeitbar bleiben dabei weiterhin nur Kontakte, die in der Kontaktgruppe `Team4All` liegen.
- Team4All-Zusatzdaten wie `anrede` und `briefanrede` werden kontaktbezogen geteilt, damit berechtigte Nutzer dieselben Werte sehen und bearbeiten.
- In den Administratoreneinstellungen gibt es einen eigenen Bereich `Team4All`, in dem ausgewaehlt werden kann, welche geteilten Adressbuecher aus dem NC-Team `Team4All` in der App nutzbar sind.
- Für Version `0.4.0` wird zusätzlich ein Team-Ordner `Team4All` sichergestellt. Die Kontengruppe `Team4All` erhält darauf standardmäßig Nur-Lese-Zugriff.
- Ab Version `0.4.1` arbeitet Team4All fachlich nur mit Adressbuchkontakten, die in der Kontaktgruppe `Team4All` liegen.
- Bei jeder App-Aktivierung wird ausschließlich im Standard-Adressbuch `contacts` des Provisionierungs-/Admin-Benutzers geprüft, ob die Kontaktgruppe `Team4All` vorhanden ist. Wenn nicht, wird sie dort über den ersten Kontakt des Provisionierungs-/Admin-Benutzers hergestellt.
- Für Team4All gilt die direkte CardDAV-/Adressbuchauswertung als fachliche Wahrheit. Die sichtbare Darstellung in der Contacts-App kann davon abweichen und ist für Team4All nicht maßgeblich.
