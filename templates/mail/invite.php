<?php use Signera\View; ?>
<p>Hej <?= e($user['name']) ?>,</p>
<p><?= e($admin['name']) ?> har gett dig ett konto i Signera, där du kan ladda upp dokument och skicka dem för digital signering.</p>
<p>Välj ett lösenord för att komma igång. Länken gäller i <?= (int) \Signera\App::config('invite_valid_hours', 72) ?> timmar.</p>
<?= View::fetch('mail/_button', ['href' => $link, 'label' => 'Välj lösenord']) ?>
