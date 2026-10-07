<?php
declare(strict_types=1);

$adminUser = require_admin();

$range = (string) ($_GET['range'] ?? '30d');
$period = dashboard_period($range);
$range = $period['range'];
$labels = array_column($period['buckets'], 'label');

$sum = inventory_summary();
$potentialMargin = $sum['value_retail'] > 0 && $sum['value_cost'] > 0 ? ($sum['value_retail'] - $sum['value_cost']) / $sum['value_retail'] * 100 : null;
$sold = dashboard_units_sold($period);
$top = dashboard_top_products($period['start'], $period['end']);

$posStmt = db()->prepare("SELECT COUNT(*) n, COALESCE(SUM(total), 0) t FROM pos_sales WHERE status = 'completed' AND created_at BETWEEN :f AND :t");
$posStmt->execute(['f' => $period['start']->format('Y-m-d 00:00:00'), 't' => $period['end']->format('Y-m-d 23:59:59')]);
$pos = $posStmt->fetch();

$reorder = db()->query(
    "SELECT p.id, p.name, p.sku, p.stock_qty, p.reorder_level,
            (SELECT COALESCE(SUM(i.qty_ordered - i.qty_received), 0) FROM purchase_order_items i JOIN purchase_orders po ON po.id = i.po_id
              WHERE i.product_id = p.id AND po.status IN ('draft', 'ordered', 'partially_received')) AS on_order
     FROM products p WHERE p.is_demo = 0 AND p.is_active = 1 AND p.stock_qty <= p.reorder_level ORDER BY p.stock_qty, p.name LIMIT 10"
)->fetchAll();
$openPos = db()->query(
    "SELECT po.id, po.po_number, po.status, po.expected_date, s.name AS supplier_name,
            (SELECT COALESCE(SUM(qty_ordered * unit_cost), 0) FROM purchase_order_items WHERE po_id = po.id) AS total
     FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id
     WHERE po.status IN ('draft', 'ordered', 'partially_received') ORDER BY po.expected_date IS NULL, po.expected_date LIMIT 6"
)->fetchAll();
$moves = db()->query('SELECT m.*, p.name AS product_name FROM stock_movements m JOIN products p ON p.id = m.product_id ORDER BY m.id DESC LIMIT 8')->fetchAll();
$warrantyEnding = (int) db()->query("SELECT COUNT(*) FROM product_units WHERE status = 'sold' AND warranty_until BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY")->fetchColumn();
$inRepair = (int) db()->query("SELECT COUNT(*) FROM assets WHERE status = 'in_repair'")->fetchColumn();

$pageTitle = 'Inventory';
require __DIR__ . '/../partials/admin_header.php';
?>

<section class="dash-head">
    <div><p class="admin-kicker">Store &amp; Inventory</p><h1>Inventory</h1></div>
    <nav class="dash-range" aria-label="Date range">
        <?php foreach (DASH_RANGES as $key => $label): ?>
            <a href="<?= path('admin/inventory') ?>?range=<?= e($key) ?>"<?= $key === $range ? ' class="is-active" aria-current="true"' : '' ?>><?= e(['7d' => '7 days', '30d' => '30 days', '90d' => '90 days', '12m' => '12 months'][$key]) ?></a>
        <?php endforeach; ?>
    </nav>
</section>

<nav class="admin-quick" aria-label="Inventory shortcuts">
    <a class="admin-quick-link glass-dark" href="<?= path('admin/pos') ?>">New walk-in sale</a>
    <a class="admin-quick-link glass-dark" href="<?= path('admin/purchase-orders/new') ?>">New purchase order</a>
    <a class="admin-quick-link glass-dark" href="<?= path('admin/products/new') ?>">Add product</a>
    <a class="admin-quick-link glass-dark is-plain" href="<?= path('admin/inventory/stock') ?>">All stock</a>
    <a class="admin-quick-link glass-dark is-plain" href="<?= path('admin/serials') ?>">Serial lookup</a>
</nav>

<div class="dash-kpis">
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Stock value (cost)</p><p class="dash-kpi-value"><?= e(naira_short($sum['value_cost'])) ?></p><p class="dash-kpi-note"><?= (int) $sum['units'] ?> units on hand<?= $sum['missing_cost'] ? ' · ' . (int) $sum['missing_cost'] . ' without a cost price' : '' ?></p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Retail value</p><p class="dash-kpi-value"><?= e(naira_short($sum['value_retail'])) ?></p><p class="dash-kpi-note"><?= $potentialMargin === null ? 'Add cost prices to see margin' : round($potentialMargin) . '% potential margin' ?></p></div>
    <a class="dash-kpi glass-dark" href="<?= path('admin/inventory/stock') ?>?filter=low"><p class="dash-kpi-label">Low stock</p><p class="dash-kpi-value<?= $sum['low_stock'] ? ' is-warn' : '' ?>"><?= (int) $sum['low_stock'] ?></p><p class="dash-kpi-note"><?= (int) $sum['out_of_stock'] ?> out of stock</p></a>
    <a class="dash-kpi glass-dark" href="<?= path('admin/pos/sales') ?>"><p class="dash-kpi-label">Walk-in takings</p><p class="dash-kpi-value"><?= e(naira_short((float) $pos['t'])) ?></p><p class="dash-kpi-note"><?= (int) $pos['n'] ?> sales · <?= e(strtolower(DASH_RANGES[$range])) ?></p></a>
</div>

<div class="dash-grid dash-grid-main">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Units sold</h2><p class="dash-card-sub"><?= e(DASH_RANGES[$range]) ?> · by <?= e($period['unit']) ?></p></div><strong class="dash-figure-sm"><?= array_sum($sold['online_sale']) + array_sum($sold['pos_sale']) ?></strong></header>
        <?= chart_legend([['name' => 'Online', 'color' => CHART_SERIES[0]], ['name' => 'Walk-in', 'color' => CHART_SERIES[2]]]) ?>
        <?php $soldSeries = [['name' => 'Online', 'values' => $sold['online_sale'], 'color' => CHART_SERIES[0]], ['name' => 'Walk-in', 'values' => $sold['pos_sale'], 'color' => CHART_SERIES[2]]]; ?>
        <div class="chart-wide"><?= chart_line($labels, $soldSeries, ['label' => 'Units sold by channel', 'integer' => true]) ?></div>
        <div class="chart-narrow"><?= chart_line($labels, $soldSeries, ['label' => 'Units sold by channel', 'width' => 360, 'height' => 240, 'integer' => true]) ?></div>
    </section>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Top sellers</h2><p class="dash-card-sub">By revenue, online + walk-in</p></div></header>
        <?php if ($top): $topMax = max(array_map(fn($t) => (float) $t['revenue'], $top)); ?>
            <ol class="dash-top">
                <?php foreach ($top as $t): ?>
                    <li data-tip="<?= e($t['name'] . ': ' . (int) $t['units'] . ' sold · ' . format_naira((float) $t['revenue'])) ?>">
                        <a href="<?= path('admin/inventory/products/' . (int) $t['id']) ?>"><span><?= e($t['name']) ?></span><strong><?= e(naira_short((float) $t['revenue'])) ?></strong></a>
                        <span class="dash-funnel-track"><span class="dash-funnel-bar" style="width:<?= max(4, round($t['revenue'] / $topMax * 100)) ?>%"></span></span>
                        <small><?= (int) $t['units'] ?> sold · <?= (int) $t['stock_qty'] ?> left</small>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?><p class="dash-empty">No sales in this period.</p><?php endif; ?>
    </section>
</div>

<div class="dash-grid dash-grid-2">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Reorder now</h2><p class="dash-card-sub">At or below reorder level</p></div><a class="dash-link" href="<?= path('admin/inventory/stock') ?>?filter=low">All low stock →</a></header>
        <?php if ($reorder): ?>
            <div class="dash-table-wrap"><table class="dash-table">
                <thead><tr><th>Product</th><th>On hand</th><th>On order</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($reorder as $r): ?>
                    <tr>
                        <td><a href="<?= path('admin/inventory/products/' . (int) $r['id']) ?>"><?= e($r['name']) ?></a></td>
                        <td><span class="badge <?= (int) $r['stock_qty'] === 0 ? 'badge-danger' : 'badge-warning' ?>"><?= (int) $r['stock_qty'] ?></span> <small class="muted-dark">/ <?= (int) $r['reorder_level'] ?></small></td>
                        <td><?= (int) $r['on_order'] ?: '—' ?></td>
                        <td><?php if (!(int) $r['on_order']): ?><a class="dash-link" href="<?= path('admin/purchase-orders/new') ?>?product=<?= (int) $r['id'] ?>">Restock →</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?><p class="dash-empty">Everything is above its reorder level.</p><?php endif; ?>
    </section>

    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Open purchase orders</h2><p class="dash-card-sub">Awaiting delivery</p></div><a class="dash-link" href="<?= path('admin/purchase-orders') ?>?status=open">All →</a></header>
        <?php if ($openPos): ?>
            <ul class="dash-attention">
                <?php foreach ($openPos as $po): ?>
                    <li><a href="<?= path('admin/purchase-orders/' . (int) $po['id']) ?>"><span><strong class="dash-plain"><?= e($po['po_number']) ?></strong> · <?= e($po['supplier_name']) ?><br><small class="muted-dark"><?= e(PO_STATUSES[$po['status']]) ?><?= $po['expected_date'] ? ' · due ' . e((new DateTimeImmutable($po['expected_date']))->format('j M')) : '' ?></small></span><em><?= e(naira_short((float) $po['total'])) ?></em></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?><p class="dash-empty">No open purchase orders.</p><?php endif; ?>
        <ul class="dash-attention dash-attention-sm">
            <li><a href="<?= path('admin/serials') ?>?status=warranty"<?= $warrantyEnding ? ' class="is-hot"' : '' ?>><span>Warranties ending within 30 days</span><strong><?= $warrantyEnding ?></strong></a></li>
            <li><a href="<?= path('admin/equipment') ?>?status=in_repair"<?= $inRepair ? ' class="is-hot"' : '' ?>><span>Hub equipment in repair</span><strong><?= $inRepair ?></strong></a></li>
        </ul>
    </section>
</div>

<section class="dash-card glass-dark">
    <header class="dash-card-head"><div><h2>Latest stock movements</h2><p class="dash-card-sub">Every change is recorded</p></div><a class="dash-link" href="<?= path('admin/inventory/movements') ?>">Full ledger →</a></header>
    <div class="dash-table-wrap"><table class="dash-table">
        <thead><tr><th>When</th><th>Product</th><th>Movement</th><th>Change</th><th>Balance</th></tr></thead>
        <tbody>
        <?php foreach ($moves as $m): ?>
            <tr>
                <td><?= e((new DateTimeImmutable($m['created_at']))->format('j M, g:i A')) ?></td>
                <td><a href="<?= path('admin/inventory/products/' . (int) $m['product_id']) ?>"><?= e($m['product_name']) ?></a></td>
                <td><?= e(STOCK_REASONS[$m['reason']] ?? $m['reason']) ?><?= $m['note'] ? ' <small class="muted-dark">· ' . e($m['note']) . '</small>' : '' ?></td>
                <td><strong class="<?= $m['change_qty'] > 0 ? 'qty-in' : 'qty-out' ?>"><?= $m['change_qty'] > 0 ? '+' : '' ?><?= (int) $m['change_qty'] ?></strong></td>
                <td><?= (int) $m['balance_after'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$moves): ?><tr><td colspan="5" class="dash-empty">No movements yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
