# Merge-Hinweis

Die Sichtbarkeit und Nutzbarkeit der App wird nicht über statische `role`-Einträge in `appinfo/info.xml` gesteuert.
Standardmäßig ist die App für alle angemeldeten Nextcloud-Nutzer verfügbar.
Zusätzliche Einschränkungen sollen app-intern und funktionsbezogen erfolgen.
Die Kontengruppe `Team4All` bleibt für Provisioning und technische Zielstrukturen relevant.
Team4All wertet für den aktuellen Nutzer alle sichtbaren bzw. freigegebenen Adressbücher aus.
In Team4All sichtbar bleiben weiterhin nur Kontakte aus der Kontaktgruppe `Team4All`.
Team4All-Zusatzdaten werden pro Kontakt geteilt statt pro NC-Benutzer getrennt gespeichert.
Team4All arbeitet fachlich nur mit Adressbuchkontakten aus der Kontaktgruppe `Team4All`.
Die Kontaktgruppen-Provisionierung arbeitet dabei ausschließlich mit dem Standard-Adressbuch `contacts` des Provisionierungs-/Admin-Benutzers.
Für Team4All ist die direkte Auswertung der CardDAV-/Adressbuchdaten maßgeblich. Die Anzeige in der Contacts-App ist dafür nicht die fachliche Referenz.
Bei Firmennamen im Muster `AAAA-NNN` verwendet Team4All nur die vier alphanumerischen Zeichen vor dem Bindestrich als fachlichen Firmennamen und damit auch als Gruppenleader.
