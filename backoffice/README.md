# Imperiales Sicherheitsbüro (Backoffice)

Internes Backoffice für BricksnBytes. PHP 8.3+ und MySQL/MariaDB, ohne Frameworks oder
Abhängigkeiten – läuft auf normalem Strato-Webspace.

**Stand Phase 1:** Login, Dashboard (Kundenzahlen; Umsatz/Forecast/Termine als Platzhalter),
globale Suche, **Kunden** (Eltern, Schulen, Horte, Kitas, Vereine, Firmen, Bibliotheken, Partner,
Öffentliche Träger, Sonstige) mit
Ansprechpartnern, **Kindern**, Einwilligungen und Änderungsprotokoll, Kinder-Übersicht,
CSV-Export und -Import. Aufträge, Kurse, Termine, Buchungen, Rechnungen, Forecast, Kursleiter
und Einstellungen sind als Seiten angelegt („Kommt bald“); ihre Tabellen existieren bereits
(`migrations/003_gesamtschema.sql`).

```
backoffice/
  public/        ← einziger Ordner, auf den die Subdomain zeigt (Webroot)
  src/           ← Logik (Login, Datenbank, Module Kunden/Import/…)
  templates/     ← HTML
  migrations/    ← Datenbankschema, nummeriert (001_…, 002_…, 003_gesamtschema = alle Module)
  bin/           ← Kommandozeile: setup.php (erster Admin), migrate.php
  storage/       ← Sessions (wird automatisch angelegt, nicht in Git)
  config.php     ← Zugangsdaten (nur auf dem Server, nie in Git)
```

> **Wichtig:** Das Repository ist öffentlich. Zugangsdaten, `config.php`, `.htpasswd`,
> CSV-Dateien und Backups gehören **nie** ins Repo (`.gitignore` schließt sie aus).
> Auch die Adresse des Backoffice steht bewusst nicht in dieser Datei.

---

## 1. Einrichtung bei Strato (einmalig)

Alle Menüpunkte beziehen sich auf den Strato-Kundenbereich. Strato benennt Menüs gelegentlich
um – falls ein Punkt anders heißt, nach dem sinngemäßen Eintrag suchen.

### 1.1 PHP-Version
Unter den PHP-Einstellungen des Pakets **PHP 8.3 oder neuer** auswählen.

### 1.2 Datenbank anlegen
1. *Datenbanken und Webspace* → *Datenbanken verwalten* → neue **MySQL**-Datenbank anlegen.
2. Ein **starkes, neues Passwort** vergeben (nicht das Kundenbereich-Passwort).
3. Folgende Angaben aus der Übersicht notieren – **genau so übernehmen, nicht raten**:
   **Datenbank-Host**, **Datenbankname**, **Datenbank-Benutzer**.

Die Datenbank ist nur vom Strato-Webspace aus erreichbar, nicht aus dem Internet.

### 1.3 SSH-Zugang
Unter *Datenbanken und Webspace* → **SFTP & SSH** einen Zugang mit der Zugangsart
**„SFTP + SSH“** anlegen (nur „SFTP“ reicht nicht → Meldung `shell access not allowed`) und ein
Passwort setzen. In der Zugangsliste stehen dann **Server** (Muster `…ssh.w….strato.hosting`) und
**Benutzername** (Muster `stu…`/`su…`).

Achtung, nicht verwechseln: Die Datenbank-Daten aus 1.2 (`database-….webspace-host.com`,
Benutzer `dbu…`) funktionieren für SSH **nicht** (→ „connection timeout“), sie gehören nur in die
`config.php`. Auch `bricksnbytes.de` als Adresse geht nicht – die zeigt auf den Website-Server.

Vom eigenen Rechner (Windows: PowerShell, SSH ist eingebaut; Mac/Linux: Terminal) – und
**nicht** mit einem lokalen PHP wie XAMPP, alle folgenden Befehle laufen auf dem Server:

```bash
ssh BENUTZER@SERVER
php -v     # muss 8.3 oder neuer zeigen
git --version
```

Die Warnung `client_global_hostkeys_prove_confirm: server gave bad signature …` beim Verbinden
ist harmlos und kann ignoriert werden. Erscheint nach dem Passwort keine Eingabezeile, einfach
trotzdem `php -v` tippen.

Zeigt `php -v` eine ältere Version, bietet Strato die neueren meist unter eigenem Namen an
(z. B. `php83`) – mit `ls /usr/bin/ | grep php` nachsehen und diesen Befehl unten statt `php`
verwenden.

### 1.4 Code auf den Server holen

**Variante A – mit git (empfohlen, wenn `git --version` funktioniert):**

```bash
cd ~
git clone https://github.com/bumbleflies/bricksnbytes.git ibs-repo
```

Der Webroot ist dann `ibs-repo/backoffice/public`.

**Variante B – ohne git (SFTP, z. B. mit FileZilla):**
1. Auf GitHub *Code → Download ZIP* und entpacken.
2. Den Inhalt des Ordners `backoffice/` per SFTP in einen neuen Ordner `ibs` im Webspace laden.

Der Webroot ist dann `ibs/public`.

### 1.5 Subdomain anlegen und auf den Webroot zeigen lassen
1. *Domains* → *bricksnbytes.de* → **Subdomain anlegen** (den gewählten Namen).
2. Als **Ziel** das Verzeichnis aus 1.4 eintragen (`/ibs-repo/backoffice/public` bzw. `/ibs/public`).
3. Für die Subdomain ein **SSL-Zertifikat aktivieren** (SSL-Verwaltung).

**An der Hauptdomain `bricksnbytes.de` und `www` nichts ändern** – die zeigen auf den
Website-Server und müssen so bleiben.

### 1.6 Zugangsdaten eintragen

```bash
cd ~/ibs-repo/backoffice      # Variante B: cd ~/ibs
cp config.example.php config.php
nano config.php               # Host, Datenbankname, Benutzer, Passwort aus 1.2 eintragen
chmod 600 config.php
```

`'debug' => false` und `'secure_cookies' => true` so lassen.

### 1.7 Datenbank-Tabellen und ersten Admin anlegen

```bash
php bin/setup.php
```

Das Skript legt alle Tabellen an und fragt nach Benutzername und Passwort (mindestens 12
Zeichen, am besten aus einem Passwort-Manager). Sobald ein Benutzer existiert, verweigert es jeden
weiteren Lauf. Bei Variante B danach `rm bin/setup.php`.

Das eigene Passwort ändert man danach jederzeit im Sicherheitsbüro selbst: unten in der
Seitenleiste unter dem Benutzernamen → **Passwort ändern** (aktuelles Passwort nötig; fünf
falsche Eingaben sperren wie beim Login für 15 Minuten).

### 1.8 Testen
- `https://<subdomain>.bricksnbytes.de` öffnen → Login erscheint, Anmeldung klappt.
- `http://…` (ohne s) muss automatisch auf `https://` umleiten.
- `https://<subdomain>.bricksnbytes.de/config.php` muss **nicht** erreichbar sein
  (liegt außerhalb des Webroots → Login bzw. „nicht gefunden“).

### 1.9 Optional: zweites Schloss vor dem Login (empfohlen)
Ein Browser-Passwortdialog vor dem eigentlichen Login hält Bots komplett fern:

```bash
cd ~/ibs-repo/backoffice      # bzw. ~/ibs
php -r 'fwrite(STDERR, "Passwort: "); echo "BENUTZER:", password_hash(trim(fgets(STDIN)), PASSWORD_BCRYPT), PHP_EOL;' > .htpasswd
chmod 644 .htpasswd
pwd                           # absoluten Pfad merken
```

Dann in `public/.htaccess` die vier `Auth…`/`Require`-Zeilen am Ende einkommentieren und bei
`AuthUserFile` den Pfad aus `pwd` + `/.htpasswd` eintragen. (Bei Variante A wird diese Änderung
bei einem `git pull` zum Konflikt – dann die Datei vor dem Pull sichern und danach wieder
anpassen.)

---

## 2. Alte Newsletter-Adressen importieren
1. Anmelden → *Kunden* → **Newsletter-Liste importieren** → Datei wählen → **Datei prüfen**.
2. Die Vorschau zeigt Zeichensatz, Trennzeichen, Spaltenzuordnung und wie viele Zeilen neu,
   doppelt oder fehlerhaft sind. Es wird erst gespeichert, wenn du **… Kunden importieren** klickst.
3. Jede neue Adresse wird als **Privatkunde** angelegt; Vor- und Nachname werden zum Elternteil.
   Der Newsletter-Status wird eine **Einwilligung**: erteilt am `date_created`, bei
   `unsubscribed` widerrufen am `date_modified`. Alle anderen Spalten (IP-Adressen, Statistiken)
   werden verworfen.
4. Bereits vorhandene E-Mail-Adressen werden übersprungen, nichts wird überschrieben.
5. Fehlende Angaben (Name, Adresse, Kinder) später beim Kunden ergänzen.

Die CSV-Datei danach von deinem Rechner löschen bzw. sicher ablegen; der Server speichert sie nicht.

---

### Kinder aus der pretix-Bestellübersicht importieren

**Kinder → Kinder importieren** nimmt die Bestell-Übersicht (eine Zeile pro Kind und Buchung) als
CSV – in Google Sheets: Reiter öffnen → *Datei → Herunterladen → CSV*. Benötigt: `E-Mail`,
`Name Kind`; genutzt außerdem `Name Eltern`, `Alter Kind`, `Bestellung`, `Bestellt am`, `Hinweis`.

- Verbindung über die **E-Mail** (auch die eines Ansprechpartners, Groß-/Kleinschreibung egal):
  bestehende Kunden bekommen nur fehlende Kinder (gleicher Vorname = schon da), neue Adressen
  werden Privatkunden mit bis zu zwei Elternteilen aus „Name Eltern“.
- Statt Geburtsdatum: **geschätztes Geburtsjahr** aus Alter und Buchungsdatum, mit Vermerk in den
  Notizen; bei mehreren Kindern in einer Buchung zusätzlich „Alter bitte prüfen“.
- Nicht importiert, sondern zum Prüfen angezeigt: Testbuchungen mit der eigenen Adresse,
  Platzhalter statt Namen, Kind mit gleichem Namen wie das Elternteil. Kinder, die unter mehreren
  E-Mail-Adressen vorkommen, werden als Hinweis gelistet.
- Buchungen/Termine und Newsletter-Einwilligungen werden nicht angelegt bzw. nicht verändert.

### Kontaktliste importieren (Schulen, Kitas, Bibliotheken, Partner, Eltern)

**Kunden → Kontakte importieren** nimmt eine Kontaktliste mit einer Zeile pro Person bzw. Adresse.
Benötigt: `E-Mail`, `Typ`; genutzt außerdem `Name`, `Telefon`, `Organisation`, `Status`,
`Letzter Kontakt`, `Anzahl Mails`, `Notizen`.

- Übernommen wird nur, wessen E-Mail es noch nicht gibt (Kunde oder Ansprechpartner).
- Zeilen derselben **Organisation** werden ein Kunde: Personen mit Namen werden Ansprechpartner (der
  jüngste Kontakt ist Hauptkontakt), die jüngste Adresse ohne Namen wird die allgemeine E-Mail,
  weitere solche Adressen stehen in den Notizen. Gibt es die Organisation unter genau diesem Namen
  schon, werden dort nur neue Ansprechpartner ergänzt.
- Eltern: ein Privatkunde je Zeile (Name → Elternteil). „Frau“/„Herr“ werden weggelassen.
- Typen: Kita/Familienzentrum → Kita, Hort/Förderverein → Hort, Unternehmen/Sponsor und Hotel →
  Firma, Veranstalter/Partner und Raumgeber/Partner → Partner, Bibliothek → Bibliothek, Öffentlicher
  Träger → Öffentlicher Träger; Kursleiter werden nicht importiert. Bei gemischten Typen einer
  Organisation zählt der häufigste.
- **Status** (Feld beim Kunden, filterbar in der Liste): Abgelehnt und Kein Auftrag → Kein Interesse;
  bei mehreren Personen zählt die mit dem jüngsten Kontakt. Unbekannte Status (z. B. „Abgemeldet“)
  stehen in den Notizen und werden in der Vorschau als Hinweis gelistet.
- Notizen und Anzahl Mails kommen in die Notizen des Kunden; Newsletter-Einwilligungen werden
  nicht gesetzt.

### Kundenliste: Typ für Auswahl ändern

- **Typ für Auswahl ändern** (im Bereich „E-Mail an Auswahl“): ausgewählte bzw. alle gefilterten
  Kunden auf einen Typ umstellen, mit Rückfrage. Wie beim Einzelwechsel werden Eltern mit Kindern
  und Kunden ohne Namen der Einrichtung nicht in eine Einrichtung umgewandelt (übersprungen, mit
  Hinweis); jede Änderung steht im Änderungsprotokoll.
- Typ **Öffentlicher Träger** (Migration 007). Die Migration stellt außerdem einmalig Kunden mit
  „Bildungslokal“ im Namen oder in der E-Mail auf Öffentlicher Träger und mit „Bibliothek“ auf
  Bibliothek um (nicht bei Kunden mit Kindern; protokolliert als „migration 007“).

### Kundenliste: Aktionen und Typ

- Am Ende jeder Zeile (immer sichtbar, auch wenn die Tabelle seitlich scrollt): **Stift** öffnet den
  Kunden direkt im Bearbeiten-Formular; Speichern und Abbrechen führen zurück in dieselbe
  Listenansicht (Suche, Filter, Sortierung, Seite). **Auge** öffnet eine Schnellansicht als Modal
  (Name, Vor-/Nachname, Typ, Status, E-Mail, Telefon, Adresse, Newsletter, Ansprechpartner, Kinder)
  mit „Bearbeiten“ und „Zur Detailseite“; schließen per ×, Escape oder Klick daneben.
- Der **Typ** lässt sich in der Liste per Auswahlfeld ändern und wird sofort gespeichert. Eltern mit
  Kindern bzw. ohne Namen der Einrichtung lassen sich dort nicht in eine Einrichtung umwandeln
  (Hinweis, dann über „Bearbeiten“).
- Detailseite und die Links auf Name/E-Mail bleiben wie bisher.

### Vor- und Nachname beim Kunden

Kunden haben in den Stammdaten eigene Felder **Vorname** und **Nachname** (Migration 006, für
bestehende Kunden leer). Sind beide leer, schlägt das Backoffice den Namen des Hauptkontakts vor,
gibt es keinen, einen Namen aus der E-Mail
(`anna.mueller@…` → Anna / Mueller; `.` und `_` trennen, `-` bleibt, Ziffern fallen weg; bei
info@, kontakt@, familie123@ … kein Vorschlag, bei nur einem Wort nur der Vorname):

- Detailseite: Vorschlag als Hinweis; im **Bearbeiten**-Formular vorausgefüllt und markiert,
  gespeichert erst mit „Speichern“ (zum Verwerfen die Felder leeren).
- **Namen aus E-Mail übernehmen** (Link auf der Detailseite, `/kunden/namen`): Vorschau aller Kunden
  ohne Namen mit Vorschlag, korrigierbar, Eltern vorausgewählt; gespeichert werden nur angehakte
  Zeilen und nur, solange beide Felder noch leer sind.

Die Ableitung (`src/Modules/Kunden/Namensvorschlag.php`) ist mit Unit-Tests abgesichert:
`php tests/run.php` (läuft auch in der GitHub-Prüfung des Backoffice).

### Import rückgängig machen

**Kunden → Importe** listet alle Importe, aus denen noch Kunden da sind (Zeitpunkt, Art, Anzahl).
„Rückgängig machen …“ zeigt erst, was gelöscht würde, und löscht dann nur Kunden, die seit dem
Import **nicht verändert** wurden. Erhalten bleiben bearbeitete Kunden, Kunden mit später ergänzten
oder geänderten Ansprechpartnern, Kindern oder Einwilligungen (auch Newsletter-Schalter) und
Kunden mit Aufträgen, Buchungen, Rechnungen, Aufgaben, Orten oder Gutscheinen. Grundlage ist das
Änderungsprotokoll.

Die **Newsletter-Liste** (früher „CSV importieren“) lehnt Dateien mit den Spalten „Typ“ oder
„Organisation“ ab und verweist auf „Kontakte importieren“.

### Newsletter und E-Mails an mehrere Kunden

In **Kunden** zeigt die Spalte **Newsletter** Ja/Nein; ein Klick auf den Schalter speichert sofort.
Dahinter steht die Newsletter-Einwilligung: „Ja“ trägt sie mit dem heutigen Datum ein, „Nein“
setzt das Widerrufsdatum (die Einwilligung bleibt dokumentiert). Filter: Alle / Newsletter Ja /
Newsletter Nein.

- **E-Mail an Auswahl**: Kunden per Checkbox auswählen („Alle auswählen“ = aktuelle Seite, danach
  „Alle N gefilterten Kunden auswählen“). Öffnet das Mailprogramm mit allen Adressen im **BCC**.
- **Newsletter verschicken**: dasselbe mit allen Kunden mit Newsletter = Ja.
- Im „An“-Feld steht die eigene Adresse: die Einstellung `eigene_email` (Tabelle `einstellungen`,
  von Migration 004 mit `info@bricksnbytes.de` angelegt). Ist in der Konfiguration `app.eigene_email`
  bzw. im Docker-Container `APP_OWNER_EMAIL` gesetzt, hat das Vorrang. Ohne Adresse sind die beiden
  Buttons deaktiviert.
- Ungültige und doppelte Adressen werden weggelassen. Wird der Mail-Link zu lang (über 2.000 Zeichen),
  werden die Adressen stattdessen mit Semikolon in die Zwischenablage kopiert – in Outlook ins
  BCC-Feld einfügen. „Adressen kopieren“ geht immer.

## 3. Updates einspielen

**Vorher immer eine Datenbank-Sicherung anlegen** (siehe Abschnitt 4) – Migrationen ändern
Tabellen und lassen sich nicht automatisch rückgängig machen.

**Variante A:**
```bash
cd ~/ibs-repo && git pull && php backoffice/bin/migrate.php
```

**Variante B:** geänderte Dateien per SFTP hochladen (nie `config.php` überschreiben), dann
`php bin/migrate.php`.

`migrate.php` wendet nur neue Migrationen an und kann gefahrlos mehrfach laufen.

---

## 4. Backups

Kundendaten von Eltern und Kindern → Backups sind Pflicht und müssen selbst geschützt werden.

1. **Strato-Sicherungen:** Strato sichert Webspace und Datenbanken automatisch und bietet die
   Wiederherstellung im Kundenbereich an (Menü *Backup* / *Sicherungen*). Wie oft und wie lange
   gesichert wird, hängt vom Paket ab – bitte dort nachsehen.
2. **Eigene Sicherung (zusätzlich, z. B. monatlich):** per SSH

   ```bash
   mkdir -p ~/backups && chmod 700 ~/backups
   mysqldump --no-tablespaces -h DATENBANK-HOST -u DATENBANK-BENUTZER -p DATENBANKNAME \
     | gzip > ~/backups/ibs-$(date +%F).sql.gz
   ```

   Die Datei per SFTP herunterladen, **verschlüsselt** aufbewahren (z. B. verschlüsselter
   USB-Stick oder Passwort-Manager-Tresor) und danach auf dem Server löschen. Alte Sicherungen
   nach einer festen Frist (z. B. 6 Monate) vernichten.
3. **Wiederherstellen testen** – einmal im Jahr eine Sicherung in eine leere Test-Datenbank
   einspielen.

---

## 5. Datenschutz (DSGVO) – zu erledigen

- **AV-Vertrag mit Strato** im Kundenbereich abschließen (falls noch nicht geschehen) – deckt
  Webspace, Datenbank und E-Mail ab.
- **Verzeichnis von Verarbeitungstätigkeiten** (Art. 30 DSGVO): Eintrag „Kundendatenbank“ mit
  Zweck (Kursorganisation, Kommunikation), Datenarten (Kontaktdaten Eltern, Vorname und
  Geburtsdatum der Kinder, Newsletter-Kennzeichen), Speicherort (Strato), Löschfristen.
- **Löschkonzept:** Kunden ohne Buchung/Kontakt nach einer festen Frist löschen; Abgemeldete
  (`newsletter = 0`) nur behalten, solange es ein Kundenverhältnis gibt.
- **Newsletter-Versand:** Das Kennzeichen stammt aus der alten Liste. Für einen Versand
  muss eine nachweisbare Einwilligung (Double-Opt-in) vorliegen – 138 der importierten
  Adressen hatten die Anmeldung in der alten Liste nicht bestätigt.
- **Zugang:** nur persönliche Konten, starke Passwörter, nicht auf fremden Geräten anmelden.
- **Änderungsprotokoll:** Jede Änderung an Kunden, Ansprechpartnern, Kindern und Einwilligungen
  wird mit Benutzer, Zeit und alt → neu gespeichert (Tabelle `aenderungen`). Beim Löschen bleibt
  nur der Vermerk „gelöscht“ ohne Inhalte. Einträge werden nach 24 Monaten automatisch entfernt
  (Einstellung `protokoll_aufbewahrung_monate`).
- **Kinderdaten:** nur erfassen, was für die Kurse nötig ist (Vorname, Alter/Geburtsjahr,
  Abholberechtigte). Foto-/Video-Einwilligungen vor jeder Veröffentlichung prüfen.
- **Rechnungen (später):** Die Kurse sind umsatzsteuerfrei; der Hinweis auf der Rechnung ist in
  `einstellungen.steuerhinweis` vorbelegt („§ 4 Nr. 21 UStG“) – bitte mit der Steuerberatung
  abstimmen, welche Rechtsgrundlage und ggf. Bescheinigung gilt.

---

## 6. Lokale Entwicklung

```bash
docker run -d --name bbo-db -p 3307:3306 -e MARIADB_ROOT_PASSWORD=root \
  -e MARIADB_DATABASE=bbo -e MARIADB_USER=bbo -e MARIADB_PASSWORD=localpw mariadb:11
cp config.example.php config.php   # dsn: mysql:host=127.0.0.1;port=3307;dbname=bbo, debug true, secure_cookies false
php bin/setup.php
php -S 127.0.0.1:8099 -t public public/index.php
```

Nur erfundene Testdaten verwenden – keine echten Kundendaten auf Entwicklungsrechnern.

### Erster Admin im Docker-Container (servyy)

Der Entrypoint führt nach den Migrationen `bin/seed-admin.php` aus. Sind
`IBS_ADMIN_USER` und `IBS_ADMIN_PASSWORD` gesetzt **und** ist die Tabelle
`benutzer` noch leer, wird daraus der erste Admin (`rolle = admin`)
angelegt – sonst passiert nichts (Meldung `ignoring IBS_ADMIN_*`). Das
Passwort braucht 12–256 Zeichen und erscheint nie im Log; danach im
Backoffice unter dem Benutzernamen → **Passwort ändern** ersetzen und die
Variablen aus der Compose-Umgebung entfernen (Hygiene – technisch ignoriert
der Container sie ab dem ersten Benutzer ohnehin).

Neue Module (Aufträge, Kurse, Rechnungen …) bekommen einen Ordner unter `src/Modules/`,
Templates unter `templates/<modul>/` und eine Zeile `…Controller::register($router);` in
`public/index.php`; danach den Eintrag aus `Modules/Platzhalter/PlatzhalterController.php`
entfernen. Die Tabellen gibt es schon – Schemaänderungen als neue Migration `00X_….sql`.

### Datenmodell (Kurzüberblick)

| Bereich | Tabellen |
|---|---|
| Basis | `benutzer` (Rollen admin/büro/kursleiter), `login_versuche`, `aenderungen`, `einstellungen`, `aufgaben` |
| Kunden | `kunden` (Typ, Status), `ansprechpartner`, `kinder`, `einwilligungen` |
| Kurse | `kursarten`, `kursleiter`, `orte`, `kurse` (Kursreihe), `termine`, `buchungen`, `anwesenheiten` |
| Aufträge | `auftraege` (Status Anfrage → … → abgerechnet / abgesagt), `auftrag_positionen`, `geburtstage` |
| Rechnungen | `nummernkreise`, `rechnungen` (Empfänger als Kopie), `rechnung_positionen`, `zahlungen`, `mahnungen`, `gutscheine`, `gutschein_einloesungen` |
| Forecast | wird aus Aufträgen/Rechnungen berechnet; Wahrscheinlichkeiten je Status in `einstellungen` |

Beträge immer in Cent (`…_cent`).
