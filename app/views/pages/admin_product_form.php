<?php
declare(strict_types=1);

/** @var array $params [id] when editing, empty when creating */

$adminUser = require_admin();

$productId = isset($params[0]) ? (int) $params[0] : 0;
$product = $productId ? product_by_id($productId) : null;

if ($productId && !$product) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$categories = product_categories();
$errors = [];

$values = [
    'category_id' => $product['category_id'] ?? ($categories[0]['id'] ?? ''),
    'name' => $product['name'] ?? '',
    'description' => $product['description'] ?? '',
    'specs' => $product['specs'] ?? '',
    'price' => $product['price'] ?? '',
    'stock_qty' => $product['stock_qty'] ?? 0,
    'is_active' => $product['is_active'] ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $values['category_id'] = (int) ($_POST['category_id'] ?? 0);
        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['description'] = trim((string) ($_POST['description'] ?? ''));
        $values['specs'] = trim((string) ($_POST['specs'] ?? ''));
        $values['price'] = (string) ($_POST['price'] ?? '');
        $values['stock_qty'] = (string) ($_POST['stock_qty'] ?? '0');
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if ($values['name'] === '') $errors[] = 'Please enter a product name.';
        if (!$values['category_id'] || !array_filter($categories, fn($c) => (int) $c['id'] === $values['category_id'])) {
            $errors[] = 'Please choose a category.';
        }
        if (!is_numeric($values['price']) || (float) $values['price'] < 0) $errors[] = 'Please enter a valid price.';
        if (!ctype_digit($values['stock_qty'])) $errors[] = 'Please enter a valid stock quantity.';

        $upload = handle_image_upload('image', 'products');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        }

        if (!$errors) {
            $imagePath = $upload['path'] ?? ($product['image_path'] ?? null);

            if ($product) {
                $slug = ($values['name'] !== $product['name'])
                    ? product_generate_slug($values['name'], $product['id'])
                    : $product['slug'];

                db()->prepare(
                    'UPDATE products SET category_id = :category_id, name = :name, slug = :slug,
                     description = :description, specs = :specs, price = :price, stock_qty = :stock_qty,
                     image_path = :image_path, is_active = :is_active WHERE id = :id'
                )->execute([
                    'category_id' => $values['category_id'],
                    'name' => $values['name'],
                    'slug' => $slug,
                    'description' => $values['description'] ?: null,
                    'specs' => $values['specs'] ?: null,
                    'price' => $values['price'],
                    'stock_qty' => $values['stock_qty'],
                    'image_path' => $imagePath,
                    'is_active' => $values['is_active'],
                    'id' => $product['id'],
                ]);
            } else {
                $slug = product_generate_slug($values['name']);

                db()->prepare(
                    'INSERT INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active)
                     VALUES (:category_id, :name, :slug, :description, :specs, :price, :stock_qty, :image_path, :is_active)'
                )->execute([
                    'category_id' => $values['category_id'],
                    'name' => $values['name'],
                    'slug' => $slug,
                    'description' => $values['description'] ?: null,
                    'specs' => $values['specs'] ?: null,
                    'price' => $values['price'],
                    'stock_qty' => $values['stock_qty'],
                    'image_path' => $imagePath,
                    'is_active' => $values['is_active'],
                ]);
            }

            flash('success', 'Product saved.');
            header('Location: ' . path('admin/products'));
            exit;
        }
    }
}

$pageTitle = $product ? 'Edit Product' : 'Add Product';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path('admin/products') ?>" class="btn btn-outline btn-sm">Back to Products</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= path($product ? 'admin/products/' . $product['id'] . '/edit' : 'admin/products/new') ?>" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= (int) $values['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
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
        <label for="specs">Specs</label>
        <textarea id="specs" name="specs"><?= e((string) $values['specs']) ?></textarea>
        <div class="form-note">One spec per line — shown as-is on the product page.</div>
    </div>
    <div class="form-group">
        <label for="price">Price (₦)</label>
        <input type="number" id="price" name="price" value="<?= e((string) $values['price']) ?>" step="0.01" min="0" required>
    </div>
    <div class="form-group">
        <label for="stock_qty">Stock Quantity</label>
        <input type="number" id="stock_qty" name="stock_qty" value="<?= e((string) $values['stock_qty']) ?>" min="0" required>
    </div>
    <div class="form-group">
        <label for="image">Product Image</label>
        <?php if (!empty($product['image_path'])): ?>
            <img src="<?= asset(e($product['image_path'])) ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:8px;display:block;">
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <div class="form-note">JPG, PNG, or WEBP, up to 5MB. Leave empty to keep the current image.</div>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?> style="width:auto;margin-right:8px;"> Visible on the site</label>
    </div>
    <button type="submit" class="btn btn-primary">Save Product</button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
