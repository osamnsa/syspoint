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
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#34348A" stroke-width="170" stroke-linecap="round" opacity="0.5" class="home-swoosh-band"/>
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#F7CB1E" stroke-width="2.5" stroke-linecap="round" opacity="0.6" class="home-swoosh-line"/>
    </svg>

    <div class="container home-hero-head">
        <span class="home-hero-kicker">...challenging conventions</span>
        <h1><?= e(content_block('home.hero_title_prefix', 'Everything Tech,')) ?> <span><?= e(content_block('home.hero_title_highlight', 'In One Place')) ?></span></h1>
        <p><?= e(content_block('home.hero_subtitle', 'Computers and accessories, a software clinic that builds what your business needs, a gaming lounge with a VIP and common room, and a training centre turning out interns ready to work.')) ?></p>
    </div>

    <!-- The laptop cycles through our page heroes: Gaming, then Shop, then Training. -->
    <div class="home-hero-stage">
        <picture class="home-screen">
            <source srcset="<?= asset('assets/img/home/hero-desk-gaming.webp') ?>" type="image/webp">
            <img src="<?= asset('assets/img/home/hero-desk-gaming.jpg') ?>" alt="A laptop on a desk showing Syspoint Hub's gaming, shop and training pages in turn" width="1472" height="844" fetchpriority="high">
        </picture>
        <?php foreach (['shop', 'training'] as $screen): ?>
            <picture class="home-screen home-screen-<?= $screen ?>" aria-hidden="true">
                <source srcset="<?= asset('assets/img/home/hero-desk-' . $screen . '.webp') ?>" type="image/webp">
                <img src="<?= asset('assets/img/home/hero-desk-' . $screen . '.jpg') ?>" alt="" width="1472" height="844" loading="lazy">
            </picture>
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
                <div class="home-stat"><strong><?= e($value) ?></strong><span><?= e($label) ?></span></div>
            <?php endforeach; ?>
            <a href="<?= path('contact') ?>" class="btn btn-primary home-stats-cta">Get in Touch →</a>
        </div>

        <p class="home-hero-tagline"><span>Shop</span> • <span>Play</span> • <span>Learn</span> • <span>Build</span></p>
    </div>
</section>

<?php
$homeProducts = products_recent(4);
$homeRooms = gaming_rooms_active();
$homeCourses = array_slice(training_courses_active(), 0, 4);
$homeBusinesses = deployed_businesses_active();
$mapsUrl = 'https://www.google.com/maps/search/?api=1&query=Awesome+Plaza+Apo+Resettlement+Abuja';
?>

<?php if ($homeBusinesses): ?>
<!-- Trusted-by strip: right under the hero's "573 clients", the organisations we've
     consulted with (Admin -> Businesses; also the Software Clinic portfolio).
     Greyscale logos; organisations without a logo show their name as a grey wordmark. -->
<section class="home-trusted" aria-labelledby="trusted-title">
    <div class="container">
        <p class="home-trusted-kicker">Our clients</p>
        <h2 id="trusted-title"><?= e(content_block('home.clients_title', 'Organisations we’ve consulted with')) ?></h2>
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
        <p class="home-trusted-more"><a href="<?= path('software-clinic') ?>">Need IT consulting or software? Tell us what you need →</a></p>
    </div>
</section>
<?php endif; ?>


<?php if ($homeProducts): ?>
<!-- 1. New in the Shop -->
<section class="section home-section">
    <div class="container">
        <div class="home-section-head">
            <div>
                <span class="eyebrow">Gadget Store</span>
                <h2>New in the Shop</h2>
            </div>
            <a href="<?= path('shop') ?>" class="home-section-link">Visit the store →</a>
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
            <span class="eyebrow">Gaming Lounge</span>
            <h2>Play. Immerse. Unwind.</h2>
            <p>PS5, a VR arena and a shelf of board games — book the VIP room for your squad or drop into the common room.</p>
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
                <a href="<?= path('gaming') ?>#rooms" class="btn btn-primary">Book a Room</a>
                <a href="<?= path('gaming') ?>" class="btn btn-outline btn-outline-light">Browse Games</a>
            </div>
            <span class="gold-tag">Free internet for all gamers</span>
        </div>
        <div class="home-gaming-tiles" aria-hidden="true">
            <?php foreach (['ps5' => 'PS5', 'vr' => 'VR Arena', 'board' => 'Board Games'] as $img => $label): ?>
                <div class="home-gaming-tile">
                    <img src="<?= asset('assets/img/gaming/' . $img . '.jpg') ?>" alt="" loading="lazy">
                    <span><?= e($label) ?></span>
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
                <span class="eyebrow">Training &amp; Internship</span>
                <h2>Learn skills that get you hired</h2>
            </div>
            <a href="<?= path('training') ?>" class="home-section-link">See all courses →</a>
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
            <p class="section-lede">Our course list is being updated — ask us about the next intake.</p>
        <?php endif; ?>
        <div class="home-training-cta">
            <p>Want real work experience? We take on interns across sales, software and IT consulting.</p>
            <a href="<?= path('contact') ?>" class="btn btn-primary">Ask About Internships</a>
        </div>
    </div>
</section>

<!-- 4. Testimonials (Admin -> Testimonials) — light band, so it doesn't sit dark-on-dark against Visit us -->
<?php $testimonials = testimonials_active(); ?>
<?php if ($testimonials): ?>
<section class="home-testimonials">
    <div class="container">
        <span class="eyebrow">Testimonials</span>
        <h2><?= e(content_block('home.testimonials_title', 'What our clients say')) ?></h2>
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
                <span class="eyebrow">Visit Us</span>
                <h2>Two doors, one plaza</h2>
                <p class="home-visit-lede">Awesome Plaza, Opposite Chicken Republic, Apo Resettlement, Abuja · Open 9am – 10pm</p>
            </div>
        </div>
        <div class="home-visit-grid">
            <div class="home-visit-card">
                <span class="home-visit-suite">Suite C1</span>
                <h3>Syspoint Hub</h3>
                <p>Gaming lounge, VR arena and IT training.</p>
                <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Get Directions →</a>
            </div>
            <div class="home-visit-card">
                <span class="home-visit-suite">Suite C20</span>
                <h3>Gadget Store</h3>
                <p>Syspoint Solutions Consult Limited — computers, gadgets and the software clinic.</p>
                <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Get Directions →</a>
            </div>
        </div>
        <p class="home-visit-contact">Questions? <a href="mailto:syspointmail@gmail.com">syspointmail@gmail.com</a> or <a href="<?= path('contact') ?>">send us a message</a>.</p>
    </div>
</section>

<?php $hideFooterAddress = true; // already shown in the Visit Us section above
require __DIR__ . '/../partials/footer.php'; ?>
