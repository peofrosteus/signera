<?php
// Kopiera till config/config.php och fyll i. config.php checkas inte in.
return [
    'app_name' => 'Signera',
    // Utan avslutande snedstreck. Används i länkar i mejl.
    'base_url' => 'https://signera.frosteus.se',
    'timezone' => 'Europe/Stockholm',
    'force_https' => true,
    'debug' => false,

    'db' => [
        // one.com: värd, databasnamn och användare finns i kontrollpanelen under "Databas".
        'dsn' => 'mysql:host=localhost;dbname=signera;charset=utf8mb4',
        'user' => '',
        'pass' => '',
    ],

    'mail' => [
        // 'smtp' (rekommenderas) eller 'mail' för PHP:s mail()-funktion om SMTP inte fungerar från servern.
        'transport' => 'smtp',
        // one.com: send.one.com, port 465 med ssl. Lokalt med Mailpit: localhost, 1025, secure ''.
        'host' => 'send.one.com',
        'port' => 465,
        'secure' => 'ssl', // 'ssl', 'tls' eller ''
        'user' => 'noreply@frosteus.se', // tomt = ingen inloggning (Mailpit)
        'pass' => '',
        'from' => 'noreply@frosteus.se',
        'from_name' => 'Signera',
    ],

    // Sätt en lång slumpsträng för att aktivera /install?token=... vid första installationen.
    // Töm värdet igen när första admin är skapad.
    'install_token' => '',

    // Visa personnummer som ÅÅÅÅMMDD-XXXX på signeringsintyget (true) eller i sin helhet (false).
    'mask_personnummer_on_certificate' => true,

    'max_upload_mb' => 20,
    'invite_valid_hours' => 72,
];
