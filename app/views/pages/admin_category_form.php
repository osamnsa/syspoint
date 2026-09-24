<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$category = product_category_by_id((int) ($params[0] ?? 0));
if (!$category) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$errors = [];
$values = ['name' => $category['name'], 'sort_order' => $category['sort_order']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['sort_order'] = (string) ($_POST['sort_order'] ?? '0');

        if ($values['name'] === '') $errors[] = 'Please enter a category name.';
        if (!ctype_digit($values['sort_order'])) $errors[] = 'Please enter a valid sort order.';

        $upload = handle_image_upload('image', 'categories');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        }

        if (!$errors) {
            $slug = ($values['name'] !== $category['name'])
                ? category_generate_slug($values['name'], $category['id'])
                : $category['slug'];
            $imagePath = $upload['path'] ?? $category['image_path'];

            db()->prepare(
                'UPDATE product_categories SET name = :name, slug = :slug, sort_order = :sort_order, image_path = :image_path WHERE id = :id'
            )->execute([
                'name' => $values['name'],
                'slug' => $slug,
                'sort_order' => $values['sort_order'],
                'image_path' => $imagePath,
                'id' => $category['id'],
            ]);

            flash('success', 'Category saved.');
            header('Location: ' . path('admin/categories'));
            exit;
        }
    }
}

$pageTitle = 'Edit Category';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Edit Category</h1>
    <a href="<?= path('admin/categories') ?>" class="btn btn-outline btn-sm">Back to Categories</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= path('admin/categories/' . $category['id'] . '/edit') ?>" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= e((string) $values['name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string) $values['sort_order']) ?>" min="0">
    </div>
    <div class="form-group">
        <label for="image">Category Image</label>
        <?php if ($category['image_path']): ?>
            <img src="<?= asset(e($category['image_path'])) ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block;">
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <div class="form-note">JPG, PNG, or WEBP, up to 5MB. Leave empty to keep the current image.</div>
    </div>
    <button type="submit" class="btn btn-primary">Save Category</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
