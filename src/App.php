<?php
declare(strict_types=1);

namespace Signera;

use Signera\Controllers\AuthController;
use Signera\Controllers\DocumentController;
use Signera\Controllers\InstallController;
use Signera\Controllers\SignController;
use Signera\Controllers\UserController;

final class App
{
    public static string $root;
    private static array $config = [];

    private const ROUTES = [
        ['GET', '#^/$#', [DocumentController::class, 'index']],
        ['GET', '#^/login$#', [AuthController::class, 'loginForm']],
        ['POST', '#^/login$#', [AuthController::class, 'login']],
        ['POST', '#^/logout$#', [AuthController::class, 'logout']],
        ['GET', '#^/password/forgot$#', [AuthController::class, 'forgotForm']],
        ['POST', '#^/password/forgot$#', [AuthController::class, 'forgot']],
        ['GET', '#^/password/set/(?<token>[A-Za-z0-9_-]{20,})$#', [AuthController::class, 'setPasswordForm']],
        ['POST', '#^/password/set/(?<token>[A-Za-z0-9_-]{20,})$#', [AuthController::class, 'setPassword']],

        ['GET', '#^/users$#', [UserController::class, 'index']],
        ['POST', '#^/users$#', [UserController::class, 'invite']],
        ['POST', '#^/users/(?<id>\d+)/reinvite$#', [UserController::class, 'reinvite']],
        ['POST', '#^/users/(?<id>\d+)/toggle$#', [UserController::class, 'toggle']],

        ['GET', '#^/documents/new$#', [DocumentController::class, 'create']],
        ['POST', '#^/documents$#', [DocumentController::class, 'store']],
        ['GET', '#^/documents/(?<id>\d+)$#', [DocumentController::class, 'show']],
        ['GET', '#^/documents/(?<id>\d+)/original$#', [DocumentController::class, 'original']],
        ['GET', '#^/documents/(?<id>\d+)/signed$#', [DocumentController::class, 'signed']],
        ['POST', '#^/documents/(?<id>\d+)/remind/(?<signer>\d+)$#', [DocumentController::class, 'remind']],
        ['POST', '#^/documents/(?<id>\d+)/cancel$#', [DocumentController::class, 'cancel']],

        ['GET', '#^/s/(?<token>[A-Za-z0-9_-]{20,})$#', [SignController::class, 'show']],
        ['POST', '#^/s/(?<token>[A-Za-z0-9_-]{20,})$#', [SignController::class, 'act']],
        ['GET', '#^/s/(?<token>[A-Za-z0-9_-]{20,})/pdf$#', [SignController::class, 'pdf']],
        ['GET', '#^/s/(?<token>[A-Za-z0-9_-]{20,})/signed$#', [SignController::class, 'signed']],

        ['GET', '#^/install$#', [InstallController::class, 'form']],
        ['POST', '#^/install$#', [InstallController::class, 'install']],
    ];

    public static function boot(string $root): void
    {
        self::$root = $root;
        $file = $root . '/config/config.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('config/config.php saknas. Kopiera config/config.example.php och fyll i.');
        }
        self::$config = require $file;

        date_default_timezone_set((string) self::config('timezone', 'Europe/Stockholm'));
        error_reporting(E_ALL);
        ini_set('display_errors', self::config('debug') ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $root . '/storage/logs/php-error.log');
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        $value = self::$config;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function run(): void
    {
        if (self::config('force_https') && !Http::isHttps()) {
            Http::redirect('https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'));
        }
        Http::securityHeaders();
        Session::start();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        try {
            if ($method === 'POST') {
                Csrf::verify();
            }
            foreach (self::ROUTES as [$routeMethod, $pattern, $handler]) {
                if ($routeMethod === $method && preg_match($pattern, $path, $m)) {
                    $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                    $handler($params);
                    return;
                }
            }
            throw new HttpError(404);
        } catch (HttpError $e) {
            http_response_code($e->getCode());
            View::render('error', ['status' => $e->getCode(), 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            error_log((string) $e);
            http_response_code(500);
            View::render('error', [
                'status' => 500,
                'message' => self::config('debug') ? $e->getMessage() : 'Något gick fel. Försök igen senare.',
            ]);
        }
    }

    public static function url(string $path = ''): string
    {
        return rtrim((string) self::config('base_url'), '/') . '/' . ltrim($path, '/');
    }

    public static function path(string $relative): string
    {
        return self::$root . '/' . ltrim($relative, '/');
    }
}
