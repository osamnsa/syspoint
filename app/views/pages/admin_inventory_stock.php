<?php
declare(strict_types=1);

$adminUser = require_admin();

$filter = (string) ($_GET['filter'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$products = inventory_products();
$products = array_values(array_filter($products, function ($p) use ($filter, $q) {
    if ($q !== '' && stripos($p['name'] . ' ' . $p['sku'] . ' ' . $p['category_name'], $q) === false) return false;
    return match ($filter) {
        'low' => !$p['is_demo'] && $p['stock_qty'] > 0 && $p['stock_qty'] <= $p['reorder_level'],
        'out' => !$p['is_demo'] && (int) $p['stock_qty'] === 0,
        'serials' => (bool) $p['track_serials'],
        'nocost' => !$p['is_demo'] && $p['cost_price'] === null,
        default => true,
    };
}));

$pageTitle = 'Stock';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Inventory</p><h1>Stock</h1></div>
    <div class="admin-header-actions">
        <a href="<?= path('admin/inventory/movements') ?>" class="btn btn-outline btn-sm">Stock Ledger</a>
        <a href="<?= path('admin/products/new') ?>" class="btn btn-primary btn-sm">Add Product</a>
    </div>
</div>

<div class="admin-toolbar">
    <nav class="admin-filters" aria-label="Filter">
        <?php foreach (['' => 'All', 'low' => 'Low stock', 'out' => 'Out of stock', 'serials' => 'Serial-tracked', 'nocost' => 'No cost price'] as $key => $label): ?>
            <a href="<?= path('admin/inventory/stock') . ($key !== '' ? '?filter=' . $key : '') ?>"<?= $filter === $key ? ' class="is-active"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" class="admin-search">
        <?php if ($filter): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, SKU, category" aria-label="Search stock">
    </form>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Product</th><th>SKU</th><th>On hand</th><th>Reorder at</th><th>Cost</th><th>Price</th><th>Margin</th><th>Stock value</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p):
            $margin = $p['cost_price'] !== null && (float) $p['price'] > 0 ? round(((float) $p['price'] - (float) $p['cost_price']) / (float) $p['price'] * 100) : null;
            $state = $p['is_demo'] ? 'demo' : ((int) $p['stock_qty'] === 0 ? 'out' : ($p['stock_qty'] <= $p['reorder_level'] ? 'low' : 'ok')); ?>
            <tr>
                <td><a href="<?= path('admin/inventory/products/' . (int) $p['id']) ?>"><strong><?= e($p['name']) ?></strong></a><br><small class="muted"><?= e($p['category_name']) ?><?= $p['track_serials'] ? ' · Serials' : '' ?></small></td>
                <td><?= e($p['sku'] ?: '—') ?></td>
                <td><span class="badge <?= ['demo' => 'badge-muted', 'out' => 'badge-danger', 'low' => 'badge-warning', 'ok' => 'badge-success'][$state] ?>"><?= $state === 'demo' ? 'Demo' : (int) $p['stock_qty'] ?></span></td>
                <td><?= (int) $p['reorder_level'] ?></td>
                <td><?= $p['cost_price'] !== null ? format_naira((float) $p['cost_price']) : '<span class="muted">—</span>' ?></td>
                <td><?= format_naira((float) $p['price']) ?></td>
                <td><?= $margin === null ? '—' : $margin . '%' ?></td>
                <td><?= $p['cost_price'] !== null ? format_naira($p['stock_qty'] * (float) $p['cost_price']) : '—' ?></td>
                <td style="white-space:nowrap;">
                    <a href="<?= path('admin/inventory/products/' . (int) $p['id']) ?>" class="btn btn-outline btn-sm">Stock</a>
                    <?php if (!$p['is_demo']): ?><a href="<?= path('admin/purchase-orders/new') ?>?product=<?= (int) $p['id'] ?>" class="btn btn-outline btn-sm">Restock</a><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?><tr><td colspan="9" class="admin-empty">Nothing matches.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
