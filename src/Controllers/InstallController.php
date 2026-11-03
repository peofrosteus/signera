<?php
declare(strict_types=1);

namespace Signera\Controllers;

use Signera\App;
use Signera\Auth;
use Signera\Db;
use Signera\Http;
use Signera\HttpError;
use Signera\Session;
use Signera\View;

/**
 * Engångsinstallation via webben (one.com saknar ofta SSH).
 * Aktiv endast när install_token är satt i config och ingen användare finns.
 */
final class InstallController
{
    public static function form(): void
    {
        $token = self::guard();
        View::render('install', ['title' => 'Installation', 'token' => $token, 'errors' => [], 'old' => ['name' => '', 'email' => '']]);
    }

    public static function install(): void
    {
        $token = self::guard();
        $name = Http::post('name');
        $email = mb_strtolower(Http::post('email'));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ange namn och en giltig e-postadress.';
        }
        if (mb_strlen($password) < 10) {
            $errors[] = 'Lösenordet måste vara minst 10 tecken.';
        }
        if ($errors) {
            View::render('install', ['title' => 'Installation', 'token' => $token, 'errors' => $errors, 'old' => compact('name', 'email')]);
            return;
        }

        try {
            $id = self::createAdmin($name, $email, $password);
        } catch (\PDOException $e) {
            // Sidan är skyddad av install_token, så det är ok att visa databasfelet här.
            error_log('Installation: ' . $e->getMessage());
            View::render('install', [
                'title' => 'Installation',
                'token' => $token,
                'errors' => ['Databasfel: ' . $e->getMessage() . ' Kontrollera db-inställningarna i config/config.php.'],
                'old' => compact('name', 'email'),
            ]);
            return;
        }
        Auth::login(Db::one('SELECT * FROM users WHERE id = ?', [$id]));
        Session::flash('success', 'Installationen är klar. Ta bort install_token från config/config.php.');
        Http::redirect('/');
    }

    public static function createAdmin(string $name, string $email, string $password): int
    {
        Db::migrate();
        return Db::insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => Db::now(),
        ]);
    }

    public static function hasUsers(): bool
    {
        try {
            return (int) Db::one('SELECT COUNT(*) AS n FROM users')['n'] > 0;
        } catch (\PDOException) {
            return false; // tabellen finns inte än
        }
    }

    private static function guard(): string
    {
        $expected = (string) App::config('install_token', '');
        $given = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
        if (strlen($expected) < 16 || !hash_equals($expected, $given)) {
            throw new HttpError(404);
        }
        if (self::hasUsers()) {
            throw new HttpError(403, 'Installationen är redan gjord. Ta bort install_token från config/config.php.');
        }
        return $given;
    }
}
