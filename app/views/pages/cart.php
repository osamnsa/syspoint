<?php
declare(strict_types=1);

$pageTitle = 'Your Cart';

$items = cart_items();
$subtotal = cart_subtotal();

require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / Cart</div>
        <h1>Your Cart</h1>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!$items): ?>
            <p style="color:var(--color-text-muted);">Your cart is empty. <a href="<?= path('shop') ?>">Browse the shop</a>.</p>
        <?php else: ?>
            <div class="cart-table-wrap">
                <table class="cart-table">
                    <thead>
                        <tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php $p = $item['product']; ?>
                            <tr>
                                <td>
                                    <div class="cart-item">
                                        <?php if ($p['image_path']): ?>
                                            <img src="<?= asset(e($p['image_path'])) ?>" alt="">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= e($p['name']) ?></strong>
                                            <?php if ($item['stock_limited']): ?>
                                                <div class="form-note">Quantity reduced to what's in stock.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= format_naira((float) $p['price']) ?></td>
                                <td>
                                    <form method="post" action="<?= path('cart/update') ?>" class="cart-qty-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                        <input type="number" name="qty" value="<?= (int) $item['qty'] ?>" min="1" max="<?= (int) $p['stock_qty'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm">Update</button>
                                    </form>
                                </td>
                                <td><?= format_naira((float) $item['line_total']) ?></td>
                                <td>
                                    <form method="post" action="<?= path('cart/update') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" name="remove" value="1" class="btn btn-outline btn-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-summary">
                <span>Subtotal</span>
                <strong><?= format_naira($subtotal) ?></strong>
            </div>
            <div style="text-align:right;margin-top:16px;">
                <a href="<?= path('checkout') ?>" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </div>
</section>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
