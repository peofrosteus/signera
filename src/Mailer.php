<?php
declare(strict_types=1);

namespace Signera;

use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    /** Senaste felet från send(), för att kunna visa orsaken för inloggade användare. */
    public static ?string $lastError = null;

    /**
     * Skickar ett mejl renderat från templates/mail/{template}.php.
     * Returnerar false (och loggar) om det inte gick, så att flödet kan fortsätta.
     *
     * @param array<string, string> $attachments filnamn => sökväg
     */
    public static function send(string $toEmail, string $toName, string $subject, string $template, array $data = [], array $attachments = []): bool
    {
        $html = View::fetch('mail/layout', [
            'subject' => $subject,
            'body' => View::fetch('mail/' . $template, $data),
        ]);

        self::$lastError = null;
        $mail = new PHPMailer(true);
        try {
            if (App::config('mail.transport', 'smtp') === 'mail') {
                $mail->isMail();
            } else {
                $mail->isSMTP();
                $mail->Host = (string) App::config('mail.host');
                $mail->Port = (int) App::config('mail.port');
                $mail->Timeout = 20;
                $secure = (string) App::config('mail.secure');
                $mail->SMTPSecure = $secure;
                $mail->SMTPAutoTLS = $secure !== '';
                if ((string) App::config('mail.user') !== '') {
                    $mail->SMTPAuth = true;
                    $mail->Username = (string) App::config('mail.user');
                    $mail->Password = (string) App::config('mail.pass');
                }
            }
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom((string) App::config('mail.from'), (string) App::config('mail.from_name', 'Signera'));
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->isHTML();
            $mail->Body = $html;
            $mail->AltBody = self::toText($html);
            foreach ($attachments as $name => $path) {
                $mail->addAttachment($path, $name, PHPMailer::ENCODING_BASE64, 'application/pdf');
            }
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            self::$lastError = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();
            error_log('Mailer: kunde inte skicka till ' . $toEmail . ': ' . $mail->ErrorInfo . ' ' . $e->getMessage());
            return false;
        }
    }

    private static function toText(string $html): string
    {
        $html = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2: $1', $html);
        $html = preg_replace('#<(br|/p|/h\d|/tr|/li)[^>]*>#i', "\n", (string) $html);
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8');
        return trim((string) preg_replace("/\n\s*\n\s*\n+/", "\n\n", $text));
    }
}
