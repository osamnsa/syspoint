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

$existingDeal = db()->prepare('SELECT id, title, stage FROM deals WHERE request_id = :id LIMIT 1');
$existingDeal->execute(['id' => $requestId]);
$existingDeal = $existingDeal->fetch() ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['action'] ?? '') === 'deal' && !$existingDeal) {
    // Make sure the requester is a customer, then open the deal on their organisation.
    if (!$request['customer_id']) {
        crm_link('software_requests', $requestId, $request['contact_name'], $request['email'], $request['phone'], 'website', $request['business_name']);
        $stmt->execute(['id' => $requestId]);
        $request = $stmt->fetch();
    }
    $person = $request['customer_id'] ? crm_customer_by_id((int) $request['customer_id']) : null;
    $customerId = $person ? (int) ($person['organisation_id'] ?: $person['id']) : crm_customer_for($request['business_name'], $request['email'], $request['phone']);
    db()->prepare("INSERT INTO deals (title, customer_id, service, value, stage, expected_close, owner_id, request_id)
                   VALUES (:t, :c, 'software', 0, 'contacted', :ec, :o, :r)")
        ->execute(['t' => 'Software for ' . $request['business_name'], 'c' => $customerId, 'ec' => date('Y-m-d', strtotime('+30 days')), 'o' => $adminUser['id'], 'r' => $requestId]);
    $dealId = (int) db()->lastInsertId();
    crm_log($customerId, $dealId, 'stage', 'Deal opened from Software Clinic request');
    if ($request['status'] === 'new') {
        db()->prepare("UPDATE software_requests SET status = 'in_review' WHERE id = :id")->execute(['id' => $requestId]);
    }
    flash('success', 'Deal created — add its value and next steps.');
    header('Location: ' . path('admin/deals/' . $dealId . '/edit'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($done = spam_handle_admin_action('software_requests', $request, $request['email']))) {
    flash('success', $done);
    header('Location: ' . path('admin/software-requests') . (($_POST['action'] ?? '') === 'spam' ? '' : '?view=spam'));
    exit;
}

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
    <div class="admin-header-actions">
        <?php if ($existingDeal): ?>
            <a href="<?= path('admin/deals/' . (int) $existingDeal['id']) ?>" class="btn btn-primary btn-sm">Open Deal (<?= e(DEAL_STAGES[$existingDeal['stage']]['label']) ?>)</a>
        <?php else: ?>
            <form method="post" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="action" value="deal"><button type="submit" class="btn btn-primary btn-sm">Turn into a Deal</button></form>
        <?php endif; ?>
        <?php if ($request['customer_id']): ?><a href="<?= path('admin/customers/' . (int) $request['customer_id']) ?>" class="btn btn-outline btn-sm">Customer</a><?php endif; ?>
        <a href="<?= path('admin/software-requests') ?>" class="btn btn-outline btn-sm">Back to Requests</a>
    </div>
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

<div class="card" style="margin-bottom:20px;">
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

<div style="max-width:520px;"><?php $spamRow = $request; $spamEmail = $request['email']; require __DIR__ . '/../partials/spam_box.php'; ?></div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
