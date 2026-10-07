<?php
declare(strict_types=1);

/** @var array $params [id] when editing */

$adminUser = require_admin();

$customerId = isset($params[0]) ? (int) $params[0] : 0;
$customer = $customerId ? crm_customer_by_id($customerId) : null;
if ($customerId && !$customer) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}
$orgs = db()->query("SELECT id, name FROM customers WHERE type = 'organisation' ORDER BY name")->fetchAll();
$staff = crm_staff();

$values = [
    'type' => $customer['type'] ?? ((string) ($_GET['type'] ?? '') === 'organisation' ? 'organisation' : 'person'),
    'name' => $customer['name'] ?? '',
    'organisation_id' => (string) ($customer['organisation_id'] ?? ($_GET['organisation'] ?? '')),
    'email' => $customer['email'] ?? '',
    'phone' => $customer['phone'] ?? '',
    'address' => $customer['address'] ?? '',
    'source' => $customer['source'] ?? 'walk_in',
    'tags' => $customer['tags'] ?? '',
    'notes' => $customer['notes'] ?? '',
    'owner_id' => (string) ($customer['owner_id'] ?? $adminUser['id']),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'delete' && $customer && admin_is_admin()) {
        $linked = (int) db()->query('SELECT (SELECT COUNT(*) FROM crm_documents WHERE customer_id = ' . $customerId . ')')->fetchColumn();
        if ($linked) {
            $errors[] = 'This customer has quotes or invoices, so it can’t be deleted.';
        } else {
            foreach (['orders', 'room_bookings', 'software_requests', 'contact_messages', 'pos_sales'] as $t) {
                db()->prepare("UPDATE $t SET customer_id = NULL WHERE customer_id = :id")->execute(['id' => $customerId]);
            }
            db()->prepare('DELETE FROM customers WHERE id = :id')->execute(['id' => $customerId]);
            flash('success', 'Customer deleted.');
            header('Location: ' . path('admin/customers'));
            exit;
        }
    } else {
        foreach ($values as $k => $_) $values[$k] = trim((string) ($_POST[$k] ?? ''));
        $values['email'] = strtolower($values['email']);
        if (!in_array($values['type'], ['person', 'organisation'], true)) $values['type'] = 'person';
        if ($values['name'] === '') $errors[] = 'Enter a name.';
        if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (!isset(CUSTOMER_SOURCES[$values['source']])) $values['source'] = 'other';
        if ($values['type'] === 'organisation' || !array_filter($orgs, fn($o) => (string) $o['id'] === $values['organisation_id'] && (int) $o['id'] !== $customerId)) $values['organisation_id'] = '';
        if (!array_filter($staff, fn($u) => (string) $u['id'] === $values['owner_id'])) $values['owner_id'] = '';
        if ($values['email'] !== '') {
            $d = db()->prepare('SELECT id, name FROM customers WHERE email = :e AND id <> :id');
            $d->execute(['e' => $values['email'], 'id' => $customerId]);
            if ($dupe = $d->fetch()) $errors[] = 'That email already belongs to ' . $dupe['name'] . '.';
        }

        if (!$errors) {
            $data = array_map(fn($v) => $v === '' ? null : $v, $values);
            $data['name'] = $values['name'];
            $data['type'] = $values['type'];
            $data['source'] = $values['source'];
            $data['phone_digits'] = crm_phone_digits($values['phone']);
            if ($customer) {
                db()->prepare('UPDATE customers SET type = :type, name = :name, organisation_id = :organisation_id, email = :email, phone = :phone,
                    phone_digits = :phone_digits, address = :address, source = :source, tags = :tags, notes = :notes, owner_id = :owner_id WHERE id = :id')
                    ->execute($data + ['id' => $customerId]);
                $id = $customerId;
            } else {
                db()->prepare('INSERT INTO customers (type, name, organisation_id, email, phone, phone_digits, address, source, tags, notes, owner_id, last_activity_at)
                    VALUES (:type, :name, :organisation_id, :email, :phone, :phone_digits, :address, :source, :tags, :notes, :owner_id, NOW())')->execute($data);
                $id = (int) db()->lastInsertId();
            }
            flash('success', 'Customer saved.');
            header('Location: ' . path('admin/customers/' . $id));
            exit;
        }
    }
}

$pageTitle = $customer ? 'Edit ' . $customer['name'] : 'Add Customer';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path($customer ? 'admin/customers/' . $customerId : 'admin/customers') ?>" class="btn btn-outline btn-sm">Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="admin-form">
    <?= csrf_field() ?>
    <fieldset class="form-group admin-fieldset">
        <legend>This is a…</legend>
        <div class="admin-choice-row">
            <label class="admin-choice"><input type="radio" name="type" value="person" <?= $values['type'] === 'person' ? 'checked' : '' ?>> <span>Person</span></label>
            <label class="admin-choice"><input type="radio" name="type" value="organisation" <?= $values['type'] === 'organisation' ? 'checked' : '' ?>> <span>Organisation (company, school, agency…)</span></label>
        </div>
    </fieldset>
    <div class="form-row">
        <div class="form-group"><label for="name">Name</label><input type="text" id="name" name="name" value="<?= e((string) $values['name']) ?>" required></div>
        <div class="form-group"><label for="organisation_id">Works at (for people)</label>
            <select id="organisation_id" name="organisation_id"><option value="">—</option><?php foreach ($orgs as $o): if ((int) $o['id'] === $customerId) continue; ?><option value="<?= (int) $o['id'] ?>" <?= $values['organisation_id'] === (string) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e((string) $values['email']) ?>"></div>
        <div class="form-group"><label for="phone">Phone / WhatsApp</label><input type="tel" id="phone" name="phone" value="<?= e((string) $values['phone']) ?>"></div>
    </div>
    <div class="form-group"><label for="address">Address</label><textarea id="address" name="address" rows="2" style="min-height:70px;"><?= e((string) $values['address']) ?></textarea></div>
    <div class="form-row">
        <div class="form-group"><label for="source">How they found us</label>
            <select id="source" name="source"><?php foreach (CUSTOMER_SOURCES as $k => $l): ?><option value="<?= $k ?>" <?= $values['source'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="owner_id">Account owner</label>
            <select id="owner_id" name="owner_id"><option value="">Unassigned</option><?php foreach ($staff as $u): ?><option value="<?= (int) $u['id'] ?>" <?= $values['owner_id'] === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-group"><label for="tags">Tags</label><input type="text" id="tags" name="tags" value="<?= e((string) $values['tags']) ?>" placeholder="e.g. school, VIP, government"><div class="form-note">Comma-separated — handy for searching.</div></div>
    <div class="form-group"><label for="notes">Background notes</label><textarea id="notes" name="notes"><?= e((string) $values['notes']) ?></textarea></div>
    <button type="submit" class="btn btn-primary">Save Customer</button>
</form>

<?php if ($customer && admin_is_admin()): ?>
<details class="admin-danger-zone">
    <summary>Delete this customer</summary>
    <form method="post" onsubmit="return confirm('Delete this customer? Their orders and bookings stay, but lose the link.');">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete">
        <button type="submit" class="btn btn-outline admin-btn-on-dark">Delete Customer</button>
    </form>
</details>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
