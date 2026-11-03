<?php
declare(strict_types=1);

namespace Signera;

final class Audit
{
    public const LABELS = [
        'created' => 'Dokument skapat',
        'sent' => 'Inbjudan skickad',
        'reminded' => 'Ny länk skickad',
        'viewed' => 'Dokumentet öppnat',
        'verify_failed' => 'Fel kontrollsiffror angavs',
        'signed' => 'Signerat',
        'declined' => 'Avböjt',
        'completed' => 'Alla har signerat, signerad PDF skapad',
        'cancelled' => 'Signeringen avbröts',
        'mail_failed' => 'Mejl kunde inte skickas',
    ];

    public static function log(string $event, ?int $documentId = null, ?int $signerId = null, ?string $detail = null): void
    {
        Db::insert('audit_log', [
            'document_id' => $documentId,
            'signer_id' => $signerId,
            'user_id' => Auth::user()['id'] ?? null,
            'event' => $event,
            'detail' => $detail !== null ? mb_substr($detail, 0, 255) : null,
            'ip' => Http::ip(),
            'created_at' => Db::now(),
        ]);
    }
}
