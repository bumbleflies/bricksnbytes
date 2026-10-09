# Imperiales Sicherheitsbüro (Backoffice)

Internes Backoffice für BricksnBytes. PHP 8.3+ und MySQL/MariaDB, ohne Frameworks oder
Abhängigkeiten – läuft auf normalem Strato-Webspace.

**Stand Phase 1:** Login, Dashboard (Kundenzahlen; Umsatz/Forecast/Termine als Platzhalter),
globale Suche, **Kunden** (Eltern, Schulen, Horte, Kitas, Vereine, Firmen, Sonstige) mit
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
1. Anmelden → *Kunden* → **CSV importieren** → Datei wählen → **Datei prüfen**.
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

Neue Module (Aufträge, Kurse, Rechnungen …) bekommen einen Ordner unter `src/Modules/`,
Templates unter `templates/<modul>/` und eine Zeile `…Controller::register($router);` in
`public/index.php`; danach den Eintrag aus `Modules/Platzhalter/PlatzhalterController.php`
entfernen. Die Tabellen gibt es schon – Schemaänderungen als neue Migration `00X_….sql`.

### Datenmodell (Kurzüberblick)

| Bereich | Tabellen |
|---|---|
| Basis | `benutzer` (Rollen admin/büro/kursleiter), `login_versuche`, `aenderungen`, `einstellungen`, `aufgaben` |
| Kunden | `kunden` (Typ), `ansprechpartner`, `kinder`, `einwilligungen` |
| Kurse | `kursarten`, `kursleiter`, `orte`, `kurse` (Kursreihe), `termine`, `buchungen`, `anwesenheiten` |
| Aufträge | `auftraege` (Status Anfrage → … → abgerechnet / abgesagt), `auftrag_positionen`, `geburtstage` |
| Rechnungen | `nummernkreise`, `rechnungen` (Empfänger als Kopie), `rechnung_positionen`, `zahlungen`, `mahnungen`, `gutscheine`, `gutschein_einloesungen` |
| Forecast | wird aus Aufträgen/Rechnungen berechnet; Wahrscheinlichkeiten je Status in `einstellungen` |

Beträge immer in Cent (`…_cent`).
