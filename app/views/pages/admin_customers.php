<?php
declare(strict_types=1);

$adminUser = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['action'] ?? '') === 'sync') {
    $n = crm_sync_existing();
    flash('success', $n ? "$n past orders, bookings, requests and messages linked to customers." : 'Everything is already linked.');
    header('Location: ' . path('admin/customers'));
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$type = (string) ($_GET['type'] ?? '');
$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(c.name LIKE :q OR c.email LIKE :q2 OR c.phone LIKE :q3 OR c.tags LIKE :q4 OR o.name LIKE :q5)';
    foreach (['q', 'q2', 'q3', 'q4', 'q5'] as $k) $params[$k] = "%$q%";
}
if (in_array($type, ['person', 'organisation'], true)) { $where[] = 'c.type = :t'; $params['t'] = $type; }
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 40;
$count = db()->prepare('SELECT COUNT(*) FROM customers c LEFT JOIN customers o ON o.id = c.organisation_id WHERE ' . implode(' AND ', $where));
$count->execute($params);
$total = (int) $count->fetchColumn();
$stmt = db()->prepare(
    'SELECT c.*, o.name AS organisation_name, u.name AS owner_name,
            (SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE payment_status = \'paid\' AND customer_id = c.id)
          + (SELECT COALESCE(SUM(total), 0) FROM pos_sales WHERE status = \'completed\' AND customer_id = c.id)
          + (SELECT COALESCE(SUM(amount_paid), 0) FROM crm_documents WHERE type = \'invoice\' AND customer_id = c.id) AS lifetime,
            (SELECT COUNT(*) FROM deals WHERE customer_id = c.id AND stage IN (\'lead\', \'contacted\', \'proposal\', \'negotiation\')) AS open_deals
     FROM customers c LEFT JOIN customers o ON o.id = c.organisation_id LEFT JOIN users u ON u.id = c.owner_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY COALESCE(c.last_activity_at, c.created_at) DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per)
);
$stmt->execute($params);
$customers = $stmt->fetchAll();
$unlinked = (int) db()->query('SELECT
    (SELECT COUNT(*) FROM orders WHERE customer_id IS NULL) + (SELECT COUNT(*) FROM room_bookings WHERE customer_id IS NULL)
  + (SELECT COUNT(*) FROM software_requests WHERE customer_id IS NULL AND is_spam = 0) + (SELECT COUNT(*) FROM contact_messages WHERE is_spam = 0 AND customer_id IS NULL)')->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$qs = fn($p) => path('admin/customers') . '?' . http_build_query(array_filter(['q' => $q, 'type' => $type, 'page' => $p > 1 ? $p : null]));

$pageTitle = 'Customers';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">CRM</p><h1>Customers</h1></div>
    <div class="admin-header-actions">
        <?php if ($unlinked): ?>
            <form method="post" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="action" value="sync"><button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark" title="Link past orders, bookings, requests and messages to customers">Import <?= $unlinked ?> past records</button></form>
        <?php endif; ?>
        <a href="<?= path('admin/customers/new') ?>" class="btn btn-primary btn-sm">Add Customer</a>
    </div>
</div>

<div class="admin-toolbar">
    <nav class="admin-filters" aria-label="Filter">
        <?php foreach (['' => 'Everyone (' . $total . ')', 'person' => 'People', 'organisation' => 'Organisations'] as $k => $label): ?>
            <a href="<?= path('admin/customers') . '?' . http_build_query(array_filter(['type' => $k, 'q' => $q])) ?>"<?= $type === $k ? ' class="is-active"' : '' ?>><?= e($k === '' && $type !== '' ? 'Everyone' : $label) ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" class="admin-search">
        <?php if ($type): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email, phone, tag, company" aria-label="Search customers">
    </form>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Customer</th><th>Contact</th><th>Source</th><th>Lifetime value</th><th>Open deals</th><th>Owner</th><th>Last activity</th></tr></thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
            <tr class="is-clickable" data-href="<?= path('admin/customers/' . (int) $c['id']) ?>">
                <td>
                    <span class="crm-avatar<?= $c['type'] === 'organisation' ? ' is-org' : '' ?>"><?= e(initials($c['name'])) ?></span>
                    <a href="<?= path('admin/customers/' . (int) $c['id']) ?>"><strong><?= e($c['name']) ?></strong></a>
                    <?php if ($c['organisation_name']): ?><br><small class="muted crm-indent"><?= e($c['organisation_name']) ?></small><?php endif; ?>
                </td>
                <td><?= e($c['email'] ?? '') ?><?= $c['phone'] ? '<br><small class="muted">' . e($c['phone']) . '</small>' : '' ?></td>
                <td><?= e(CUSTOMER_SOURCES[$c['source']]) ?></td>
                <td><?= (float) $c['lifetime'] > 0 ? format_naira((float) $c['lifetime']) : '—' ?></td>
                <td><?= (int) $c['open_deals'] ?: '—' ?></td>
                <td><?= e($c['owner_name'] ?? '—') ?></td>
                <td style="white-space:nowrap;"><?= e((new DateTimeImmutable($c['last_activity_at'] ?? $c['created_at']))->format('j M Y')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$customers): ?><tr><td colspan="7" class="admin-empty"><?= $q !== '' ? 'No customer matches “' . e($q) . '”.' : 'No customers yet — they appear automatically from orders, bookings, requests, messages and walk-in sales.' ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($pages > 1): ?>
<nav class="admin-pager">
    <?php if ($page > 1): ?><a href="<?= e($qs($page - 1)) ?>">← Previous</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="<?= e($qs($page + 1)) ?>">Next →</a><?php endif; ?>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
