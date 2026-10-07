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
    'sku' => $product['sku'] ?? '',
    'cost_price' => $product['cost_price'] ?? '',
    'reorder_level' => $product['reorder_level'] ?? 3,
    'track_serials' => (int) ($product['track_serials'] ?? 0),
    'warranty_months' => $product['warranty_months'] ?? '',
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
        // Existing products change stock only via Inventory -> Adjust stock (ledgered).
        $values['stock_qty'] = $product ? (string) $product['stock_qty'] : (string) ($_POST['stock_qty'] ?? '0');
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $values['sku'] = strtoupper(trim((string) ($_POST['sku'] ?? '')));
        $values['cost_price'] = trim((string) ($_POST['cost_price'] ?? ''));
        $values['reorder_level'] = (string) ($_POST['reorder_level'] ?? '3');
        $values['track_serials'] = isset($_POST['track_serials']) ? 1 : 0;
        $values['warranty_months'] = trim((string) ($_POST['warranty_months'] ?? ''));

        if ($values['name'] === '') $errors[] = 'Please enter a product name.';
        if (!$values['category_id'] || !array_filter($categories, fn($c) => (int) $c['id'] === $values['category_id'])) {
            $errors[] = 'Please choose a category.';
        }
        if (!is_numeric($values['price']) || (float) $values['price'] < 0) $errors[] = 'Please enter a valid price.';
        if (!ctype_digit($values['stock_qty'])) $errors[] = 'Please enter a valid stock quantity.';
        if ($values['cost_price'] !== '' && (!is_numeric($values['cost_price']) || (float) $values['cost_price'] < 0)) $errors[] = 'Please enter a valid cost price.';
        if (!ctype_digit($values['reorder_level'])) $errors[] = 'Please enter a valid reorder level.';
        if ($values['warranty_months'] !== '' && !ctype_digit($values['warranty_months'])) $errors[] = 'Warranty must be a whole number of months.';
        if ($values['sku'] !== '') {
            $dupe = db()->prepare('SELECT COUNT(*) FROM products WHERE sku = :sku AND id <> :id');
            $dupe->execute(['sku' => $values['sku'], 'id' => $productId]);
            if ((int) $dupe->fetchColumn() > 0) $errors[] = 'Another product already uses that SKU.';
        }
        if (!$product && $values['track_serials'] && (int) $values['stock_qty'] > 0) {
            $errors[] = 'Serial-tracked products start at 0 — receive them through a purchase order so each unit gets its serial number.';
        }

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
                     description = :description, specs = :specs, price = :price,
                     image_path = :image_path, is_active = :is_active, sku = :sku, cost_price = :cost_price,
                     reorder_level = :reorder_level, track_serials = :track_serials, warranty_months = :warranty_months WHERE id = :id'
                )->execute([
                    'category_id' => $values['category_id'],
                    'name' => $values['name'],
                    'slug' => $slug,
                    'description' => $values['description'] ?: null,
                    'specs' => $values['specs'] ?: null,
                    'price' => $values['price'],
                    'image_path' => $imagePath,
                    'is_active' => $values['is_active'],
                    'sku' => $values['sku'] ?: null,
                    'cost_price' => $values['cost_price'] === '' ? null : $values['cost_price'],
                    'reorder_level' => $values['reorder_level'],
                    'track_serials' => $values['track_serials'],
                    'warranty_months' => $values['warranty_months'] === '' ? null : $values['warranty_months'],
                    'id' => $product['id'],
                ]);
            } else {
                $slug = product_generate_slug($values['name']);

                // Created at 0, then the opening stock goes through the ledger.
                db()->prepare(
                    'INSERT INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active,
                                           sku, cost_price, reorder_level, track_serials, warranty_months)
                     VALUES (:category_id, :name, :slug, :description, :specs, :price, 0, :image_path, :is_active,
                             :sku, :cost_price, :reorder_level, :track_serials, :warranty_months)'
                )->execute([
                    'category_id' => $values['category_id'],
                    'name' => $values['name'],
                    'slug' => $slug,
                    'description' => $values['description'] ?: null,
                    'specs' => $values['specs'] ?: null,
                    'price' => $values['price'],
                    'image_path' => $imagePath,
                    'is_active' => $values['is_active'],
                    'sku' => $values['sku'] ?: null,
                    'cost_price' => $values['cost_price'] === '' ? null : $values['cost_price'],
                    'reorder_level' => $values['reorder_level'],
                    'track_serials' => $values['track_serials'],
                    'warranty_months' => $values['warranty_months'] === '' ? null : $values['warranty_months'],
                ]);
                $newId = (int) db()->lastInsertId();
                if ((int) $values['stock_qty'] > 0) {
                    inventory_tx(fn() => inventory_move($newId, (int) $values['stock_qty'], 'opening', null, null, 'Opening stock when the product was added'));
                }
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
    <div class="form-row">
        <div class="form-group">
            <label for="cost_price">Cost Price (₦, optional)</label>
            <input type="number" id="cost_price" name="cost_price" value="<?= e((string) $values['cost_price']) ?>" step="0.01" min="0">
            <div class="form-note">What you pay the supplier — used for stock value and margins. Updated automatically when a purchase order is received.</div>
        </div>
        <div class="form-group">
            <label for="sku">SKU / Code (optional)</label>
            <input type="text" id="sku" name="sku" value="<?= e((string) $values['sku']) ?>" maxlength="60">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="stock_qty">Stock on Hand</label>
            <?php if ($product): ?>
                <input type="number" id="stock_qty" value="<?= (int) $values['stock_qty'] ?>" disabled>
                <div class="form-note">Change stock with <a href="<?= path('admin/inventory/products/' . (int) $product['id']) ?>">Adjust stock</a> or a purchase order, so every change is recorded.</div>
            <?php else: ?>
                <input type="number" id="stock_qty" name="stock_qty" value="<?= e((string) $values['stock_qty']) ?>" min="0" required>
                <div class="form-note">Opening stock. Use 0 for serial-tracked items and receive them on a purchase order.</div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="reorder_level">Reorder Level</label>
            <input type="number" id="reorder_level" name="reorder_level" value="<?= e((string) $values['reorder_level']) ?>" min="0" required>
            <div class="form-note">Flagged as low stock at or below this number.</div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label><input type="checkbox" name="track_serials" value="1" <?= $values['track_serials'] ? 'checked' : '' ?> style="width:auto;margin-right:8px;"> Track serial / IMEI numbers</label>
            <div class="form-note">For laptops and phones: each unit is received and sold by its serial number.</div>
        </div>
        <div class="form-group">
            <label for="warranty_months">Warranty (months, optional)</label>
            <input type="number" id="warranty_months" name="warranty_months" value="<?= e((string) $values['warranty_months']) ?>" min="0" max="120">
        </div>
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
