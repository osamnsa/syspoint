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

<?php
$split = fn(string $line) => array_map('trim', explode('|', $line));
$timeline = array_map($split, site_list('about.timeline', "\n"));
$values = array_map($split, site_list('about.values', "\n"));
?>
<section class="section about-story" id="story">
    <div class="container about-story-grid">
        <div class="about-story-head">
            <?php if (site('about.story_title') !== ''): ?><span class="eyebrow"><?= e(site('about.story_title')) ?></span><?php endif; ?>
            <h2><?= e(site('about.story_heading')) ?></h2>
            <div class="about-since" aria-hidden="true"><span>Since</span><strong>2010</strong></div>
        </div>
        <div class="about-story-body">
            <?= site_sanitize_html(site('about.body')) ?>
        </div>
    </div>
</section>

<?php if ($timeline): ?>
<section class="section section-soft about-timeline-section">
    <div class="container">
        <h2><?= e(site('about.timeline_title')) ?></h2>
        <ol class="about-timeline">
            <?php foreach ($timeline as $step): ?>
                <li>
                    <span class="about-timeline-label"><?= e($step[0] ?? '') ?></span>
                    <strong><?= e($step[1] ?? '') ?></strong>
                    <?php if (!empty($step[2])): ?><p><?= e($step[2]) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
<?php endif; ?>

<?php if ($values): ?>
<section class="section">
    <div class="container">
        <h2><?= e(site('about.values_title')) ?></h2>
        <div class="about-values">
            <?php foreach ($values as $i => $val): ?>
                <div class="about-value">
                    <span class="about-value-num"><?= sprintf('%02d', $i + 1) ?></span>
                    <strong><?= e($val[0] ?? '') ?></strong>
                    <?php if (!empty($val[1])): ?><p><?= e($val[1]) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section" style="padding-top:0;">
    <div class="container">
        <div class="about-cta">
            <div>
                <h2><?= e(site('about.cta_title')) ?></h2>
                <p><?= e(site('about.cta_text')) ?></p>
            </div>
            <div class="about-cta-actions">
                <a href="<?= path('contact') ?>" class="btn btn-primary"><?= e(site('about.btn_primary')) ?></a>
                <a href="<?= path('software-clinic') ?>" class="btn btn-outline btn-outline-light">Software Clinic</a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
