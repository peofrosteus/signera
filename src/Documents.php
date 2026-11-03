<?php
declare(strict_types=1);

namespace Signera;

use Signera\Pdf\Certificate;

/** Dokumentens livscykel: skapa, skicka länkar, signera, avböja, slutföra. */
final class Documents
{
    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM documents WHERE id = ?', [$id]);
    }

    public static function signers(int $documentId): array
    {
        return Db::all('SELECT * FROM signers WHERE document_id = ? ORDER BY position', [$documentId]);
    }

    public static function creator(array $document): array
    {
        return Db::one('SELECT id, name, email FROM users WHERE id = ?', [$document['created_by']]) ?? ['id' => 0, 'name' => 'Okänd', 'email' => ''];
    }

    public static function originalPath(array $document): string
    {
        return App::path('storage/originals/' . $document['stored_path']);
    }

    public static function signedPath(array $document): string
    {
        return App::path('storage/signed/' . $document['signed_path']);
    }

    /**
     * @param array<int, array{name: string, email: string, personnummer: string}> $signers
     */
    public static function create(array $user, string $title, string $message, string $originalName, string $tmpFile, int $pageCount, array $signers): int
    {
        $stored = bin2hex(random_bytes(16)) . '.pdf';
        $target = App::path('storage/originals/' . $stored);
        $moved = is_uploaded_file($tmpFile) ? move_uploaded_file($tmpFile, $target) : rename($tmpFile, $target);
        if (!$moved) {
            throw new \RuntimeException('Kunde inte spara filen.');
        }

        $documentId = Db::transaction(function () use ($user, $title, $message, $originalName, $stored, $target, $pageCount, $signers) {
            $documentId = Db::insert('documents', [
                'public_id' => strtoupper(bin2hex(random_bytes(8))),
                'title' => $title,
                'message' => $message !== '' ? $message : null,
                'original_filename' => $originalName,
                'stored_path' => $stored,
                'sha256' => hash_file('sha256', $target),
                'page_count' => $pageCount,
                'status' => 'pending',
                'created_by' => $user['id'],
                'created_at' => Db::now(),
            ]);
            foreach (array_values($signers) as $i => $s) {
                Db::insert('signers', [
                    'document_id' => $documentId,
                    'position' => $i + 1,
                    'name' => $s['name'],
                    'email' => $s['email'],
                    'personnummer' => $s['personnummer'],
                    // Ersätts med riktig token när inbjudan skickas.
                    'token_hash' => Token::generate()[1],
                ]);
            }
            Audit::log('created', $documentId, null, $title);
            return $documentId;
        });

        $document = self::find($documentId);
        foreach (self::signers($documentId) as $signer) {
            self::sendInvite($document, $signer, 'sent');
        }
        return $documentId;
    }

    /** Skapar en ny unik länk (gamla slutar gälla) och mejlar den till undertecknaren. */
    public static function sendInvite(array $document, array $signer, string $event): bool
    {
        [$token, $hash] = Token::generate();
        Db::run('UPDATE signers SET token_hash = ?, last_sent_at = ? WHERE id = ?', [$hash, Db::now(), $signer['id']]);

        $creator = self::creator($document);
        $ok = Mailer::send(
            $signer['email'],
            $signer['name'],
            ($event === 'reminded' ? 'Påminnelse: ' : '') . 'Signera ' . $document['title'],
            'sign_request',
            [
                'signer' => $signer,
                'document' => $document,
                'creator' => $creator,
                'link' => App::url('s/' . $token),
                'reminder' => $event === 'reminded',
            ]
        );
        Audit::log($ok ? $event : 'mail_failed', (int) $document['id'], (int) $signer['id'], $signer['email']);
        return $ok;
    }

    /** @return array{0: array, 1: array}|null [signer, document] för en giltig signeringslänk. */
    public static function byToken(string $token): ?array
    {
        $signer = Db::one('SELECT * FROM signers WHERE token_hash = ?', [Token::hash($token)]);
        if ($signer === null) {
            return null;
        }
        return [$signer, self::find((int) $signer['document_id'])];
    }

    public static function sign(array $signer): void
    {
        $updated = Db::run(
            "UPDATE signers SET status = 'signed', signed_at = ?, ip = ?, user_agent = ?
             WHERE id = ? AND status = 'pending'
               AND EXISTS (SELECT 1 FROM documents d WHERE d.id = signers.document_id AND d.status = 'pending')",
            [Db::now(), Http::ip(), Http::userAgent(), $signer['id']]
        )->rowCount();
        if ($updated === 0) {
            return;
        }
        Audit::log('signed', (int) $signer['document_id'], (int) $signer['id'], $signer['name']);
        self::completeIfReady((int) $signer['document_id']);
    }

    public static function decline(array $signer, string $reason): void
    {
        $documentId = (int) $signer['document_id'];
        $changed = Db::transaction(function () use ($signer, $reason, $documentId) {
            $doc = Db::one('SELECT status FROM documents WHERE id = ? FOR UPDATE', [$documentId]);
            if ($doc['status'] !== 'pending') {
                return false;
            }
            Db::run(
                "UPDATE signers SET status = 'declined', declined_at = ?, decline_reason = ?, ip = ?, user_agent = ? WHERE id = ? AND status = 'pending'",
                [Db::now(), $reason !== '' ? $reason : null, Http::ip(), Http::userAgent(), $signer['id']]
            );
            Db::run("UPDATE documents SET status = 'declined' WHERE id = ?", [$documentId]);
            return true;
        });
        if (!$changed) {
            return;
        }
        Audit::log('declined', $documentId, (int) $signer['id'], $reason !== '' ? $reason : null);

        $document = self::find($documentId);
        $creator = self::creator($document);
        Mailer::send($creator['email'], $creator['name'], 'Avböjt: ' . $document['title'], 'declined', [
            'document' => $document,
            'signer' => $signer,
            'reason' => $reason,
            'link' => App::url('documents/' . $documentId),
        ]);
    }

    public static function cancel(array $document): void
    {
        $updated = Db::run("UPDATE documents SET status = 'cancelled' WHERE id = ? AND status = 'pending'", [$document['id']])->rowCount();
        if ($updated === 0) {
            return;
        }
        Audit::log('cancelled', (int) $document['id']);
        foreach (self::signers((int) $document['id']) as $signer) {
            Mailer::send($signer['email'], $signer['name'], 'Avbrutet: ' . $document['title'], 'cancelled', [
                'document' => $document,
                'signer' => $signer,
            ]);
        }
    }

    private static function completeIfReady(int $documentId): void
    {
        $completed = Db::transaction(function () use ($documentId) {
            $document = Db::one('SELECT * FROM documents WHERE id = ? FOR UPDATE', [$documentId]);
            $left = Db::one("SELECT COUNT(*) AS n FROM signers WHERE document_id = ? AND status <> 'signed'", [$documentId]);
            if ($document['status'] !== 'pending' || (int) $left['n'] > 0) {
                return false;
            }

            $document['completed_at'] = Db::now();
            $signedName = bin2hex(random_bytes(16)) . '.pdf';
            $target = App::path('storage/signed/' . $signedName);
            Certificate::build($document, self::signers($documentId), self::creator($document), self::originalPath($document), $target);

            Db::run(
                "UPDATE documents SET status = 'completed', completed_at = ?, signed_path = ?, signed_sha256 = ? WHERE id = ?",
                [$document['completed_at'], $signedName, hash_file('sha256', $target), $documentId]
            );
            return true;
        });
        if (!$completed) {
            return;
        }
        Audit::log('completed', $documentId);

        $document = self::find($documentId);
        $creator = self::creator($document);
        $attachment = [self::downloadName($document, true) => self::signedPath($document)];
        $recipients = array_map(fn ($s) => ['email' => $s['email'], 'name' => $s['name']], self::signers($documentId));
        if (!in_array(strtolower($creator['email']), array_map(fn ($r) => strtolower($r['email']), $recipients), true)) {
            $recipients[] = ['email' => $creator['email'], 'name' => $creator['name']];
        }
        foreach ($recipients as $r) {
            $ok = Mailer::send($r['email'], $r['name'], 'Signerat: ' . $document['title'], 'completed', [
                'document' => $document,
                'name' => $r['name'],
            ], $attachment);
            if (!$ok) {
                Audit::log('mail_failed', $documentId, null, $r['email']);
            }
        }
    }

    public static function downloadName(array $document, bool $signed): string
    {
        $base = preg_replace('/\.pdf$/i', '', $document['original_filename']);
        return $base . ($signed ? ' (signerad)' : '') . '.pdf';
    }
}
