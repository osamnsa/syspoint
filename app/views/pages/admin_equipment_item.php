<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$assetId = (int) ($params[0] ?? 0);
$stmt = db()->prepare('SELECT a.*, r.name AS room_name FROM assets a LEFT JOIN gaming_rooms r ON r.id = a.room_id WHERE a.id = :id');
$stmt->execute(['id' => $assetId]);
$asset = $stmt->fetch();
if (!$asset) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $type = (string) ($_POST['type'] ?? 'note');
    $desc = trim((string) ($_POST['description'] ?? ''));
    $cost = trim((string) ($_POST['cost'] ?? ''));
    if (!in_array($type, ['note', 'repair'], true) || $desc === '') {
        $error = 'Describe what happened.';
    } elseif ($cost !== '' && !is_numeric($cost)) {
        $error = 'Enter a valid cost.';
    } else {
        db()->prepare('INSERT INTO asset_logs (asset_id, type, description, cost, user_id) VALUES (:a, :t, :d, :c, :u)')
            ->execute(['a' => $assetId, 't' => $type, 'd' => $desc, 'c' => $cost === '' ? null : $cost, 'u' => $adminUser['id']]);
        if ($type === 'repair' && isset($_POST['back_in_use'])) {
            db()->prepare("UPDATE assets SET status = 'in_use' WHERE id = :id")->execute(['id' => $assetId]);
        } elseif ($type === 'repair' && isset($_POST['send_to_repair'])) {
            db()->prepare("UPDATE assets SET status = 'in_repair' WHERE id = :id")->execute(['id' => $assetId]);
        }
        flash('success', 'Logged.');
        header('Location: ' . path('admin/equipment/' . $assetId));
        exit;
    }
}

$logs = db()->prepare('SELECT l.*, u.name AS user_name FROM asset_logs l LEFT JOIN users u ON u.id = l.user_id WHERE l.asset_id = :id ORDER BY l.id DESC');
$logs->execute(['id' => $assetId]);
$logs = $logs->fetchAll();
$repairTotal = array_sum(array_map(fn($l) => $l['type'] === 'repair' ? (float) $l['cost'] : 0, $logs));

$pageTitle = $asset['asset_tag'] . ' · ' . $asset['name'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker"><?= e($asset['asset_tag']) ?> · <?= e(ASSET_CATEGORIES[$asset['category']]) ?></p><h1><?= e($asset['name']) ?></h1></div>
    <div class="admin-header-actions">
        <a href="<?= path('admin/equipment/' . $assetId . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
        <a href="<?= path('admin/equipment') ?>" class="btn btn-outline btn-sm">All Equipment</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="pos-today">
    <div><span>Status</span><strong class="<?= $asset['status'] === 'in_repair' ? 'is-warn' : '' ?>"><?= e(ASSET_STATUSES[$asset['status']]) ?></strong></div>
    <div><span>Condition</span><strong><?= e(ucfirst($asset['asset_condition'])) ?></strong></div>
    <div><span>Where</span><strong><?= e($asset['room_name'] ?? ($asset['location'] ?: '—')) ?></strong></div>
    <div><span>Bought</span><strong><?= $asset['purchase_cost'] !== null ? format_naira((float) $asset['purchase_cost']) : '—' ?></strong><?= $asset['purchase_date'] ? '<small>' . e((new DateTimeImmutable($asset['purchase_date']))->format('j M Y')) . '</small>' : '' ?></div>
    <div><span>Repairs so far</span><strong><?= format_naira($repairTotal) ?></strong></div>
</div>

<div class="admin-split">
    <form method="post" class="admin-form">
        <?= csrf_field() ?>
        <h2 class="admin-form-title">Add to history</h2>
        <div class="form-group">
            <label for="type">Type</label>
            <select id="type" name="type"><option value="repair">Repair / service</option><option value="note">Note</option></select>
        </div>
        <div class="form-group"><label for="description">What happened</label><textarea id="description" name="description" rows="3" required placeholder="e.g. Left stick drift — replaced joystick module"></textarea></div>
        <div class="form-group"><label for="cost">Cost (₦, optional)</label><input type="number" id="cost" name="cost" min="0" step="0.01"></div>
        <?php if ($asset['status'] === 'in_repair'): ?>
            <label class="admin-choice"><input type="checkbox" name="back_in_use" value="1" checked> <span>Fixed — mark as back in use</span></label>
        <?php elseif ($asset['status'] !== 'retired'): ?>
            <label class="admin-choice"><input type="checkbox" name="send_to_repair" value="1"> <span>Mark as in repair</span></label>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>When</th><th>Entry</th><th>Cost</th><th>By</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td style="white-space:nowrap;"><?= e((new DateTimeImmutable($l['created_at']))->format('j M Y')) ?></td>
                    <td><span class="badge badge-muted"><?= e($l['type']) ?></span> <?= e($l['description']) ?></td>
                    <td><?= $l['cost'] !== null ? format_naira((float) $l['cost']) : '—' ?></td>
                    <td><?= e($l['user_name'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td colspan="4" class="admin-empty">No history yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <?php if ($asset['notes']): ?><p class="admin-doc-notes" style="padding:0 16px 16px;"><?= nl2br(e($asset['notes'])) ?></p><?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
