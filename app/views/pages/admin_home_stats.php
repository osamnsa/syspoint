<?php
declare(strict_types=1);

$adminUser = require_admin();

$errors = [];
$values = [];
foreach (HOME_STATS as $key => $label) {
    $values[$key] = content_block($key, '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        foreach (HOME_STATS as $key => $label) {
            $field = str_replace('.', '_', $key);
            $values[$key] = trim((string) ($_POST[$field] ?? ''));
            // Free text on purpose ("500+", "12", "1,200") — but short, it's a headline number.
            if (mb_strlen($values[$key]) > 12) {
                $errors[] = $label . ' should be a short figure, like 500+ or 12.';
            }
        }

        if (!$errors) {
            foreach ($values as $key => $value) {
                content_block_save($key, $value);
            }
            flash('success', 'Homepage stats saved.');
            header('Location: ' . path('admin/home-stats'));
            exit;
        }
    }
}

$successMessage = flash('success');
$pageTitle = 'Homepage Stats';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Homepage Stats</h1>
    <a href="<?= path() ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">View Homepage</a>
</div>

<?php if ($successMessage): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= path('admin/home-stats') ?>" class="admin-form">
    <?= csrf_field() ?>
    <?php foreach (HOME_STATS as $key => $label): $field = str_replace('.', '_', $key); ?>
        <div class="form-group">
            <label for="<?= e($field) ?>"><?= e($label) ?></label>
            <input type="text" id="<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e($values[$key]) ?>" maxlength="12" placeholder="e.g. 500+">
        </div>
    <?php endforeach; ?>
    <div class="form-note" style="margin-bottom:16px;">Leave a figure empty to hide it. The stats band only appears on the homepage once at least one is filled in.</div>
    <button type="submit" class="btn btn-primary">Save Stats</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
