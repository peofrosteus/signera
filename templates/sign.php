<?php use Signera\Csrf; ?>
<div class="page-head">
    <div>
        <h1><?= e($document['title']) ?></h1>
        <p class="muted small">Från <?= e($creator['name']) ?> · <?= (int) $document['page_count'] ?> sidor</p>
    </div>
</div>

<?php if ($document['status'] === 'completed'): ?>
    <section class="card center">
        <h2>Dokumentet är signerat av alla</h2>
        <p>Den signerade PDF:en har skickats till din e-post. Du kan även ladda ner den här.</p>
        <p><a class="btn btn-primary" href="/s/<?= e($token) ?>/signed">Ladda ner signerad PDF</a></p>
    </section>
<?php elseif ($signer['status'] === 'signed'): ?>
    <section class="card center">
        <h2>Tack, du har signerat</h2>
        <p>Du signerade <?= e(format_dt($signer['signed_at'])) ?>. När alla har signerat får du den signerade PDF:en via e-post.</p>
    </section>
<?php elseif ($signer['status'] === 'declined'): ?>
    <section class="card center">
        <h2>Du har avböjt att signera</h2>
        <p>Avsändaren har meddelats.</p>
    </section>
<?php elseif ($document['status'] === 'declined'): ?>
    <section class="card center">
        <h2>Signeringen är stoppad</h2>
        <p>En av undertecknarna har avböjt, så dokumentet kan inte längre signeras. Kontakta <?= e($creator['name']) ?> vid frågor.</p>
    </section>
<?php elseif ($document['status'] === 'cancelled'): ?>
    <section class="card center">
        <h2>Signeringen är avbruten</h2>
        <p>Avsändaren har avbrutit signeringen av dokumentet.</p>
    </section>
<?php endif; ?>

<?php if ($document['message'] && $document['status'] === 'pending' && $signer['status'] === 'pending'): ?>
    <section class="card">
        <p class="muted small">Meddelande från <?= e($creator['name']) ?>:</p>
        <p class="prewrap"><?= e($document['message']) ?></p>
    </section>
<?php endif; ?>

<section class="card viewer-card">
    <div class="viewer-bar">
        <span class="muted small" id="viewer-status">Laddar dokumentet…</span>
        <a class="btn btn-small" href="/s/<?= e($token) ?>/pdf" target="_blank" rel="noopener">Öppna PDF</a>
    </div>
    <div id="pdf-viewer" data-src="/s/<?= e($token) ?>/pdf"></div>
</section>

<?php if ($document['status'] === 'pending' && $signer['status'] === 'pending'): ?>
    <section class="card stack" id="sign-panel">
        <h2>Signera som <?= e($signer['name']) ?></h2>
        <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" action="/s/<?= e($token) ?>" class="stack">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="sign">
            <label class="check">
                <input type="checkbox" name="read" value="1" required>
                Jag har läst dokumentet och godkänner att signera det elektroniskt.
            </label>
            <label>Bekräfta med de fyra sista siffrorna i ditt personnummer
                <input name="last4" inputmode="numeric" pattern="\d{4}" maxlength="4" required autocomplete="off" class="short">
            </label>
            <button class="btn btn-primary btn-large" type="submit">Signera</button>
        </form>

        <details class="decline">
            <summary>Jag vill inte signera</summary>
            <form method="post" action="/s/<?= e($token) ?>" class="stack"
                  data-confirm="Är du säker på att du vill avböja? Signeringen stoppas för alla.">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="decline">
                <label>Anledning <span class="muted">(valfritt, skickas till avsändaren)</span>
                    <textarea name="reason" rows="3" maxlength="1000"></textarea>
                </label>
                <button class="btn btn-danger" type="submit">Avböj att signera</button>
            </form>
        </details>
    </section>
<?php endif; ?>

<script src="/assets/vendor/pdf.min.js"></script>
<script src="/assets/viewer.js?v=1"></script>
<script src="/assets/confirm.js?v=1"></script>
