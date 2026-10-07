<?php
declare(strict_types=1);

$adminUser = require_admin();

$reason = (string) ($_GET['reason'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 50;
$where = isset(STOCK_REASONS[$reason]) ? 'WHERE m.reason = :reason' : '';
$params = $where ? ['reason' => $reason] : [];
$count = db()->prepare("SELECT COUNT(*) FROM stock_movements m $where");
$count->execute($params);
$total = (int) $count->fetchColumn();
$stmt = db()->prepare("SELECT m.*, p.name AS product_name, u.name AS user_name FROM stock_movements m
                       JOIN products p ON p.id = m.product_id LEFT JOIN users u ON u.id = m.user_id
                       $where ORDER BY m.id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$stmt->execute($params);
$moves = $stmt->fetchAll();
$pages = max(1, (int) ceil($total / $per));

$pageTitle = 'Stock Ledger';
require __DIR__ . '/../partials/admin_header.php';
$qs = fn($p) => path('admin/inventory/movements') . '?' . http_build_query(array_filter(['reason' => $reason, 'page' => $p > 1 ? $p : null]));
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Inventory</p><h1>Stock Ledger</h1></div>
    <a href="<?= path('admin/inventory/stock') ?>" class="btn btn-outline btn-sm">Back to Stock</a>
</div>
<p class="form-note" style="margin:-8px 0 16px;">Every change to stock, newest first: sales, purchases, adjustments, returns and cancellations.</p>

<nav class="admin-filters" aria-label="Filter">
    <a href="<?= path('admin/inventory/movements') ?>"<?= $reason === '' ? ' class="is-active"' : '' ?>>All</a>
    <?php foreach (STOCK_REASONS as $key => $label): ?>
        <a href="<?= path('admin/inventory/movements') ?>?reason=<?= $key ?>"<?= $reason === $key ? ' class="is-active"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>When</th><th>Product</th><th>Movement</th><th>Change</th><th>Balance</th><th>By</th></tr></thead>
        <tbody>
        <?php foreach ($moves as $m): ?>
            <tr>
                <td style="white-space:nowrap;"><?= e((new DateTimeImmutable($m['created_at']))->format('j M Y, g:i A')) ?></td>
                <td><a href="<?= path('admin/inventory/products/' . (int) $m['product_id']) ?>"><?= e($m['product_name']) ?></a></td>
                <td><?= e(STOCK_REASONS[$m['reason']] ?? $m['reason']) ?><?= $m['note'] ? '<br><small class="muted">' . e($m['note']) . '</small>' : '' ?></td>
                <td><strong class="<?= $m['change_qty'] > 0 ? 'qty-in' : 'qty-out' ?>"><?= $m['change_qty'] > 0 ? '+' : '' ?><?= (int) $m['change_qty'] ?></strong></td>
                <td><?= (int) $m['balance_after'] ?></td>
                <td><?= e($m['user_name'] ?? ($m['reason'] === 'online_sale' ? 'Website' : '—')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$moves): ?><tr><td colspan="6" class="admin-empty">No movements.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($pages > 1): ?>
<nav class="admin-pager">
    <?php if ($page > 1): ?><a href="<?= e($qs($page - 1)) ?>">← Newer</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="<?= e($qs($page + 1)) ?>">Older →</a><?php endif; ?>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
