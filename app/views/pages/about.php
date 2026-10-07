<?php
declare(strict_types=1);

$pageTitle = 'About';
$pageDescription = 'About Syspoint — computers & accessories, software, gaming, consulting, and training under one roof.';

require __DIR__ . '/../partials/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / About</div>
        <h1><?= e(site('about.title')) ?></h1>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:760px;">
        <div class="prose">
            <?= site_sanitize_html(site('about.body')) ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
