<?php use Signera\View; ?>
<p><b><?= e($signer['name']) ?></b> (<?= e($signer['email']) ?>) har avböjt att signera <b><?= e($document['title']) ?></b>.</p>
<?php if ($reason !== ''): ?>
<p style="border-left:3px solid #d0d7de;padding-left:12px;color:#3e4c59;white-space:pre-wrap;"><?= e($reason) ?></p>
<?php endif; ?>
<p>Signeringen är stoppad. Om dokumentet ska signeras behöver du ladda upp det på nytt.</p>
<?= View::fetch('mail/_button', ['href' => $link, 'label' => 'Visa dokumentet']) ?>
