# TODO for Codex – Team4All Kontakt-Zusatzdaten

## Ausgangslage

Die fachliche Spezifikation steht in:

- `docs/team4all-contact-meta.md`

Die allgemeinen Arbeitsregeln stehen in:

- `AGENTS.md`

Diese Datei übersetzt die fachlichen Anforderungen in eine konkrete technische Aufgabenliste für die Nextcloud-App **Team4All**.

---

## Ziel der Umsetzung

Für Kontakte sollen zusätzliche, nicht in Nextcloud Contacts vorgesehene Felder in einer eigenen Team4All-Datenbanktabelle gespeichert werden.

Erste Felder:

- `anrede`
- `briefanrede`

Die Daten sollen anhand von

- `nc_user_id`
- `contact_uid`

eindeutig geladen und gespeichert werden können.

---

## Umsetzungsauftrag

### 1. Projektstruktur prüfen

- Ermittle die bestehende Struktur der Nextcloud-App.
- Identifiziere vorhandene Verzeichnisse und Muster für:
  - `lib/Db`
  - `lib/Service`
  - `lib/Controller`
  - `appinfo`
  - bestehende Bootstrap-/Setup-/Migrationslogik
- Halte Dich an die vorhandene Struktur und Namenskonventionen der App.

### 2. Datenbankschema anlegen

Implementiere die Datenbankstruktur für die Tabelle:

- `team4all_contact_meta`

Erforderliche Spalten:

- `id`
- `nc_user_id`
- `contact_uid`
- `anrede`
- `briefanrede`
- `created_at`
- `updated_at`

Erforderliche Regeln:

- Primärschlüssel auf `id`
- Unique-Constraint auf (`nc_user_id`, `contact_uid`)
- zusätzlicher Index auf `contact_uid`

### 3. App-Setup / Migration / Initialisierung

Sorge dafür, dass die Tabelle automatisch angelegt wird, sobald die App installiert bzw. aktiviert wird.

Wichtig:

- Es darf keine manuelle SQL-Anlage durch den Benutzer nötig sein.
- Die Daten dürfen bei Deaktivierung der App nicht gelöscht werden.
- Nutze die für Nextcloud-Apps im Projekt bereits vorhandene oder übliche Setup-/Migrationslogik.

### 4. Entity anlegen

Erstelle eine Entity-Klasse für einen Datensatz der Tabelle `team4all_contact_meta`.

Die Entity soll mindestens folgende Felder kapseln:

- `id`
- `ncUserId`
- `contactUid`
- `anrede`
- `briefanrede`
- `createdAt`
- `updatedAt`

Achte auf:

- klare Getter/Setter bzw. Projektstandard
- sinnvolle Typisierung
- konsistente Benennung

### 5. Repository / Mapper anlegen

Erstelle eine Datenzugriffsklasse für `team4all_contact_meta`.

Sie soll mindestens folgende Operationen unterstützen:

- Datensatz anhand von `nc_user_id` + `contact_uid` laden
- neuen Datensatz anlegen
- bestehenden Datensatz aktualisieren
- optional: Datensatz löschen oder Felder leeren

Wichtig:

- kein doppelter Datensatz für dieselbe Kombination aus Benutzer und Kontakt
- sinnvolle Fehlerbehandlung
- lesbarer, kleiner Datenzugriffscode

### 6. Service-Layer anlegen

Erstelle einen Service, der die fachliche Logik bündelt.

Mindestens benötigte Methoden:

- `getMeta(string $ncUserId, string $contactUid)`
- `saveMeta(string $ncUserId, string $contactUid, ?string $anrede, ?string $briefanrede)`
- optional: `clearMeta(...)`

Verhalten von `saveMeta(...)`:

- wenn noch kein Datensatz existiert: anlegen
- wenn Datensatz existiert: aktualisieren
- `updated_at` aktualisieren
- `created_at` nur beim Neuanlegen setzen

### 7. Controller- oder API-Schnittstelle anlegen

Stelle eine einfache Schnittstelle bereit, über die die Zusatzdaten gelesen und gespeichert werden können.

Bevorzugt:

- ein kleiner Controller mit klaren Endpunkten
- oder ein bereits vorhandenes App-Muster verwenden

Mindestens benötigte Operationen:

- Laden der Zusatzdaten für `contact_uid`
- Speichern von `anrede` und `briefanrede`

Erwartetes Verhalten:

- bei nicht vorhandenem Datensatz leere Antwortstruktur statt Fehler
- klare Validierung der Eingaben
- Zugriff nur im Kontext des aktuellen Nextcloud-Benutzers

### 8. Benutzerkontext korrekt verwenden

Stelle sicher:

- `nc_user_id` wird aus dem aktuellen Nextcloud-Benutzerkontext abgeleitet oder konsistent übergeben
- Kontakte verschiedener Benutzer werden sauber getrennt
- kein Benutzer kann auf Zusatzdaten eines anderen Benutzers zugreifen

### 9. Zeitstempel sauber behandeln

Beim Speichern:

- `created_at` beim ersten Anlegen setzen
- `updated_at` bei jeder Änderung aktualisieren

Verwende das im Projekt übliche Datums-/Zeitformat bzw. die Nextcloud-konforme Vorgehensweise.

### 10. Erweiterbarkeit berücksichtigen

Baue die Lösung so, dass später weitere Felder ergänzt werden können, zum Beispiel:

- `titel`
- `namenszusatz`
- `kundennummer`
- `objektbezug`
- `interne_bemerkung`

Die jetzige Lösung soll dafür keine Sackgasse erzeugen.

---

## Erwartete Dateien

Passe die Pfade an die reale Projektstruktur an. Typischerweise werden neue oder geänderte Dateien in diesen Bereichen erwartet:

- `appinfo/`
- `lib/Db/`
- `lib/Service/`
- `lib/Controller/`

Mögliche neue Dateien, falls passend zur Projektstruktur:

- `lib/Db/ContactMeta.php`
- `lib/Db/ContactMetaMapper.php`
- `lib/Service/ContactMetaService.php`
- `lib/Controller/ContactMetaController.php`

Außerdem voraussichtlich Änderungen an Setup-/Bootstrap-/Migrationsdateien.

---

## Akzeptanztests

Bitte die Umsetzung so vorbereiten, dass diese Prüfungen möglich sind:

### Test 1 – Tabellenanlage
- App installieren oder aktivieren
- prüfen, ob die Tabelle `team4all_contact_meta` existiert

### Test 2 – Neuen Datensatz speichern
- für einen Testkontakt mit bekannter `contact_uid`
- `anrede = Herr`
- `briefanrede = Sehr geehrter Herr Mustermann`
- speichern
- Ergebnis prüfen

### Test 3 – Datensatz wieder laden
- denselben Kontakt erneut laden
- Werte müssen unverändert zurückkommen

### Test 4 – Datensatz aktualisieren
- `briefanrede` ändern
- erneut speichern
- es darf kein zweiter Datensatz entstehen

### Test 5 – Benutzertrennung
- gleicher `contact_uid`-Wert in anderem Benutzerkontext
- Daten dürfen nicht vermischt werden

### Test 6 – Leerer Zustand
- Kontakt ohne Zusatzdaten laden
- es soll eine leere, aber gültige Antwort zurückgegeben werden

---

## Nicht umsetzen

Bitte ausdrücklich vermeiden:

- Änderungen direkt an Nextcloud Contacts
- Speicherung der Zusatzfelder als Hauptlösung in vCard-`NOTE`
- unstrukturierte Freitext-Hacks
- große Refactorings außerhalb dieses Features
- Löschen der Tabelle bei App-Deaktivierung

---

## Dokumentation der Lieferung

Bitte am Ende der Arbeit ausgeben:

### A. Erstellte Dateien
Liste aller neu angelegten Dateien

### B. Geänderte Dateien
Liste aller geänderten Dateien

### C. Technische Kurzbeschreibung
Kurze Beschreibung, welche Klasse welche Aufgabe hat

### D. Prüfschritte
Konkrete Schritte, wie die Funktion lokal getestet werden kann

### E. Offene Annahmen
Welche Punkte im Projekt nicht eindeutig waren und wie sie gelöst wurden

---

## Arbeitsstil

Bitte arbeite in kleinen, sauberen Schritten:

1. Projektstruktur analysieren
2. Datenbankschema/Setup umsetzen
3. Entity + Repository/Mapper erstellen
4. Service erstellen
5. Controller/API ergänzen
6. Tests oder Prüfschritte dokumentieren

Halte die Änderungen möglichst klein, nachvollziehbar und review-freundlich.