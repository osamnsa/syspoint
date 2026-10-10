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
$values = ['name' => $category['name'], 'sort_order' => $category['sort_order'],
           'hero_kicker' => $category['hero_kicker'] ?? '', 'hero_title' => $category['hero_title'] ?? '', 'hero_text' => $category['hero_text'] ?? ''];
$heroDefaults = category_hero(['slug' => $category['slug'], 'name' => $category['name']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['sort_order'] = (string) ($_POST['sort_order'] ?? '0');
        foreach (['hero_kicker' => 120, 'hero_title' => 190, 'hero_text' => 500] as $k => $max) $values[$k] = mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);

        if ($values['name'] === '') $errors[] = 'Please enter a category name.';
        if (!ctype_digit($values['sort_order'])) $errors[] = 'Please enter a valid sort order.';

        $upload = handle_image_upload('image', 'categories');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        }
        $heroUpload = handle_image_upload('hero_image', 'categories');
        if (!$heroUpload['ok']) $errors[] = $heroUpload['error'];

        if (!$errors) {
            $slug = ($values['name'] !== $category['name'])
                ? category_generate_slug($values['name'], $category['id'])
                : $category['slug'];
            $imagePath = $upload['path'] ?? $category['image_path'];

            db()->prepare(
                'UPDATE product_categories SET name = :name, slug = :slug, sort_order = :sort_order, image_path = :image_path,
                 hero_kicker = :hk, hero_title = :ht, hero_text = :hx, hero_image = :hi WHERE id = :id'
            )->execute([
                'hk' => $values['hero_kicker'] ?: null, 'ht' => $values['hero_title'] ?: null, 'hx' => $values['hero_text'] ?: null,
                'hi' => isset($_POST['remove_hero_image']) ? ($heroUpload['path'] ?? null) : ($heroUpload['path'] ?? $category['hero_image']),
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
    <h2 class="admin-form-title" style="margin-top:28px;">Category page hero</h2>
    <p class="form-note" style="margin-top:-6px;">Shown at the top of <a href="<?= path('shop/' . e($category['slug'])) ?>" target="_blank" rel="noopener">/shop/<?= e($category['slug']) ?></a>. Leave a field empty to use the text in grey.</p>
    <div class="form-group">
        <label for="hero_kicker">Small line above the heading</label>
        <input type="text" id="hero_kicker" name="hero_kicker" maxlength="120" value="<?= e((string) $values['hero_kicker']) ?>" placeholder="<?= e($heroDefaults['kicker']) ?>">
    </div>
    <div class="form-group">
        <label for="hero_title">Heading</label>
        <input type="text" id="hero_title" name="hero_title" maxlength="190" value="<?= e((string) $values['hero_title']) ?>" placeholder="<?= e(trim($heroDefaults['title'] . ' ' . $heroDefaults['highlight'])) ?>">
        <div class="form-note">Empty: the default heading, with its last word in the gold script.</div>
    </div>
    <div class="form-group">
        <label for="hero_text">Intro</label>
        <textarea id="hero_text" name="hero_text" rows="3" maxlength="500" placeholder="<?= e($heroDefaults['text']) ?>"><?= e((string) $values['hero_text']) ?></textarea>
    </div>
    <div class="form-group">
        <label for="hero_image">Hero picture</label>
        <?php if (!empty($category['hero_image'])): ?>
            <div class="site-image"><img src="<?= media_url($category['hero_image']) ?>" alt=""><label class="admin-choice"><input type="checkbox" name="remove_hero_image" value="1"> <span>Remove (use the category image)</span></label></div>
        <?php endif; ?>
        <input type="file" id="hero_image" name="hero_image" accept="image/jpeg,image/png,image/webp">
        <div class="form-note">A product photo with a transparent background (PNG) floats on the hero; a normal photo is shown in a soft circle. Empty: the category image above.</div>
    </div>
    <button type="submit" class="btn btn-primary">Save Category</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
