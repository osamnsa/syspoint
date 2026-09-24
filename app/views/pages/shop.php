<?php
declare(strict_types=1);

$pageTitle = 'Shop';
$pageDescription = 'Computers and accessories — browse by category, shop online, and check out securely.';

$categories = product_categories();
$trending = products_recent(6);

require __DIR__ . '/../partials/header.php';
?>

<section class="shop-hero">
    <div class="container shop-hero-inner">
        <div class="shop-hero-copy">
            <span class="eyebrow" style="color:var(--color-accent);">Discover. Shop. Upgrade.</span>
            <h1><?= e(content_block('shop.hero_title', 'Computers & Accessories')) ?></h1>
            <p><?= e(content_block('shop.hero_subtitle', 'Genuine gadgets and accessories, in stock now, with secure checkout and fast local delivery.')) ?></p>
            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:24px;">
                <a href="#categories" class="btn btn-primary">Shop Now</a>
                <a href="<?= path('contact') ?>" class="btn btn-outline btn-outline-light">Ask a Question</a>
            </div>
        </div>
        <div class="shop-hero-art" aria-hidden="true">🖥️</div>
    </div>
</section>

<section class="section" id="categories">
    <div class="container">
        <span class="eyebrow">Browse</span>
        <h2>Shop by Category</h2>

        <?php if (!$categories): ?>
            <p style="color:var(--color-text-muted);">No categories yet — check back soon.</p>
        <?php else: ?>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <a class="category-card" href="<?= path('shop/' . e($category['slug'])) ?>">
                        <div class="category-card-img">
                            <?php if ($category['image_path']): ?>
                                <img src="<?= asset(e($category['image_path'])) ?>" alt="<?= e($category['name']) ?>">
                            <?php else: ?>
                                <span class="category-card-placeholder">🖥️</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($category['name']) ?></h3>
                        <p><?= (int) $category['product_count'] ?> item<?= (int) $category['product_count'] === 1 ? '' : 's' ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($trending): ?>
<section class="section section-soft">
    <div class="container">
        <div class="admin-header-row" style="margin-bottom:12px;">
            <div>
                <span class="eyebrow">Just In</span>
                <h2 style="margin-bottom:0;">Trending Products</h2>
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
                    <p class="product-card-price"><?= format_naira((float) $product['price']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="trust-badges">
    <div class="container trust-badges-grid">
        <div class="trust-badge">
            <span class="trust-badge-icon">✅</span>
            <div><strong>100% Genuine</strong><span>Authentic products only</span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🔒</span>
            <div><strong>Secure Checkout</strong><span>Paystack-protected payments</span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🚚</span>
            <div><strong>Fast Delivery</strong><span>Quick local dispatch</span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🎧</span>
            <div><strong>Real Support</strong><span>We answer, quote your order ref</span></div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
