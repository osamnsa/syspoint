<?php
declare(strict_types=1);

/** @var array $params [id] when editing */

$adminUser = require_admin();

$assetId = isset($params[0]) ? (int) $params[0] : 0;
$asset = null;
if ($assetId) {
    $stmt = db()->prepare('SELECT * FROM assets WHERE id = :id');
    $stmt->execute(['id' => $assetId]);
    $asset = $stmt->fetch() ?: null;
    if (!$asset) {
        http_response_code(404);
        require __DIR__ . '/not_found.php';
        return;
    }
}
$rooms = db()->query('SELECT id, name FROM gaming_rooms ORDER BY name')->fetchAll();

$nextTag = function (): string {
    $n = (int) db()->query("SELECT COALESCE(MAX(CAST(SUBSTRING(asset_tag, 5) AS UNSIGNED)), 0) FROM assets WHERE asset_tag LIKE 'HUB-%'")->fetchColumn();
    return sprintf('HUB-%03d', $n + 1);
};
$values = [
    'asset_tag' => $asset['asset_tag'] ?? $nextTag(),
    'name' => $asset['name'] ?? '',
    'category' => $asset['category'] ?? 'console',
    'room_id' => $asset['room_id'] ?? '',
    'location' => $asset['location'] ?? '',
    'serial' => $asset['serial'] ?? '',
    'purchase_date' => $asset['purchase_date'] ?? '',
    'purchase_cost' => $asset['purchase_cost'] ?? '',
    'status' => $asset['status'] ?? 'in_use',
    'asset_condition' => $asset['asset_condition'] ?? 'good',
    'notes' => $asset['notes'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        foreach ($values as $k => $_) $values[$k] = trim((string) ($_POST[$k] ?? ''));
        $values['asset_tag'] = strtoupper($values['asset_tag']);
        if ($values['asset_tag'] === '') $errors[] = 'Enter an asset tag.';
        if ($values['name'] === '') $errors[] = 'Enter a name.';
        if (!isset(ASSET_CATEGORIES[$values['category']])) $errors[] = 'Choose a type.';
        if (!isset(ASSET_STATUSES[$values['status']])) $errors[] = 'Choose a status.';
        if (!in_array($values['asset_condition'], ['good', 'fair', 'poor'], true)) $errors[] = 'Choose a condition.';
        if ($values['room_id'] !== '' && !array_filter($rooms, fn($r) => (string) $r['id'] === $values['room_id'])) $errors[] = 'Choose a valid room.';
        if ($values['purchase_cost'] !== '' && !is_numeric($values['purchase_cost'])) $errors[] = 'Enter a valid cost.';
        if ($values['purchase_date'] !== '' && !DateTimeImmutable::createFromFormat('Y-m-d', $values['purchase_date'])) $errors[] = 'Enter a valid purchase date.';
        $dupe = db()->prepare('SELECT COUNT(*) FROM assets WHERE asset_tag = :t AND id <> :id');
        $dupe->execute(['t' => $values['asset_tag'], 'id' => $assetId]);
        if ((int) $dupe->fetchColumn() > 0) $errors[] = 'That asset tag is already used.';

        if (!$errors) {
            $data = array_map(fn($v) => $v === '' ? null : $v, $values);
            if ($asset) {
                // Log moves and status changes automatically.
                $changes = [];
                if ((string) $asset['room_id'] !== (string) $values['room_id'] || (string) $asset['location'] !== $values['location']) $changes[] = ['moved', 'Moved'];
                if ($asset['status'] !== $values['status']) $changes[] = ['status', 'Status: ' . ASSET_STATUSES[$asset['status']] . ' → ' . ASSET_STATUSES[$values['status']]];
                db()->prepare('UPDATE assets SET asset_tag = :asset_tag, name = :name, category = :category, room_id = :room_id, location = :location,
                    serial = :serial, purchase_date = :purchase_date, purchase_cost = :purchase_cost, status = :status,
                    asset_condition = :asset_condition, notes = :notes WHERE id = :id')->execute($data + ['id' => $assetId]);
                foreach ($changes as [$type, $desc]) {
                    db()->prepare('INSERT INTO asset_logs (asset_id, type, description, user_id) VALUES (:a, :t, :d, :u)')
                        ->execute(['a' => $assetId, 't' => $type, 'd' => $desc, 'u' => $adminUser['id']]);
                }
                $id = $assetId;
            } else {
                db()->prepare('INSERT INTO assets (asset_tag, name, category, room_id, location, serial, purchase_date, purchase_cost, status, asset_condition, notes)
                    VALUES (:asset_tag, :name, :category, :room_id, :location, :serial, :purchase_date, :purchase_cost, :status, :asset_condition, :notes)')->execute($data);
                $id = (int) db()->lastInsertId();
            }
            flash('success', 'Equipment saved.');
            header('Location: ' . path('admin/equipment/' . $id));
            exit;
        }
    }
}

$pageTitle = $asset ? 'Edit ' . $asset['asset_tag'] : 'Add Equipment';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path('admin/equipment') ?>" class="btn btn-outline btn-sm">Back to Equipment</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group"><label for="name">Name</label><input type="text" id="name" name="name" value="<?= e((string) $values['name']) ?>" placeholder="e.g. PS5 Console #2" required></div>
        <div class="form-group"><label for="asset_tag">Asset Tag</label><input type="text" id="asset_tag" name="asset_tag" value="<?= e((string) $values['asset_tag']) ?>" required><div class="form-note">Write this on a sticker on the item.</div></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="category">Type</label>
            <select id="category" name="category"><?php foreach (ASSET_CATEGORIES as $k => $l): ?><option value="<?= $k ?>" <?= $values['category'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="serial">Serial Number</label><input type="text" id="serial" name="serial" value="<?= e((string) $values['serial']) ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="room_id">Room</label>
            <select id="room_id" name="room_id"><option value="">Not in a gaming room</option><?php foreach ($rooms as $r): ?><option value="<?= (int) $r['id'] ?>" <?= (string) $values['room_id'] === (string) $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="location">Other location</label><input type="text" id="location" name="location" value="<?= e((string) $values['location']) ?>" placeholder="e.g. Training room, store cupboard"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="status">Status</label>
            <select id="status" name="status"><?php foreach (ASSET_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $values['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="asset_condition">Condition</label>
            <select id="asset_condition" name="asset_condition"><?php foreach (['good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor'] as $k => $l): ?><option value="<?= $k ?>" <?= $values['asset_condition'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="purchase_date">Purchase Date</label><input type="date" id="purchase_date" name="purchase_date" value="<?= e((string) $values['purchase_date']) ?>"></div>
        <div class="form-group"><label for="purchase_cost">Purchase Cost (₦)</label><input type="number" id="purchase_cost" name="purchase_cost" min="0" step="0.01" value="<?= e((string) $values['purchase_cost']) ?>"></div>
    </div>
    <div class="form-group"><label for="notes">Notes</label><textarea id="notes" name="notes"><?= e((string) $values['notes']) ?></textarea></div>
    <button type="submit" class="btn btn-primary">Save Equipment</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
