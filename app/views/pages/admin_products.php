<?php
declare(strict_types=1);

$adminUser = require_admin();

$products = db()->query(
    "SELECT p.*, c.name AS category_name
     FROM products p JOIN product_categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC"
)->fetchAll();

$pageTitle = 'Products';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Products</h1>
    <a href="<?= path('admin/products/new') ?>" class="btn btn-primary btn-sm">Add Product</a>
</div>

<div class="admin-table-wrap">
    <?php if ($products): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if ($product['image_path']): ?>
                                <img src="<?= asset(e($product['image_path'])) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($product['name']) ?></td>
                        <td><?= e($product['category_name']) ?></td>
                        <td><?= format_naira((float) $product['price']) ?></td>
                        <td><?= (int) $product['stock_qty'] ?></td>
                        <td><?php if ($product['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-muted">Hidden</span><?php endif; ?></td>
                        <td style="white-space:nowrap;">
                            <a href="<?= path('admin/products/' . (int) $product['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post" action="<?= path('admin/products/' . (int) $product['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No products yet. <a href="<?= path('admin/products/new') ?>">Add the first one</a>.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
