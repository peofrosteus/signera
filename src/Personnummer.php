<?php
declare(strict_types=1);

namespace Signera;

/** Validering och normalisering av svenska personnummer (inkl. samordningsnummer). */
final class Personnummer
{
    /** Returnerar ÅÅÅÅMMDD-NNNN eller null om numret är ogiltigt. */
    public static function normalize(string $input): ?string
    {
        $input = preg_replace('/\s+/', '', $input);
        if (!preg_match('/^(\d{2})?(\d{2})(\d{2})(\d{2})([-+]?)(\d{4})$/', $input, $m)) {
            return null;
        }
        [, $century, $yy, $mm, $dd, $sep, $last4] = $m;

        if ($century === '') {
            $year = (int) date('Y');
            $full = (int) (substr((string) $year, 0, 2) . $yy);
            if ($full > $year) {
                $full -= 100;
            }
            if ($sep === '+') {
                $full -= 100;
            }
            $century = substr((string) $full, 0, 2);
        }

        $day = (int) $dd > 60 ? (int) $dd - 60 : (int) $dd;
        if (!checkdate((int) $mm, $day, (int) ($century . $yy))) {
            return null;
        }
        if (!self::luhn($yy . $mm . $dd . $last4)) {
            return null;
        }
        return $century . $yy . $mm . $dd . '-' . $last4;
    }

    public static function mask(string $normalized): string
    {
        return substr($normalized, 0, 9) . 'XXXX';
    }

    public static function lastFour(string $normalized): string
    {
        return substr($normalized, -4);
    }

    private static function luhn(string $digits): bool
    {
        $sum = 0;
        foreach (str_split($digits) as $i => $digit) {
            $n = (int) $digit * ($i % 2 === 0 ? 2 : 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }
        return $sum % 10 === 0;
    }
}
