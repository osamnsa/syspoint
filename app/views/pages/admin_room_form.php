<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$room = gaming_room_by_id((int) ($params[0] ?? 0));
if (!$room) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$errors = [];
$values = [
    'name' => $room['name'],
    'description' => $room['description'],
    'hourly_rate' => $room['hourly_rate'],
    'capacity' => $room['capacity'],
    'is_active' => $room['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['description'] = trim((string) ($_POST['description'] ?? ''));
        $values['hourly_rate'] = (string) ($_POST['hourly_rate'] ?? '');
        $values['capacity'] = trim((string) ($_POST['capacity'] ?? ''));
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if ($values['name'] === '') $errors[] = 'Please enter a room name.';
        if (!is_numeric($values['hourly_rate']) || (float) $values['hourly_rate'] < 0) $errors[] = 'Please enter a valid hourly rate.';
        if ($values['capacity'] !== '' && !ctype_digit($values['capacity'])) $errors[] = 'Please enter a valid capacity, or leave it blank.';

        $upload = handle_image_upload('image', 'rooms');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        }

        if (!$errors) {
            $slug = ($values['name'] !== $room['name']) ? room_generate_slug($values['name'], $room['id']) : $room['slug'];
            $imagePath = $upload['path'] ?? $room['image_path'];

            db()->prepare(
                'UPDATE gaming_rooms SET name = :name, slug = :slug, description = :description, hourly_rate = :hourly_rate,
                 capacity = :capacity, is_active = :is_active, image_path = :image_path WHERE id = :id'
            )->execute([
                'name' => $values['name'],
                'slug' => $slug,
                'description' => $values['description'] ?: null,
                'hourly_rate' => $values['hourly_rate'],
                'capacity' => $values['capacity'] !== '' ? $values['capacity'] : null,
                'is_active' => $values['is_active'],
                'image_path' => $imagePath,
                'id' => $room['id'],
            ]);

            flash('success', 'Room saved.');
            header('Location: ' . path('admin/rooms'));
            exit;
        }
    }
}

$pageTitle = 'Edit Room';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Edit Room</h1>
    <a href="<?= path('admin/rooms') ?>" class="btn btn-outline btn-sm">Back to Rooms</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= path('admin/rooms/' . $room['id'] . '/edit') ?>" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= e((string) $values['name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e((string) $values['description']) ?></textarea>
    </div>
    <div class="form-group">
        <label for="hourly_rate">Hourly Rate (₦)</label>
        <input type="number" id="hourly_rate" name="hourly_rate" value="<?= e((string) $values['hourly_rate']) ?>" step="0.01" min="0" required>
    </div>
    <div class="form-group">
        <label for="capacity">Capacity (optional)</label>
        <input type="number" id="capacity" name="capacity" value="<?= e((string) $values['capacity']) ?>" min="1">
    </div>
    <div class="form-group">
        <label for="image">Room Image</label>
        <?php if ($room['image_path']): ?>
            <img src="<?= asset(e($room['image_path'])) ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block;">
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <div class="form-note">JPG, PNG, or WEBP, up to 5MB. Leave empty to keep the current image.</div>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> style="width:auto;margin-right:8px;"> Visible on the site</label>
    </div>
    <button type="submit" class="btn btn-primary">Save Room</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
