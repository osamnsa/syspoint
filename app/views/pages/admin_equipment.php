<?php
declare(strict_types=1);

$adminUser = require_admin();

$status = (string) ($_GET['status'] ?? '');
$room = (string) ($_GET['room'] ?? '');
$rooms = db()->query('SELECT id, name FROM gaming_rooms ORDER BY name')->fetchAll();
$sql = 'SELECT a.*, r.name AS room_name,
               (SELECT COALESCE(SUM(cost), 0) FROM asset_logs l WHERE l.asset_id = a.id AND l.type = \'repair\') AS repair_cost
        FROM assets a LEFT JOIN gaming_rooms r ON r.id = a.room_id WHERE 1 = 1';
$params = [];
if (isset(ASSET_STATUSES[$status])) { $sql .= ' AND a.status = :s'; $params['s'] = $status; }
if ($room !== '' && ctype_digit($room)) { $sql .= ' AND a.room_id = :r'; $params['r'] = (int) $room; }
$stmt = db()->prepare($sql . " ORDER BY FIELD(a.status, 'in_repair', 'in_use', 'spare', 'retired'), a.category, a.name");
$stmt->execute($params);
$assets = $stmt->fetchAll();

$counts = ['in_use' => 0, 'spare' => 0, 'in_repair' => 0, 'retired' => 0];
foreach (db()->query('SELECT status, COUNT(*) n FROM assets GROUP BY status') as $r) $counts[$r['status']] = (int) $r['n'];
$value = (float) db()->query("SELECT COALESCE(SUM(purchase_cost), 0) FROM assets WHERE status <> 'retired'")->fetchColumn();

$pageTitle = 'Hub Equipment';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker"><?= e(site('site.hub_name') . ' · ' . site('site.hub_suite')) ?></p><h1>Hub Equipment</h1></div>
    <a href="<?= path('admin/equipment/new') ?>" class="btn btn-primary btn-sm">Add Equipment</a>
</div>

<div class="pos-today">
    <div><span>In use</span><strong><?= $counts['in_use'] ?></strong></div>
    <div><span>Spare</span><strong><?= $counts['spare'] ?></strong></div>
    <div><span>In repair</span><strong class="<?= $counts['in_repair'] ? 'is-warn' : '' ?>"><?= $counts['in_repair'] ?></strong></div>
    <div><span>Retired</span><strong><?= $counts['retired'] ?></strong></div>
    <div><span>Value (cost)</span><strong><?= format_naira($value) ?></strong></div>
</div>

<div class="admin-toolbar">
    <nav class="admin-filters" aria-label="Filter by status">
        <?php foreach (['' => 'All'] + ASSET_STATUSES as $k => $label): ?>
            <a href="<?= path('admin/equipment') . '?' . http_build_query(array_filter(['status' => $k, 'room' => $room])) ?>"<?= $status === $k ? ' class="is-active"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <nav class="admin-filters" aria-label="Filter by room">
        <a href="<?= path('admin/equipment') . '?' . http_build_query(array_filter(['status' => $status])) ?>"<?= $room === '' ? ' class="is-active"' : '' ?>>All rooms</a>
        <?php foreach ($rooms as $r): ?>
            <a href="<?= path('admin/equipment') . '?' . http_build_query(array_filter(['status' => $status, 'room' => $r['id']])) ?>"<?= $room === (string) $r['id'] ? ' class="is-active"' : '' ?>><?= e($r['name']) ?></a>
        <?php endforeach; ?>
    </nav>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Tag</th><th>Item</th><th>Type</th><th>Where</th><th>Condition</th><th>Status</th><th>Repairs</th></tr></thead>
        <tbody>
        <?php foreach ($assets as $a): ?>
            <tr class="is-clickable" data-href="<?= path('admin/equipment/' . (int) $a['id']) ?>">
                <td><code><?= e($a['asset_tag']) ?></code></td>
                <td><a href="<?= path('admin/equipment/' . (int) $a['id']) ?>"><strong><?= e($a['name']) ?></strong></a><?= $a['serial'] ? '<br><small class="muted">S/N ' . e($a['serial']) . '</small>' : '' ?></td>
                <td><?= e(ASSET_CATEGORIES[$a['category']]) ?></td>
                <td><?= e($a['room_name'] ?? ($a['location'] ?: '—')) ?></td>
                <td><span class="badge <?= ['good' => 'badge-success', 'fair' => 'badge-warning', 'poor' => 'badge-danger'][$a['asset_condition']] ?>"><?= e(ucfirst($a['asset_condition'])) ?></span></td>
                <td><span class="badge <?= ['in_use' => 'badge-success', 'spare' => 'badge-muted', 'in_repair' => 'badge-warning', 'retired' => 'badge-muted'][$a['status']] ?>"><?= e(ASSET_STATUSES[$a['status']]) ?></span></td>
                <td><?= (float) $a['repair_cost'] > 0 ? format_naira((float) $a['repair_cost']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$assets): ?><tr><td colspan="7" class="admin-empty">No equipment recorded yet. <a href="<?= path('admin/equipment/new') ?>">Add the PS5s, controllers and VR headsets</a>.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
