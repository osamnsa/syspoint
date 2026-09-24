<?php
declare(strict_types=1);

/** @var array $params [order_ref] */

$order = order_by_ref($params[0] ?? '');
if (!$order) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

if ($order['payment_status'] === 'paid') {
    header('Location: ' . path('order/' . $order['order_ref']));
    exit;
}

$result = paystack_initialize(
    $order['customer_email'],
    (float) $order['subtotal'],
    $order['order_ref'],
    url('checkout/callback'),
    ['order_id' => $order['id']]
);

if ($result['ok'] && $result['authorization_url']) {
    header('Location: ' . $result['authorization_url']);
    exit;
}

$pageTitle = 'Complete Payment';
require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">

<section class="page-header">
    <div class="container">
        <h1>Payment Unavailable Right Now</h1>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:560px;">
        <div class="alert alert-error"><?= e($result['error'] ?? 'We could not start the payment. Please try again.') ?></div>

        <div class="card">
            <p>Your order <strong><?= e($order['order_ref']) ?></strong> has been saved and your items are held —
               it just hasn't been paid for yet. You can try the payment again, or contact us quoting your order
               reference and we'll help you complete it.</p>
            <a href="<?= path('checkout/pay/' . $order['order_ref']) ?>" class="btn btn-primary">Try Again</a>
            <a href="<?= path('contact') ?>" class="btn btn-outline" style="margin-top:10px;">Contact Us</a>
        </div>
    </div>
</section>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
