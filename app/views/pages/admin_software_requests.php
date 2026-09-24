<?php
declare(strict_types=1);

$adminUser = require_admin();

$requests = db()->query('SELECT * FROM software_requests ORDER BY created_at DESC')->fetchAll();

$statusBadge = [
    'new' => 'badge-warning',
    'in_review' => 'badge-warning',
    'quoted' => 'badge-success',
    'closed' => 'badge-muted',
];

$pageTitle = 'Software Requests';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Software Requests</h1>
</div>

<div class="admin-table-wrap">
    <?php if ($requests): ?>
        <table class="admin-table">
            <thead><tr><th>Business</th><th>Contact</th><th>Status</th><th>Received</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= e($request['business_name']) ?></td>
                        <td><?= e($request['contact_name']) ?></td>
                        <td><span class="badge <?= $statusBadge[$request['status']] ?? 'badge-muted' ?>"><?= e(ucwords(str_replace('_', ' ', $request['status']))) ?></span></td>
                        <td><?= e((new DateTimeImmutable($request['created_at']))->format('M j, g:i A')) ?></td>
                        <td><a href="<?= path('admin/software-requests/' . (int) $request['id']) ?>" class="btn btn-outline btn-sm">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No requests yet.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
