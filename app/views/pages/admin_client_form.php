<?php
declare(strict_types=1);

/** @var array $params [id] when editing, empty when creating */

$adminUser = require_admin();

$clientId = isset($params[0]) ? (int) $params[0] : 0;
$client = $clientId ? client_by_id($clientId) : null;

if ($clientId && !$client) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$errors = [];
$values = [
    'name' => $client['name'] ?? '',
        'website_url' => $client['website_url'] ?? '',
    'sort_order' => $client['sort_order'] ?? 0,
    'is_active' => $client['is_active'] ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['website_url'] = trim((string) ($_POST['website_url'] ?? ''));
        $values['sort_order'] = (string) ($_POST['sort_order'] ?? '0');
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if ($values['name'] === '') $errors[] = "Please enter the client's name.";
        if (!ctype_digit($values['sort_order'])) $errors[] = 'Please enter a valid sort order.';
        if ($values['website_url'] !== '' && !filter_var($values['website_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid website URL (including https://).';
        }

        $upload = handle_image_upload('logo', 'clients');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        }

        if (!$errors) {
            $logoPath = $upload['path'] ?? ($client['logo_path'] ?? null);

            if ($client) {
                db()->prepare(
                    'UPDATE clients SET name = :name, website_url = :website_url,
                     sort_order = :sort_order, is_active = :is_active, logo_path = :logo_path WHERE id = :id'
                )->execute([
                    'name' => $values['name'],
                    'website_url' => $values['website_url'] ?: null,
                    'sort_order' => $values['sort_order'],
                    'is_active' => $values['is_active'],
                    'logo_path' => $logoPath,
                    'id' => $client['id'],
                ]);
            } else {
                db()->prepare(
                    'INSERT INTO clients (name, website_url, sort_order, is_active, logo_path)
                     VALUES (:name, :website_url, :sort_order, :is_active, :logo_path)'
                )->execute([
                    'name' => $values['name'],
                    'website_url' => $values['website_url'] ?: null,
                    'sort_order' => $values['sort_order'],
                    'is_active' => $values['is_active'],
                    'logo_path' => $logoPath,
                ]);
            }

            flash('success', 'Client saved.');
            header('Location: ' . path('admin/clients'));
            exit;
        }
    }
}

$pageTitle = $client ? 'Edit Client' : 'Add Client';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path('admin/clients') ?>" class="btn btn-outline btn-sm">Back to Clients</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= path($client ? 'admin/clients/' . $client['id'] . '/edit' : 'admin/clients/new') ?>" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= e((string) $values['name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="website_url">Website URL (optional)</label>
        <input type="url" id="website_url" name="website_url" value="<?= e((string) $values['website_url']) ?>" placeholder="https://">
    </div>
    <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string) $values['sort_order']) ?>" min="0">
    </div>
    <div class="form-group">
        <label for="logo">Logo</label>
        <?php if (!empty($client['logo_path'])): ?>
            <img src="<?= media_url($client['logo_path']) ?>" alt="" style="width:64px;height:64px;object-fit:contain;border-radius:8px;margin-bottom:8px;display:block;">
        <?php endif; ?>
        <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
        <div class="form-note">JPG, PNG, or WEBP, up to 5MB. Leave empty to keep the current logo. Shown greyscale until hovered, so any colour logo works.</div>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> style="width:auto;margin-right:8px;"> Visible on the site</label>
    </div>
    <button type="submit" class="btn btn-primary">Save Client</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
