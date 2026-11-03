<?php
declare(strict_types=1);

namespace Signera;

final class Http
{
    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    public static function securityHeaders(): void
    {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data: blob:; worker-src 'self' blob:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('X-Frame-Options: DENY');
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    public static function redirect(string $to): never
    {
        header('Location: ' . $to, true, 303);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function wantsJson(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    }

    public static function post(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    public static function ip(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    }

    public static function userAgent(): string
    {
        return mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public static function sendPdf(string $file, string $downloadName, bool $inline = false): never
    {
        if (!is_file($file)) {
            throw new HttpError(404);
        }
        $safe = preg_replace('/[^\w.\- åäöÅÄÖ]+/u', '_', $downloadName) ?: 'dokument.pdf';
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($file));
        header(sprintf(
            "Content-Disposition: %s; filename=\"%s\"; filename*=UTF-8''%s",
            $inline ? 'inline' : 'attachment',
            preg_replace('/[^\x20-\x7e]/', '_', $safe),
            rawurlencode($safe)
        ));
        header('Cache-Control: private, no-store');
        readfile($file);
        exit;
    }
}
