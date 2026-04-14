# Nextcloud-Hinweise für Codex – Team4All

## Zweck dieser Datei

Diese Datei beschreibt die bevorzugte technische Einordnung für Änderungen an der Nextcloud-App **Team4All**.

Sie ist absichtlich konservativ formuliert, damit Codex sich an einer robusten, gut wartbaren und möglichst kompatiblen Nextcloud-App-Struktur orientiert.

Diese Datei ergänzt:

- `AGENTS.md`
- `docs/team4all-contact-meta.md`
- `TODO-codex.md`

Bei Konflikten gilt folgende Reihenfolge:

1. `AGENTS.md`
2. `docs/team4all-contact-meta.md`
3. `TODO-codex.md`
4. `NEXTCLOUD-HINWEISE.md`

---

## 1. Grundsatz zur App-Struktur

Bitte halte Dich an die übliche Nextcloud-App-Struktur und an die bereits im Projekt vorhandenen Muster.

Bevorzuge bestehende Projektkonventionen gegenüber generischen Beispielstrukturen.

Typische Bereiche sind:

- `appinfo/`
- `lib/AppInfo/`
- `lib/Controller/`
- `lib/Db/`
- `lib/Service/`
- `lib/Migration/`
- `templates/`
- `js/` oder `src/`

Nicht jede App nutzt alle Bereiche.  
Neue Dateien sollen nur dort angelegt werden, wo sie zur vorhandenen Struktur passen.

---

## 2. Einstiegspunkt der App

Wenn die App bereits eine Klasse

- `lib/AppInfo/Application.php`

besitzt, soll diese als zentraler Einstiegspunkt respektiert und weiterverwendet werden.

Falls die App bereits das Bootstrap-Muster verwendet, soll dieses beibehalten werden.

Neue Feature-Logik gehört nicht unkoordiniert in verstreute Altmechanismen, wenn bereits eine saubere `Application`-Struktur vorhanden ist.

---

## 3. Bootstrapping-Regel

Wenn im Projekt eine `Application`-Klasse mit Bootstrap-Mechanismus vorhanden ist, soll Codex diese bevorzugen.

Dabei gilt:

### `register(...)`
Nur für Registrierung, Verdrahtung und lazy Service-Definitionen verwenden.

### `boot(...)`
Nur für wirklich notwendige Initialisierung verwenden, die pro Nextcloud-Prozess gebraucht wird.

Wichtig:
- keine schwere Geschäftslogik in `boot(...)`
- keine unnötigen Datenbankoperationen bei jedem Request
- keine verstreute Initialisierung an mehreren Orten

---

## 4. Datenbankschema: bevorzugter Weg

Für Tabellenanlage und Schemaänderungen ist der bevorzugte Weg:

- Migrationen in `lib/Migration/`

Bitte keine manuelle SQL-Anlage in zufälligen Klassen, Controllern oder Services einbauen.

Wenn das Projekt bereits einen anderen klaren, app-typischen Mechanismus nutzt, darf dieser nur dann verwendet werden, wenn er im Projekt konsistent vorhanden ist und keine schlechtere Wartbarkeit erzeugt.

### Wichtige Regel für Migrationen
Migrationen sollen nach ihrer Einführung nicht inhaltlich umgebaut werden.  
Neue Schemaänderungen sollen in neuen Migrationen erfolgen.

---

## 5. Installation / Aktivierung / Deaktivierung

Ziel ist:

- Die benötigte Tabelle wird automatisch beim Installieren bzw. app-üblichen Setup verfügbar.
- Die Daten bleiben bei Deaktivierung erhalten.
- Es soll keine Löschlogik für diese Daten bei App-Deaktivierung eingebaut werden.

Falls das Projekt bereits Install- oder Repair-Step-Mechanismen verwendet, dürfen diese berücksichtigt werden.  
Für reine Schemaanlage ist jedoch grundsätzlich die vorhandene bzw. bevorzugte Migrationsstruktur zu respektieren.

---

## 6. Controller und Routing

Wenn HTTP-Endpunkte benötigt werden, sollen Controller in der Regel unter

- `lib/Controller/`

angelegt oder erweitert werden.

Routing soll bevorzugt so umgesetzt werden, wie es das Projekt bereits verwendet.

### Standardpräferenz
Wenn nichts anderes im Projekt vorgegeben ist, bevorzuge:

- `appinfo/routes.php`

### Nur falls im Projekt bereits vorhanden oder eindeutig passend
- Attribut-basierte Routen direkt an Controller-Methoden

Bitte nicht beide Stile ohne Not mischen.

---

## 7. Datenzugriff

Für Datenbanklogik soll die Business-Logik nicht direkt im Controller landen.

Bevorzugte Schichtung:

1. Entity
2. Mapper / Repository
3. Service
4. Controller

### Entity
Eine Entity repräsentiert genau einen Datensatz.

### Mapper / Repository
Hier gehört der direkte Datenbankzugriff hinein.

### Service
Hier gehört die fachliche Logik hinein, z. B.:
- laden
- speichern
- upsert-Verhalten
- Validierung auf Anwendungsebene

### Controller
Nur Request/Response, Benutzerkontext, Übergabe an den Service.

---

## 8. Benennung für dieses Feature

Für das aktuelle Feature sind folgende Namen sinnvoll, sofern sie zur Projektstruktur passen:

### Tabelle
- `team4all_contact_meta`

### Entity
- `ContactMeta`

### Mapper / Repository
- `ContactMetaMapper`
oder
- `ContactMetaRepository`

### Service
- `ContactMetaService`

### Controller
- `ContactMetaController`

Bitte Namenskonflikte mit bestehenden Klassen vermeiden.

---

## 9. Empfohlene Dateien für das aktuelle Feature

Je nach vorhandener Projektstruktur sind diese Dateien typische Kandidaten:

- `lib/Db/ContactMeta.php`
- `lib/Db/ContactMetaMapper.php`
- `lib/Service/ContactMetaService.php`
- `lib/Controller/ContactMetaController.php`
- `lib/Migration/VersionXXXXXXXXXXXXDate2026....php`
- ggf. Änderungen in `lib/AppInfo/Application.php`
- ggf. Änderungen in `appinfo/routes.php`
- ggf. Änderungen in `appinfo/info.xml`

Die exakten Dateinamen sollen an das reale Projekt angepasst werden.

---

## 10. Tabellen- und Feldkonventionen

Für das aktuelle Feature soll die Datenbankstruktur inhaltlich auf dieser fachlichen Basis beruhen:

### Tabelle
`team4all_contact_meta`

### Fachfelder
- `nc_user_id`
- `contact_uid`
- `anrede`
- `briefanrede`
- `created_at`
- `updated_at`

### Eindeutigkeit
Es soll nur einen Datensatz pro Kombination geben aus:

- `nc_user_id`
- `contact_uid`

### Indizes
- Unique auf (`nc_user_id`, `contact_uid`)
- zusätzlicher Index auf `contact_uid`

---

## 11. Entity-Konventionen

Wenn eine Nextcloud-Entity verwendet wird, soll die Zuordnung zwischen Datenbankfeldern und PHP-Eigenschaften konsistent bleiben.

Beispiel:

- `contact_uid` → `contactUid`
- `nc_user_id` → `ncUserId`
- `created_at` → `createdAt`
- `updated_at` → `updatedAt`

Bitte innerhalb der PHP-Klassen konsistent mit lowerCamelCase arbeiten.

---

## 12. Upsert-Verhalten

Die Speicherlogik soll fachlich so funktionieren:

- Datensatz mit `nc_user_id + contact_uid` suchen
- wenn vorhanden: aktualisieren
- wenn nicht vorhanden: neu anlegen

Es dürfen keine Dubletten entstehen.

---

## 13. Benutzerkontext und Sicherheit

Bitte Benutzerkontext sauber behandeln.

Wichtige Leitlinien:

- Kein freier Zugriff auf fremde Zusatzdaten
- Benutzerbezug aus dem aktuellen Nextcloud-Kontext ableiten, wenn das Projekt dies so vorsieht
- nicht blind einem vom Client gesendeten `nc_user_id` vertrauen, wenn der Benutzerkontext serverseitig verfügbar ist
- Controller sollen nur den autorisierten Kontext bedienen

---

## 14. Validierung

Mindestens auf diese Punkte achten:

- `contact_uid` darf nicht leer sein
- Stringfelder sollen sauber normalisiert werden
- leere Eingaben dürfen als leer oder `null` behandelt werden – aber konsistent
- keine stillen Dubletten erzeugen
- Fehlerfälle verständlich behandeln

---

## 15. Zeitstempel

Beim Speichern:

- `created_at` nur beim erstmaligen Anlegen setzen
- `updated_at` bei jeder Änderung aktualisieren

Bitte die im Projekt bereits verwendete Zeitdarstellung bevorzugen.

---

## 16. Erweiterbarkeit

Die Struktur soll spätere zusätzliche Kontakt-Zusatzfelder erlauben, z. B.:

- `titel`
- `namenszusatz`
- `kundennummer`
- `objektbezug`
- `interne_bemerkung`

Bitte keine Lösung bauen, die spätere Felder unnötig erschwert.

---

## 17. Tests und Prüfbarkeit

Wenn das Projekt bereits Teststrukturen besitzt, sollen diese genutzt werden.

Wenn keine Tests kurzfristig ergänzt werden, dann mindestens am Ende liefern:

- Liste der angelegten/geänderten Dateien
- Prüfschritte zur Tabellenanlage
- Prüfschritte zum Speichern und Laden
- Prüfschritte zur Benutzertrennung

---

## 18. Was vermieden werden soll

Bitte ausdrücklich vermeiden:

- Patchen von Nextcloud Contacts selbst
- Speicherung der Zusatzdaten als Hauptlösung in Freitextfeldern wie `NOTE`
- direkte SQL-Logik im Controller
- Datenbankanlage durch zufällige Laufzeitnebenwirkungen
- unnötig große Refactorings außerhalb dieses Features
- Löschung der Tabelle bei App-Deaktivierung

---

## 19. Entscheidungsregel bei Unsicherheit

Falls die Projektstruktur nicht eindeutig ist, gilt:

1. Bestehende Projektmuster prüfen
2. vorhandene Nextcloud-App-Struktur respektieren
3. konservative, wartbare Standardlösung wählen
4. Annahmen am Ende dokumentieren

Bitte im Zweifel eine kleine, klare und review-freundliche Lösung bevorzugen.

---

## 20. Erwartete Abschlussausgabe von Codex

Am Ende der Arbeit bitte ausgeben:

### Neu erstellte Dateien
Liste aller neuen Dateien

### Geänderte Dateien
Liste aller angepassten Dateien

### Technische Kurzbeschreibung
Welche Klasse welche Aufgabe übernimmt

### Prüfschritte
Wie die Funktion lokal getestet werden kann

### Annahmen
Welche Projektentscheidungen nicht eindeutig waren