<?php
declare(strict_types=1);

/** @var array $params [id] when editing, empty when creating */

$adminUser = require_admin();

$courseId = isset($params[0]) ? (int) $params[0] : 0;
$course = $courseId ? training_course_by_id($courseId) : null;

if ($courseId && !$course) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$errors = [];
$values = [
    'title' => $course['title'] ?? '',
    'description' => $course['description'] ?? '',
    'duration_label' => $course['duration_label'] ?? '',
    'price' => $course['price'] ?? '',
    'sort_order' => $course['sort_order'] ?? 0,
    'is_active' => $course['is_active'] ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['title'] = trim((string) ($_POST['title'] ?? ''));
        $values['description'] = trim((string) ($_POST['description'] ?? ''));
        $values['duration_label'] = trim((string) ($_POST['duration_label'] ?? ''));
        $values['price'] = (string) ($_POST['price'] ?? '');
        $values['sort_order'] = (string) ($_POST['sort_order'] ?? '0');
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if ($values['title'] === '') $errors[] = 'Please enter a course title.';
        if ($values['price'] !== '' && (!is_numeric($values['price']) || (float) $values['price'] < 0)) $errors[] = 'Please enter a valid price, or leave it blank.';
        if (!ctype_digit($values['sort_order'])) $errors[] = 'Please enter a valid sort order.';

        $upload = handle_image_upload('image', 'courses');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        }

        if (!$errors) {
            $imagePath = $upload['path'] ?? ($course['image_path'] ?? null);
            $price = $values['price'] !== '' ? $values['price'] : null;

            if ($course) {
                db()->prepare(
                    'UPDATE training_courses SET title = :title, description = :description, duration_label = :duration_label,
                     price = :price, sort_order = :sort_order, is_active = :is_active, image_path = :image_path WHERE id = :id'
                )->execute([
                    'title' => $values['title'],
                    'description' => $values['description'] ?: null,
                    'duration_label' => $values['duration_label'] ?: null,
                    'price' => $price,
                    'sort_order' => $values['sort_order'],
                    'is_active' => $values['is_active'],
                    'image_path' => $imagePath,
                    'id' => $course['id'],
                ]);
            } else {
                db()->prepare(
                    'INSERT INTO training_courses (title, description, duration_label, price, sort_order, is_active, image_path)
                     VALUES (:title, :description, :duration_label, :price, :sort_order, :is_active, :image_path)'
                )->execute([
                    'title' => $values['title'],
                    'description' => $values['description'] ?: null,
                    'duration_label' => $values['duration_label'] ?: null,
                    'price' => $price,
                    'sort_order' => $values['sort_order'],
                    'is_active' => $values['is_active'],
                    'image_path' => $imagePath,
                ]);
            }

            flash('success', 'Course saved.');
            header('Location: ' . path('admin/courses'));
            exit;
        }
    }
}

$pageTitle = $course ? 'Edit Course' : 'Add Course';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path('admin/courses') ?>" class="btn btn-outline btn-sm">Back to Courses</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= path($course ? 'admin/courses/' . $course['id'] . '/edit' : 'admin/courses/new') ?>" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e((string) $values['title']) ?>" required>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e((string) $values['description']) ?></textarea>
    </div>
    <div class="form-group">
        <label for="duration_label">Duration (e.g. "6 weeks", optional)</label>
        <input type="text" id="duration_label" name="duration_label" value="<?= e((string) $values['duration_label']) ?>">
    </div>
    <div class="form-group">
        <label for="price">Price (₦, optional)</label>
        <input type="number" id="price" name="price" value="<?= e((string) $values['price']) ?>" step="0.01" min="0">
    </div>
    <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string) $values['sort_order']) ?>" min="0">
    </div>
    <div class="form-group">
        <label for="image">Image</label>
        <?php if (!empty($course['image_path'])): ?>
            <img src="<?= asset(e($course['image_path'])) ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block;">
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <div class="form-note">JPG, PNG, or WEBP, up to 5MB. Leave empty to keep the current image.</div>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> style="width:auto;margin-right:8px;"> Visible on the site</label>
    </div>
    <button type="submit" class="btn btn-primary">Save Course</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
