# team4all

## App-Bilder

### Beschreibung / Branding
![Team4All Beschreibung](img/app-description.svg)

### Menü-Logo (Nextcloud Navigation)
![Team4All Menü-Logo](img/app-menu.svg)

Teamarbeit für alle im Unternehmen

## Pflegehinweise

Bei Änderungen an `appinfo/info.xml` gilt für `navigations/navigation/role`:
- `<role>admin</role>` nur setzen, wenn für den Navigationseintrag noch kein `<role>` existiert.
- Wenn bereits ein `<role>` vorhanden ist, diesen nicht blind ersetzen, sondern die bestehende Berechtigungsabsicht zuerst prüfen.

Für maximale Betriebssicherheit wird diese Regel nicht zur Nextcloud-Aktivierungszeit erzwungen. Stattdessen kann sie vor Release oder Deployment kontrolliert mit `php8.2 scripts/ensure-navigation-role.php` angewendet werden.

Für eine strikte Prüfung ohne Änderungen steht zusätzlich `php8.2 scripts/validate-navigation-role.php` zur Verfügung. Der sicherste Ablauf ist:
- optional `php8.2 scripts/ensure-navigation-role.php`
- danach verpflichtend `php8.2 scripts/validate-navigation-role.php`
