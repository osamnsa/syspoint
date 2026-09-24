<?php
declare(strict_types=1);

$adminUser = require_admin();

$courses = db()->query('SELECT * FROM training_courses ORDER BY sort_order ASC, title ASC')->fetchAll();

$pageTitle = 'Training Courses';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Training Courses</h1>
    <a href="<?= path('admin/courses/new') ?>" class="btn btn-primary btn-sm">Add Course</a>
</div>

<div class="admin-table-wrap">
    <?php if ($courses): ?>
        <table class="admin-table">
            <thead><tr><th></th><th>Title</th><th>Duration</th><th>Price</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td>
                            <?php if ($course['image_path']): ?>
                                <img src="<?= asset(e($course['image_path'])) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($course['title']) ?></td>
                        <td><?= e($course['duration_label'] ?: '—') ?></td>
                        <td><?= $course['price'] !== null ? format_naira((float) $course['price']) : '—' ?></td>
                        <td><?php if ($course['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-muted">Hidden</span><?php endif; ?></td>
                        <td style="white-space:nowrap;">
                            <a href="<?= path('admin/courses/' . (int) $course['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post" action="<?= path('admin/courses/' . (int) $course['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this course? This cannot be undone.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No courses yet. <a href="<?= path('admin/courses/new') ?>">Add the first one</a>.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
