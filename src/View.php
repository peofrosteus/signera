<?php
declare(strict_types=1);

namespace Signera;

final class View
{
    /** Renderar en mall inuti templates/layout.php. */
    public static function render(string $template, array $data = []): void
    {
        $data += ['user' => self::currentUser()];
        $content = self::fetch($template, $data);
        echo self::fetch('layout', $data + [
            'content' => $content,
            'flash' => Session::takeFlash(),
        ]);
    }

    private static function currentUser(): ?array
    {
        try {
            return Auth::user();
        } catch (\Throwable) {
            return null; // t.ex. databasen nere när felsidan visas
        }
    }

    public static function fetch(string $template, array $data = []): string
    {
        $file = App::path('templates/' . $template . '.php');
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
