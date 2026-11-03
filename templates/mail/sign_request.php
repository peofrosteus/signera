<?php use Signera\View; ?>
<p>Hej <?= e($signer['name']) ?>,</p>
<p><?= $reminder ? 'Påminnelse: ' : '' ?><?= e($creator['name']) ?> har bjudit in dig att signera dokumentet <b><?= e($document['title']) ?></b>.</p>
<?php if ($document['message']): ?>
<p style="border-left:3px solid #d0d7de;padding-left:12px;color:#3e4c59;white-space:pre-wrap;"><?= e($document['message']) ?></p>
<?php endif; ?>
<p>Öppna länken för att läsa dokumentet och välja om du vill signera. Du bekräftar med de fyra sista siffrorna i ditt personnummer.</p>
<?= View::fetch('mail/_button', ['href' => $link, 'label' => 'Läs och signera']) ?>
<p style="font-size:13px;color:#52606d;">Länken är personlig, så vidarebefordra inte mejlet.<?= $reminder ? ' Tidigare länkar till dokumentet gäller inte längre.' : '' ?></p>
