<?php
declare(strict_types=1);

$pageTitle = 'Shop';
$pageDescription = 'Computers and accessories — browse by category, shop online, and check out securely.';

$categories = product_categories();
$trending = products_recent(10);

require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">

<section class="shop-hero">
    <div class="container shop-hero-inner">
        <div class="shop-hero-copy">
            <span class="eyebrow"><?= e(site('shop.eyebrow')) ?></span>
            <h1><?= e(site('shop.hero_title_prefix')) ?> <span class="shop-hero-highlight"><?= e(site('shop.hero_title_highlight')) ?></span></h1>
            <p><?= e(site('shop.hero_subtitle')) ?></p>
            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:24px;">
                <a href="#categories" class="btn btn-primary"><?= e(site('shop.btn_primary')) ?></a>
                <?php if ($trending): ?>
                    <a href="#trending" class="btn btn-outline btn-outline-light"><?= e(site('shop.btn_secondary')) ?></a>
                <?php endif; ?>
            </div>
        </div>
        <div class="shop-hero-art">
            <?= site_picture('shop.hero_image', 'Two slim laptops, one open showing a colourful screen', ['width' => 728, 'height' => 520, 'fetchpriority' => 'high']) ?>
        </div>
    </div>
</section>

<section class="section" id="categories">
    <div class="container">
        <span class="eyebrow"><?= e(site('shop.categories_eyebrow')) ?></span>
        <h2><?= e(site('shop.categories_title')) ?></h2>

        <?php if (!$categories): ?>
            <p style="color:var(--color-text-muted);">No categories yet — check back soon.</p>
        <?php else: ?>
            <div class="shop-category-strip">
                <?php foreach ($categories as $category): ?>
                    <a class="shop-category-strip-item" href="<?= path('shop/' . e($category['slug'])) ?>">
                        <span class="shop-category-strip-icon"><?= $category['slug'] === 'accessories' ? '🎧' : '🖥️' ?></span>
                        <span>
                            <strong><?= e($category['name']) ?></strong>
                            <span><?= (int) $category['product_count'] ?> item<?= (int) $category['product_count'] === 1 ? '' : 's' ?></span>
                        </span>
                        <span class="shop-category-strip-arrow">→</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

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

<section class="trust-badges">
    <div class="container trust-badges-grid">
        <div class="trust-badge">
            <span class="trust-badge-icon">✅</span>
            <div><strong><?= e(site('shop.badge1_title')) ?></strong><span><?= e(site('shop.badge1_text')) ?></span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🔒</span>
            <div><strong><?= e(site('shop.badge2_title')) ?></strong><span><?= e(site('shop.badge2_text')) ?></span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🚚</span>
            <div><strong><?= e(site('shop.badge3_title')) ?></strong><span><?= e(site('shop.badge3_text')) ?></span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🎧</span>
            <div><strong><?= e(site('shop.badge4_title')) ?></strong><span><?= e(site('shop.badge4_text')) ?></span></div>
        </div>
    </div>
</section>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
