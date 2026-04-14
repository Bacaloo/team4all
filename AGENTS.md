# AGENTS.md

## Projekt

Dies ist eine Nextcloud-App mit dem Namen **Team4All**.

## Arbeitsweise

Bitte arbeite mit möglichst kleinen, klaren und nachvollziehbaren Änderungen, die zur bestehenden Projektstruktur passen.  
Bevorzuge saubere Integration statt schneller Sonderlösungen.

## Architekturregel

Zusätzliche Kontaktfelder, die nicht regulär durch Nextcloud Contacts abgebildet werden, dürfen **nicht** direkt in Nextcloud Contacts oder in vCard-Hacks als Hauptlösung implementiert werden.

Stattdessen gilt:

- Standard-Kontaktdaten bleiben in Nextcloud Contacts / CardDAV
- Team4All-spezifische Zusatzfelder werden in einer eigenen App-Datenbanktabelle gespeichert

## Aktuelles Feature

Es soll eine Datenhaltung für zusätzliche Kontaktinformationen umgesetzt werden, zunächst für:

- `anrede`
- `briefanrede`

Die fachliche Spezifikation steht in:

`docs/team4all-contact-meta.md`

Diese Datei ist maßgeblich.

## Datenbank

Es soll eine Tabelle mit dem Namen

`team4all_contact_meta`

angelegt und verwendet werden.

### Erwartete Spalten

- `id`
- `nc_user_id`
- `contact_uid`
- `anrede`
- `briefanrede`
- `created_at`
- `updated_at`

### Datenregel

Die Kombination aus

- `nc_user_id`
- `contact_uid`

muss eindeutig sein.

## Verhalten beim App-Setup

Die benötigte Datenbankstruktur soll automatisch beim Installieren bzw. Aktivieren der App angelegt werden.

Die Daten dürfen bei einer Deaktivierung der App nicht gelöscht werden.

## Erwartete Code-Bausteine

Bitte implementiere mindestens:

1. Datenbankschema / Migration / Setup-Logik
2. Entity für Kontakt-Zusatzdaten
3. Repository oder Mapper für Datenzugriff
4. Service für Lade- und Speicherlogik
5. eine einfache Schnittstelle zum Laden und Speichern der Zusatzfelder

## Schreiblogik

Speichern soll upsert-artig funktionieren:

- existiert noch kein Datensatz für `nc_user_id + contact_uid`, dann anlegen
- existiert bereits ein Datensatz, dann aktualisieren
- keine Dubletten erzeugen

## Qualitätsregeln

- Halte Dich an bestehende Namenskonventionen im Projekt
- Vermeide unnötige Umbauten in nicht betroffenen Bereichen
- Ergänze sinnvolle Typisierung und klare Methodennamen
- Dokumentiere nicht offensichtliche Entscheidungen kurz im Code
- Füge nach Möglichkeit Tests hinzu
- Gib am Ende eine kurze Zusammenfassung der geänderten Dateien und der Prüfschritte

## Was vermieden werden soll

Bitte nicht:

- Nextcloud Contacts direkt patchen
- Zusatzfelder nur in `NOTE` oder ähnlichen Freitextfeldern verstecken
- unnötig große Refactorings außerhalb des Features durchführen
- Daten bei App-Deaktivierung entfernen

## Abschlussformat

Am Ende der Arbeit bitte ausgeben:

1. welche Dateien neu erstellt wurden
2. welche Dateien geändert wurden
3. wie die Funktion getestet werden kann
4. welche Annahmen getroffen wurden