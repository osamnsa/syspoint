<?php
declare(strict_types=1);

$adminUser = require_admin();

$errors = [];
$values = ['name' => $adminUser['name'], 'email' => $adminUser['email']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');

        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $adminUser['id']]);
        if (!password_verify($current, (string) $stmt->fetchColumn())) $errors[] = 'Your current password is incorrect.';
        if ($values['name'] === '') $errors[] = 'Please enter your name.';
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($new !== '' && strlen($new) < 10) $errors[] = 'Your new password must be at least 10 characters.';
        if ($new !== '' && $new !== (string) ($_POST['confirm_password'] ?? '')) $errors[] = 'The new passwords don’t match.';
        $dupe = db()->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
        $dupe->execute(['email' => $values['email'], 'id' => $adminUser['id']]);
        if ((int) $dupe->fetchColumn() > 0) $errors[] = 'Another account already uses that email.';

        if (!$errors) {
            $data = ['name' => $values['name'], 'email' => $values['email'], 'id' => $adminUser['id']];
            $sql = 'UPDATE users SET name = :name, email = :email';
            if ($new !== '') {
                $sql .= ', password_hash = :hash';
                $data['hash'] = password_hash($new, PASSWORD_DEFAULT);
            }
            db()->prepare($sql . ' WHERE id = :id')->execute($data);
            flash('success', 'Your account has been updated.');
            header('Location: ' . path('admin/account'));
            exit;
        }
    }
}

$pageTitle = 'My Account';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row"><h1>My Account</h1></div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email (used to sign in)</label>
        <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" required autocomplete="username">
    </div>
    <div class="form-group">
        <label for="new_password">New password (optional)</label>
        <input type="password" id="new_password" name="new_password" minlength="10" autocomplete="new-password">
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm new password</label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="10" autocomplete="new-password">
    </div>
    <div class="form-group">
        <label for="current_password">Current password (required to save)</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
