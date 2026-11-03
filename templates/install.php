<?php use Signera\Csrf; ?>
<section class="card narrow">
    <h1>Installation</h1>
    <p class="muted">Skapar databastabellerna och det första administratörskontot.</p>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" action="/install" class="stack">
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label>Namn
            <input name="name" value="<?= e($old['name']) ?>" required autofocus>
        </label>
        <label>E-post
            <input type="email" name="email" value="<?= e($old['email']) ?>" required autocomplete="username">
        </label>
        <label>Lösenord (minst 10 tecken)
            <input type="password" name="password" minlength="10" required autocomplete="new-password">
        </label>
        <button class="btn btn-primary" type="submit">Installera</button>
    </form>
</section>
