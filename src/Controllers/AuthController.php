<?php
declare(strict_types=1);

namespace Signera\Controllers;

use Signera\App;
use Signera\Auth;
use Signera\Db;
use Signera\Http;
use Signera\Mailer;
use Signera\Session;
use Signera\Token;
use Signera\View;

final class AuthController
{
    private const MIN_PASSWORD = 10;

    public static function loginForm(): void
    {
        if (Auth::user()) {
            Http::redirect('/');
        }
        View::render('login', ['title' => 'Logga in', 'email' => '']);
    }

    public static function login(): void
    {
        $email = mb_strtolower(Http::post('email'));
        if (Auth::attempt($email, (string) ($_POST['password'] ?? ''))) {
            $to = $_SESSION['intended'] ?? '/';
            unset($_SESSION['intended']);
            Http::redirect(str_starts_with($to, '/') && !str_starts_with($to, '//') ? $to : '/');
        }
        Session::flash('error', 'Fel e-post eller lösenord.');
        View::render('login', ['title' => 'Logga in', 'email' => $email]);
    }

    public static function logout(): void
    {
        Auth::logout();
        Http::redirect('/login');
    }

    public static function forgotForm(): void
    {
        View::render('forgot', ['title' => 'Glömt lösenord']);
    }

    public static function forgot(): void
    {
        $email = mb_strtolower(Http::post('email'));
        $user = Db::one('SELECT * FROM users WHERE email = ? AND active = 1', [$email]);
        if ($user !== null) {
            $link = self::issuePasswordToken($user);
            Mailer::send($user['email'], $user['name'], 'Återställ ditt lösenord', 'reset', ['user' => $user, 'link' => $link]);
        }
        Session::flash('success', 'Om adressen finns registrerad har vi skickat en länk för att välja nytt lösenord.');
        Http::redirect('/login');
    }

    public static function setPasswordForm(array $p): void
    {
        $user = self::userByToken($p['token']);
        View::render('set_password', ['title' => 'Välj lösenord', 'invitee' => $user, 'token' => $p['token'], 'errors' => []]);
    }

    public static function setPassword(array $p): void
    {
        $user = self::userByToken($p['token']);
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];
        if (mb_strlen($password) < self::MIN_PASSWORD) {
            $errors[] = 'Lösenordet måste vara minst ' . self::MIN_PASSWORD . ' tecken.';
        }
        if ($user !== null && $password !== (string) ($_POST['password_confirm'] ?? '')) {
            $errors[] = 'Lösenorden matchar inte.';
        }
        if ($user === null || $errors) {
            View::render('set_password', ['title' => 'Välj lösenord', 'invitee' => $user, 'token' => $p['token'], 'errors' => $errors]);
            return;
        }

        Db::run(
            'UPDATE users SET password_hash = ?, token_hash = NULL, token_expires = NULL WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $user['id']]
        );
        Auth::login($user);
        Session::flash('success', 'Ditt lösenord är sparat. Välkommen!');
        Http::redirect('/');
    }

    /** Skapar en engångslänk för att sätta lösenord. Används för både inbjudan och återställning. */
    public static function issuePasswordToken(array $user): string
    {
        [$token, $hash] = Token::generate();
        $hours = (int) App::config('invite_valid_hours', 72);
        Db::run(
            'UPDATE users SET token_hash = ?, token_expires = ? WHERE id = ?',
            [$hash, date('Y-m-d H:i:s', time() + $hours * 3600), $user['id']]
        );
        return App::url('password/set/' . $token);
    }

    private static function userByToken(string $token): ?array
    {
        return Db::one(
            'SELECT * FROM users WHERE token_hash = ? AND token_expires > ? AND active = 1',
            [Token::hash($token), Db::now()]
        );
    }
}
