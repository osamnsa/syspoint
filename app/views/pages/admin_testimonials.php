<?php
declare(strict_types=1);

$adminUser = require_admin();

$testimonials = db()->query('SELECT * FROM testimonials ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Testimonials';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Testimonials</h1>
    <a href="<?= path('admin/testimonials/new') ?>" class="btn btn-primary btn-sm">Add Testimonial</a>
</div>

<div class="admin-table-wrap">
    <?php if ($testimonials): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>From</th><th>Quote</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($testimonials as $t): ?>
                    <tr>
                        <td>
                            <?php if ($t['photo_path']): ?>
                                <img src="<?= media_url($t['photo_path']) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($t['author_name']) ?>
                            <?php if ($t['author_company']): ?><br><small style="color:var(--color-text-muted);"><?= e($t['author_company']) ?></small><?php endif; ?>
                        </td>
                        <td><?= e(mb_strimwidth($t['quote'], 0, 80, '…')) ?></td>
                        <td><?php if ($t['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-muted">Hidden</span><?php endif; ?></td>
                        <td style="white-space:nowrap;">
                            <a href="<?= path('admin/testimonials/' . (int) $t['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post" action="<?= path('admin/testimonials/' . (int) $t['id'] . '/delete') ?>" style="display:inline;" data-confirm="Delete this testimonial? This cannot be undone." data-confirm-button="Delete" data-confirm-danger>
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No testimonials yet. <a href="<?= path('admin/testimonials/new') ?>">Add the first one</a>.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
