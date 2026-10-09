<?php
declare(strict_types=1);

$pageTitle = 'Shop';
$pageDescription = 'Computers and accessories — browse by category, shop online, and check out securely.';

$categories = product_categories();
$trending = products_recent(10);

require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">

<?php
// Line icons for the trust row (stroke = currentColor).
$shopIcon = fn(string $d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
$trust = [
    [$shopIcon('<path d="M12 3l2.6 5.3 5.8.8-4.2 4.1 1 5.8L12 16.3 6.8 19l1-5.8L3.6 9.1l5.8-.8z"/>'), 'shop.badge1'],
    [$shopIcon('<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>'), 'shop.badge2'],
    [$shopIcon('<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>'), 'shop.badge3'],
    [$shopIcon('<path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 2zM20 13v3a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2z"/>'), 'shop.badge4'],
];
$catImages = ['computers' => 'assets/img/shop/cat-computers.png'];
$catIcons = [
    'accessories' => '<path d="M4 14a8 8 0 0 1 16 0"/><path d="M4 14v3a2 2 0 0 0 2 2h1.5v-6H6a2 2 0 0 0-2 2zM20 14v3a2 2 0 0 1-2 2h-1.5v-6H18a2 2 0 0 1 2 2z"/>',
];
$promoUrl = site('shop.promo_url');
$promoHref = preg_match('#^https?://#i', $promoUrl) ? $promoUrl : path(ltrim($promoUrl, '/'));
?>
<div class="shop-dark">
<?php require __DIR__ . '/../partials/page_hero_swoosh.php'; ?>

<section class="shop-hero">
    <div class="container shop-hero-inner">
        <div class="shop-hero-copy">
            <span class="shop-kicker"><?= e(site('shop.eyebrow')) ?></span>
            <?php $heroLines = site_list('shop.hero_title_prefix', "\n") ?: ['']; $heroLast = array_pop($heroLines); ?>
            <h1>
                <?php foreach ($heroLines as $line): ?><span class="shop-hero-line"><?= e($line) ?></span><?php endforeach; ?>
                <span class="shop-hero-line"><?= e($heroLast) ?>
                <?php if (site('shop.hero_title_highlight') !== ''): ?>
                    <span class="shop-hero-script"><?= e(site('shop.hero_title_highlight')) ?><svg viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path d="M4 16 C 70 6, 150 4, 296 10 C 210 12, 120 16, 40 21" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg></span>
                <?php endif; ?>
                </span>
            </h1>
            <p class="shop-hero-sub"><?= nl2br(e(site('shop.hero_subtitle'))) ?></p>
            <div class="shop-hero-actions">
                <a href="#categories" class="btn btn-primary shop-pill"><?= e(site('shop.btn_primary')) ?> <span aria-hidden="true">→</span></a>
                <a href="<?= e(site('site.maps_url')) ?>" class="shop-hero-visit" target="_blank" rel="noopener">
                    <span class="shop-hero-visit-icon"><?= $shopIcon('<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>') ?></span>
                    <span><strong><?= e(site('shop.btn_secondary')) ?></strong><small><?= e(site('site.store_suite')) ?>, <?= e(explode(', ', site('site.plaza'))[0]) ?></small></span>
                </a>
            </div>
            <ul class="shop-trust">
                <?php foreach ($trust as [$icon, $key]): ?>
                    <li><span class="shop-trust-icon"><?= $icon ?></span><span><strong><?= e(site($key . '_title')) ?></strong><small><?= e(site($key . '_text')) ?></small></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="shop-hero-stage">
            <span class="shop-hero-ring" aria-hidden="true"></span>
            <span class="shop-hero-podium" aria-hidden="true"></span>
            <div class="shop-hero-product">
                <?= site_picture('shop.hero_image', 'A slim laptop with a colourful wave wallpaper on its screen', ['width' => 900, 'height' => 541, 'fetchpriority' => 'high']) ?>
            </div>
        </div>
    </div>
</section>

<section class="shop-cats" id="categories">
    <div class="container">
        <div class="shop-section-head">
            <h2><?= e(site('shop.categories_title')) ?></h2>
            <span class="shop-section-rule" aria-hidden="true"></span>
        </div>
        <div class="shop-cat-grid">
            <?php foreach ($categories as $category):
                $img = $category['image_path'] ?: ($catImages[$category['slug']] ?? null); ?>
                <a class="shop-cat-card" href="<?= path('shop/' . e($category['slug'])) ?>">
                    <span class="shop-cat-media">
                        <?php if ($img): ?>
                            <?php $webp = str_ends_with($img, '.png') && is_file(__DIR__ . '/../../../public/' . substr($img, 0, -4) . '.webp') ? substr($img, 0, -4) . '.webp' : null; ?>
                            <picture<?= str_ends_with($img, '.png') ? '' : ' class="is-photo"' ?>><?php if ($webp): ?><source srcset="<?= e(media_url($webp)) ?>" type="image/webp"><?php endif; ?><img src="<?= e(media_url($img)) ?>" alt="" loading="lazy"></picture>
                        <?php else: ?>
                            <?= $shopIcon($catIcons[$category['slug']] ?? '<rect x="3" y="5" width="18" height="12" rx="2"/><path d="M2 19h20"/>') ?>
                        <?php endif; ?>
                    </span>
                    <strong><?= e($category['name']) ?></strong>
                    <small><?= (int) $category['product_count'] ? (int) $category['product_count'] . ' item' . ((int) $category['product_count'] === 1 ? '' : 's') . ' · ' : '' ?>Shop Now <span aria-hidden="true">→</span></small>
                </a>
            <?php endforeach; ?>
            <a class="shop-cat-card" href="<?= path('contact') ?>">
                <span class="shop-cat-media"><?= $shopIcon('<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.5-.6-.6-2.5z"/>') ?></span>
                <strong><?= e(site('shop.repair_title')) ?></strong>
                <small><?= e(site('shop.repair_text')) ?> <span aria-hidden="true">→</span></small>
            </a>
        </div>
    </div>
</section>

<section class="shop-promo-wrap">
    <div class="container">
        <div class="shop-promo">
            <div class="shop-promo-art"><?= site_picture('shop.promo_image', 'A PlayStation 5 console and controller', ['loading' => 'lazy']) ?></div>
            <div class="shop-promo-copy">
                <span class="shop-kicker"><?= e(site('shop.promo_kicker')) ?></span>
                <h2><?= e(site('shop.promo_title')) ?><?php if (site('shop.promo_highlight') !== ''): ?> <span class="shop-script"><?= e(site('shop.promo_highlight')) ?></span><?php endif; ?></h2>
                <p><?= e(site('shop.promo_text')) ?></p>
                <a href="<?= e($promoHref) ?>" class="btn btn-primary shop-pill"><?= e(site('shop.promo_btn')) ?> <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </div>
</section>
</div>

<?php if ($trending): ?>
<section class="section section-soft" id="trending">
    <div class="container">
        <div class="admin-header-row" style="margin-bottom:12px;">
            <div>
                <span class="eyebrow"><?= e(site('shop.new_eyebrow')) ?></span>
                <h2 style="margin-bottom:0;"><?= e(site('shop.new_title')) ?></h2>
            </div>
        </div>
        <div class="product-grid">
            <?php foreach ($trending as $product): ?>
                <a class="product-card" href="<?= path('shop/' . e($product['category_slug']) . '/' . e($product['slug'])) ?>">
                    <div class="product-card-img">
                        <?php if ($product['image_path']): ?>
                            <img src="<?= asset(e($product['image_path'])) ?>" alt="<?= e($product['name']) ?>">
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

<section class="section shop-visit">
    <div class="container">
        <div class="shop-visit-card">
            <div class="shop-visit-icon" aria-hidden="true">📍</div>
            <div class="shop-visit-copy">
                <span class="eyebrow"><?= e(site('shop.visit_eyebrow')) ?></span>
                <h2><?= e(site('shop.visit_title')) ?></h2>
                <address>
                    <strong><?= e(site('site.store_short')) ?></strong><br>
                    <?php $plazaParts = explode(', ', site('site.plaza'), 3); ?>
                    <?= e(site('site.store_suite')) ?>, <?= e(implode(', ', array_slice($plazaParts, 0, 2))) ?><?= isset($plazaParts[2]) ? ',<br>' . e($plazaParts[2]) : '' ?>
                </address>
                <p class="shop-visit-meta">Open <?= e(site('site.hours')) ?> · <a href="mailto:<?= e(site('site.email')) ?>"><?= e(site('site.email')) ?></a><?php if (site('site.phone')): ?> · <a href="tel:<?= e(preg_replace('/[^\d+]/', '', site('site.phone'))) ?>"><?= e(site('site.phone')) ?></a><?php endif; ?></p>
            </div>
            <a class="btn btn-primary" href="<?= e(site('site.maps_url')) ?>" target="_blank" rel="noopener"><?= e(site('home.visit_btn')) ?></a>
        </div>
    </div>
</section>


</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
