<?php use Signera\Csrf; ?>
<section class="card narrow">
    <h1>Glömt lösenord</h1>
    <p class="muted">Ange din e-postadress så skickar vi en länk där du kan välja ett nytt lösenord.</p>
    <form method="post" action="/password/forgot" class="stack">
        <?= Csrf::field() ?>
        <label>E-post
            <input type="email" name="email" required autofocus>
        </label>
        <button class="btn btn-primary" type="submit">Skicka länk</button>
    </form>
    <p class="muted small"><a href="/login">Tillbaka till inloggningen</a></p>
</section>
