<?php
declare(strict_types=1);

namespace Signera\Controllers;

use Signera\App;
use Signera\Auth;
use Signera\Db;
use Signera\Documents;
use Signera\Http;
use Signera\HttpError;
use Signera\Pdf\Certificate;
use Signera\Personnummer;
use Signera\Session;
use Signera\View;

final class DocumentController
{
    public static function index(): void
    {
        $user = Auth::require();
        $sql = "SELECT d.*, u.name AS creator_name,
                       (SELECT COUNT(*) FROM signers s WHERE s.document_id = d.id) AS signer_count,
                       (SELECT COUNT(*) FROM signers s WHERE s.document_id = d.id AND s.status = 'signed') AS signed_count
                FROM documents d JOIN users u ON u.id = d.created_by";
        $documents = Auth::isAdmin()
            ? Db::all($sql . ' ORDER BY d.created_at DESC')
            : Db::all($sql . ' WHERE d.created_by = ? ORDER BY d.created_at DESC', [$user['id']]);
        View::render('dashboard', ['title' => 'Dokument', 'documents' => $documents]);
    }

    public static function create(): void
    {
        Auth::require();
        View::render('document_new', [
            'title' => 'Nytt dokument',
            'errors' => [],
            'old' => ['title' => '', 'message' => '', 'signers' => [['name' => '', 'email' => '', 'personnummer' => '']]],
        ]);
    }

    public static function store(): void
    {
        $user = Auth::require();
        $title = mb_substr(Http::post('title'), 0, 255);
        $message = mb_substr(Http::post('message'), 0, 2000);
        $errors = [];

        $signers = [];
        $emails = [];
        foreach ((array) ($_POST['signers'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $email = mb_strtolower(trim((string) ($row['email'] ?? '')));
            $pnrInput = trim((string) ($row['personnummer'] ?? ''));
            if ($name === '' && $email === '' && $pnrInput === '') {
                continue;
            }
            $label = $name !== '' ? $name : 'Undertecknare ' . (count($signers) + 1);
            $pnr = Personnummer::normalize($pnrInput);
            if ($name === '') {
                $errors[] = "$label: namn saknas.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "$label: ogiltig e-postadress.";
            } elseif (in_array($email, $emails, true)) {
                $errors[] = "$label: e-postadressen förekommer flera gånger.";
            }
            if ($pnr === null) {
                $errors[] = "$label: ogiltigt personnummer.";
            }
            $emails[] = $email;
            $signers[] = ['name' => mb_substr($name, 0, 190), 'email' => $email, 'personnummer' => $pnr ?? $pnrInput];
        }
        if (!$signers) {
            $errors[] = 'Lägg till minst en undertecknare.';
        }

        $file = $_FILES['pdf'] ?? null;
        $maxBytes = (int) App::config('max_upload_mb', 20) * 1024 * 1024;
        $pageCount = 0;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Välj en PDF-fil.';
        } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > $maxBytes) {
            $errors[] = 'Filen är för stor (max ' . App::config('max_upload_mb', 20) . ' MB).';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Uppladdningen misslyckades. Försök igen.';
        } elseif (!self::looksLikePdf($file['tmp_name'])) {
            $errors[] = 'Filen är inte en PDF.';
        } else {
            try {
                $pageCount = Certificate::pageCount($file['tmp_name']);
            } catch (\Throwable $e) {
                error_log('PDF-provläsning: ' . $e->getMessage());
                $errors[] = 'PDF:en kunde inte läsas (den kan vara krypterad eller lösenordsskyddad). '
                    . 'Prova att skriva ut den till en ny PDF och ladda upp igen.';
            }
        }

        $originalName = is_array($file) ? basename((string) ($file['name'] ?? 'dokument.pdf')) : 'dokument.pdf';
        if ($title === '') {
            $title = preg_replace('/\.pdf$/i', '', $originalName) ?: 'Dokument';
        }

        if ($errors) {
            if (Http::wantsJson()) {
                Http::json(['errors' => $errors], 422);
            }
            View::render('document_new', [
                'title' => 'Nytt dokument',
                'errors' => $errors,
                'old' => ['title' => Http::post('title'), 'message' => $message, 'signers' => $signers ?: [['name' => '', 'email' => '', 'personnummer' => '']]],
            ]);
            return;
        }

        $id = Documents::create($user, $title, $message, $originalName, $file['tmp_name'], $pageCount, $signers);
        $failed = Db::one("SELECT COUNT(*) AS n FROM audit_log WHERE document_id = ? AND event = 'mail_failed'", [$id]);
        (int) $failed['n'] > 0
            ? Session::flash('error', 'Dokumentet skapades, men något mejl kunde inte skickas. Använd "Skicka ny länk" för att försöka igen.')
            : Session::flash('success', 'Dokumentet är skickat för signering.');

        if (Http::wantsJson()) {
            Http::json(['redirect' => '/documents/' . $id]);
        }
        Http::redirect('/documents/' . $id);
    }

    public static function show(array $p): void
    {
        $document = self::authorized((int) $p['id']);
        View::render('document_show', [
            'title' => $document['title'],
            'document' => $document,
            'creator' => Documents::creator($document),
            'signers' => Documents::signers((int) $document['id']),
            'log' => Db::all(
                'SELECT a.*, s.name AS signer_name, u.name AS user_name FROM audit_log a
                 LEFT JOIN signers s ON s.id = a.signer_id LEFT JOIN users u ON u.id = a.user_id
                 WHERE a.document_id = ? ORDER BY a.id DESC',
                [$document['id']]
            ),
        ]);
    }

    public static function original(array $p): void
    {
        $document = self::authorized((int) $p['id']);
        Http::sendPdf(Documents::originalPath($document), Documents::downloadName($document, false), isset($_GET['inline']));
    }

    public static function signed(array $p): void
    {
        $document = self::authorized((int) $p['id']);
        if ($document['status'] !== 'completed') {
            throw new HttpError(404);
        }
        Http::sendPdf(Documents::signedPath($document), Documents::downloadName($document, true));
    }

    public static function remind(array $p): void
    {
        $document = self::authorized((int) $p['id']);
        $signer = Db::one(
            "SELECT * FROM signers WHERE id = ? AND document_id = ? AND status = 'pending'",
            [(int) $p['signer'], $document['id']]
        ) ?? throw new HttpError(404);
        if ($document['status'] !== 'pending') {
            throw new HttpError(400, 'Dokumentet är inte längre öppet för signering.');
        }
        Documents::sendInvite($document, $signer, 'reminded')
            ? Session::flash('success', 'En ny länk har skickats till ' . $signer['email'] . '. Den tidigare länken slutar gälla.')
            : Session::flash('error', 'Mejlet kunde inte skickas till ' . $signer['email'] . '.');
        Http::redirect('/documents/' . $document['id']);
    }

    public static function cancel(array $p): void
    {
        $document = self::authorized((int) $p['id']);
        Documents::cancel($document);
        Session::flash('success', 'Signeringen är avbruten och undertecknarna har meddelats.');
        Http::redirect('/documents/' . $document['id']);
    }

    private static function authorized(int $id): array
    {
        $user = Auth::require();
        $document = Documents::find($id) ?? throw new HttpError(404);
        if (!Auth::isAdmin() && (int) $document['created_by'] !== (int) $user['id']) {
            throw new HttpError(403);
        }
        return $document;
    }

    private static function looksLikePdf(string $path): bool
    {
        $handle = fopen($path, 'rb');
        $head = $handle ? (string) fread($handle, 1024) : '';
        if ($handle) {
            fclose($handle);
        }
        return str_contains($head, '%PDF-');
    }
}
