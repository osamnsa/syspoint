<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$id = (int) ($params[0] ?? 0);
$enrolment = enrolment_by_id($id);
if (!$enrolment) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    try {
        if (($_POST['action'] ?? '') === 'payment') {
            $paidOn = (string) ($_POST['paid_on'] ?? '');
            if (!DateTimeImmutable::createFromFormat('Y-m-d', $paidOn)) throw new InventoryException('Enter the payment date.');
            enrolment_record_payment($enrolment, (float) ($_POST['amount'] ?? 0), (string) ($_POST['method'] ?? ''), $paidOn, trim((string) ($_POST['reference'] ?? '')));
            flash('success', 'Payment recorded.');
        } elseif (($_POST['action'] ?? '') === 'status' && isset(ENROLMENT_STATUSES[$_POST['status'] ?? ''])) {
            $to = (string) $_POST['status'];
            db()->prepare('UPDATE enrolments SET status = :s WHERE id = :id')->execute(['s' => $to, 'id' => $id]);
            crm_log((int) $enrolment['customer_id'], null, 'note', 'Training: ' . ENROLMENT_STATUSES[$enrolment['status']] . ' → ' . ENROLMENT_STATUSES[$to]);
            flash('success', 'Marked as ' . ENROLMENT_STATUSES[$to] . '.');
        }
        header('Location: ' . path('admin/students/' . $id));
        exit;
    } catch (InventoryException $ex) {
        $error = $ex->getMessage();
    }
}

$payments = db()->prepare('SELECT p.*, u.name AS user_name FROM enrolment_payments p LEFT JOIN users u ON u.id = p.user_id WHERE p.enrolment_id = :id ORDER BY p.paid_on, p.id');
$payments->execute(['id' => $id]);
$payments = $payments->fetchAll();
$balance = round((float) $enrolment['fee'] - (float) $enrolment['amount_paid'], 2);
$programme = $enrolment['course_title'] ?? ($enrolment['track'] ?: ENROLMENT_KINDS[$enrolment['kind']]);

$pageTitle = $enrolment['student_name'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div>
        <p class="admin-kicker"><?= e(ENROLMENT_KINDS[$enrolment['kind']]) ?> · <?= e($programme) ?></p>
        <h1><?= e($enrolment['student_name']) ?></h1>
        <p class="crm-head-meta"><?= admin_can('sales') ? '<a href="' . path('admin/customers/' . (int) $enrolment['customer_id']) . '">Customer record</a>' : 'Student' ?><?= $enrolment['student_phone'] ? ' · ' . e($enrolment['student_phone']) : '' ?><?= $enrolment['student_email'] ? ' · ' . e($enrolment['student_email']) : '' ?></p>
    </div>
    <div class="admin-header-actions"><a href="<?= path('admin/students/' . $id . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a><a href="<?= path('admin/students') ?>" class="btn btn-outline btn-sm">All Students</a></div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="crm-stepper"><?= csrf_field() ?><input type="hidden" name="action" value="status">
    <?php foreach (ENROLMENT_STATUSES as $k => $l): ?><button type="submit" name="status" value="<?= $k ?>" class="crm-step<?= $k === $enrolment['status'] ? ' is-current' : '' ?><?= $k === 'dropped' ? ' crm-step-lost' : ($k === 'completed' ? ' crm-step-won' : '') ?>"><?= e($l) ?></button><?php endforeach; ?>
</form>

<div class="dash-kpis">
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Fee</p><p class="dash-kpi-value"><?= e(naira_short((float) $enrolment['fee'])) ?></p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Paid</p><p class="dash-kpi-value"><?= e(naira_short((float) $enrolment['amount_paid'])) ?></p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Balance</p><p class="dash-kpi-value<?= $balance > 0 ? ' is-warn' : '' ?>"><?= e(naira_short(max(0, $balance))) ?></p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Dates</p><p class="dash-kpi-value dash-kpi-value-sm"><?= $enrolment['start_date'] ? e((new DateTimeImmutable($enrolment['start_date']))->format('j M')) : '—' ?></p><p class="dash-kpi-note"><?= $enrolment['end_date'] ? 'to ' . e((new DateTimeImmutable($enrolment['end_date']))->format('j M Y')) : 'No end date' ?></p></div>
</div>

<div class="admin-split">
    <?php if ($balance > 0): ?>
    <form method="post" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="payment">
        <h2 class="admin-form-title">Record a payment</h2>
        <div class="form-group"><label for="amount">Amount (₦)</label><input type="number" id="amount" name="amount" min="1" step="0.01" max="<?= $balance ?>" value="<?= $balance ?>" required></div>
        <div class="form-row">
            <div class="form-group"><label for="paid_on">Paid on</label><input type="date" id="paid_on" name="paid_on" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group"><label for="method">Method</label><select id="method" name="method"><?php foreach (['transfer' => 'Bank transfer', 'cash' => 'Cash', 'card' => 'Card', 'other' => 'Other'] as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-group"><label for="reference">Reference</label><input type="text" id="reference" name="reference" maxlength="120"></div>
        <button type="submit" class="btn btn-primary">Record Payment</button>
    </form>
    <?php else: ?>
    <div class="admin-form"><h2 class="admin-form-title"><?= (float) $enrolment['fee'] > 0 ? 'Fully paid' : 'No fee' ?></h2><p class="form-note"><?= nl2br(e((string) $enrolment['notes'])) ?></p></div>
    <?php endif; ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Paid on</th><th>Amount</th><th>Method</th><th>Reference</th><th>By</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr><td><?= e((new DateTimeImmutable($p['paid_on']))->format('j M Y')) ?></td><td><?= format_naira((float) $p['amount']) ?></td><td><?= e(ucfirst($p['method'])) ?></td><td><?= e($p['reference'] ?? '—') ?></td><td><?= e($p['user_name'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$payments): ?><tr><td colspan="5" class="admin-empty">No payments yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
