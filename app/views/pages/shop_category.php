<?php
declare(strict_types=1);

/** @var array $params [category_slug] */

$category = product_category_by_slug($params[0] ?? '');
if (!$category) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$products = products_in_category((int) $category['id']);

$pageTitle = $category['name'];
$pageDescription = 'Browse ' . $category['name'] . ' — photos, specs, and current prices.';

require __DIR__ . '/../partials/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / <a href="<?= path('shop') ?>">Shop</a> / <?= e($category['name']) ?></div>
        <h1><?= e($category['name']) ?></h1>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!$products): ?>
            <p style="color:var(--color-text-muted);">Nothing in this category right now — check back soon.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <a class="product-card" href="<?= path('shop/' . e($category['slug']) . '/' . e($product['slug'])) ?>">
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
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
