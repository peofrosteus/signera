<?php use Signera\Csrf; ?>
<section class="card narrow">
    <?php if (!$invitee): ?>
        <h1>Länken är inte giltig</h1>
        <p>Länken har gått ut eller redan använts. Be en administratör skicka en ny inbjudan, eller använd <a href="/password/forgot">Glömt lösenord</a>.</p>
    <?php else: ?>
        <h1>Välj lösenord</h1>
        <p class="muted">Hej <?= e($invitee['name']) ?>! Välj ett lösenord för <?= e($invitee['email']) ?>.</p>
        <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" action="/password/set/<?= e($token) ?>" class="stack">
            <?= Csrf::field() ?>
            <input type="hidden" name="username" value="<?= e($invitee['email']) ?>" autocomplete="username">
            <label>Nytt lösenord (minst 10 tecken)
                <input type="password" name="password" minlength="10" required autofocus autocomplete="new-password">
            </label>
            <label>Upprepa lösenordet
                <input type="password" name="password_confirm" minlength="10" required autocomplete="new-password">
            </label>
            <button class="btn btn-primary" type="submit">Spara och logga in</button>
        </form>
    <?php endif; ?>
</section>
