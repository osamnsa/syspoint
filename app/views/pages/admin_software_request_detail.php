<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$requestId = (int) ($params[0] ?? 0);
$stmt = db()->prepare('SELECT * FROM software_requests WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $requestId]);
$request = $stmt->fetch();

if (!$request) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$statuses = ['new', 'in_review', 'quoted', 'closed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $newStatus = (string) ($_POST['status'] ?? '');
    if (in_array($newStatus, $statuses, true)) {
        db()->prepare('UPDATE software_requests SET status = :status WHERE id = :id')
            ->execute(['status' => $newStatus, 'id' => $requestId]);
        flash('success', 'Status updated.');
        header('Location: ' . path('admin/software-requests/' . $requestId));
        exit;
    }
}

$successMessage = flash('success');

$pageTitle = 'Request from ' . $request['business_name'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($request['business_name']) ?></h1>
    <a href="<?= path('admin/software-requests') ?>" class="btn btn-outline btn-sm">Back to Requests</a>
</div>

<?php if ($successMessage): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <p style="margin:0;">
        <strong><?= e($request['contact_name']) ?></strong><br>
        <?= e($request['email']) ?><br>
        <?php if ($request['phone']): ?><?= e($request['phone']) ?><br><?php endif; ?>
        Received <?= e((new DateTimeImmutable($request['created_at']))->format('M j, Y g:i A')) ?>
    </p>
</div>

<div class="card" style="margin-bottom:20px;">
    <h3 style="margin-top:0;">What They Need</h3>
    <p style="margin:0;white-space:pre-wrap;"><?= e($request['description']) ?></p>
</div>

<div class="card">
    <h3 style="margin-top:0;">Status</h3>
    <form method="post" action="<?= path('admin/software-requests/' . $requestId) ?>" style="display:flex;gap:10px;align-items:center;">
        <?= csrf_field() ?>
        <select name="status">
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status) ?>" <?= $request['status'] === $status ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
    </form>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
