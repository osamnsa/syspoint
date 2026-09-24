<?php
declare(strict_types=1);

$adminUser = require_admin();

$games = db()->query('SELECT * FROM games ORDER BY type ASC, sort_order ASC, name ASC')->fetchAll();

$pageTitle = 'Games';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Games</h1>
    <a href="<?= path('admin/games/new') ?>" class="btn btn-primary btn-sm">Add Game</a>
</div>

<div class="admin-table-wrap">
    <?php if ($games): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>Name</th><th>Type</th><th>Price</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td>
                            <?php if ($game['image_path']): ?>
                                <img src="<?= asset(e($game['image_path'])) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($game['name']) ?></td>
                        <td><span class="badge badge-muted"><?= e(game_type_label($game['type'])) ?></span></td>
                        <td><?= $game['price'] !== null ? format_naira((float) $game['price']) : '—' ?></td>
                        <td><?php if ($game['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-muted">Hidden</span><?php endif; ?></td>
                        <td style="white-space:nowrap;">
                            <a href="<?= path('admin/games/' . (int) $game['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post" action="<?= path('admin/games/' . (int) $game['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this game? This cannot be undone.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No games yet. <a href="<?= path('admin/games/new') ?>">Add the first one</a>.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
