<?php
declare(strict_types=1);

$adminUser = require_admin();

$businesses = db()->query('SELECT * FROM deployed_businesses ORDER BY sort_order ASC, name ASC')->fetchAll();

$pageTitle = 'Deployed Businesses';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Deployed Businesses</h1>
    <a href="<?= path('admin/businesses/new') ?>" class="btn btn-primary btn-sm">Add Business</a>
</div>

<div class="admin-table-wrap">
    <?php if ($businesses): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>Name</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($businesses as $business): ?>
                    <tr>
                        <td>
                            <?php if ($business['logo_path']): ?>
                                <img src="<?= asset(e($business['logo_path'])) ?>" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:6px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($business['name']) ?></td>
                        <td><?php if ($business['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-muted">Hidden</span><?php endif; ?></td>
                        <td style="white-space:nowrap;">
                            <a href="<?= path('admin/businesses/' . (int) $business['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post" action="<?= path('admin/businesses/' . (int) $business['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this business? This cannot be undone.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No businesses yet. <a href="<?= path('admin/businesses/new') ?>">Add the first one</a>.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
