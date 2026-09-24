<?php
declare(strict_types=1);

/** @var array $params [order_ref] */

$order = order_by_ref($params[0] ?? '');
if (!$order) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$items = order_items_for((int) $order['id']);
$isPaid = $order['payment_status'] === 'paid';

$pageTitle = 'Order ' . $order['order_ref'];

require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / Order <?= e($order['order_ref']) ?></div>
        <h1><?= $isPaid ? 'Thank You for Your Order!' : 'Order Received' ?></h1>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:640px;">
        <?php if ($isPaid): ?>
            <div class="alert alert-success">Payment confirmed. A receipt has been sent to <?= e($order['customer_email']) ?>.</div>
        <?php else: ?>
            <div class="alert alert-error">
                This order hasn't been paid for yet.
                <a href="<?= path('checkout/pay/' . $order['order_ref']) ?>">Complete payment</a>.
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="admin-header-row">
                <h3 style="margin:0;">Order <?= e($order['order_ref']) ?></h3>
                <span class="badge <?= $isPaid ? 'badge-success' : 'badge-warning' ?>"><?= e(ucfirst($order['status'])) ?></span>
            </div>

            <?php foreach ($items as $item): ?>
                <div class="order-summary-line">
                    <span><?= e($item['product_name']) ?> &times; <?= (int) $item['quantity'] ?></span>
                    <span><?= format_naira((float) $item['unit_price'] * (int) $item['quantity']) ?></span>
                </div>
            <?php endforeach; ?>
            <div class="order-summary-line order-summary-total">
                <span>Total</span>
                <span><?= format_naira((float) $order['subtotal']) ?></span>
            </div>

            <p style="margin-top:20px;color:var(--color-text-muted);font-size:0.9rem;">
                Delivering to: <?= e($order['delivery_address']) ?><br>
                Phone: <?= e($order['customer_phone']) ?>
            </p>
        </div>
    </div>
</section>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
