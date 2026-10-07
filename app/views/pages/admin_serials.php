<?php
declare(strict_types=1);

$adminUser = require_admin();

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$sql = 'SELECT u.*, p.name AS product_name, s.receipt_no FROM product_units u JOIN products p ON p.id = u.product_id
        LEFT JOIN pos_sales s ON u.sale_type = \'pos\' AND s.id = u.sale_id WHERE 1 = 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (u.serial LIKE :q OR p.name LIKE :q2 OR u.customer_name LIKE :q3 OR u.customer_phone LIKE :q4)';
    $params += ['q' => "%$q%", 'q2' => "%$q%", 'q3' => "%$q%", 'q4' => "%$q%"];
}
if ($status === 'warranty') {
    $sql .= " AND u.status = 'sold' AND u.warranty_until >= CURDATE()";
} elseif (in_array($status, ['in_stock', 'sold', 'returned', 'faulty'], true)) {
    $sql .= ' AND u.status = :st';
    $params['st'] = $status;
}
$stmt = db()->prepare($sql . ' ORDER BY u.id DESC LIMIT 300');
$stmt->execute($params);
$units = $stmt->fetchAll();

$pageTitle = 'Serials & Warranty';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row"><div><p class="admin-kicker">Inventory</p><h1>Serials &amp; Warranty</h1></div></div>
<p class="form-note" style="margin:-8px 0 16px;">Look up any laptop or phone by serial / IMEI, customer name or phone — see where it is, who bought it and whether it’s under warranty.</p>

<div class="admin-toolbar">
    <nav class="admin-filters" aria-label="Filter">
        <?php foreach (['' => 'All', 'in_stock' => 'In stock', 'sold' => 'Sold', 'warranty' => 'Under warranty', 'returned' => 'Returned', 'faulty' => 'Faulty'] as $k => $label): ?>
            <a href="<?= path('admin/serials') . '?' . http_build_query(array_filter(['status' => $k, 'q' => $q])) ?>"<?= $status === $k ? ' class="is-active"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" class="admin-search">
        <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Serial, IMEI, customer or phone" aria-label="Search serials" autofocus>
    </form>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Serial / IMEI</th><th>Product</th><th>Status</th><th>Customer</th><th>Sold</th><th>Warranty</th></tr></thead>
        <tbody>
        <?php foreach ($units as $u): $w = warranty_status($u['warranty_until']); ?>
            <tr>
                <td><code><?= e($u['serial']) ?></code></td>
                <td><a href="<?= path('admin/inventory/products/' . (int) $u['product_id']) ?>"><?= e($u['product_name']) ?></a></td>
                <td><span class="badge <?= ['in_stock' => 'badge-success', 'sold' => 'badge-muted', 'returned' => 'badge-warning', 'faulty' => 'badge-danger'][$u['status']] ?>"><?= e(str_replace('_', ' ', $u['status'])) ?></span></td>
                <td><?= e($u['customer_name'] ?: '—') ?><?= $u['customer_phone'] ? '<br><small class="muted">' . e($u['customer_phone']) . '</small>' : '' ?></td>
                <td><?= $u['sold_at'] ? e((new DateTimeImmutable($u['sold_at']))->format('j M Y')) : '—' ?><?= $u['receipt_no'] ? '<br><small><a href="' . path('admin/pos/sales/' . (int) $u['sale_id']) . '">' . e($u['receipt_no']) . '</a></small>' : '' ?></td>
                <td><?= $u['status'] === 'sold' ? '<span class="badge ' . $w['class'] . '">' . e($w['label']) . '</span>' : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$units): ?><tr><td colspan="6" class="admin-empty"><?= $q !== '' ? 'No unit matches “' . e($q) . '”.' : 'No serial-tracked units yet. Tick “Track serial / IMEI numbers” on a product, then receive units on a purchase order.' ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
