<?php use Signera\Csrf; ?>
<div class="page-head">
    <h1>Användare</h1>
</div>

<section class="card">
    <h2>Bjud in användare</h2>
    <p class="muted small">Personen får ett mejl med en länk för att välja lösenord. Länken gäller i 72 timmar.</p>
    <form method="post" action="/users" class="grid-form">
        <?= Csrf::field() ?>
        <label>Namn<input name="name" value="<?= e($old['name']) ?>" required></label>
        <label>E-post<input type="email" name="email" value="<?= e($old['email']) ?>" required></label>
        <label>Roll
            <select name="role">
                <option value="uploader" <?= $old['role'] === 'uploader' ? 'selected' : '' ?>>Användare – laddar upp dokument</option>
                <option value="admin" <?= $old['role'] === 'admin' ? 'selected' : '' ?>>Admin – ser alla dokument, hanterar användare</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Skicka inbjudan</button>
    </form>
</section>

<section class="card">
    <div class="table-scroll">
    <table class="table">
        <thead><tr><th>Namn</th><th>E-post</th><th>Roll</th><th>Status</th><th>Senast inloggad</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr class="<?= $u['active'] ? '' : 'inactive' ?>">
                <td><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= $u['role'] === 'admin' ? 'Admin' : 'Användare' ?></td>
                <td>
                    <?php if (!$u['active']): ?>Inaktiv
                    <?php elseif ($u['password_hash'] === null): ?>Inbjuden
                    <?php else: ?>Aktiv<?php endif; ?>
                </td>
                <td><?= e(format_dt($u['last_login_at'])) ?></td>
                <td class="actions">
                    <?php if ($u['active']): ?>
                        <form method="post" action="/users/<?= (int) $u['id'] ?>/reinvite" class="inline">
                            <?= Csrf::field() ?>
                            <button class="btn btn-small" type="submit"><?= $u['password_hash'] === null ? 'Skicka igen' : 'Skicka lösenordslänk' ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                        <form method="post" action="/users/<?= (int) $u['id'] ?>/toggle" class="inline">
                            <?= Csrf::field() ?>
                            <button class="btn btn-small" type="submit"><?= $u['active'] ? 'Inaktivera' : 'Aktivera' ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
