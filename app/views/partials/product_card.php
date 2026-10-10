<?php /** One product tile. Set $product (with category_slug) before including. */ ?>
<a class="product-card" href="<?= path('shop/' . e($product['category_slug']) . '/' . e($product['slug'])) ?>">
    <div class="product-card-img">
        <?php if ($product['image_path']): ?>
            <img src="<?= media_url($product['image_path']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
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
