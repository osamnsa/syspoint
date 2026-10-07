<?php
declare(strict_types=1);

/** @var array $params [id] when editing */

$adminUser = require_admin();

$dealId = isset($params[0]) ? (int) $params[0] : 0;
$deal = $dealId ? deal_by_id($dealId) : null;
if ($dealId && !$deal) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}
$customers = db()->query("SELECT c.id, c.name, c.type, o.name AS org FROM customers c LEFT JOIN customers o ON o.id = c.organisation_id ORDER BY c.name")->fetchAll();
$staff = crm_staff();

$values = [
    'title' => $deal['title'] ?? '',
    'customer_id' => (string) ($deal['customer_id'] ?? ($_GET['customer'] ?? '')),
    'service' => $deal['service'] ?? 'software',
    'value' => $deal['value'] ?? '',
    'stage' => $deal['stage'] ?? 'lead',
    'expected_close' => $deal['expected_close'] ?? date('Y-m-d', strtotime('+30 days')),
    'owner_id' => (string) ($deal['owner_id'] ?? $adminUser['id']),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        foreach ($values as $k => $_) $values[$k] = trim((string) ($_POST[$k] ?? ''));
        if ($values['title'] === '') $errors[] = 'Give the deal a short title.';
        if (!array_filter($customers, fn($c) => (string) $c['id'] === $values['customer_id'])) $errors[] = 'Choose the customer.';
        if (!isset(DEAL_SERVICES[$values['service']])) $values['service'] = 'other';
        if ($values['value'] === '' || !is_numeric($values['value']) || (float) $values['value'] < 0) $errors[] = 'Enter the deal value (0 if unknown yet).';
        if (!isset(DEAL_STAGES[$values['stage']])) $values['stage'] = 'lead';
        if ($values['expected_close'] !== '' && !DateTimeImmutable::createFromFormat('Y-m-d', $values['expected_close'])) $errors[] = 'Enter a valid close date.';
        if (!array_filter($staff, fn($u) => (string) $u['id'] === $values['owner_id'])) $values['owner_id'] = '';

        if (!$errors) {
            $data = ['t' => $values['title'], 'c' => $values['customer_id'], 's' => $values['service'], 'v' => $values['value'],
                     'ec' => $values['expected_close'] ?: null, 'o' => $values['owner_id'] ?: null];
            if ($deal) {
                db()->prepare('UPDATE deals SET title = :t, customer_id = :c, service = :s, value = :v, expected_close = :ec, owner_id = :o WHERE id = :id')
                    ->execute($data + ['id' => $dealId]);
                deal_set_stage(deal_by_id($dealId), $values['stage']);
                $id = $dealId;
            } else {
                db()->prepare('INSERT INTO deals (title, customer_id, service, value, stage, expected_close, owner_id, request_id, closed_at)
                               VALUES (:t, :c, :s, :v, :stage, :ec, :o, :r, ' . (in_array($values['stage'], ['won', 'lost'], true) ? 'NOW()' : 'NULL') . ')')
                    ->execute($data + ['stage' => $values['stage'], 'r' => (int) ($_POST['request_id'] ?? 0) ?: null]);
                $id = (int) db()->lastInsertId();
                crm_log((int) $values['customer_id'], $id, 'stage', 'New deal: ' . $values['title'] . ' · ' . format_naira((float) $values['value']));
            }
            flash('success', 'Deal saved.');
            header('Location: ' . path('admin/deals/' . $id));
            exit;
        }
    }
}

$pageTitle = $deal ? 'Edit Deal' : 'New Deal';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path($deal ? 'admin/deals/' . $dealId : 'admin/deals') ?>" class="btn btn-outline btn-sm">Back</a>
</div>

<?php if (!$customers): ?><div class="alert alert-error">Add a customer first — <a href="<?= path('admin/customers/new') ?>">Add Customer</a>.</div><?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="admin-form">
    <?= csrf_field() ?>
    <?php if (!empty($_GET['request'])): ?><input type="hidden" name="request_id" value="<?= (int) $_GET['request'] ?>"><?php endif; ?>
    <div class="form-group"><label for="title">Deal</label><input type="text" id="title" name="title" value="<?= e((string) $values['title']) ?>" placeholder="e.g. School management system for Ladela Schools" required></div>
    <div class="form-row">
        <div class="form-group"><label for="customer_id">Customer</label>
            <select id="customer_id" name="customer_id" required><option value="">Choose…</option>
                <?php foreach ($customers as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $values['customer_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?><?= $c['org'] ? ' (' . e($c['org']) . ')' : '' ?></option><?php endforeach; ?>
            </select>
            <div class="form-note">Not listed? <a href="<?= path('admin/customers/new') ?>">Add the customer</a> first.</div></div>
        <div class="form-group"><label for="service">Service</label>
            <select id="service" name="service"><?php foreach (DEAL_SERVICES as $k => $l): ?><option value="<?= $k ?>" <?= $values['service'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="value">Value (₦)</label><input type="number" id="value" name="value" min="0" step="1000" value="<?= e((string) $values['value']) ?>" required></div>
        <div class="form-group"><label for="stage">Stage</label>
            <select id="stage" name="stage"><?php foreach (DEAL_STAGES as $k => $s): ?><option value="<?= $k ?>" <?= $values['stage'] === $k ? 'selected' : '' ?>><?= e($s['label']) ?> (<?= $s['chance'] ?>%)</option><?php endforeach; ?></select></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="expected_close">Expected close</label><input type="date" id="expected_close" name="expected_close" value="<?= e((string) $values['expected_close']) ?>"></div>
        <div class="form-group"><label for="owner_id">Owner</label>
            <select id="owner_id" name="owner_id"><option value="">Unassigned</option><?php foreach ($staff as $u): ?><option value="<?= (int) $u['id'] ?>" <?= $values['owner_id'] === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div>
    </div>
    <button type="submit" class="btn btn-primary">Save Deal</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
