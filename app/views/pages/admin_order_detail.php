<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$orderId = (int) ($params[0] ?? 0);
$stmt = db()->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $allowedStatuses = ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'];
    $newStatus = (string) ($_POST['status'] ?? '');
    if (in_array($newStatus, $allowedStatuses, true)) {
        order_update_status($orderId, $newStatus);
        flash('success', 'Order status updated.');
        header('Location: ' . path('admin/orders/' . $orderId));
        exit;
    }
}

$items = order_items_for($orderId);
$successMessage = flash('success');

$pageTitle = 'Order ' . $order['order_ref'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Order <?= e($order['order_ref']) ?></h1>
    <a href="<?= path('admin/orders') ?>" class="btn btn-outline btn-sm">Back to Orders</a>
</div>

<?php if ($successMessage): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-top:0;">Customer</h3>
    <p style="margin:0;">
        <?= e($order['customer_name']) ?><br>
        <?= e($order['customer_email']) ?><br>
        <?= e($order['customer_phone']) ?><br>
        <?= nl2br(e($order['delivery_address'])) ?>
    </p>
</div>

<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-top:0;">Items</h3>
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
</div>

<div class="card">
    <h3 style="margin-top:0;">Status</h3>
    <p>
        Payment: <span class="badge <?= $order['payment_status'] === 'paid' ? 'badge-success' : 'badge-muted' ?>"><?= e(ucfirst($order['payment_status'])) ?></span>
        <?php if ($order['payment_reference']): ?>
            <span class="form-note">Ref: <?= e($order['payment_reference']) ?></span>
        <?php endif; ?>
    </p>
    <form method="post" action="<?= path('admin/orders/' . $orderId) ?>" style="display:flex;gap:10px;align-items:center;">
        <?= csrf_field() ?>
        <select name="status">
            <?php foreach (['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
    </form>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
