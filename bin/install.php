<?php
// Installation från kommandoraden: php bin/install.php "Namn" e-post@exempel.se lösenord
// Kör databasmigreringar och skapar första admin (hoppar över admin om användare redan finns).
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';

use Signera\App;
use Signera\Controllers\InstallController;
use Signera\Db;

App::boot(dirname(__DIR__));

$ran = Db::migrate();
echo $ran ? 'Körde migreringar: ' . implode(', ', $ran) . PHP_EOL : 'Databasen är redan uppdaterad.' . PHP_EOL;

if (InstallController::hasUsers()) {
    echo 'Det finns redan användare, ingen admin skapades.' . PHP_EOL;
    exit(0);
}

[, $name, $email, $password] = $argv + [null, '', '', ''];
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 10) {
    fwrite(STDERR, 'Användning: php bin/install.php "Namn" e-post lösenord (minst 10 tecken)' . PHP_EOL);
    exit(1);
}
InstallController::createAdmin($name, mb_strtolower($email), $password);
echo "Admin skapad: $email" . PHP_EOL;
