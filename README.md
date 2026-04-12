# team4all

## App-Bilder

### Beschreibung / Branding
![Team4All Beschreibung](img/app-description.svg)

### Menü-Logo (Nextcloud Navigation)
![Team4All Menü-Logo](img/app-menu.svg)

Teamarbeit für alle im Unternehmen

## Pflegehinweise

Die Sichtbarkeit und Nutzbarkeit der App wird gruppenbasiert über die Kontengruppe `Team4All` gesteuert.
- Mitglieder der Gruppe `Team4All` sehen die App im Menü und dürfen sie nutzen.
- Nichtmitglieder sehen die App nicht im Menü und dürfen sie nicht nutzen.
- Solange die Gruppe noch nicht existiert, darf ein Administrator die App einmalig aufrufen. Dabei wird die Gruppe `Team4All` angelegt und der angemeldete Administrator als erstes Mitglied hinzugefügt.
- Für Version `0.4.0` wird zusätzlich ein Team-Ordner `Team4All` sichergestellt. Die Kontengruppe `Team4All` erhält darauf standardmäßig Nur-Lese-Zugriff.
- Ab Version `0.4.1` arbeitet Team4All fachlich nur mit Adressbuchkontakten, die in der Kontaktgruppe `Team4All` liegen.
- Bei jeder App-Aktivierung wird ausschließlich im Standard-Adressbuch `contacts` des Provisionierungs-/Admin-Benutzers geprüft, ob die Kontaktgruppe `Team4All` vorhanden ist. Wenn nicht, wird sie dort über den ersten Kontakt des Provisionierungs-/Admin-Benutzers hergestellt.
- Für Team4All gilt die direkte CardDAV-/Adressbuchauswertung als fachliche Wahrheit. Die sichtbare Darstellung in der Contacts-App kann davon abweichen und ist für Team4All nicht maßgeblich.
