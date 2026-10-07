<?php
declare(strict_types=1);

/** @var array $params [id] when editing a draft */

$adminUser = require_admin();

$poId = isset($params[0]) ? (int) $params[0] : 0;
$po = $poId ? po_by_id($poId) : null;
if ($poId && !$po) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}
if ($po && $po['status'] !== 'draft') {
    flash('success', 'Only draft purchase orders can be edited.');
    header('Location: ' . path('admin/purchase-orders/' . $poId));
    exit;
}

$suppliers = db()->query('SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name')->fetchAll();
$products = array_values(array_filter(inventory_products(), fn($p) => !(int) $p['is_demo']));

$values = [
    'supplier_id' => (int) ($po['supplier_id'] ?? ($_GET['supplier'] ?? 0)),
    'order_date' => $po['order_date'] ?? date('Y-m-d'),
    'expected_date' => $po['expected_date'] ?? '',
    'notes' => $po['notes'] ?? '',
];
$lines = $po ? array_map(fn($i) => ['product_id' => (int) $i['product_id'], 'qty' => (int) $i['qty_ordered'], 'cost' => $i['unit_cost']], po_items($poId)) : [];
if (!$lines && isset($_GET['product'])) {
    foreach ($products as $p) {
        if ((int) $p['id'] === (int) $_GET['product']) {
            $lines[] = ['product_id' => (int) $p['id'], 'qty' => max(1, (int) $p['reorder_level'] * 2 - (int) $p['stock_qty']), 'cost' => $p['cost_price'] ?? ''];
        }
    }
}
if (!$lines) $lines[] = ['product_id' => 0, 'qty' => 1, 'cost' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['supplier_id'] = (int) ($_POST['supplier_id'] ?? 0);
        $values['order_date'] = (string) ($_POST['order_date'] ?? '');
        $values['expected_date'] = (string) ($_POST['expected_date'] ?? '');
        $values['notes'] = trim((string) ($_POST['notes'] ?? ''));
        $lines = [];
        $ids = (array) ($_POST['product_id'] ?? []);
        foreach ($ids as $i => $pid) {
            $pid = (int) $pid;
            $qty = (int) ($_POST['qty'][$i] ?? 0);
            $cost = trim((string) ($_POST['cost'][$i] ?? ''));
            if (!$pid && $qty <= 1 && $cost === '') continue; // empty row
            $lines[] = ['product_id' => $pid, 'qty' => $qty, 'cost' => $cost];
            if (!$pid) $errors[] = 'Choose a product on every line.';
            if ($qty < 1) $errors[] = 'Quantities must be at least 1.';
            if (!is_numeric($cost) || (float) $cost < 0) $errors[] = 'Enter a unit cost on every line.';
        }
        $errors = array_values(array_unique($errors));
        if (!array_filter($suppliers, fn($s) => (int) $s['id'] === $values['supplier_id'])) $errors[] = 'Choose a supplier.';
        if (!$lines) $errors[] = 'Add at least one product.';
        foreach (['order_date', 'expected_date'] as $d) {
            if ($values[$d] !== '' && !DateTimeImmutable::createFromFormat('Y-m-d', $values[$d])) $errors[] = 'Please enter valid dates.';
        }

        if (!$errors) {
            $status = isset($_POST['mark_ordered']) ? 'ordered' : 'draft';
            $newId = inventory_tx(function (PDO $pdo) use ($po, $values, $lines, $status) {
                $data = [
                    'supplier' => $values['supplier_id'],
                    'od' => $values['order_date'] ?: null,
                    'ed' => $values['expected_date'] ?: null,
                    'notes' => $values['notes'] ?: null,
                    'status' => $status,
                ];
                if ($po) {
                    $pdo->prepare('UPDATE purchase_orders SET supplier_id = :supplier, order_date = :od, expected_date = :ed, notes = :notes, status = :status WHERE id = :id')
                        ->execute($data + ['id' => $po['id']]);
                    $pdo->prepare('DELETE FROM purchase_order_items WHERE po_id = :id')->execute(['id' => $po['id']]);
                    $id = (int) $po['id'];
                } else {
                    $pdo->prepare('INSERT INTO purchase_orders (po_number, supplier_id, order_date, expected_date, notes, status, created_by)
                                   VALUES (:num, :supplier, :od, :ed, :notes, :status, :uid)')
                        ->execute($data + ['num' => inventory_next_number('purchase_orders', 'po_number', 'PO', 4), 'uid' => admin_user()['id']]);
                    $id = (int) $pdo->lastInsertId();
                }
                $ins = $pdo->prepare('INSERT INTO purchase_order_items (po_id, product_id, qty_ordered, unit_cost) VALUES (:po, :p, :q, :c)');
                foreach ($lines as $l) $ins->execute(['po' => $id, 'p' => $l['product_id'], 'q' => $l['qty'], 'c' => $l['cost']]);
                return $id;
            });
            flash('success', $status === 'ordered' ? 'Purchase order saved and marked as ordered.' : 'Draft purchase order saved.');
            header('Location: ' . path('admin/purchase-orders/' . $newId));
            exit;
        }
    }
}

$pageTitle = $po ? 'Edit ' . $po['po_number'] : 'New Purchase Order';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path('admin/purchase-orders') ?>" class="btn btn-outline btn-sm">Back to Purchase Orders</a>
</div>

<?php if (!$suppliers): ?>
    <div class="alert alert-error alert-static">Add a supplier first — <a href="<?= path('admin/suppliers/new') ?>">Add Supplier</a>.</div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="admin-form admin-form-wide">
    <?= csrf_field() ?>
    <div class="form-row form-row-3">
        <div class="form-group">
            <label for="supplier_id">Supplier</label>
            <select id="supplier_id" name="supplier_id" required>
                <option value="">Choose…</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= $values['supplier_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="order_date">Order Date</label>
            <input type="date" id="order_date" name="order_date" value="<?= e((string) $values['order_date']) ?>">
        </div>
        <div class="form-group">
            <label for="expected_date">Expected Delivery</label>
            <input type="date" id="expected_date" name="expected_date" value="<?= e((string) $values['expected_date']) ?>">
        </div>
    </div>

    <table class="admin-table po-lines" data-repeater>
        <thead><tr><th>Product</th><th style="width:110px;">Qty</th><th style="width:170px;">Unit cost (₦)</th><th style="width:150px;">Line total</th><th style="width:40px;"></th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
            <tr data-repeater-row>
                <td>
                    <select name="product_id[]" data-po-product>
                        <option value="">Choose a product…</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" data-cost="<?= e((string) ($p['cost_price'] ?? '')) ?>" <?= $l['product_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?><?= $p['sku'] ? ' · ' . e($p['sku']) : '' ?> (<?= (int) $p['stock_qty'] ?> in stock)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" name="qty[]" value="<?= (int) $l['qty'] ?>" min="1" data-po-qty></td>
                <td><input type="number" name="cost[]" value="<?= e((string) $l['cost']) ?>" min="0" step="0.01" data-po-cost></td>
                <td data-po-total>—</td>
                <td><button type="button" class="admin-row-remove" data-repeater-remove aria-label="Remove line">×</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="3"><button type="button" class="btn btn-outline btn-sm" data-repeater-add>+ Add line</button></td><td colspan="2"><strong data-po-grand>—</strong></td></tr></tfoot>
    </table>

    <div class="form-group" style="margin-top:18px;">
        <label for="notes">Notes for the supplier / team</label>
        <textarea id="notes" name="notes" rows="2" style="min-height:70px;"><?= e((string) $values['notes']) ?></textarea>
    </div>
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-outline">Save as Draft</button>
        <button type="submit" name="mark_ordered" value="1" class="btn btn-primary">Save &amp; Mark Ordered</button>
    </div>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
