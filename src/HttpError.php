<?php
declare(strict_types=1);

namespace Signera;

final class HttpError extends \RuntimeException
{
    private const MESSAGES = [
        400 => 'Felaktig förfrågan.',
        403 => 'Du har inte behörighet till den här sidan.',
        404 => 'Sidan kunde inte hittas.',
        419 => 'Sidan har gått ut. Gå tillbaka, ladda om och försök igen.',
        429 => 'För många försök. Vänta en stund och försök igen.',
    ];

    public function __construct(int $status, ?string $message = null)
    {
        parent::__construct($message ?? self::MESSAGES[$status] ?? 'Ett fel uppstod.', $status);
    }
}
