<?php use Signera\App; use Signera\Csrf; ?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e(($title ?? '') !== '' ? $title . ' – ' : '') ?><?= e(App::config('app_name', 'Signera')) ?></title>
    <link rel="stylesheet" href="/assets/app.css?v=1">
    <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
</head>
<body>
<header class="topbar">
    <div class="wrap topbar-inner">
        <a class="brand" href="<?= $user ? '/' : '#' ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            <?= e(App::config('app_name', 'Signera')) ?>
        </a>
        <?php if ($user): ?>
            <nav class="nav">
                <a href="/">Dokument</a>
                <a href="/documents/new">Nytt dokument</a>
                <?php if ($user['role'] === 'admin'): ?><a href="/users">Användare</a><?php endif; ?>
                <form method="post" action="/logout" class="inline">
                    <?= Csrf::field() ?>
                    <button class="link" type="submit" title="<?= e($user['email']) ?>">Logga ut</button>
                </form>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="wrap">
    <?php foreach ($flash as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
</body>
</html>
