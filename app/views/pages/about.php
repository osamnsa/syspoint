<?php
declare(strict_types=1);

$pageTitle = 'About';
$pageDescription = 'About Syspoint — computers & accessories, software, gaming, consulting, and training under one roof.';

require __DIR__ . '/../partials/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / About</div>
        <h1>About Syspoint</h1>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:760px;">
        <div class="prose">
            <?= content_block('about.body', '<p>Syspoint brings together the tech essentials people and businesses actually need: computers and accessories you can trust, a software clinic that builds and deploys what your business is missing, a gaming lounge to unwind in, and hands-on training that turns beginners into job-ready talent.</p><p>This page is ready for the real story — edit it from the admin panel once it is live.</p>') ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
