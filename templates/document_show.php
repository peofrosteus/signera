<?php use Signera\Audit; use Signera\Csrf; use Signera\Personnummer; ?>
<div class="page-head">
    <div>
        <h1><?= e($document['title']) ?></h1>
        <p class="muted small">
            <?= status_badge($document['status']) ?>
            Skickat <?= e(format_dt($document['created_at'])) ?> av <?= e($creator['name']) ?>
            · <?= (int) $document['page_count'] ?> sidor · ID <?= e($document['public_id']) ?>
        </p>
    </div>
    <div class="actions">
        <a class="btn" href="/documents/<?= (int) $document['id'] ?>/original">Ladda ner original</a>
        <?php if ($document['status'] === 'completed'): ?>
            <a class="btn btn-primary" href="/documents/<?= (int) $document['id'] ?>/signed">Ladda ner signerad PDF</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($document['message']): ?>
    <section class="card"><p class="prewrap"><?= e($document['message']) ?></p></section>
<?php endif; ?>

<section class="card">
    <h2>Undertecknare</h2>
    <div class="table-scroll">
    <table class="table">
        <thead><tr><th>Namn</th><th>E-post</th><th>Personnummer</th><th>Status</th><th>Tidpunkt</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($signers as $s): ?>
            <tr>
                <td><?= e($s['name']) ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e(Personnummer::mask($s['personnummer'])) ?></td>
                <td>
                    <?= status_badge($s['status']) ?>
                    <?php if ($s['status'] === 'pending' && $s['viewed_at']): ?><span class="muted small">öppnat</span><?php endif; ?>
                    <?php if ($s['decline_reason']): ?><div class="small prewrap">"<?= e($s['decline_reason']) ?>"</div><?php endif; ?>
                </td>
                <td><?= e(format_dt($s['signed_at'] ?? $s['declined_at'] ?? null)) ?></td>
                <td class="actions">
                    <?php if ($document['status'] === 'pending' && $s['status'] === 'pending'): ?>
                        <form method="post" action="/documents/<?= (int) $document['id'] ?>/remind/<?= (int) $s['id'] ?>" class="inline">
                            <?= Csrf::field() ?>
                            <button class="btn btn-small" type="submit" title="Skickar en ny länk, den gamla slutar gälla">Skicka ny länk</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if ($document['status'] === 'pending'): ?>
        <form method="post" action="/documents/<?= (int) $document['id'] ?>/cancel" class="cancel-form"
              data-confirm="Avbryta signeringen? Alla länkar slutar gälla och undertecknarna meddelas.">
            <?= Csrf::field() ?>
            <button class="btn btn-danger btn-small" type="submit">Avbryt signeringen</button>
        </form>
    <?php endif; ?>
</section>

<?php if ($document['status'] === 'completed'): ?>
    <section class="card small">
        <p><b>SHA-256 original:</b> <code><?= e($document['sha256']) ?></code></p>
        <p><b>SHA-256 signerad:</b> <code><?= e($document['signed_sha256']) ?></code></p>
    </section>
<?php endif; ?>

<section class="card">
    <h2>Händelselogg</h2>
    <ul class="log">
        <?php foreach ($log as $l): ?>
            <li>
                <span class="muted"><?= e(date('Y-m-d H:i:s', strtotime($l['created_at']))) ?></span>
                <?= e(Audit::LABELS[$l['event']] ?? $l['event']) ?>
                <?php if ($l['signer_name']): ?>– <?= e($l['signer_name']) ?><?php elseif ($l['user_name']): ?>– <?= e($l['user_name']) ?><?php endif; ?>
                <?php if ($l['detail'] && $l['event'] !== 'signed'): ?><span class="muted">(<?= e($l['detail']) ?>)</span><?php endif; ?>
                <span class="muted small">IP <?= e($l['ip']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<script src="/assets/confirm.js?v=1"></script>
