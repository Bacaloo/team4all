# Merge-Hinweis

Die Sichtbarkeit und Nutzbarkeit der App wird nicht mehr über statische `role`-Einträge in `appinfo/info.xml` gesteuert, sondern dynamisch über die Kontengruppe `Team4All`.
Team4All arbeitet fachlich nur mit Adressbuchkontakten aus der Kontaktgruppe `Team4All`.
Die Kontaktgruppen-Provisionierung arbeitet dabei ausschließlich mit dem Standard-Adressbuch `contacts` des Provisionierungs-/Admin-Benutzers.
Für Team4All ist die direkte Auswertung der CardDAV-/Adressbuchdaten maßgeblich. Die Anzeige in der Contacts-App ist dafür nicht die fachliche Referenz.
Bei Firmennamen im Muster `AAAA-NNN` verwendet Team4All nur die vier alphanumerischen Zeichen vor dem Bindestrich als fachlichen Firmennamen und damit auch als Gruppenleader.
