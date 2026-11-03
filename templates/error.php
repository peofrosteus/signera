<section class="card narrow center">
    <h1><?= $status === 404 ? 'Hittades inte' : ($status === 403 ? 'Ingen behörighet' : 'Något gick fel') ?></h1>
    <p><?= e($message) ?></p>
</section>
