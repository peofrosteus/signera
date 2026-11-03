<?php
declare(strict_types=1);

namespace Signera;

final class Token
{
    /** @return array{0: string, 1: string} [klartext-token, sha256-hash] */
    public static function generate(): array
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        return [$token, self::hash($token)];
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
