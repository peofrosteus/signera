<?php
declare(strict_types=1);

namespace Signera\Controllers;

use Signera\Auth;
use Signera\Db;
use Signera\HttpError;
use Signera\Http;
use Signera\Mailer;
use Signera\Session;
use Signera\View;

final class UserController
{
    public static function index(): void
    {
        Auth::requireAdmin();
        View::render('users', [
            'title' => 'Användare',
            'users' => Db::all('SELECT * FROM users ORDER BY active DESC, name'),
            'old' => ['name' => '', 'email' => '', 'role' => 'uploader'],
        ]);
    }

    public static function invite(): void
    {
        $admin = Auth::requireAdmin();
        $name = Http::post('name');
        $email = mb_strtolower(Http::post('email'));
        $role = Http::post('role') === 'admin' ? 'admin' : 'uploader';

        $error = null;
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ange namn och en giltig e-postadress.';
        } elseif (Db::one('SELECT id FROM users WHERE email = ?', [$email])) {
            $error = 'Det finns redan en användare med den e-postadressen.';
        }
        if ($error !== null) {
            Session::flash('error', $error);
            View::render('users', [
                'title' => 'Användare',
                'users' => Db::all('SELECT * FROM users ORDER BY active DESC, name'),
                'old' => compact('name', 'email', 'role'),
            ]);
            return;
        }

        $id = Db::insert('users', ['name' => $name, 'email' => $email, 'role' => $role, 'created_at' => Db::now()]);
        self::sendInvite(Db::one('SELECT * FROM users WHERE id = ?', [$id]), $admin);
        Http::redirect('/users');
    }

    public static function reinvite(array $p): void
    {
        $admin = Auth::requireAdmin();
        $user = Db::one('SELECT * FROM users WHERE id = ? AND active = 1', [(int) $p['id']]) ?? throw new HttpError(404);
        self::sendInvite($user, $admin);
        Http::redirect('/users');
    }

    public static function toggle(array $p): void
    {
        $admin = Auth::requireAdmin();
        if ((int) $p['id'] === (int) $admin['id']) {
            Session::flash('error', 'Du kan inte inaktivera dig själv.');
            Http::redirect('/users');
        }
        Db::run('UPDATE users SET active = 1 - active WHERE id = ?', [(int) $p['id']]);
        Http::redirect('/users');
    }

    private static function sendInvite(array $user, array $admin): void
    {
        $link = AuthController::issuePasswordToken($user);
        $ok = Mailer::send($user['email'], $user['name'], 'Du är inbjuden till Signera', 'invite', [
            'user' => $user,
            'admin' => $admin,
            'link' => $link,
        ]);
        $ok
            ? Session::flash('success', 'Inbjudan skickad till ' . $user['email'] . '.')
            : Session::flash('error', 'Mejlet kunde inte skickas. Kontrollera e-postinställningarna och försök igen.');
    }
}
