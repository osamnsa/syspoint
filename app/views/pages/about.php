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
        <?php
        // Four arc segments around an empty centre (an opened aperture).
        $cx = 300; $cy = 300; $r1 = 92; $r2 = 286;
        $pt = fn(float $r, float $deg) => [$cx + $r * cos(deg2rad($deg)), $cy + $r * sin(deg2rad($deg))];
        $arcs = [
            ['shop', 'about.photo_shop', 'Gadget Store', 'Laptops on display in the Syspoint Gadget Store', 196, 274],
            ['software-clinic', 'about.photo_clinic', 'Software Clinic', 'Hands typing code on a laptop', 286, 364],
            ['training', 'about.photo_training', 'IT Training', 'A student learning on a laptop', 16, 94],
            ['gaming', 'about.photo_gaming', 'Gaming Lounge', 'A gamer wearing a VR headset', 106, 184],
        ];
        ?>
        <div class="about-arcs">
            <svg viewBox="0 0 600 600" role="list" aria-label="What’s at Syspoint">
                <defs>
                    <?php foreach ($arcs as $i => [, , , , $a0, $a1]):
                        // annular sector with rounded corners (rc = corner radius, outer / inner)
                        $ro = 24; $ri = 16; $do = rad2deg($ro / $r2); $di = rad2deg($ri / $r1);
                        $f = fn(array $p) => sprintf('%.1f %.1f', $p[0], $p[1]);
                        $d = 'M' . $f($pt($r2, $a0 + $do))
                           . ' A' . $r2 . ' ' . $r2 . ' 0 0 1 ' . $f($pt($r2, $a1 - $do))
                           . ' Q' . $f($pt($r2, $a1)) . ' ' . $f($pt($r2 - $ro, $a1))
                           . ' L' . $f($pt($r1 + $ri, $a1))
                           . ' Q' . $f($pt($r1, $a1)) . ' ' . $f($pt($r1, $a1 - $di))
                           . ' A' . $r1 . ' ' . $r1 . ' 0 0 0 ' . $f($pt($r1, $a0 + $di))
                           . ' Q' . $f($pt($r1, $a0)) . ' ' . $f($pt($r1 + $ri, $a0))
                           . ' L' . $f($pt($r2 - $ro, $a0))
                           . ' Q' . $f($pt($r2, $a0)) . ' ' . $f($pt($r2, $a0 + $do)) . ' Z'; ?>
                        <path id="arc-<?= $i ?>" d="<?= $d ?>"/>
                        <clipPath id="arc-clip-<?= $i ?>"><use href="#arc-<?= $i ?>"/></clipPath>
                    <?php endforeach; ?>
                    <linearGradient id="arc-shade" x1="0" y1="0" x2="0" y2="1"><stop offset=".45" stop-color="#0E0E24" stop-opacity="0"/><stop offset="1" stop-color="#0E0E24" stop-opacity=".75"/></linearGradient>
                </defs>
                <g class="arcs-spin">
                <?php foreach ($arcs as $i => [$page, $key, $label, $alt, $a0, $a1]):
                    $mid = ($a0 + $a1) / 2;
                    // bounding box of the segment, for the photo
                    $xs = []; $ys = [];
                    foreach ([$a0, $a1, $mid, $a0 + 20, $a1 - 20] as $deg) foreach ([$r1, $r2] as $r) { [$x, $y] = $pt($r, $deg); $xs[] = $x; $ys[] = $y; }
                    [$bx, $by, $bw, $bh] = [min($xs), min($ys), max($xs) - min($xs), max($ys) - min($ys)];
                    [$lx, $ly] = $pt(($r1 + $r2) / 2 + 22, $mid);
                    $dx = round(cos(deg2rad($mid)) * 8, 1); $dy = round(sin(deg2rad($mid)) * 8, 1); ?>
                    <a href="<?= path($page) ?>" class="arc" role="listitem" aria-label="<?= e($label) ?>" style="--dx:<?= $dx ?>px;--dy:<?= $dy ?>px;--i:<?= $i ?>">
                        <g clip-path="url(#arc-clip-<?= $i ?>)">
                            <rect width="600" height="600" fill="#1C1C48"/>
                            <!-- the photo travels round with its arc but stays upright (counter-turns about its own centre; 460px covers the arc at any angle) -->
                            <g class="arc-upright">
                                <image class="arc-photo" href="<?= e(media_url(site($key))) ?>" x="<?= round($cx + cos(deg2rad($mid)) * 190 - 230) ?>" y="<?= round($cy + sin(deg2rad($mid)) * 190 - 230) ?>" width="460" height="460" preserveAspectRatio="xMidYMid slice"><title><?= e($alt) ?></title></image>
                            </g>
                            <rect width="600" height="600" fill="url(#arc-shade)" opacity=".7"/>
                        </g>
                        <use href="#arc-<?= $i ?>" class="arc-edge"/>
                        <g transform="translate(<?= round($lx, 1) ?> <?= round($ly, 1) ?>)"><g class="arc-label">
                            <rect x="-<?= 8 + strlen($label) * 4.3 ?>" y="-15" width="<?= 16 + strlen($label) * 8.6 ?>" height="30" rx="15"/>
                            <text text-anchor="middle" y="5"><?= e($label) ?></text>
                        </g></g>
                    </a>
                <?php endforeach; ?>
                </g>
            </svg>
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
