<?php
declare(strict_types=1);

$adminUser = require_admin();

$businesses = db()->query('SELECT * FROM deployed_businesses ORDER BY sort_order ASC, name ASC')->fetchAll();

$pageTitle = 'Clients';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Clients</h1>
    <a href="<?= path('admin/businesses/new') ?>" class="btn btn-primary btn-sm">Add Client</a>
</div>
<p class="form-note" style="margin:-8px 0 16px;">Visible clients appear on the home page under “Organisations we’ve consulted with” and on the Software Clinic page, in sort order (lowest first). Logos are shown in grey on the site.</p>

<div class="admin-table-wrap">
    <?php if ($businesses): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>Name</th><th>Order</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($businesses as $business): ?>
                    <tr>
                        <td>
                            <?php if ($business['logo_path']): ?>
                                <img src="<?= media_url($business['logo_path']) ?>" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:6px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($business['name']) ?></td>
                        <td><?= (int) $business['sort_order'] ?></td>
                        <td><?php if ($business['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-muted">Hidden</span><?php endif; ?></td>
                        <td style="white-space:nowrap;">
                            <a href="<?= path('admin/businesses/' . (int) $business['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post" action="<?= path('admin/businesses/' . (int) $business['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this client? This cannot be undone.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No clients yet. <a href="<?= path('admin/businesses/new') ?>">Add the first one</a>.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
