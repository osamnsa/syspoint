<?php
declare(strict_types=1);

/** @var array $params [product id] */

$adminUser = require_admin();

$productId = (int) ($params[0] ?? 0);
$product = product_by_id($productId);
if (!$product) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'adjust') {
            $reason = (string) ($_POST['reason'] ?? '');
            $mode = (string) ($_POST['mode'] ?? 'set');
            $qty = (string) ($_POST['qty'] ?? '');
            $note = trim((string) ($_POST['note'] ?? ''));
            if (!in_array($reason, STOCK_MANUAL_REASONS, true)) throw new InventoryException('Choose a reason.');
            if (!preg_match('/^\d+$/', $qty)) throw new InventoryException('Enter a whole number.');
            if ($product['track_serials']) {
                throw new InventoryException('For serial-tracked items, change the unit’s status below instead — stock follows the serials.');
            }
            $change = match ($mode) {
                'set' => (int) $qty - (int) $product['stock_qty'],
                'add' => (int) $qty,
                'remove' => -(int) $qty,
                default => 0,
            };
            if ($change === 0) throw new InventoryException('That doesn’t change the stock.');
            if ($reason === 'damage' && $change > 0) throw new InventoryException('Damaged stock can only be removed.');
            if ($reason === 'return' && $change < 0) throw new InventoryException('Returns add stock back.');
            if ($note === '' && $reason === 'adjustment') throw new InventoryException('Add a short note explaining the count difference.');
            inventory_tx(fn() => inventory_move($productId, $change, $reason, 'manual', null, $note ?: null));
            flash('success', 'Stock updated (' . ($change > 0 ? '+' : '') . $change . ').');
        } elseif ($action === 'register_serials') {
            // Existing stock that predates serial tracking: attach serials without changing stock.
            $list = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) ($_POST['serials'] ?? '')) ?: []))));
            inventory_tx(function (PDO $pdo) use ($list, $productId) {
                $p = $pdo->prepare('SELECT stock_qty FROM products WHERE id = :id FOR UPDATE');
                $p->execute(['id' => $productId]);
                $c = $pdo->prepare("SELECT COUNT(*) FROM product_units WHERE product_id = :id AND status IN ('in_stock', 'returned')");
                $c->execute(['id' => $productId]);
                $missing = (int) $p->fetchColumn() - (int) $c->fetchColumn();
                if (!$list) throw new InventoryException('Enter at least one serial number.');
                if (count($list) > $missing) throw new InventoryException('Only ' . $missing . ' unit(s) in stock are missing a serial.');
                foreach ($list as $sn) {
                    $d = $pdo->prepare('SELECT COUNT(*) FROM product_units WHERE serial = :s');
                    $d->execute(['s' => $sn]);
                    if ((int) $d->fetchColumn() > 0) throw new InventoryException('Serial ' . $sn . ' is already in the system.');
                    $pdo->prepare('INSERT INTO product_units (product_id, serial, notes) VALUES (:p, :s, :n)')
                        ->execute(['p' => $productId, 's' => $sn, 'n' => 'Registered for existing stock']);
                }
            });
            flash('success', count($list) . ' serial number(s) registered.');
        } elseif ($action === 'unit_status') {
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $new = (string) ($_POST['status'] ?? '');
            inventory_tx(function (PDO $pdo) use ($unitId, $new, $productId) {
                $u = $pdo->prepare('SELECT * FROM product_units WHERE id = :id AND product_id = :p FOR UPDATE');
                $u->execute(['id' => $unitId, 'p' => $productId]);
                $unit = $u->fetch();
                if (!$unit || !in_array($new, ['in_stock', 'faulty', 'returned'], true) || $unit['status'] === $new) {
                    throw new InventoryException('That status change isn’t allowed.');
                }
                // Units count toward stock while in_stock or returned (resellable).
                $wasStock = in_array($unit['status'], ['in_stock', 'returned'], true);
                $isStock = in_array($new, ['in_stock', 'returned'], true);
                if ($unit['status'] === 'sold' && $new !== 'returned') throw new InventoryException('A sold unit can only be marked as returned.');
                if ($wasStock !== $isStock) {
                    inventory_move($productId, $isStock ? 1 : -1, $isStock ? 'return' : 'damage', 'unit', $unitId,
                        'Serial ' . $unit['serial'] . ($unit['status'] === 'sold' ? ' returned by customer' : ''));
                }
                $pdo->prepare('UPDATE product_units SET status = :s WHERE id = :id')->execute(['s' => $new, 'id' => $unitId]);
            });
            flash('success', 'Serial updated.');
        }
        header('Location: ' . path('admin/inventory/products/' . $productId));
        exit;
    } catch (InventoryException $ex) {
        $error = $ex->getMessage();
    }
}

$product = product_by_id($productId);
$moves = db()->prepare('SELECT m.*, u.name AS user_name FROM stock_movements m LEFT JOIN users u ON u.id = m.user_id WHERE m.product_id = :id ORDER BY m.id DESC LIMIT 100');
$moves->execute(['id' => $productId]);
$moves = $moves->fetchAll();
$units = [];
if ($product['track_serials']) {
    $st = db()->prepare("SELECT * FROM product_units WHERE product_id = :id ORDER BY FIELD(status, 'in_stock', 'returned', 'faulty', 'sold'), serial");
    $st->execute(['id' => $productId]);
    $units = $st->fetchAll();
}
$sold90 = db()->prepare("SELECT COALESCE(-SUM(change_qty), 0) FROM stock_movements WHERE product_id = :id AND reason IN ('online_sale', 'pos_sale') AND created_at >= NOW() - INTERVAL 90 DAY");
$sold90->execute(['id' => $productId]);
$sold90 = (int) $sold90->fetchColumn();
$margin = $product['cost_price'] !== null && (float) $product['price'] > 0 ? ((float) $product['price'] - (float) $product['cost_price']) / (float) $product['price'] * 100 : null;
$low = (int) $product['stock_qty'] <= (int) $product['reorder_level'];

$pageTitle = $product['name'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div>
        <p class="admin-kicker">Inventory</p>
        <h1><?= e($product['name']) ?></h1>
    </div>
    <div class="admin-header-actions">
        <a href="<?= path('admin/purchase-orders/new') ?>?product=<?= $productId ?>" class="btn btn-primary btn-sm">Restock</a>
        <a href="<?= path('admin/products/' . $productId . '/edit') ?>" class="btn btn-outline btn-sm">Edit Product</a>
        <a href="<?= path('admin/inventory/stock') ?>" class="btn btn-outline btn-sm">All Stock</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="dash-kpis">
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">On hand</p><p class="dash-kpi-value<?= $low ? ' is-warn' : '' ?>"><?= (int) $product['stock_qty'] ?></p><p class="dash-kpi-note"><?= $low ? 'At or below reorder level (' . (int) $product['reorder_level'] . ')' : 'Reorder at ' . (int) $product['reorder_level'] ?></p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Sold, last 90 days</p><p class="dash-kpi-value"><?= $sold90 ?></p><p class="dash-kpi-note">Online + walk-in</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Price / cost</p><p class="dash-kpi-value dash-kpi-value-sm"><?= e(naira_short((float) $product['price'])) ?></p><p class="dash-kpi-note">Cost <?= $product['cost_price'] !== null ? e(naira_short((float) $product['cost_price'])) : 'not set' ?></p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Margin</p><p class="dash-kpi-value"><?= $margin === null ? '—' : round($margin) . '%' ?></p><p class="dash-kpi-note">Stock value <?= e(naira_short((float) $product['stock_qty'] * (float) ($product['cost_price'] ?? 0))) ?> at cost</p></div>
</div>

<div class="admin-split">
    <?php if ($product['track_serials']): ?>
    <div class="admin-form">
        <h2 class="admin-form-title">Stock follows serial numbers</h2>
        <p>This product is tracked by serial / IMEI, so its stock is the number of units in stock below. Receive new units on a <a href="<?= path('admin/purchase-orders/new') ?>?product=<?= $productId ?>">purchase order</a>; mark returns or faulty units in the serial list.</p>
    </div>
    <?php else: ?>
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="adjust">
        <h2 class="admin-form-title">Adjust stock</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="mode">Change</label>
                <select id="mode" name="mode">
                    <option value="set">Set counted quantity to</option>
                    <option value="add">Add</option>
                    <option value="remove">Remove</option>
                </select>
            </div>
            <div class="form-group">
                <label for="qty">Quantity</label>
                <input type="number" id="qty" name="qty" min="0" value="<?= (int) $product['stock_qty'] ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="reason">Reason</label>
            <select id="reason" name="reason">
                <?php foreach (STOCK_MANUAL_REASONS as $r): ?><option value="<?= $r ?>"><?= e(STOCK_REASONS[$r]) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="note">Note</label>
            <input type="text" id="note" name="note" maxlength="255" placeholder="e.g. Monthly stock count, 1 unit missing">
        </div>
        <button type="submit" class="btn btn-primary">Save Adjustment</button>
        <p class="form-note">Sales, purchase orders and cancellations update stock automatically — use this for counts, returns and write-offs.</p>
    </form>
    <?php endif; ?>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>When</th><th>Movement</th><th>Change</th><th>Balance</th><th>By</th></tr></thead>
            <tbody>
            <?php foreach ($moves as $m):
                $ref = match ($m['ref_type']) {
                    'order' => '<a href="' . path('admin/orders/' . (int) $m['ref_id']) . '">' . e((string) $m['note']) . '</a>',
                    'pos_sale' => '<a href="' . path('admin/pos/sales/' . (int) $m['ref_id']) . '">' . e((string) $m['note']) . '</a>',
                    'purchase_order' => '<a href="' . path('admin/purchase-orders/' . (int) $m['ref_id']) . '">' . e((string) $m['note']) . '</a>',
                    default => e((string) $m['note']),
                }; ?>
                <tr>
                    <td style="white-space:nowrap;"><?= e((new DateTimeImmutable($m['created_at']))->format('j M Y, g:i A')) ?></td>
                    <td><?= e(STOCK_REASONS[$m['reason']] ?? $m['reason']) ?><?= $ref !== '' ? '<br><small class="muted">' . $ref . '</small>' : '' ?></td>
                    <td><strong class="<?= $m['change_qty'] > 0 ? 'qty-in' : 'qty-out' ?>"><?= $m['change_qty'] > 0 ? '+' : '' ?><?= (int) $m['change_qty'] ?></strong></td>
                    <td><?= (int) $m['balance_after'] ?></td>
                    <td><?= e($m['user_name'] ?? ($m['reason'] === 'online_sale' ? 'Website' : '—')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$moves): ?><tr><td colspan="5" class="admin-empty">No stock movements yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($product['track_serials']):
    $inStockUnits = count(array_filter($units, fn($u) => in_array($u['status'], ['in_stock', 'returned'], true)));
    $missingSerials = max(0, (int) $product['stock_qty'] - $inStockUnits); ?>
<div class="admin-header-row" style="margin-top:28px;"><h2>Serial numbers</h2></div>
<?php if ($missingSerials > 0): ?>
    <form method="post" class="admin-form" style="margin-bottom:18px;">
        <?= csrf_field() ?><input type="hidden" name="action" value="register_serials">
        <h2 class="admin-form-title"><?= $missingSerials ?> unit(s) in stock have no serial yet</h2>
        <p class="form-note">This stock was added before serial tracking was switched on. Enter their serial / IMEI numbers, one per line, so they can be sold and covered by warranty.</p>
        <textarea name="serials" rows="3" style="font-family:var(--font-mono);font-size:0.85rem;"></textarea>
        <button type="submit" class="btn btn-primary" style="margin-top:10px;">Register Serials</button>
    </form>
<?php endif; ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Serial / IMEI</th><th>Status</th><th>Received</th><th>Sold to</th><th>Warranty</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($units as $u): $w = warranty_status($u['warranty_until']); ?>
            <tr>
                <td><code><?= e($u['serial']) ?></code></td>
                <td><span class="badge <?= ['in_stock' => 'badge-success', 'sold' => 'badge-muted', 'returned' => 'badge-warning', 'faulty' => 'badge-danger'][$u['status']] ?>"><?= e(str_replace('_', ' ', $u['status'])) ?></span></td>
                <td><?= e((new DateTimeImmutable($u['received_at']))->format('j M Y')) ?></td>
                <td><?= $u['sold_at'] ? e(($u['customer_name'] ?: 'Walk-in customer') . ' · ' . (new DateTimeImmutable($u['sold_at']))->format('j M Y')) : '—' ?></td>
                <td><?= $u['status'] === 'sold' ? '<span class="badge ' . $w['class'] . '">' . e($w['label']) . '</span>' : '—' ?></td>
                <td>
                    <form method="post" class="admin-inline-form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="unit_status"><input type="hidden" name="unit_id" value="<?= (int) $u['id'] ?>">
                        <select name="status" aria-label="Change status">
                            <?php foreach (($u['status'] === 'sold' ? ['returned' => 'Returned by customer'] : ['in_stock' => 'In stock', 'returned' => 'Returned', 'faulty' => 'Faulty']) as $k => $lab): ?>
                                <?php if ($k !== $u['status']): ?><option value="<?= $k ?>"><?= e($lab) ?></option><?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-outline btn-sm">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$units): ?><tr><td colspan="6" class="admin-empty">No units yet — receive them on a purchase order with their serial numbers.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
