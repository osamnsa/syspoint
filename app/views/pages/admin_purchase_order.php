<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$poId = (int) ($params[0] ?? 0);
$po = po_by_id($poId);
if (!$po) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'receive') {
            $serials = [];
            foreach ((array) ($_POST['serials'] ?? []) as $itemId => $text) {
                $serials[(int) $itemId] = preg_split('/[\r\n,]+/', (string) $text) ?: [];
            }
            po_receive($poId, array_map('intval', (array) ($_POST['receive'] ?? [])), $serials);
            flash('success', 'Stock received and added to inventory.');
        } elseif ($action === 'ordered' && $po['status'] === 'draft') {
            db()->prepare("UPDATE purchase_orders SET status = 'ordered', order_date = COALESCE(order_date, CURDATE()) WHERE id = :id")->execute(['id' => $poId]);
            flash('success', 'Marked as ordered.');
        } elseif ($action === 'cancel' && in_array($po['status'], ['draft', 'ordered'], true)) {
            db()->prepare("UPDATE purchase_orders SET status = 'cancelled' WHERE id = :id")->execute(['id' => $poId]);
            flash('success', 'Purchase order cancelled.');
        }
        header('Location: ' . path('admin/purchase-orders/' . $poId));
        exit;
    } catch (InventoryException $ex) {
        $error = $ex->getMessage();
    }
}

$items = po_items($poId);
$total = array_sum(array_map(fn($i) => $i['qty_ordered'] * $i['unit_cost'], $items));
$receivedValue = array_sum(array_map(fn($i) => $i['qty_received'] * $i['unit_cost'], $items));
$canReceive = in_array($po['status'], ['draft', 'ordered', 'partially_received'], true)
    && array_sum(array_map(fn($i) => $i['qty_ordered'] - $i['qty_received'], $items)) > 0;
$statusBadge = ['draft' => 'badge-muted', 'ordered' => 'badge-warning', 'partially_received' => 'badge-warning', 'received' => 'badge-success', 'cancelled' => 'badge-muted'][$po['status']];

$pageTitle = $po['po_number'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($po['po_number']) ?> <span class="badge <?= $statusBadge ?>" style="vertical-align:middle;"><?= e(PO_STATUSES[$po['status']]) ?></span></h1>
    <div class="admin-header-actions">
        <?php if ($po['status'] === 'draft'): ?><a href="<?= path('admin/purchase-orders/' . $poId . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a><?php endif; ?>
        <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">Print</button>
        <a href="<?= path('admin/purchase-orders') ?>" class="btn btn-outline btn-sm">All Purchase Orders</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="card admin-doc">
    <div class="admin-doc-meta">
        <div><span>Supplier</span><strong><?= e($po['supplier_name']) ?></strong><?= $po['supplier_phone'] ? '<small>' . e($po['supplier_phone']) . '</small>' : '' ?></div>
        <div><span>Ordered</span><strong><?= $po['order_date'] ? e((new DateTimeImmutable($po['order_date']))->format('j M Y')) : '—' ?></strong></div>
        <div><span>Expected</span><strong><?= $po['expected_date'] ? e((new DateTimeImmutable($po['expected_date']))->format('j M Y')) : '—' ?></strong></div>
        <div><span>Total</span><strong><?= format_naira($total) ?></strong><small>Received: <?= format_naira($receivedValue) ?></small></div>
    </div>
    <?php if ($po['notes']): ?><p class="admin-doc-notes"><?= nl2br(e($po['notes'])) ?></p><?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="receive">
        <div class="admin-table-wrap admin-table-plain">
        <table class="admin-table">
            <thead><tr><th>Product</th><th>Ordered</th><th>Received</th><th>Unit cost</th><th>Line total</th><?php if ($canReceive): ?><th>Receive now</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($items as $i):
                $left = (int) $i['qty_ordered'] - (int) $i['qty_received']; ?>
                <tr>
                    <td><a href="<?= path('admin/inventory/products/' . (int) $i['product_id']) ?>"><?= e($i['product_name']) ?></a><?= $i['sku'] ? '<br><small class="muted">' . e($i['sku']) . '</small>' : '' ?><?= $i['track_serials'] ? ' <span class="badge badge-muted">Serials</span>' : '' ?></td>
                    <td><?= (int) $i['qty_ordered'] ?></td>
                    <td><?= (int) $i['qty_received'] ?></td>
                    <td><?= format_naira((float) $i['unit_cost']) ?></td>
                    <td><?= format_naira($i['qty_ordered'] * $i['unit_cost']) ?></td>
                    <?php if ($canReceive): ?>
                        <td style="min-width:200px;">
                            <?php if ($left > 0): ?>
                                <input type="number" name="receive[<?= (int) $i['id'] ?>]" value="0" min="0" max="<?= $left ?>" style="width:90px;"> <small class="muted">of <?= $left ?></small>
                                <?php if ($i['track_serials']): ?>
                                    <textarea name="serials[<?= (int) $i['id'] ?>]" rows="2" placeholder="Serial / IMEI numbers, one per line" style="margin-top:6px;min-height:60px;font-family:var(--font-mono);font-size:0.8rem;"></textarea>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-success">Complete</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if ($canReceive): ?>
            <div class="admin-form-actions"><button type="submit" class="btn btn-primary">Receive Stock</button></div>
        <?php endif; ?>
    </form>
</div>

<?php if (in_array($po['status'], ['draft', 'ordered'], true)): ?>
<div class="admin-form-actions">
    <?php if ($po['status'] === 'draft'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="ordered"><button type="submit" class="btn btn-primary btn-sm">Mark as Ordered</button></form>
    <?php endif; ?>
    <?php if (!array_sum(array_column($items, 'qty_received'))): ?>
        <form method="post" onsubmit="return confirm('Cancel this purchase order?');"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark">Cancel Order</button></form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
