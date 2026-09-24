<?php
declare(strict_types=1);

$adminUser = require_admin();

$rooms = db()->query('SELECT * FROM gaming_rooms ORDER BY name ASC')->fetchAll();

$pageTitle = 'Rooms';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Rooms</h1>
</div>

<div class="admin-table-wrap">
    <?php if ($rooms): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>Name</th><th>Rate</th><th>Capacity</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td>
                            <?php if ($room['image_path']): ?>
                                <img src="<?= asset(e($room['image_path'])) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($room['name']) ?></td>
                        <td><?= format_naira((float) $room['hourly_rate']) ?>/hr</td>
                        <td><?= $room['capacity'] ? (int) $room['capacity'] : '—' ?></td>
                        <td><?php if ($room['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-muted">Hidden</span><?php endif; ?></td>
                        <td><a href="<?= path('admin/rooms/' . (int) $room['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No rooms yet.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
