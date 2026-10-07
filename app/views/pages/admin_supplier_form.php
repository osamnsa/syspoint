<?php
declare(strict_types=1);

/** @var array $params [id] when editing */

$adminUser = require_admin();

$supplierId = isset($params[0]) ? (int) $params[0] : 0;
$supplier = null;
if ($supplierId) {
    $stmt = db()->prepare('SELECT * FROM suppliers WHERE id = :id');
    $stmt->execute(['id' => $supplierId]);
    $supplier = $stmt->fetch() ?: null;
    if (!$supplier) {
        http_response_code(404);
        require __DIR__ . '/not_found.php';
        return;
    }
}

$fields = ['name', 'contact_name', 'phone', 'email', 'address', 'notes'];
$values = [];
foreach ($fields as $f) $values[$f] = $supplier[$f] ?? '';
$values['is_active'] = (int) ($supplier['is_active'] ?? 1);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        foreach ($fields as $f) $values[$f] = trim((string) ($_POST[$f] ?? ''));
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        if ($values['name'] === '') $errors[] = 'Please enter the supplier’s name.';
        if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

        if (!$errors) {
            $data = array_map(fn($v) => $v === '' ? null : $v, $values);
            $data['name'] = $values['name'];
            if ($supplier) {
                $data['id'] = $supplier['id'];
                db()->prepare('UPDATE suppliers SET name = :name, contact_name = :contact_name, phone = :phone, email = :email,
                               address = :address, notes = :notes, is_active = :is_active WHERE id = :id')->execute($data);
            } else {
                db()->prepare('INSERT INTO suppliers (name, contact_name, phone, email, address, notes, is_active)
                               VALUES (:name, :contact_name, :phone, :email, :address, :notes, :is_active)')->execute($data);
            }
            flash('success', 'Supplier saved.');
            header('Location: ' . path('admin/suppliers'));
            exit;
        }
    }
}

$pageTitle = $supplier ? 'Edit Supplier' : 'Add Supplier';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path('admin/suppliers') ?>" class="btn btn-outline btn-sm">Back to Suppliers</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="name">Supplier / Company Name</label>
        <input type="text" id="name" name="name" value="<?= e((string) $values['name']) ?>" required>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="contact_name">Contact Person</label>
            <input type="text" id="contact_name" name="contact_name" value="<?= e((string) $values['contact_name']) ?>">
        </div>
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?= e((string) $values['phone']) ?>">
        </div>
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e((string) $values['email']) ?>">
    </div>
    <div class="form-group">
        <label for="address">Address</label>
        <textarea id="address" name="address" rows="2" style="min-height:70px;"><?= e((string) $values['address']) ?></textarea>
    </div>
    <div class="form-group">
        <label for="notes">Notes (payment terms, delivery days, account numbers…)</label>
        <textarea id="notes" name="notes"><?= e((string) $values['notes']) ?></textarea>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> style="width:auto;margin-right:8px;"> Active</label>
    </div>
    <button type="submit" class="btn btn-primary">Save Supplier</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
