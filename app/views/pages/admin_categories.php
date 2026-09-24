<?php
declare(strict_types=1);

$adminUser = require_admin();

$categories = product_categories();

$pageTitle = 'Categories';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Categories</h1>
</div>

<div class="admin-table-wrap">
    <?php if ($categories): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>Name</th><th>Products</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <?php if ($category['image_path']): ?>
                                <img src="<?= asset(e($category['image_path'])) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($category['name']) ?></td>
                        <td><?= (int) $category['product_count'] ?></td>
                        <td><a href="<?= path('admin/categories/' . (int) $category['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No categories yet.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
