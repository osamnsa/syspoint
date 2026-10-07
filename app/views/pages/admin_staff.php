<?php
declare(strict_types=1);

$adminUser = require_admin();

$staff = db()->query('SELECT id, name, email, role, areas, is_active, last_login_at FROM users ORDER BY is_active DESC, role = \'admin\' DESC, name')->fetchAll();

$pageTitle = 'Staff & Roles';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Staff &amp; Roles</h1>
    <a href="<?= path('admin/staff/new') ?>" class="btn btn-primary btn-sm">Add Staff</a>
</div>
<p class="form-note" style="margin:-8px 0 16px;">Administrators see everything. Other staff only see the areas ticked for them. Deactivate someone instead of deleting them, so their history stays.</p>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Access</th><th>Last sign-in</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($staff as $u): ?>
            <tr>
                <td><strong><?= e($u['name']) ?></strong><?= (int) $u['id'] === (int) $adminUser['id'] ? ' <span class="badge badge-muted">You</span>' : '' ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= $u['role'] === 'admin' ? '<span class="badge badge-success">Administrator</span>' : e(implode(', ', array_map(fn($a) => ADMIN_AREAS[$a], admin_user_areas($u))) ?: 'No areas yet') ?></td>
                <td><?= $u['last_login_at'] ? e((new DateTimeImmutable($u['last_login_at']))->format('M j, g:i A')) : '—' ?></td>
                <td><?= (int) $u['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-muted">Deactivated</span>' ?></td>
                <td><a href="<?= path('admin/staff/' . (int) $u['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
