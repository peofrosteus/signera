<?php use Signera\App; use Signera\Csrf; ?>
<div class="page-head">
    <h1>Nytt dokument</h1>
</div>

<div id="form-errors">
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
</div>

<form method="post" action="/documents" enctype="multipart/form-data" id="document-form" class="stack"
      data-max-mb="<?= (int) App::config('max_upload_mb', 20) ?>">
    <?= Csrf::field() ?>

    <section class="card stack">
        <h2>1. Dokument</h2>
        <label>PDF-fil (max <?= (int) App::config('max_upload_mb', 20) ?> MB)
            <input type="file" name="pdf" accept="application/pdf,.pdf" required>
        </label>
        <label>Titel <span class="muted">(visas i mejl och på intyget, lämna tomt för filnamnet)</span>
            <input name="title" value="<?= e($old['title']) ?>" maxlength="255">
        </label>
        <label>Meddelande till undertecknarna <span class="muted">(valfritt)</span>
            <textarea name="message" rows="3" maxlength="2000"><?= e($old['message']) ?></textarea>
        </label>
    </section>

    <section class="card stack">
        <h2>2. Undertecknare</h2>
        <div id="signers" class="stack">
            <?php foreach ($old['signers'] as $i => $s): ?>
                <div class="signer-row">
                    <label>Namn<input name="signers[<?= $i ?>][name]" value="<?= e($s['name']) ?>" required autocomplete="off"></label>
                    <label>E-post<input type="email" name="signers[<?= $i ?>][email]" value="<?= e($s['email']) ?>" required autocomplete="off"></label>
                    <label>Personnummer<input name="signers[<?= $i ?>][personnummer]" value="<?= e($s['personnummer']) ?>" placeholder="ÅÅÅÅMMDD-XXXX" required autocomplete="off" inputmode="numeric"></label>
                    <button type="button" class="btn btn-small remove-signer" aria-label="Ta bort undertecknare">✕</button>
                </div>
            <?php endforeach; ?>
        </div>
        <div><button type="button" class="btn" id="add-signer">+ Lägg till undertecknare</button></div>
    </section>

    <div class="form-actions">
        <a class="btn" href="/">Avbryt</a>
        <button class="btn btn-primary" type="submit" id="submit-btn">Skicka för signering</button>
    </div>
</form>

<script src="/assets/vendor/pdf-lib.min.js"></script>
<script src="/assets/upload.js?v=1"></script>
