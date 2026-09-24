<?php
declare(strict_types=1);

/** @var array $params [category_slug, product_slug] */

$product = product_by_slug($params[1] ?? '');
if (!$product || $product['category_slug'] !== ($params[0] ?? '')) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$gallery = product_gallery_images((int) $product['id']);
$inStock = (int) $product['stock_qty'] > 0;

$pageTitle = $product['name'];
$pageDescription = $product['name'] . ' — ' . format_naira((float) $product['price']);

$added = flash('cart_added');

require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">

<section class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= path() ?>">Home</a> / <a href="<?= path('shop') ?>">Shop</a> /
            <a href="<?= path('shop/' . e($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a> /
            <?= e($product['name']) ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($added): ?>
            <div class="alert alert-success"><?= e($added) ?> <a href="<?= path('cart') ?>">View cart</a></div>
        <?php endif; ?>

        <div class="product-detail">
            <div class="product-detail-gallery">
                <div class="product-detail-img">
                    <?php if ($product['image_path']): ?>
                        <img src="<?= asset(e($product['image_path'])) ?>" alt="<?= e($product['name']) ?>">
                    <?php else: ?>
                        <span class="product-card-placeholder">📦</span>
                    <?php endif; ?>
                </div>
                <?php if ($gallery): ?>
                    <div class="product-detail-thumbs">
                        <?php foreach ($gallery as $imagePath): ?>
                            <img src="<?= asset(e($imagePath)) ?>" alt="">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-detail-info">
                <span class="eyebrow"><?= e($product['category_name']) ?></span>
                <h1><?= e($product['name']) ?></h1>
                <p class="product-detail-price"><?= format_naira((float) $product['price']) ?></p>

                <?php if ($inStock): ?>
                    <span class="badge badge-success">In Stock (<?= (int) $product['stock_qty'] ?> available)</span>
                <?php else: ?>
                    <span class="badge badge-muted">Out of Stock</span>
                <?php endif; ?>

                <?php if ($product['description']): ?>
                    <p style="margin-top:18px;"><?= nl2br(e($product['description'])) ?></p>
                <?php endif; ?>

                <?php if ($product['specs']): ?>
                    <h3 style="margin-top:22px;font-size:1rem;">Specs</h3>
                    <p style="color:var(--color-text-muted);"><?= nl2br(e($product['specs'])) ?></p>
                <?php endif; ?>

                <?php if ($inStock): ?>
                    <form method="post" action="<?= path('cart/add') ?>" class="add-to-cart-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="redirect_to" value="<?= e(path('shop/' . $product['category_slug'] . '/' . $product['slug'])) ?>">
                        <label for="qty">Quantity</label>
                        <input type="number" id="qty" name="qty" value="1" min="1" max="<?= (int) $product['stock_qty'] ?>">
                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
