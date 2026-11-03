<?php use Signera\View; ?>
<p>Hej <?= e($user['name']) ?>,</p>
<p>Någon (förhoppningsvis du) har bett om att få återställa lösenordet till ditt konto i Signera.</p>
<?= View::fetch('mail/_button', ['href' => $link, 'label' => 'Välj nytt lösenord']) ?>
<p style="font-size:13px;color:#52606d;">Om det inte var du kan du bortse från det här mejlet.</p>
