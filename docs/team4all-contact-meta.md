# Team4All – Zusatzdaten zu Kontakten

## Zweck

Die Nextcloud-App **Team4All** soll zusätzliche Kontaktinformationen speichern, die in der normalen Nextcloud-Kontakte-App bzw. im üblichen vCard-Datenmodell nicht oder nicht zuverlässig strukturiert abgebildet werden können.

Beispiele für solche Zusatzinformationen sind:

- Anrede
- Briefanrede

Standard-Kontaktdaten wie Name, Firma, E-Mail, Telefonnummer und Anschrift bleiben weiterhin im normalen Nextcloud-Contacts-/CardDAV-Bereich.  
Die zusätzlichen Team4All-Daten werden in einer eigenen App-Tabelle gespeichert.

---

## Ziel

Für jeden Kontakt soll Team4All strukturierte Zusatzfelder speichern und wieder laden können, ohne die Standard-Kontaktverwaltung von Nextcloud zu verändern.

Die Lösung soll:

- technisch sauber in die Nextcloud-App integriert sein
- beim Aktivieren bzw. Installieren der App automatisch die benötigte Datenbankstruktur anlegen
- bestehende Daten bei Deaktivierung der App nicht löschen
- pro Benutzer und Kontakt genau einen Datensatz für die Zusatzfelder verwalten

---

## Fachliches Modell

### Primäre Zusatzfelder

Aktuell sollen mindestens folgende Felder unterstützt werden:

- `anrede`
- `briefanrede`

Die Lösung soll so aufgebaut sein, dass später weitere Zusatzfelder ergänzt werden können, ohne die Architektur neu zu erfinden.

---

## Eindeutigkeit eines Datensatzes

Ein Datensatz ist eindeutig über folgende Kombination definiert:

- `nc_user_id`
- `contact_uid`

### Bedeutung

- `nc_user_id` = Nextcloud-Benutzer, dem der Kontaktkontext zugeordnet ist
- `contact_uid` = stabile UID des Kontakts aus dem Contacts-/vCard-Kontext

Für dieselbe Kombination aus Benutzer und Kontakt darf es nur **einen** Datensatz geben.

---

## Datenbankstruktur

### Tabellenname

`team4all_contact_meta`

### Spalten

| Spalte | Typ | Null | Beschreibung |
|---|---|---:|---|
| `id` | bigint / integer autoincrement | nein | Primärschlüssel |
| `nc_user_id` | string(64) | nein | Benutzerkennung in Nextcloud |
| `contact_uid` | string(255) | nein | Kontakt-UID |
| `anrede` | string(255) | ja | z. B. Herr, Frau, Firma, Dr. |
| `briefanrede` | text | ja | z. B. Sehr geehrter Herr Müller |
| `created_at` | datetime | nein | Erzeugt am |
| `updated_at` | datetime | nein | Zuletzt geändert am |

### Indizes und Constraints

Es sollen mindestens folgende Datenbankregeln existieren:

1. Primärschlüssel auf `id`
2. Eindeutiger Index auf:
   - `nc_user_id`
   - `contact_uid`
3. Zusätzlicher Index auf:
   - `contact_uid`

---

## Fachliche Regeln

### Lesen

Wenn für eine Kombination aus `nc_user_id` und `contact_uid` ein Datensatz existiert, sollen die Zusatzfelder geladen und an die App zurückgegeben werden.

Wenn kein Datensatz existiert, soll die App einen leeren Ergebniszustand liefern, aber keinen Fehler werfen.

### Schreiben

Beim Speichern gilt:

- Existiert noch kein Datensatz für `nc_user_id + contact_uid`, wird ein neuer Datensatz angelegt.
- Existiert bereits ein Datensatz, wird dieser aktualisiert.
- Es dürfen keine Dubletten entstehen.

### Löschen

Ein Löschen der Zusatzdaten kann optional vorgesehen werden.  
Mindestens soll es möglich sein, Felder auf `NULL` bzw. leer zu setzen, ohne den Kontaktdatensatz selbst zu beeinflussen.

---

## Technische Anforderungen

## Datenbankanlage beim App-Setup

Die benötigte Tabelle muss automatisch angelegt werden, wenn die App installiert bzw. aktiviert wird.

Die Umsetzung soll sich an den in Nextcloud üblichen Mechanismen für App-Datenbankschemata orientieren.  
Die Struktur darf **nicht** manuell durch den Anwender angelegt werden müssen.

## Datenhaltung

Die Zusatzdaten sollen über klar getrennte Klassen gekapselt werden, mindestens in Form von:

- Entity
- Repository bzw. Mapper
- Service

## Schnittstelle

Es soll eine einfache Schnittstelle geben, um Zusatzdaten anhand von `nc_user_id` und `contact_uid` zu laden und zu speichern.

Diese Schnittstelle kann je nach vorhandener Projektstruktur z. B. sein:

- Controller-Endpunkt
- internes Service-API
- Kombination aus Controller und Service

---

## Nicht-Ziele

Folgendes ist ausdrücklich **nicht** Ziel dieser Änderung:

- Erweiterung der Nextcloud-Contacts-App selbst
- Speicherung der Zusatzfelder direkt im vCard-Standardmodell
- Veränderung oder Überschreiben bestehender Contacts-Felder
- Löschung der Team4All-Zusatzdaten bei Deaktivierung der App

---

## Erweiterbarkeit

Die Architektur soll so vorbereitet sein, dass später weitere Felder ergänzt werden können, zum Beispiel:

- Titel
- Namenszusatz
- interne Kundennummer
- Objektbezug
- individuelle Anschrift für Briefe
- abweichende Empfängerzeile
- interne Bemerkungen

Die Erweiterung soll bevorzugt durch zusätzliche Spalten oder eine bewusst geplante Folgestruktur erfolgen, ohne den bestehenden Zugriffspfad zu brechen.

---

## Akzeptanzkriterien

Die Umsetzung gilt als erfolgreich, wenn folgende Punkte erfüllt sind:

1. Nach Installation bzw. Aktivierung der App existiert die Tabelle `team4all_contact_meta`.
2. Für einen Kontakt mit bekannter `contact_uid` können `anrede` und `briefanrede` gespeichert werden.
3. Die gespeicherten Werte können anschließend wieder geladen werden.
4. Wiederholtes Speichern desselben Kontakts erzeugt keine Dubletten.
5. Die Daten bleiben bei Deaktivierung der App erhalten.
6. Die Änderung ist in die vorhandene Team4All-Projektstruktur sauber integriert.
7. Der Code ist so aufgebaut, dass weitere Zusatzfelder später ohne Grundumbau ergänzt werden können.

---

## Erwartete Deliverables

Die Implementierung soll mindestens folgende Punkte umfassen:

- Datenbank-Migration bzw. Schema-Anlage für `team4all_contact_meta`
- Entity-Klasse für Kontakt-Zusatzdaten
- Repository/Mapper für Datenbankzugriffe
- Service für Lade-/Speicherlogik
- Controller oder andere App-Schnittstelle zum Lesen und Schreiben
- kurze technische Dokumentation der geänderten Dateien
- nach Möglichkeit Tests