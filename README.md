# Signera

Enkel tjänst för digital signering av PDF:er (avtal, styrelseprotokoll m.m.) på **signera.frosteus.se**.

- Inloggade användare laddar upp en PDF och lägger till undertecknare (namn, e-post, personnummer).
- Varje undertecknare får en personlig länk via e-post. Hen läser dokumentet och bekräftar med de fyra sista siffrorna i sitt personnummer. Därefter kan hen signera eller avböja.
- När alla har signerat skapas en ny PDF: originalet plus ett **signeringsintyg** som sista sida. Den mejlas till alla undertecknare och till den som laddade upp.
- Om någon avböjer stoppas signeringen och uppladdaren meddelas.

Byggd i PHP 8.1+ och MySQL utan ramverk, för one.com:s webbhotell. Beroenden: PHPMailer, FPDI och TCPDF.

## Roller

| Roll | Kan |
|---|---|
| Admin | Allt, inklusive att se alla dokument och bjuda in eller inaktivera användare |
| Användare | Ladda upp dokument och följa sina egna dokument |

## Driftsättning på one.com

1. **Kontrollpanelen**
   - Skapa subdomänen `signera.frosteus.se` och aktivera SSL (Let's Encrypt).
   - Välj PHP 8.1 eller senare under *Avancerade inställningar → PHP*.
   - Skapa en MySQL-databas och notera värd, databasnamn, användare och lösenord.
   - Skapa e-postkontot `noreply@frosteus.se`.
2. **Bygg lokalt:** `php composer.phar install --no-dev --optimize-autoloader`
3. **Konfigurera:** kopiera `config/config.example.php` till `config/config.php` och fyll i databas, SMTP (`send.one.com`, port 465, `ssl`) och en lång slumpsträng i `install_token`.
4. **Ladda upp** hela mappen via SFTP till subdomänens katalog, inklusive `vendor/` och alla `.htaccess`-filer. Mappen `.git` behöver inte följa med.
5. **Installera:** öppna `https://signera.frosteus.se/install?token=<install_token>` och skapa första admin.
6. **Töm `install_token`** i `config/config.php`.
7. **Kontrollera skyddet:** följande ska ge *403 Forbidden*:
   - `https://signera.frosteus.se/config/config.php`
   - `https://signera.frosteus.se/storage/`
   - `https://signera.frosteus.se/vendor/autoload.php`

Mappen `storage/` måste vara skrivbar för PHP. Där sparas original, signerade PDF:er och felloggen (`storage/logs/php-error.log`). Ta regelbundet backup på `storage/` och databasen.

## Lokal utveckling

Kräver PHP 8.1+ och Docker. På Windows laddas `pdo_mysql` och `mbstring` med `-d` om de inte är aktiverade i php.ini.

```sh
docker compose up -d                       # MySQL på port 3307, Mailpit på http://localhost:8025
cp config/config.example.php config/config.php
# Ändra i config.php:
#   base_url    http://localhost:8000
#   force_https false
#   db          mysql:host=127.0.0.1;port=3307;dbname=signera  (användare/lösenord: signera/signera)
#   mail        localhost:1025, secure '', user ''
php composer.phar install
php -d extension=pdo_mysql -d extension=mbstring bin/install.php "Ditt Namn" du@exempel.se ettlangtlosenord
php -d extension=pdo_mysql -d extension=mbstring -S localhost:8000 router.php
```

Alla mejl hamnar i Mailpit på http://localhost:8025.

## Bra att veta

- **PDF-kompatibilitet:** gratisversionen av FPDI kan inte läsa PDF:er med komprimerade objektströmmar (vanligt från Word). Därför sparar webbläsaren om filen med pdf-lib före uppladdning. Servern provläser alltid filen och nekar den om den inte går att slutföra. Krypterade eller lösenordsskyddade PDF:er nekas.
- **Länkar:** varje länk är personlig. Bara en hash av den sparas. "Skicka ny länk" skapar en ny länk och den gamla slutar gälla.
- **Personnummer:** valideras med Luhn och sparas som `ÅÅÅÅMMDD-NNNN`. Ett tiosiffrigt nummer tolkas som födelseår inom de senaste 100 åren. Använd `+` (eller tolv siffror) för äldre personer. Intyget visar `ÅÅÅÅMMDD-XXXX` som standard, se `mask_personnummer_on_certificate`.
- **Bevisvärde:** signeringen bygger på e-postlänk plus kontrollsiffror, inte BankID. Intyget innehåller tidpunkt, IP-adress och SHA-256 av originalet. Händelseloggen finns på dokumentsidan.
- **Begränsningar:** det finns inga automatiska påminnelser (one.com saknar cron på de flesta planer) och ingen funktion för att radera dokument ännu.
