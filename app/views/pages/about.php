<?php
declare(strict_types=1);

$pageTitle = 'About';
$pageDescription = 'About Syspoint — computers & accessories, software, gaming, consulting, and training under one roof.';

require __DIR__ . '/../partials/header.php';
?>

<section class="page-hero">
    <?php require __DIR__ . '/../partials/page_hero_swoosh.php'; ?>
    <div class="container page-hero-inner">
        <div class="page-hero-copy">
            <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / About</div>
            <span class="page-hero-kicker"><?= e(site('about.kicker')) ?></span>
            <h1><?= e(site('about.title')) ?><?php if (site('about.title_highlight') !== ''): ?> <span class="page-hero-highlight"><?= e(site('about.title_highlight')) ?></span><?php endif; ?></h1>
            <p class="page-hero-intro"><?= e(site('about.intro')) ?></p>
            <div class="page-hero-actions">
                <a href="<?= path('contact') ?>" class="btn btn-primary"><?= e(site('about.btn_primary')) ?></a>
                <a href="#story" class="btn btn-outline btn-outline-light"><?= e(site('about.btn_secondary')) ?></a>
            </div>
        </div>
        <div class="about-collage" aria-label="What’s at Syspoint">
            <?php foreach ([['shop', 'Shop', 'about.photo_shop', 'Laptops in the Syspoint shop'], ['gaming', 'Gaming', 'about.photo_gaming', 'A PS5 in the gaming lounge'], ['training', 'Training', 'about.photo_training', 'A student training on a laptop']] as $i => [$page, $label, $key, $alt]): ?>
                <a class="about-tile about-tile-<?= $i + 1 ?>" href="<?= path($page) ?>">
                    <?= site_picture($key, $alt, ['loading' => $i ? 'lazy' : 'eager']) ?>
                    <span class="about-tile-label"><?= e($label) ?> <span aria-hidden="true">→</span></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="story">
    <div class="container" style="max-width:760px;">
        <?php if (site('about.story_title') !== ''): ?><span class="eyebrow"><?= e(site('about.story_title')) ?></span><?php endif; ?>
        <div class="prose">
            <?= site_sanitize_html(site('about.body')) ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
