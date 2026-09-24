<?php
declare(strict_types=1);

$pageTitle = 'Shop';
$pageDescription = 'Computers and accessories — browse by category, shop online, and check out securely.';

$categories = product_categories();
$trending = products_recent(6);

require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">

<section class="shop-hero">
    <div class="container shop-hero-inner">
        <div class="shop-hero-copy">
            <span class="eyebrow">Discover. Shop. Upgrade.</span>
            <h1><?= e(content_block('shop.hero_title_prefix', 'Latest Tech')) ?> <span class="shop-hero-highlight"><?= e(content_block('shop.hero_title_highlight', 'Gadgets')) ?></span></h1>
            <p><?= e(content_block('shop.hero_subtitle', 'Genuine gadgets and accessories, in stock now, with secure checkout and fast local delivery.')) ?></p>
            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:24px;">
                <a href="#categories" class="btn btn-primary">Shop Now →</a>
                <a href="#trending" class="btn btn-outline btn-outline-light">Browse Collection</a>
            </div>
        </div>
        <div class="shop-hero-art" aria-hidden="true">
            <svg viewBox="0 0 440 380" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Illustration of a laptop and phone showing a shopping app">
                <defs>
                    <linearGradient id="heroRed" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#ff4d5e"/>
                        <stop offset="100%" stop-color="#c8102e"/>
                    </linearGradient>
                    <linearGradient id="heroBase" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#3a3d46"/>
                        <stop offset="100%" stop-color="#1c1e24"/>
                    </linearGradient>
                    <radialGradient id="heroShadow" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#000000" stop-opacity="0.35"/>
                        <stop offset="100%" stop-color="#000000" stop-opacity="0"/>
                    </radialGradient>
                </defs>

                <ellipse cx="210" cy="248" rx="150" ry="20" fill="url(#heroShadow)"/>

                <circle cx="48" cy="60" r="5" fill="#e0142c" opacity="0.5"/>
                <circle cx="400" cy="240" r="4" fill="#ffffff" opacity="0.25"/>
                <circle cx="30" cy="220" r="3" fill="#ffffff" opacity="0.2"/>

                <!-- Earbuds case -->
                <rect x="36" y="140" width="56" height="40" rx="12" fill="#f4f4f6"/>
                <circle cx="56" cy="160" r="6" fill="#d8dadf"/>
                <circle cx="76" cy="160" r="6" fill="#d8dadf"/>

                <!-- Laptop base -->
                <path d="M60 214 L360 214 L378 234 Q380 238 375 238 L45 238 Q40 238 42 234 Z" fill="url(#heroBase)"/>
                <rect x="196" y="216" width="28" height="4" rx="2" fill="#0d0e12"/>

                <!-- Laptop screen -->
                <rect x="66" y="30" width="288" height="188" rx="14" fill="#15171d"/>
                <rect x="80" y="44" width="260" height="156" rx="4" fill="#fbfafa"/>

                <!-- Screen: nav bar -->
                <rect x="80" y="44" width="260" height="18" fill="#14161c"/>
                <circle cx="92" cy="53" r="3" fill="#ff5a5f"/>
                <circle cx="104" cy="53" r="3" fill="#ffd257"/>
                <circle cx="116" cy="53" r="3" fill="#4ade80"/>

                <!-- Screen: hero banner -->
                <rect x="94" y="76" width="118" height="60" rx="6" fill="url(#heroRed)"/>
                <rect x="106" y="90" width="60" height="6" rx="3" fill="#ffffff" opacity="0.9"/>
                <rect x="106" y="102" width="80" height="5" rx="2.5" fill="#ffffff" opacity="0.6"/>
                <rect x="106" y="118" width="36" height="12" rx="6" fill="#0d0e12"/>

                <!-- Screen: product thumbnails -->
                <rect x="224" y="76" width="42" height="42" rx="5" fill="#eceef1"/>
                <circle cx="245" cy="97" r="9" fill="#5b8def"/>
                <rect x="272" y="76" width="42" height="42" rx="5" fill="#eceef1"/>
                <circle cx="293" cy="97" r="9" fill="#4ade80"/>
                <rect x="224" y="122" width="90" height="14" rx="4" fill="#f1f2f4"/>
                <rect x="230" y="126" width="30" height="6" rx="3" fill="#e0142c"/>

                <!-- Screen: price chips row -->
                <rect x="94" y="150" width="52" height="16" rx="8" fill="#fdecec"/>
                <rect x="102" y="155" width="30" height="6" rx="3" fill="#e0142c"/>
                <rect x="154" y="150" width="52" height="16" rx="8" fill="#eceef1"/>
                <rect x="162" y="155" width="30" height="6" rx="3" fill="#8a8f9a"/>
                <rect x="214" y="150" width="52" height="16" rx="8" fill="#eceef1"/>
                <rect x="222" y="155" width="30" height="6" rx="3" fill="#8a8f9a"/>

                <!-- Floating phone -->
                <rect x="294" y="150" width="74" height="140" rx="16" fill="#15171d"/>
                <rect x="300" y="162" width="62" height="104" rx="6" fill="#fbfafa"/>
                <circle cx="331" cy="172" r="2.5" fill="#c7c9cf"/>
                <rect x="308" y="184" width="46" height="46" rx="6" fill="url(#heroRed)"/>
                <rect x="308" y="238" width="46" height="6" rx="3" fill="#eceef1"/>
                <rect x="308" y="248" width="30" height="6" rx="3" fill="#eceef1"/>
                <rect x="308" y="262" width="46" height="14" rx="7" fill="#0d0e12"/>
            </svg>
        </div>
    </div>
</section>

<section class="section" id="categories">
    <div class="container">
        <span class="eyebrow">Browse</span>
        <h2>Shop by Category</h2>

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
                    <p class="product-card-price"><?= format_naira((float) $product['price']) ?><?= $product['is_demo'] ? demo_badge() : '' ?></p>
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

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
