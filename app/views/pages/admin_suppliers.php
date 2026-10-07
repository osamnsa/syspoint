<?php
declare(strict_types=1);

$adminUser = require_admin();

$suppliers = db()->query(
    "SELECT s.*,
            (SELECT COUNT(*) FROM purchase_orders po WHERE po.supplier_id = s.id) AS po_count,
            (SELECT COALESCE(SUM(i.qty_received * i.unit_cost), 0) FROM purchase_order_items i
               JOIN purchase_orders po ON po.id = i.po_id WHERE po.supplier_id = s.id) AS received_value
     FROM suppliers s ORDER BY s.is_active DESC, s.name"
)->fetchAll();

$pageTitle = 'Suppliers';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Suppliers</h1>
    <a href="<?= path('admin/suppliers/new') ?>" class="btn btn-primary btn-sm">Add Supplier</a>
</div>

<div class="admin-table-wrap">
    <?php if ($suppliers): ?>
        <table class="admin-table">
            <thead><tr><th>Supplier</th><th>Contact</th><th>Phone</th><th>Purchase orders</th><th>Bought (received)</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><strong><?= e($s['name']) ?></strong><?php if ($s['email']): ?><br><small class="muted"><?= e($s['email']) ?></small><?php endif; ?></td>
                    <td><?= e($s['contact_name'] ?: '—') ?></td>
                    <td><?= $s['phone'] ? '<a href="tel:' . e($s['phone']) . '">' . e($s['phone']) . '</a>' : '—' ?></td>
                    <td><?= (int) $s['po_count'] ?></td>
                    <td><?= format_naira((float) $s['received_value']) ?></td>
                    <td><?= $s['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-muted">Inactive</span>' ?></td>
                    <td style="white-space:nowrap;">
                        <a href="<?= path('admin/purchase-orders/new') ?>?supplier=<?= (int) $s['id'] ?>" class="btn btn-outline btn-sm">New PO</a>
                        <a href="<?= path('admin/suppliers/' . (int) $s['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No suppliers yet. <a href="<?= path('admin/suppliers/new') ?>">Add the first one</a>.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
