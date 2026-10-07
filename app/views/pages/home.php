<?php
declare(strict_types=1);

$pageTitle = 'Home';
$pageDescription = site('site.meta_description');

require __DIR__ . '/../partials/header.php';
?>

<?php
$consultingUrl = site('site.consulting_url');
// Icons: simple 24px line glyphs, stroked in currentColor (gold on the cards).
$icons = [
    'shop' => '<path d="M3 6h18M5 6l1.5 12h11L19 6M9 10v4M15 10v4"/><path d="M9 6a3 3 0 0 1 6 0"/>',
    'code' => '<path d="M8 8l-4 4 4 4M16 8l4 4-4 4M13.5 5l-3 14"/>',
    'game' => '<path d="M6 9h12a3 3 0 0 1 3 3l-1 4a2.5 2.5 0 0 1-4.3 1.2L14.5 16h-5l-1.2 1.2A2.5 2.5 0 0 1 4 16l-1-4a3 3 0 0 1 3-3z"/><path d="M8 11.5v3M6.5 13h3M15.5 12.5h.01M17.5 14h.01"/>',
    'learn' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5M22 9v6"/>',
    'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
];
$services = [
    ['href' => path('shop'), 'icon' => 'shop', 'title' => site('home.svc1_title'), 'text' => site('home.svc1_text')],
    ['href' => path('software-clinic'), 'icon' => 'code', 'title' => site('home.svc2_title'), 'text' => site('home.svc2_text')],
    ['href' => path('gaming'), 'icon' => 'game', 'title' => site('home.svc3_title'), 'text' => site('home.svc3_text'), 'featured' => true],
    ['href' => path('training'), 'icon' => 'learn', 'title' => site('home.svc4_title'), 'text' => site('home.svc4_text')],
    ['href' => $consultingUrl ?: null, 'icon' => 'chart', 'title' => site('home.svc5_title'), 'text' => site('home.svc5_text'), 'external' => true],
];
?>
<section class="home-hero">
    <svg class="home-swoosh" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#34348A" stroke-width="170" stroke-linecap="round" opacity="0.5" class="home-swoosh-band"/>
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#F7CB1E" stroke-width="2.5" stroke-linecap="round" opacity="0.6" class="home-swoosh-line"/>
    </svg>
    <!-- Phones: the same swoosh redrawn for a tall screen — in behind the laptop, across
         the service cards, out under the stats bar; never through the headline. -->
    <svg class="home-swoosh-mobile" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
        <path d="M108 24 C 70 20, -4 30, 8 46 S 104 60, 92 74 S 30 84, -12 90" fill="none" stroke="#34348A" stroke-width="110" stroke-linecap="round" opacity="0.5" vector-effect="non-scaling-stroke"/>
        <path d="M108 24 C 70 20, -4 30, 8 46 S 104 60, 92 74 S 30 84, -12 90" fill="none" stroke="#F7CB1E" stroke-width="2" stroke-linecap="round" opacity="0.6" vector-effect="non-scaling-stroke"/>
    </svg>

    <div class="container home-hero-head">
        <span class="home-hero-kicker"><?= e(site('site.tagline')) ?></span>
        <h1><?= e(site('home.hero_title_prefix')) ?> <span><?= e(site('home.hero_title_highlight')) ?></span></h1>
        <p><?= e(site('home.hero_subtitle')) ?></p>
    </div>

    <!-- The laptop cycles through our page heroes: Gaming, then Shop, then Training. -->
    <div class="home-hero-stage">
        <?= site_picture('home.screen_gaming', 'A laptop on a desk showing Syspoint Hub\'s gaming, shop and training pages in turn', ['width' => 1472, 'height' => 844, 'fetchpriority' => 'high'], 'home-screen') ?>
        <?php foreach (['shop', 'training'] as $screen): ?>
            <?= str_replace('<picture class=', '<picture aria-hidden="true" class=', site_picture('home.screen_' . $screen, '', ['width' => 1472, 'height' => 844, 'loading' => 'lazy'], 'home-screen home-screen-' . $screen)) ?>
        <?php endforeach; ?>
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
            <?php $heroStats = home_stats() ?: ['Services, one roof' => '5', 'Wi-Fi for every gamer' => 'Free', 'Opening hours' => '9am–10pm']; ?>
            <?php foreach ($heroStats as $label => $value): ?>
                <?php $short = HOME_STATS_SHORT[$label] ?? null; // shorter label on phones, e.g. "Experience" ?>
                <div class="home-stat"><strong><?= e($value) ?></strong><span><?php if ($short): ?><span class="label-full"><?= e($label) ?></span><span class="label-short"><?= e($short) ?></span><?php else: ?><?= e($label) ?><?php endif; ?></span></div>
            <?php endforeach; ?>
            <a href="<?= path('contact') ?>" class="btn btn-primary home-stats-cta"><?= e(site('home.stats_cta')) ?></a>
        </div>

        <p class="home-hero-tagline"><?= implode(' • ', array_map(fn($w) => '<span>' . e($w) . '</span>', site_list('home.tagline'))) ?></p>
    </div>
</section>

<?php
$homeProducts = products_recent(4);
$homeRooms = gaming_rooms_active();
$homeCourses = array_slice(training_courses_active(), 0, 4);
$homeBusinesses = deployed_businesses_active();
$mapsUrl = site('site.maps_url');
?>

<?php if ($homeBusinesses): ?>
<!-- Trusted-by strip: right under the hero's "573 clients", the organisations we've
     consulted with (Admin -> Businesses; also the Software Clinic portfolio).
     Greyscale logos; organisations without a logo show their name as a grey wordmark. -->
<section class="home-trusted" aria-labelledby="trusted-title">
    <div class="container">
        <p class="home-trusted-kicker"><?= e(site('home.clients_kicker')) ?></p>
        <h2 id="trusted-title"><?= e(site('home.clients_title')) ?></h2>
        <ul class="home-trusted-list">
            <?php foreach ($homeBusinesses as $biz): ?>
                <li>
                    <?php $tag = $biz['website_url'] ? 'a' : 'div'; ?>
                    <<?= $tag ?> class="home-trusted-item<?= $biz['logo_path'] ? ' has-logo' : '' ?>"<?php if ($biz['website_url']): ?> href="<?= e($biz['website_url']) ?>" target="_blank" rel="noopener"<?php endif; ?>>
                        <?php if ($biz['logo_path']): ?>
                            <img src="<?= media_url($biz['logo_path']) ?>" alt="" loading="lazy" onerror="this.hidden=true;this.parentElement.classList.remove('has-logo')">
                        <?php endif; ?>
                        <span class="home-trusted-name"><?= e($biz['name']) ?></span>
                    </<?= $tag ?>>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="home-trusted-more"><a href="<?= path('software-clinic') ?>"><?= e(site('home.clients_link')) ?></a></p>
    </div>
</section>
<?php endif; ?>


<?php if ($homeProducts): ?>
<!-- 1. New in the Shop -->
<section class="section home-section">
    <div class="container">
        <div class="home-section-head">
            <div>
                <span class="eyebrow"><?= e(site('home.shop_eyebrow')) ?></span>
                <h2><?= e(site('home.shop_title')) ?></h2>
            </div>
            <a href="<?= path('shop') ?>" class="home-section-link"><?= e(site('home.shop_link')) ?></a>
        </div>
        <div class="product-grid home-product-grid">
            <?php foreach ($homeProducts as $product): ?>
                <a class="product-card" href="<?= path('shop/' . e($product['category_slug']) . '/' . e($product['slug'])) ?>">
                    <div class="product-card-img">
                        <?php if ($product['image_path']): ?>
                            <img src="<?= asset(e($product['image_path'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="product-card-placeholder">📦</span>
                        <?php endif; ?>
                        <?php if ((int) $product['stock_qty'] <= 0): ?>
                            <span class="badge badge-muted product-card-badge">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <h3><?= e($product['name']) ?></h3>
                    <p class="product-card-price"><?= format_naira((float) $product['price']) ?><?= $product['is_demo'] ? demo_badge() : '' ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 2. Gaming Lounge band -->
<section class="home-gaming">
    <div class="container home-gaming-inner">
        <div class="home-gaming-copy">
            <span class="eyebrow"><?= e(site('home.gaming_eyebrow')) ?></span>
            <h2><?= e(site('home.gaming_title')) ?></h2>
            <p><?= e(site('home.gaming_text')) ?></p>
            <?php if ($homeRooms): ?>
                <ul class="home-rooms">
                    <?php foreach ($homeRooms as $room): ?>
                        <li>
                            <span class="home-room-name">
                                <strong><?= e($room['name']) ?></strong>
                                <?php if ($room['capacity']): ?><small>Up to <?= (int) $room['capacity'] ?> people</small><?php endif; ?>
                            </span>
                            <span class="home-room-rate"><?= format_naira((float) $room['hourly_rate']) ?>/hour<?= $room['is_demo'] ? demo_badge() : '' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="home-gaming-actions">
                <a href="<?= path('gaming') ?>#rooms" class="btn btn-primary"><?= e(site('home.gaming_btn_book')) ?></a>
                <a href="<?= path('gaming') ?>" class="btn btn-outline btn-outline-light"><?= e(site('home.gaming_btn_games')) ?></a>
            </div>
            <span class="gold-tag"><?= e(site('home.gaming_tag')) ?></span>
        </div>
        <div class="home-gaming-tiles" aria-hidden="true">
            <?php foreach ([1, 2, 3] as $n): ?>
                <div class="home-gaming-tile">
                    <img src="<?= e(media_url(site('gaming.card' . $n . '_image'))) ?>" alt="" loading="lazy">
                    <span><?= e(site('gaming.card' . $n . '_name')) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3. Training & Internship -->
<section class="section section-soft home-section">
    <div class="container">
        <div class="home-section-head">
            <div>
                <span class="eyebrow"><?= e(site('home.training_eyebrow')) ?></span>
                <h2><?= e(site('home.training_title')) ?></h2>
            </div>
            <a href="<?= path('training') ?>" class="home-section-link"><?= e(site('home.training_link')) ?></a>
        </div>
        <?php if ($homeCourses): ?>
            <div class="home-courses">
                <?php foreach ($homeCourses as $course): ?>
                    <a class="home-course" href="<?= path('training') ?>#courses">
                        <span class="home-course-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/></svg>
                        </span>
                        <strong><?= e($course['title']) ?></strong>
                        <?php if ($course['description']): ?><span class="home-course-text"><?= e($course['description']) ?></span><?php endif; ?>
                        <span class="home-course-meta">
                            <?= $course['duration_label'] ? e($course['duration_label']) : '' ?><?= $course['duration_label'] && $course['price'] !== null ? ' · ' : '' ?><?= $course['price'] !== null ? format_naira((float) $course['price']) : '' ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="section-lede"><?= e(site('home.training_empty')) ?></p>
        <?php endif; ?>
        <div class="home-training-cta">
            <p><?= e(site('home.internship_text')) ?></p>
            <a href="<?= path('contact') ?>" class="btn btn-primary"><?= e(site('home.internship_btn')) ?></a>
        </div>
    </div>
</section>

<!-- 4. Testimonials (Admin -> Testimonials) — light band, so it doesn't sit dark-on-dark against Visit us -->
<?php $testimonials = testimonials_active(); ?>
<?php if ($testimonials): ?>
<section class="home-testimonials">
    <div class="container">
        <span class="eyebrow"><?= e(site('home.testimonials_eyebrow')) ?></span>
        <h2><?= e(site('home.testimonials_title')) ?></h2>
        <div class="testimonial-grid">
            <?php foreach ($testimonials as $t): ?>
                <figure class="testimonial">
                    <svg class="testimonial-mark" viewBox="0 0 32 24" aria-hidden="true"><path d="M0 24V14C0 6 4 1 12 0l1 4C8 5 6 8 6 12h6v12zm19 0V14c0-8 4-13 12-14l1 4c-5 1-7 4-7 8h6v12z" fill="currentColor"/></svg>
                    <blockquote><?= e($t['quote']) ?></blockquote>
                    <figcaption>
                        <?php if ($t['photo_path']): ?>
                            <img class="testimonial-photo" src="<?= media_url($t['photo_path']) ?>" alt="" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                        <?php endif; ?>
                        <span class="testimonial-photo testimonial-initials" aria-hidden="true"<?= $t['photo_path'] ? ' hidden' : '' ?>><?= e(initials($t['author_name'])) ?></span>
                        <span>
                            <strong><?= e($t['author_name']) ?></strong>
                            <?php if ($t['author_company']): ?><span class="testimonial-company"><?= e($t['author_company']) ?></span><?php endif; ?>
                        </span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 5. Visit us -->
<section class="home-visit">
    <div class="container">
        <div class="home-section-head">
            <div>
                <span class="eyebrow"><?= e(site('home.visit_eyebrow')) ?></span>
                <h2><?= e(site('home.visit_title')) ?></h2>
                <p class="home-visit-lede"><?= e(site('site.plaza')) ?> · Open <?= e(site('site.hours')) ?></p>
            </div>
        </div>
        <div class="home-visit-grid">
            <div class="home-visit-card">
                <span class="home-visit-suite"><?= e(site('site.hub_suite')) ?></span>
                <h3><?= e(site('site.hub_name')) ?></h3>
                <p><?= e(site('site.hub_desc')) ?></p>
                <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><?= e(site('home.visit_btn')) ?></a>
            </div>
            <div class="home-visit-card">
                <span class="home-visit-suite"><?= e(site('site.store_suite')) ?></span>
                <h3><?= e(site('site.store_name')) ?></h3>
                <p><?= e(site('site.store_desc')) ?></p>
                <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><?= e(site('home.visit_btn')) ?></a>
            </div>
        </div>
        <p class="home-visit-contact">Questions? <a href="mailto:<?= e(site('site.email')) ?>"><?= e(site('site.email')) ?></a><?php if (site('site.phone')): ?>, <a href="tel:<?= e(preg_replace('/[^\d+]/', '', site('site.phone'))) ?>"><?= e(site('site.phone')) ?></a><?php endif; ?> or <a href="<?= path('contact') ?>">send us a message</a>.</p>
    </div>
</section>

<?php $hideFooterAddress = true; // already shown in the Visit Us section above
require __DIR__ . '/../partials/footer.php'; ?>
