<?php
declare(strict_types=1);

/** @var array $params [id] when editing, empty when creating */

$adminUser = require_admin();

$staffId = isset($params[0]) ? (int) $params[0] : 0;
$member = null;
if ($staffId) {
    $stmt = db()->prepare('SELECT id, name, email, role, areas, is_active FROM users WHERE id = :id');
    $stmt->execute(['id' => $staffId]);
    $member = $stmt->fetch() ?: null;
    if (!$member) {
        http_response_code(404);
        require __DIR__ . '/not_found.php';
        return;
    }
}
$isSelf = $member && (int) $member['id'] === (int) $adminUser['id'];

$errors = [];
$values = [
    'name' => $member['name'] ?? '',
    'email' => $member['email'] ?? '',
    'role' => $member['role'] ?? 'staff',
    'areas' => $member ? admin_user_areas(['role' => 'staff', 'areas' => $member['areas']]) : [],
    'is_active' => (int) ($member['is_active'] ?? 1),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
        $values['role'] = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'staff';
        $values['areas'] = array_values(array_intersect(array_keys(ADMIN_AREAS), (array) ($_POST['areas'] ?? [])));
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $password = (string) ($_POST['password'] ?? '');

        if ($isSelf) {
            // You can't lock yourself out.
            $values['role'] = $member['role'];
            $values['is_active'] = 1;
        }

        if ($values['name'] === '') $errors[] = 'Please enter a name.';
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (!$member && strlen($password) < 10) $errors[] = 'Please set a password of at least 10 characters.';
        if ($member && $password !== '' && strlen($password) < 10) $errors[] = 'A new password must be at least 10 characters.';

        $dupe = db()->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
        $dupe->execute(['email' => $values['email'], 'id' => $staffId]);
        if ((int) $dupe->fetchColumn() > 0) $errors[] = 'Another account already uses that email.';

        // Keep at least one active administrator.
        if ($member && $member['role'] === 'admin' && ($values['role'] !== 'admin' || !$values['is_active'])) {
            $admins = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
            if ($admins <= 1) $errors[] = 'This is the only active administrator — make someone else an administrator first.';
        }

        if (!$errors) {
            $data = [
                'name' => $values['name'],
                'email' => $values['email'],
                'role' => $values['role'],
                'areas' => $values['role'] === 'admin' ? null : (implode(',', $values['areas']) ?: null),
                'is_active' => $values['is_active'],
            ];
            if ($member) {
                $sql = 'UPDATE users SET name = :name, email = :email, role = :role, areas = :areas, is_active = :is_active';
                if ($password !== '') {
                    $sql .= ', password_hash = :hash';
                    $data['hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $data['id'] = $member['id'];
                db()->prepare($sql . ' WHERE id = :id')->execute($data);
            } else {
                $data['hash'] = password_hash($password, PASSWORD_DEFAULT);
                db()->prepare('INSERT INTO users (name, email, role, areas, is_active, password_hash) VALUES (:name, :email, :role, :areas, :is_active, :hash)')->execute($data);
            }
            flash('success', 'Staff saved.');
            header('Location: ' . path('admin/staff'));
            exit;
        }
    }
}

$pageTitle = $member ? 'Edit Staff' : 'Add Staff';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path('admin/staff') ?>" class="btn btn-outline btn-sm">Back to Staff</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="admin-form" autocomplete="off">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email (used to sign in)</label>
        <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" required>
    </div>
    <div class="form-group">
        <label for="password"><?= $member ? 'New password (leave empty to keep the current one)' : 'Password' ?></label>
        <input type="password" id="password" name="password" minlength="10" autocomplete="new-password" <?= $member ? '' : 'required' ?>>
        <div class="form-note">At least 10 characters. Share it with them privately; they can change it in My Account.</div>
    </div>
    <fieldset class="form-group admin-fieldset" <?= $isSelf ? 'disabled' : '' ?>>
        <legend>Role</legend>
        <label class="admin-choice"><input type="radio" name="role" value="staff" <?= $values['role'] !== 'admin' ? 'checked' : '' ?>> <span><strong>Staff</strong> — only the areas ticked below</span></label>
        <label class="admin-choice"><input type="radio" name="role" value="admin" <?= $values['role'] === 'admin' ? 'checked' : '' ?>> <span><strong>Administrator</strong> — everything, including Staff &amp; Roles</span></label>
        <?php if ($isSelf): ?><div class="form-note">You can’t change your own role.</div><?php endif; ?>
    </fieldset>
    <fieldset class="form-group admin-fieldset">
        <legend>Areas (for Staff)</legend>
        <?php foreach (ADMIN_AREAS as $key => $label): ?>
            <label class="admin-choice"><input type="checkbox" name="areas[]" value="<?= e($key) ?>" <?= in_array($key, $values['areas'], true) ? 'checked' : '' ?>> <span><?= e($label) ?></span></label>
        <?php endforeach; ?>
    </fieldset>
    <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?> style="width:auto;margin-right:8px;"> Active (can sign in)</label>
    </div>
    <button type="submit" class="btn btn-primary">Save Staff</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
