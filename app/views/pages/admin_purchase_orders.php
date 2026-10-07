<?php
declare(strict_types=1);

$adminUser = require_admin();

$status = (string) ($_GET['status'] ?? '');
$sql = 'SELECT po.*, s.name AS supplier_name,
               (SELECT COALESCE(SUM(qty_ordered * unit_cost), 0) FROM purchase_order_items WHERE po_id = po.id) AS total,
               (SELECT COALESCE(SUM(qty_ordered), 0) FROM purchase_order_items WHERE po_id = po.id) AS units,
               (SELECT COALESCE(SUM(qty_received), 0) FROM purchase_order_items WHERE po_id = po.id) AS received
        FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id';
$params = [];
if ($status === 'open') {
    $sql .= " WHERE po.status IN ('draft', 'ordered', 'partially_received')";
} elseif (isset(PO_STATUSES[$status])) {
    $sql .= ' WHERE po.status = :status';
    $params['status'] = $status;
}
$stmt = db()->prepare($sql . ' ORDER BY po.id DESC');
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Purchase Orders';
require __DIR__ . '/../partials/admin_header.php';
$badge = ['draft' => 'badge-muted', 'ordered' => 'badge-warning', 'partially_received' => 'badge-warning', 'received' => 'badge-success', 'cancelled' => 'badge-muted'];
?>

<div class="admin-header-row">
    <h1>Purchase Orders</h1>
    <a href="<?= path('admin/purchase-orders/new') ?>" class="btn btn-primary btn-sm">New Purchase Order</a>
</div>

<nav class="admin-filters" aria-label="Filter">
    <?php foreach (['' => 'All', 'open' => 'Open'] + PO_STATUSES as $key => $label): ?>
        <a href="<?= path('admin/purchase-orders') . ($key !== '' ? '?status=' . $key : '') ?>"<?= $status === $key ? ' class="is-active"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

<div class="admin-table-wrap">
    <?php if ($orders): ?>
        <table class="admin-table">
            <thead><tr><th>PO</th><th>Supplier</th><th>Ordered</th><th>Expected</th><th>Units (received)</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr class="is-clickable" data-href="<?= path('admin/purchase-orders/' . (int) $o['id']) ?>">
                    <td><a href="<?= path('admin/purchase-orders/' . (int) $o['id']) ?>"><strong><?= e($o['po_number']) ?></strong></a></td>
                    <td><?= e($o['supplier_name']) ?></td>
                    <td><?= $o['order_date'] ? e((new DateTimeImmutable($o['order_date']))->format('j M Y')) : '—' ?></td>
                    <td><?= $o['expected_date'] ? e((new DateTimeImmutable($o['expected_date']))->format('j M Y')) : '—' ?></td>
                    <td><?= (int) $o['units'] ?> (<?= (int) $o['received'] ?>)</td>
                    <td><?= format_naira((float) $o['total']) ?></td>
                    <td><span class="badge <?= $badge[$o['status']] ?>"><?= e(PO_STATUSES[$o['status']]) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No purchase orders<?= $status ? ' here' : ' yet' ?>. <a href="<?= path('admin/purchase-orders/new') ?>">Create one</a> to restock from a supplier.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
