# Signera

En liten, självhostad tjänst för att signera PDF-dokument digitalt, till exempel avtal och styrelseprotokoll. Undertecknarna behöver inget konto och ingen BankID, bara en e-postadress.

## Så fungerar det

1. En inloggad användare laddar upp en PDF och lägger till undertecknare med namn, e-post och personnummer.
2. Varje undertecknare får ett mejl med en personlig länk. På sidan läser hen dokumentet och bekräftar med de fyra sista siffrorna i sitt personnummer. Sedan väljer hen att **signera** eller **avböja**.
3. När alla har signerat skapas en ny PDF med originaldokumentet och ett **signeringsintyg** som sista sida. Intyget innehåller namn, maskerat personnummer, tidpunkt, IP-adress och originalets SHA-256. Den nya PDF:en mejlas till alla undertecknare och till den som laddade upp dokumentet.
4. Om någon avböjer stoppas signeringen och uppladdaren meddelas.

Varje dokument har en händelselogg. Uppladdaren kan skicka en ny länk till en undertecknare eller avbryta signeringen.

Gränssnitt och mejl är på svenska.

### Roller

| Roll | Kan |
|---|---|
| **Admin** | Se alla dokument, bjuda in, aktivera och inaktivera användare |
| **Användare** | Ladda upp dokument och följa sina egna dokument |

Det finns ingen öppen registrering. Den första admin skapas vid installationen och nya användare bjuds in via mejl.

## Krav

- **PHP 8.1 eller senare** med tilläggen `pdo_mysql`, `mbstring`, `openssl` och `zlib`
- **MySQL 5.7+ eller MariaDB 10.3+**
- **Webbserver:** Apache med `mod_rewrite` (`.htaccess` medföljer) eller nginx (se konfigurationen nedan)
- **SMTP-konto** för utgående mejl, eller en server där PHP:s `mail()` fungerar
- **HTTPS:** rekommenderas starkt, eftersom länkarna ger åtkomst till dokumenten
- **[Composer](https://getcomposer.org/)** för att installera beroendena (PHPMailer, FPDI och TCPDF)

Det krävs ingen Node, inga köer och inga cron-jobb. Det räcker med vanligt delat webbhotell.

## Installation

### 1. Hämta koden och beroendena

```sh
git clone <repo-url> signera
cd signera
composer install --no-dev --optimize-autoloader
```

Saknar servern Composer och SSH kör du `composer install` lokalt och laddar sedan upp hela mappen, inklusive `vendor/`, via SFTP.

### 2. Konfigurera

```sh
cp config/config.example.php config/config.php
```

Fyll i `config/config.php`:

| Inställning | Beskrivning |
|---|---|
| `base_url` | Fullständig adress utan avslutande `/`, t.ex. `https://signera.example.com`. Används i länkar i mejl. |
| `db.dsn`, `db.user`, `db.pass` | Databasanslutning, t.ex. `mysql:host=localhost;dbname=signera;charset=utf8mb4`. Databasen måste finnas, men tabellerna skapas av installationen. |
| `mail.*` | SMTP-värd, port, kryptering (`ssl`/`tls`/`''`), användare, lösenord och avsändare. Med `transport` satt till `'mail'` används PHP:s `mail()` i stället. |
| `install_token` | En lång slumpsträng med bara bokstäver och siffror, minst 16 tecken. Aktiverar webbinstallationen. |
| `force_https` | `true` i produktion. |
| `mask_personnummer_on_certificate` | `true` visar `ÅÅÅÅMMDD-XXXX` på intyget, `false` visar hela numret. |
| `max_upload_mb`, `invite_valid_hours` | Maxstorlek för uppladdade PDF:er och hur länge en inbjudningslänk gäller. |

`config/config.php` checkas inte in i git.

### 3. Webbservern

Webbroten ska peka på **projektets rotmapp**. Alla anrop går till `index.php`, utom statiska filer under `/assets/`. Mapparna `config/`, `src/`, `storage/`, `templates/`, `migrations/`, `bin/` och `vendor/` får inte kunna nås via webben.

**Apache:** den medföljande `.htaccess` sköter omdirigering, HTTPS-tvång och spärrar. `AllowOverride All` (eller minst `FileInfo` och `AuthConfig`) måste vara tillåtet för katalogen.

**nginx**, exempel:

```nginx
server {
    server_name signera.example.com;
    root /var/www/signera;
    index index.php;
    client_max_body_size 30M;

    location ^~ /assets/ { try_files $uri =404; }
    location ~ ^/(config|src|storage|templates|migrations|bin|vendor)(/|$) { deny all; }
    location ~ /\. { deny all; }

    location / {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

Mappen `storage/` (och dess undermappar) måste vara **skrivbar** för PHP. Där sparas original, signerade PDF:er och felloggen.

Se också till att PHP tillåter tillräckligt stora uppladdningar. `upload_max_filesize` och `post_max_size` bör vara något större än `max_upload_mb`.

### 4. Skapa databastabellerna och första admin

**Via webbläsaren**, t.ex. på webbhotell utan SSH:

1. Öppna `https://<din-domän>/install?token=<install_token>`.
2. Fyll i namn, e-post och lösenord. Kontot blir admin och du loggas in.
3. **Töm `install_token`** i `config/config.php`.

Installationssidan fungerar bara medan inga användare finns. Om databasanslutningen misslyckas visas felet direkt på sidan.

**Via kommandoraden:**

```sh
php bin/install.php "Ditt Namn" du@example.com "ett-långt-lösenord"
```

### 5. Kontrollera

- Följande ska ge **403** eller **404**, aldrig innehåll:
  - `https://<din-domän>/config/config.php`
  - `https://<din-domän>/storage/`
  - `https://<din-domän>/vendor/autoload.php`
- Bjud in en användare under **Användare**. Om mejlet inte kan skickas visas SMTP-felet i meddelandet.
- Gör en provsignering med dig själv som undertecknare.

Ta regelbundet backup av databasen och av mappen `storage/`.

## Uppdatering

```sh
git pull
composer install --no-dev --optimize-autoloader
```

Nya databasändringar läggs som filer i `migrations/`. Kör dem med `php bin/install.php`, som bara kör migreringar som inte redan körts och inte skapar någon ny admin om användare redan finns.

## Lokal utveckling

Kräver PHP 8.1+, Composer och Docker. `docker-compose.yml` startar MySQL på port 3307 och [Mailpit](https://mailpit.axllent.org/), som fångar alla mejl och visar dem på http://localhost:8025.

```sh
docker compose up -d
composer install
cp config/config.example.php config/config.php
```

Ändra följande i `config/config.php`:

| Inställning | Värde |
|---|---|
| `base_url` | `http://localhost:8000` |
| `force_https` | `false` |
| `debug` | `true` |
| `db.dsn` | `mysql:host=127.0.0.1;port=3307;dbname=signera;charset=utf8mb4` |
| `db.user` och `db.pass` | `signera` och `signera` |
| `mail.host` | `localhost` |
| `mail.port` | `1025` |
| `mail.secure` och `mail.user` | tomma (`''`) |

Starta sedan:

```sh
php bin/install.php "Test Admin" admin@example.com "testlosenord123"
php -S localhost:8000 router.php
```

`router.php` ersätter `.htaccess` för PHP:s inbyggda server. Är `pdo_mysql` eller `mbstring` inte aktiverade i din php.ini kan du ladda dem per körning med `php -d extension=pdo_mysql -d extension=mbstring …`.

## Projektstruktur

```
index.php              Front controller
assets/                CSS, JS, pdf.js och pdf-lib (lokalt, inget CDN)
src/                   Applikationskod (namespace Signera\)
  App.php              Routing och uppstart
  Documents.php        Dokumentens livscykel: skapa, signera, avböja, slutföra
  Pdf/Certificate.php  Läser originalet och skapar signeringsintyget
  Controllers/         Inloggning, användare, dokument, signering, installation
templates/             Sidor och mejlmallar (templates/mail/)
migrations/            SQL-migreringar
bin/install.php        Installation och migrering från kommandoraden
storage/               Uppladdade och signerade PDF:er, loggar (ej i git)
```

## Säkerhet och integritet

- **Signeringslänkar** är slumpade 256-bitarstoken. Bara en hash sparas i databasen. "Skicka ny länk" gör den gamla länken ogiltig.
- **PDF:er** serveras bara via PHP, efter kontroll av inloggning eller länk.
- **Skydd i applikationen:** CSRF-skydd på alla formulär, begränsat antal inloggningsförsök, begränsat antal felaktiga kontrollsiffror per undertecknare, säkra sessionscookies och en strikt Content Security Policy.
- **Personnummer** valideras med Luhn-algoritmen, inklusive samordningsnummer, och visas maskerat i gränssnittet. Tänk på att personnummer är personuppgifter. Den som driver tjänsten ansvarar för att hanteringen följer GDPR, till exempel när det gäller hur länge dokument sparas.
- **Bevisvärde:** signeringen bygger på e-postlänk plus kontrollsiffror, inte på e-legitimation. Intyget och händelseloggen visar vem som signerade, när och varifrån. Behöver ni starkare identifiering, till exempel för avtal med höga krav, krävs BankID eller motsvarande.

## Bra att veta

- **PDF-kompatibilitet:** gratisversionen av FPDI kan inte läsa PDF:er med komprimerade objektströmmar, vilket är vanligt från bland annat Word. Webbläsaren sparar därför om filen med pdf-lib innan den laddas upp. Servern provläser alltid filen och nekar den om den inte går att slutföra. Krypterade eller lösenordsskyddade PDF:er nekas.
- **Tiosiffriga personnummer** tolkas som födelseår inom de senaste 100 åren. Ange `+` eller tolv siffror för personer som är äldre än så.
- **Begränsningar:** det finns inga automatiska påminnelser (det skulle kräva cron), ingen funktion för att radera dokument och ingen knapp för att byta roll på en befintlig användare.
