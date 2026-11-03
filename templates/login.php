<?php use Signera\Csrf; ?>
<section class="card narrow">
    <h1>Logga in</h1>
    <form method="post" action="/login" class="stack">
        <?= Csrf::field() ?>
        <label>E-post
            <input type="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username">
        </label>
        <label>Lösenord
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn btn-primary" type="submit">Logga in</button>
    </form>
    <p class="muted small"><a href="/password/forgot">Glömt lösenord?</a></p>
</section>
