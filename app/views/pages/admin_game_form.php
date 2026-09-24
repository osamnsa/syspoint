<?php
declare(strict_types=1);

/** @var array $params [id] when editing, empty when creating */

$adminUser = require_admin();

$gameId = isset($params[0]) ? (int) $params[0] : 0;
$game = $gameId ? game_by_id($gameId) : null;

if ($gameId && !$game) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$errors = [];
$values = [
    'type' => $game['type'] ?? 'video',
    'name' => $game['name'] ?? '',
    'description' => $game['description'] ?? '',
    'price' => $game['price'] ?? '',
    'sort_order' => $game['sort_order'] ?? 0,
    'is_active' => $game['is_active'] ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['type'] = (string) ($_POST['type'] ?? 'video');
        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['description'] = trim((string) ($_POST['description'] ?? ''));
        $values['price'] = (string) ($_POST['price'] ?? '');
        $values['sort_order'] = (string) ($_POST['sort_order'] ?? '0');
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if (!in_array($values['type'], ['video', 'board'], true)) $errors[] = 'Please choose a valid game type.';
        if ($values['name'] === '') $errors[] = 'Please enter a game name.';
        if ($values['price'] !== '' && (!is_numeric($values['price']) || (float) $values['price'] < 0)) $errors[] = 'Please enter a valid price, or leave it blank.';
        if (!ctype_digit($values['sort_order'])) $errors[] = 'Please enter a valid sort order.';

        $upload = handle_image_upload('image', 'games');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        }

        if (!$errors) {
            $imagePath = $upload['path'] ?? ($game['image_path'] ?? null);
            $price = $values['price'] !== '' ? $values['price'] : null;

            if ($game) {
                db()->prepare(
                    'UPDATE games SET type = :type, name = :name, description = :description, price = :price,
                     sort_order = :sort_order, is_active = :is_active, image_path = :image_path WHERE id = :id'
                )->execute([
                    'type' => $values['type'],
                    'name' => $values['name'],
                    'description' => $values['description'] ?: null,
                    'price' => $price,
                    'sort_order' => $values['sort_order'],
                    'is_active' => $values['is_active'],
                    'image_path' => $imagePath,
                    'id' => $game['id'],
                ]);
            } else {
                db()->prepare(
                    'INSERT INTO games (type, name, description, price, sort_order, is_active, image_path)
                     VALUES (:type, :name, :description, :price, :sort_order, :is_active, :image_path)'
                )->execute([
                    'type' => $values['type'],
                    'name' => $values['name'],
                    'description' => $values['description'] ?: null,
                    'price' => $price,
                    'sort_order' => $values['sort_order'],
                    'is_active' => $values['is_active'],
                    'image_path' => $imagePath,
                ]);
            }

            flash('success', 'Game saved.');
            header('Location: ' . path('admin/games'));
            exit;
        }
    }
}

$pageTitle = $game ? 'Edit Game' : 'Add Game';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path('admin/games') ?>" class="btn btn-outline btn-sm">Back to Games</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= path($game ? 'admin/games/' . $game['id'] . '/edit' : 'admin/games/new') ?>" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="type">Type</label>
        <select id="type" name="type" required>
            <option value="video" <?= $values['type'] === 'video' ? 'selected' : '' ?>>Video Game</option>
            <option value="board" <?= $values['type'] === 'board' ? 'selected' : '' ?>>Board Game</option>
        </select>
    </div>
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= e((string) $values['name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e((string) $values['description']) ?></textarea>
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
        <?php if (!empty($game['image_path'])): ?>
            <img src="<?= asset(e($game['image_path'])) ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block;">
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <div class="form-note">JPG, PNG, or WEBP, up to 5MB. Leave empty to keep the current image.</div>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> style="width:auto;margin-right:8px;"> Visible on the site</label>
    </div>
    <button type="submit" class="btn btn-primary">Save Game</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
