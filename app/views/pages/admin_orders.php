<?php
declare(strict_types=1);

$adminUser = require_admin();

$orders = db()->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Orders';
require __DIR__ . '/../partials/admin_header.php';

$statusBadge = [
    'pending' => 'badge-warning',
    'paid' => 'badge-success',
    'processing' => 'badge-warning',
    'shipped' => 'badge-success',
    'completed' => 'badge-success',
    'cancelled' => 'badge-muted',
];
?>

<div class="admin-header-row">
    <h1>Orders</h1>
</div>

<div class="admin-table-wrap">
    <?php if ($orders): ?>
        <table class="admin-table">
            <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= e($order['order_ref']) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= format_naira((float) $order['subtotal']) ?></td>
                        <td><span class="badge <?= $order['payment_status'] === 'paid' ? 'badge-success' : 'badge-muted' ?>"><?= e(ucfirst($order['payment_status'])) ?></span></td>
                        <td><span class="badge <?= $statusBadge[$order['status']] ?? 'badge-muted' ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                        <td><?= e((new DateTimeImmutable($order['created_at']))->format('M j, g:i A')) ?></td>
                        <td><a href="<?= path('admin/orders/' . (int) $order['id']) ?>" class="btn btn-outline btn-sm">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No orders yet.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
