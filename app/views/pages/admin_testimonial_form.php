<?php
declare(strict_types=1);

/** @var array $params [id] when editing, empty when creating */

$adminUser = require_admin();

$testimonialId = isset($params[0]) ? (int) $params[0] : 0;
$testimonial = $testimonialId ? testimonial_by_id($testimonialId) : null;

if ($testimonialId && !$testimonial) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$errors = [];
$values = [
    'quote' => $testimonial['quote'] ?? '',
    'author_name' => $testimonial['author_name'] ?? '',
    'author_company' => $testimonial['author_company'] ?? '',
    'sort_order' => $testimonial['sort_order'] ?? 0,
    'is_active' => $testimonial['is_active'] ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['quote'] = trim((string) ($_POST['quote'] ?? ''));
        $values['author_name'] = trim((string) ($_POST['author_name'] ?? ''));
        $values['author_company'] = trim((string) ($_POST['author_company'] ?? ''));
        $values['sort_order'] = (string) ($_POST['sort_order'] ?? '0');
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $removePhoto = isset($_POST['remove_photo']);

        if ($values['quote'] === '') $errors[] = 'Please enter the quote.';
        if ($values['author_name'] === '') $errors[] = "Please enter the person's name.";
        if (!ctype_digit($values['sort_order'])) $errors[] = 'Please enter a valid sort order.';

        $upload = handle_image_upload('photo', 'testimonials');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        }

        if (!$errors) {
            $photoPath = $upload['path'] ?? ($removePhoto ? null : ($testimonial['photo_path'] ?? null));
            $row = [
                'quote' => $values['quote'],
                'author_name' => $values['author_name'],
                'author_company' => $values['author_company'] ?: null,
                'photo_path' => $photoPath,
                'sort_order' => $values['sort_order'],
                'is_active' => $values['is_active'],
            ];

            if ($testimonial) {
                db()->prepare(
                    'UPDATE testimonials SET quote = :quote, author_name = :author_name, author_company = :author_company,
                     photo_path = :photo_path, sort_order = :sort_order, is_active = :is_active WHERE id = :id'
                )->execute($row + ['id' => $testimonial['id']]);
            } else {
                db()->prepare(
                    'INSERT INTO testimonials (quote, author_name, author_company, photo_path, sort_order, is_active)
                     VALUES (:quote, :author_name, :author_company, :photo_path, :sort_order, :is_active)'
                )->execute($row);
            }

            flash('success', 'Testimonial saved.');
            header('Location: ' . path('admin/testimonials'));
            exit;
        }
    }
}

$pageTitle = $testimonial ? 'Edit Testimonial' : 'Add Testimonial';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path('admin/testimonials') ?>" class="btn btn-outline btn-sm">Back to Testimonials</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= path($testimonial ? 'admin/testimonials/' . $testimonial['id'] . '/edit' : 'admin/testimonials/new') ?>" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="quote">Quote</label>
        <textarea id="quote" name="quote" required><?= e((string) $values['quote']) ?></textarea>
    </div>
    <div class="form-group">
        <label for="author_name">Name</label>
        <input type="text" id="author_name" name="author_name" value="<?= e((string) $values['author_name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="author_company">Company (optional)</label>
        <input type="text" id="author_company" name="author_company" value="<?= e((string) $values['author_company']) ?>">
    </div>
    <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string) $values['sort_order']) ?>" min="0">
    </div>
    <div class="form-group">
        <label for="photo">Photo (optional)</label>
        <?php if (!empty($testimonial['photo_path'])): ?>
            <img src="<?= media_url($testimonial['photo_path']) ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:50%;margin-bottom:8px;display:block;">
            <label style="font-weight:normal;"><input type="checkbox" name="remove_photo" value="1" style="width:auto;margin-right:8px;"> Remove photo (show initials instead)</label>
        <?php endif; ?>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
        <div class="form-note">JPG, PNG, or WEBP, up to 5MB. A square photo works best. Without one, the person's initials are shown.</div>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> style="width:auto;margin-right:8px;"> Visible on the site</label>
    </div>
    <button type="submit" class="btn btn-primary">Save Testimonial</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
