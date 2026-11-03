<div class="page-head">
    <h1>Dokument</h1>
    <a class="btn btn-primary" href="/documents/new">+ Nytt dokument</a>
</div>

<?php if (!$documents): ?>
    <section class="card center">
        <p>Inga dokument ännu.</p>
        <p><a class="btn btn-primary" href="/documents/new">Ladda upp ditt första dokument</a></p>
    </section>
<?php else: ?>
    <section class="card">
        <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Dokument</th><th>Status</th><th>Signerat</th><th>Skapat</th><th>Av</th></tr></thead>
            <tbody>
            <?php foreach ($documents as $d): ?>
                <tr>
                    <td><a href="/documents/<?= (int) $d['id'] ?>"><?= e($d['title']) ?></a></td>
                    <td><?= status_badge($d['status']) ?></td>
                    <td><?= (int) $d['signed_count'] ?> av <?= (int) $d['signer_count'] ?></td>
                    <td><?= e(format_dt($d['created_at'])) ?></td>
                    <td><?= e($d['creator_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </section>
<?php endif; ?>
