<?php
declare(strict_types=1);

$adminUser = require_admin();

$from = (string) ($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
$to = (string) ($_GET['to'] ?? date('Y-m-d'));
if (!DateTimeImmutable::createFromFormat('Y-m-d', $from)) $from = date('Y-m-d', strtotime('-30 days'));
if (!DateTimeImmutable::createFromFormat('Y-m-d', $to)) $to = date('Y-m-d');

$stmt = db()->prepare("SELECT s.*, u.name AS cashier_name,
        (SELECT COALESCE(SUM(quantity), 0) FROM pos_sale_items WHERE sale_id = s.id) AS units
    FROM pos_sales s LEFT JOIN users u ON u.id = s.cashier_id
    WHERE s.created_at BETWEEN :f AND :t ORDER BY s.id DESC");
$stmt->execute(['f' => $from . ' 00:00:00', 't' => $to . ' 23:59:59']);
$sales = $stmt->fetchAll();
$completed = array_filter($sales, fn($s) => $s['status'] === 'completed');
$byMethod = [];
foreach ($completed as $s) $byMethod[$s['payment_method']] = ($byMethod[$s['payment_method']] ?? 0) + (float) $s['total'];

$pageTitle = 'Walk-in Sales';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Point of Sale</p><h1>Walk-in Sales</h1></div>
    <a href="<?= path('admin/pos') ?>" class="btn btn-primary btn-sm">New Sale</a>
</div>

<div class="admin-toolbar">
    <form method="get" class="admin-daterange">
        <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
        <button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark">Show</button>
    </form>
</div>

<div class="pos-today">
    <div><span>Sales</span><strong><?= count($completed) ?></strong></div>
    <div><span>Takings</span><strong><?= format_naira(array_sum(array_column($completed, 'total'))) ?></strong></div>
    <?php foreach (PAYMENT_METHODS as $k => $label): ?>
        <div><span><?= e($label) ?></span><strong><?= format_naira($byMethod[$k] ?? 0) ?></strong></div>
    <?php endforeach; ?>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Receipt</th><th>When</th><th>Customer</th><th>Items</th><th>Payment</th><th>Total</th><th>Cashier</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($sales as $s): ?>
            <tr class="is-clickable" data-href="<?= path('admin/pos/sales/' . (int) $s['id']) ?>">
                <td><a href="<?= path('admin/pos/sales/' . (int) $s['id']) ?>"><strong><?= e($s['receipt_no']) ?></strong></a></td>
                <td style="white-space:nowrap;"><?= e((new DateTimeImmutable($s['created_at']))->format('j M, g:i A')) ?></td>
                <td><?= e($s['customer_name'] ?: 'Walk-in') ?></td>
                <td><?= (int) $s['units'] ?></td>
                <td><?= e(PAYMENT_METHODS[$s['payment_method']]) ?></td>
                <td><?= format_naira((float) $s['total']) ?></td>
                <td><?= e($s['cashier_name'] ?? '—') ?></td>
                <td><?= $s['status'] === 'completed' ? '<span class="badge badge-success">Completed</span>' : '<span class="badge badge-muted">Voided</span>' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$sales): ?><tr><td colspan="8" class="admin-empty">No walk-in sales in this period.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
