<?php
declare(strict_types=1);

namespace Signera;

final class Auth
{
    private const MAX_ATTEMPTS = 10;
    private const WINDOW_MINUTES = 15;

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            if (!empty($_SESSION['user_id'])) {
                self::$user = Db::one('SELECT * FROM users WHERE id = ? AND active = 1', [$_SESSION['user_id']]);
                if (self::$user === null) {
                    unset($_SESSION['user_id']);
                }
            }
        }
        return self::$user;
    }

    public static function require(): array
    {
        $user = self::user();
        if ($user === null) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '/';
            Http::redirect('/login');
        }
        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::require();
        if ($user['role'] !== 'admin') {
            throw new HttpError(403);
        }
        return $user;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === 'admin';
    }

    public static function attempt(string $email, string $password): bool
    {
        $since = date('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);
        $tries = Db::one(
            'SELECT COUNT(*) AS n FROM login_attempts WHERE (ip = ? OR email = ?) AND created_at > ?',
            [Http::ip(), $email, $since]
        );
        if ((int) $tries['n'] >= self::MAX_ATTEMPTS) {
            throw new HttpError(429);
        }

        $user = Db::one('SELECT * FROM users WHERE email = ? AND active = 1', [$email]);
        if ($user === null || $user['password_hash'] === null || !password_verify($password, $user['password_hash'])) {
            Db::insert('login_attempts', ['ip' => Http::ip(), 'email' => $email, 'created_at' => Db::now()]);
            return false;
        }

        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        Db::run('UPDATE users SET last_login_at = ? WHERE id = ?', [Db::now(), $user['id']]);
        Db::run('DELETE FROM login_attempts WHERE email = ?', [$user['email']]);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        Session::regenerate();
        self::$user = null;
    }
}
