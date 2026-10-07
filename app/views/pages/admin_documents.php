<?php
declare(strict_types=1);

$adminUser = require_admin();

$type = str_ends_with(admin_request_path(), 'quotes') ? 'quote' : 'invoice';
$status = (string) ($_GET['status'] ?? '');
$where = 'd.type = :type';
$params = ['type' => $type];
if ($status === 'overdue') {
    $where .= " AND d.status IN ('sent', 'part_paid') AND d.due_date < CURDATE()";
} elseif ($status === 'unpaid') {
    $where .= " AND d.status IN ('sent', 'part_paid')";
} elseif (isset(DOC_STATUSES[$type][$status])) {
    $where .= ' AND d.status = :st';
    $params['st'] = $status;
}
$stmt = db()->prepare("SELECT d.*, c.name AS customer_name FROM crm_documents d JOIN customers c ON c.id = d.customer_id WHERE $where ORDER BY d.id DESC LIMIT 300");
$stmt->execute($params);
$docs = $stmt->fetchAll();
$sum = db()->prepare("SELECT COALESCE(SUM(IF(status IN ('sent', 'part_paid'), total - amount_paid, 0)), 0) outstanding,
    COALESCE(SUM(IF(status IN ('sent', 'part_paid') AND due_date < CURDATE(), total - amount_paid, 0)), 0) overdue,
    COALESCE(SUM(IF(status = 'sent', total, 0)), 0) sent_value,
    COALESCE(SUM(IF(status = 'accepted', total, 0)), 0) accepted_value,
    SUM(status = 'accepted') accepted, SUM(status IN ('accepted', 'declined', 'expired')) decided
    FROM crm_documents WHERE type = :t");
$sum->execute(['t' => $type]);
$sum = $sum->fetch();
$paidMonth = (float) db()->query("SELECT COALESCE(SUM(amount), 0) FROM crm_payments WHERE paid_on >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();

$label = $type === 'quote' ? 'Quotes' : 'Invoices';
$pageTitle = $label;
require __DIR__ . '/../partials/admin_header.php';
$filters = $type === 'quote'
    ? ['' => 'All'] + DOC_STATUSES['quote']
    : ['' => 'All', 'unpaid' => 'Unpaid', 'overdue' => 'Overdue'] + DOC_STATUSES['invoice'];
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">CRM</p><h1><?= $label ?></h1></div>
    <a href="<?= path('admin/documents/new') ?>?type=<?= $type ?>" class="btn btn-primary btn-sm">New <?= $type === 'quote' ? 'Quote' : 'Invoice' ?></a>
</div>

<div class="pos-today">
    <?php if ($type === 'invoice'): ?>
        <div><span>Outstanding</span><strong><?= format_naira((float) $sum['outstanding']) ?></strong></div>
        <div><span>Overdue</span><strong class="<?= $sum['overdue'] > 0 ? 'is-warn' : '' ?>"><?= format_naira((float) $sum['overdue']) ?></strong></div>
        <div><span>Received this month</span><strong><?= format_naira($paidMonth) ?></strong></div>
    <?php else: ?>
        <div><span>Awaiting reply</span><strong><?= format_naira((float) $sum['sent_value']) ?></strong></div>
        <div><span>Accepted</span><strong><?= format_naira((float) $sum['accepted_value']) ?></strong></div>
        <div><span>Acceptance rate</span><strong><?= $sum['decided'] ? round($sum['accepted'] / $sum['decided'] * 100) . '%' : '—' ?></strong></div>
    <?php endif; ?>
</div>

<nav class="admin-filters" aria-label="Filter">
    <?php foreach ($filters as $k => $l): ?><a href="<?= path($type === 'quote' ? 'admin/quotes' : 'admin/invoices') . ($k !== '' ? '?status=' . $k : '') ?>"<?= $status === $k ? ' class="is-active"' : '' ?>><?= e($l) ?></a><?php endforeach; ?>
</nav>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Number</th><th>Customer</th><th>Issued</th><th><?= $type === 'quote' ? 'Valid until' : 'Due' ?></th><th>Total</th><?php if ($type === 'invoice'): ?><th>Balance</th><?php endif; ?><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($docs as $d): [$cls, $lab] = doc_badge($d); ?>
            <tr class="is-clickable" data-href="<?= path('admin/documents/' . (int) $d['id']) ?>">
                <td><a href="<?= path('admin/documents/' . (int) $d['id']) ?>"><strong><?= e($d['number']) ?></strong></a></td>
                <td><?= e($d['customer_name']) ?></td>
                <td><?= e((new DateTimeImmutable($d['issue_date']))->format('j M Y')) ?></td>
                <td><?= $d['due_date'] ? e((new DateTimeImmutable($d['due_date']))->format('j M Y')) : '—' ?></td>
                <td><?= format_naira((float) $d['total']) ?></td>
                <?php if ($type === 'invoice'): ?><td><?= in_array($d['status'], ['void', 'draft'], true) ? '—' : format_naira($d['total'] - $d['amount_paid']) ?></td><?php endif; ?>
                <td><span class="badge <?= $cls ?>"><?= e($lab) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$docs): ?><tr><td colspan="7" class="admin-empty">No <?= strtolower($label) ?> here yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
