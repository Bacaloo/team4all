# Merge-Hinweis

Bei Änderungen an `appinfo/info.xml` gilt für `navigations/navigation/role`:
- `<role>admin</role>` darf nur ergänzt werden, wenn für den jeweiligen Navigationseintrag noch kein `<role>` existiert.
- Ein bereits gesetzter Rollenwert darf nicht stillschweigend überschrieben werden und muss vor einer Änderung bewusst geprüft werden.
- Für maximale Betriebssicherheit erfolgt diese Ergänzung nicht zur App-Aktivierungszeit in Nextcloud, sondern kontrolliert im Repository, zum Beispiel mit `php8.2 scripts/ensure-navigation-role.php`.
- Für die harte Prüfung ohne Änderungen dient `php8.2 scripts/validate-navigation-role.php`.
