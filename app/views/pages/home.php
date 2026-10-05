<?php
declare(strict_types=1);

$pageTitle = 'Home';
$pageDescription = 'Syspoint — computers & accessories, a software clinic, a gaming lounge, IT consulting, and internship training, all in one place.';

require __DIR__ . '/../partials/header.php';
?>

<?php
$consultingUrl = config()['app']['consulting_url'];
// Icons: simple 24px line glyphs, stroked in currentColor (gold on the cards).
$icons = [
    'shop' => '<path d="M3 6h18M5 6l1.5 12h11L19 6M9 10v4M15 10v4"/><path d="M9 6a3 3 0 0 1 6 0"/>',
    'code' => '<path d="M8 8l-4 4 4 4M16 8l4 4-4 4M13.5 5l-3 14"/>',
    'game' => '<path d="M6 9h12a3 3 0 0 1 3 3l-1 4a2.5 2.5 0 0 1-4.3 1.2L14.5 16h-5l-1.2 1.2A2.5 2.5 0 0 1 4 16l-1-4a3 3 0 0 1 3-3z"/><path d="M8 11.5v3M6.5 13h3M15.5 12.5h.01M17.5 14h.01"/>',
    'learn' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5M22 9v6"/>',
    'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
];
$services = [
    ['href' => path('shop'), 'icon' => 'shop', 'title' => 'Computers & Gadgets', 'text' => 'Genuine laptops & accessories'],
    ['href' => path('software-clinic'), 'icon' => 'code', 'title' => 'Software Clinic', 'text' => 'We build what your business needs'],
    ['href' => path('gaming'), 'icon' => 'game', 'title' => 'Gaming Lounge', 'text' => 'PS5, VR arena & board games', 'featured' => true],
    ['href' => path('training'), 'icon' => 'learn', 'title' => 'IT Training', 'text' => 'Courses & internships'],
    ['href' => $consultingUrl ?: null, 'icon' => 'chart', 'title' => 'IT Consulting', 'text' => 'Strategy for people & businesses', 'external' => true],
];
?>
<section class="home-hero">
    <svg class="home-swoosh" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#34348A" stroke-width="170" stroke-linecap="round" opacity="0.5"/>
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#F7CB1E" stroke-width="2.5" stroke-linecap="round" opacity="0.6"/>
    </svg>

    <div class="container home-hero-head">
        <span class="home-hero-kicker">...challenging conventions</span>
        <h1><?= e(content_block('home.hero_title_prefix', 'Everything Tech,')) ?> <span><?= e(content_block('home.hero_title_highlight', 'In One Place')) ?></span></h1>
        <p><?= e(content_block('home.hero_subtitle', 'Computers and accessories, a software clinic that builds what your business needs, a gaming lounge with a VIP and common room, and a training centre turning out interns ready to work.')) ?></p>
    </div>

    <div class="home-hero-stage">
        <picture>
            <source srcset="<?= asset('assets/img/home/hero-desk.webp') ?>" type="image/webp">
            <img src="<?= asset('assets/img/home/hero-desk.jpg') ?>" alt="A laptop on a desk showing the Syspoint Hub gaming page" width="1472" height="844" fetchpriority="high">
        </picture>
    </div>

    <div class="container">
        <div class="home-services">
            <?php foreach ($services as $svc): ?>
                <?php $tag = $svc['href'] ? 'a' : 'div'; ?>
                <<?= $tag ?> class="home-service<?= !empty($svc['featured']) ? ' is-featured' : '' ?>"<?php if ($svc['href']): ?> href="<?= e($svc['href']) ?>"<?php if (!empty($svc['external'])): ?> target="_blank" rel="noopener"<?php endif; endif; ?>>
                    <span class="home-service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons[$svc['icon']] ?></svg></span>
                    <strong><?= e($svc['title']) ?></strong>
                    <span class="home-service-text"><?= e($svc['text']) ?></span>
                    <span class="home-service-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>

        <div class="home-stats">
            <div class="home-stat"><strong>5</strong><span>Services, one roof</span></div>
            <div class="home-stat"><strong>Free</strong><span>Wi-Fi for every gamer</span></div>
            <div class="home-stat"><strong>9am–10pm</strong><span>Opening hours</span></div>
            <a href="<?= path('contact') ?>" class="btn btn-primary home-stats-cta">Get in Touch →</a>
        </div>

        <p class="home-hero-tagline"><span>Shop</span> • <span>Play</span> • <span>Learn</span> • <span>Build</span></p>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
