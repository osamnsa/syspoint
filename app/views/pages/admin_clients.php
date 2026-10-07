<?php
declare(strict_types=1);

$adminUser = require_admin();

$clients = db()->query('SELECT * FROM clients ORDER BY sort_order ASC, name ASC')->fetchAll();

$pageTitle = 'Clients';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Clients</h1>
    <a href="<?= path('admin/clients/new') ?>" class="btn btn-primary btn-sm">Add Client</a>
</div>

<div class="admin-table-wrap">
    <?php if ($clients): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>Name</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <?php if ($client['logo_path']): ?>
                                <img src="<?= media_url($client['logo_path']) ?>" alt="" style="width:36px;height:36px;object-fit:contain;border-radius:6px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($client['name']) ?></td>
                        <td><?php if ($client['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-muted">Hidden</span><?php endif; ?></td>
                        <td style="white-space:nowrap;">
                            <a href="<?= path('admin/clients/' . (int) $client['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post" action="<?= path('admin/clients/' . (int) $client['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this client? This cannot be undone.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No clients yet. <a href="<?= path('admin/clients/new') ?>">Add the first one</a>.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
