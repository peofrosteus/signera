<?php
declare(strict_types=1);

namespace Signera\Controllers;

use Signera\Audit;
use Signera\Db;
use Signera\Documents;
use Signera\Http;
use Signera\HttpError;
use Signera\Personnummer;
use Signera\View;

/** Publika sidor för undertecknare. Åtkomst sker enbart via den unika länken. */
final class SignController
{
    private const MAX_VERIFY_FAILS = 5;

    public static function show(array $p): void
    {
        [$signer, $document] = self::resolve($p['token']);
        if ($signer['viewed_at'] === null) {
            Db::run('UPDATE signers SET viewed_at = ? WHERE id = ?', [Db::now(), $signer['id']]);
            Audit::log('viewed', (int) $document['id'], (int) $signer['id']);
        }
        self::render($signer, $document, $p['token'], []);
    }

    public static function act(array $p): void
    {
        [$signer, $document] = self::resolve($p['token']);
        if ($document['status'] !== 'pending' || $signer['status'] !== 'pending') {
            Http::redirect('/s/' . $p['token']);
        }

        $action = Http::post('action');
        $errors = [];
        if ($action === 'sign') {
            $since = date('Y-m-d H:i:s', time() - 3600);
            $fails = Db::one(
                "SELECT COUNT(*) AS n FROM audit_log WHERE signer_id = ? AND event = 'verify_failed' AND created_at > ?",
                [$signer['id'], $since]
            );
            if ((int) $fails['n'] >= self::MAX_VERIFY_FAILS) {
                throw new HttpError(429, 'För många felaktiga försök. Vänta en timme och försök igen.');
            }
            if (Http::post('read') !== '1') {
                $errors[] = 'Bekräfta att du har läst dokumentet.';
            }
            $digits = preg_replace('/\D/', '', Http::post('last4'));
            if (!hash_equals(Personnummer::lastFour($signer['personnummer']), (string) $digits)) {
                $errors[] = 'De fyra sista siffrorna stämmer inte med personnumret som angavs för dig.';
                Audit::log('verify_failed', (int) $document['id'], (int) $signer['id']);
            }
            if (!$errors) {
                Documents::sign($signer);
                Http::redirect('/s/' . $p['token']);
            }
        } elseif ($action === 'decline') {
            Documents::decline($signer, mb_substr(Http::post('reason'), 0, 1000));
            Http::redirect('/s/' . $p['token']);
        } else {
            throw new HttpError(400);
        }

        self::render($signer, $document, $p['token'], $errors);
    }

    public static function pdf(array $p): void
    {
        [, $document] = self::resolve($p['token']);
        Http::sendPdf(Documents::originalPath($document), Documents::downloadName($document, false), true);
    }

    public static function signed(array $p): void
    {
        [, $document] = self::resolve($p['token']);
        if ($document['status'] !== 'completed') {
            throw new HttpError(404);
        }
        Http::sendPdf(Documents::signedPath($document), Documents::downloadName($document, true));
    }

    private static function render(array $signer, array $document, string $token, array $errors): void
    {
        View::render('sign', [
            'title' => $document['title'],
            'signer' => $signer,
            'document' => $document,
            'creator' => Documents::creator($document),
            'token' => $token,
            'errors' => $errors,
        ]);
    }

    /** @return array{0: array, 1: array} */
    private static function resolve(string $token): array
    {
        return Documents::byToken($token)
            ?? throw new HttpError(404, 'Länken är ogiltig eller har ersatts av en nyare länk. Kontrollera att du använder den senaste länken i din e-post.');
    }
}
