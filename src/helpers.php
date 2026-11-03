<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_dt(?string $datetime): string
{
    return $datetime ? date('Y-m-d H:i', strtotime($datetime)) : '–';
}

function status_label(string $status): string
{
    return [
        'pending' => 'Väntar',
        'signed' => 'Signerat',
        'completed' => 'Klart',
        'declined' => 'Avböjt',
        'cancelled' => 'Avbrutet',
    ][$status] ?? $status;
}

function status_badge(string $status): string
{
    return '<span class="badge badge-' . e($status) . '">' . e(status_label($status)) . '</span>';
}
