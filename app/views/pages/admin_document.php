<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$docId = (int) ($params[0] ?? 0);
$doc = doc_by_id($docId);
if (!$doc) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}
$isQuote = $doc['type'] === 'quote';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = (string) ($_POST['action'] ?? '');
    try {
        switch ($action) {
            case 'status':
                $to = (string) ($_POST['to'] ?? '');
                $allowed = $isQuote ? ['sent', 'accepted', 'declined', 'expired', 'draft'] : ['sent', 'draft'];
                if (!in_array($to, $allowed, true) || (float) $doc['amount_paid'] > 0) throw new InventoryException('That change isn’t allowed.');
                db()->prepare('UPDATE crm_documents SET status = :s WHERE id = :id')->execute(['s' => $to, 'id' => $docId]);
                crm_log((int) $doc['customer_id'], $doc['deal_id'] ? (int) $doc['deal_id'] : null, 'note', $doc['number'] . ' marked ' . DOC_STATUSES[$doc['type']][$to]);
                if ($isQuote && $to === 'accepted' && $doc['deal_id'] && ($deal = deal_by_id((int) $doc['deal_id'])) && in_array($deal['stage'], DEAL_OPEN_STAGES, true)) {
                    deal_set_stage($deal, 'won');
                }
                flash('success', 'Marked as ' . DOC_STATUSES[$doc['type']][$to] . '.');
                break;
            case 'convert':
                if (!$isQuote) throw new InventoryException('Only quotes can be converted.');
                $invId = doc_convert_to_invoice($doc);
                flash('success', 'Invoice created from ' . $doc['number'] . '.');
                header('Location: ' . path('admin/documents/' . $invId));
                exit;
            case 'payment':
                $paidOn = (string) ($_POST['paid_on'] ?? date('Y-m-d'));
                if (!DateTimeImmutable::createFromFormat('Y-m-d', $paidOn)) throw new InventoryException('Enter the payment date.');
                doc_record_payment($doc, (float) ($_POST['amount'] ?? 0), (string) ($_POST['method'] ?? ''), $paidOn, trim((string) ($_POST['reference'] ?? '')));
                flash('success', 'Payment recorded.');
                break;
            case 'void':
                if ($isQuote || (float) $doc['amount_paid'] > 0) throw new InventoryException('Only unpaid invoices can be voided.');
                db()->prepare("UPDATE crm_documents SET status = 'void' WHERE id = :id")->execute(['id' => $docId]);
                crm_log((int) $doc['customer_id'], null, 'note', $doc['number'] . ' voided');
                flash('success', 'Invoice voided.');
                break;
            case 'email':
                if (!$doc['customer_email']) throw new InventoryException('This customer has no email address.');
                ob_start();
                $emailMode = true;
                require __DIR__ . '/../partials/crm_document_body.php';
                $html = ob_get_clean();
                $ok = send_mail($doc['customer_email'], ($isQuote ? 'Quote ' : 'Invoice ') . $doc['number'] . ' from ' . company_details()['name'], $html);
                if (!$ok) throw new InventoryException('The email couldn’t be sent from this server — download/print it and send it instead.');
                if ($doc['status'] === 'draft') db()->prepare("UPDATE crm_documents SET status = 'sent' WHERE id = :id")->execute(['id' => $docId]);
                crm_log((int) $doc['customer_id'], $doc['deal_id'] ? (int) $doc['deal_id'] : null, 'email', $doc['number'] . ' emailed to ' . $doc['customer_email']);
                flash('success', 'Emailed to ' . $doc['customer_email'] . '.');
                break;
        }
        header('Location: ' . path('admin/documents/' . $docId));
        exit;
    } catch (InventoryException $ex) {
        $error = $ex->getMessage();
    }
}

$items = doc_items($docId);
$payments = doc_payments($docId);
$balance = round((float) $doc['total'] - (float) $doc['amount_paid'], 2);
[$cls, $lab] = doc_badge($doc);
$canEdit = (float) $doc['amount_paid'] == 0.0 && !in_array($doc['status'], ['void', 'paid', 'accepted'], true);
$invoice = null;
if ($isQuote) {
    $s = db()->prepare("SELECT id, number FROM crm_documents WHERE type = 'invoice' AND source_id = :id LIMIT 1");
    $s->execute(['id' => $docId]);
    $invoice = $s->fetch() ?: null;
}

$pageTitle = $doc['number'];
$activeNav = $isQuote ? 'admin/quotes' : 'admin/invoices';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row no-print">
    <div>
        <p class="admin-kicker"><?= $isQuote ? 'Quote' : 'Invoice' ?> · <a href="<?= path('admin/customers/' . (int) $doc['customer_id']) ?>" class="dash-link"><?= e($doc['customer_name']) ?></a></p>
        <h1><?= e($doc['number']) ?> <span class="badge <?= $cls ?>" style="vertical-align:middle;"><?= e($lab) ?></span></h1>
    </div>
    <div class="admin-header-actions">
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print / Save PDF</button>
        <?php if ($doc['customer_email'] && $doc['status'] !== 'void'): ?>
            <form method="post" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="action" value="email"><button type="submit" class="btn btn-outline btn-sm">Email to customer</button></form>
        <?php endif; ?>
        <?php if ($canEdit): ?><a href="<?= path('admin/documents/' . $docId . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a><?php endif; ?>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error no-print"><?= e($error) ?></div><?php endif; ?>

<div class="doc-layout">
    <div class="card doc-paper">
        <?php $emailMode = false; require __DIR__ . '/../partials/crm_document_body.php'; ?>
    </div>

    <aside class="doc-actions no-print">
        <?php if ($isQuote): ?>
            <section class="dash-card glass-dark">
                <header class="dash-card-head"><div><h2>Quote status</h2></div></header>
                <form method="post" class="doc-status-buttons"><?= csrf_field() ?><input type="hidden" name="action" value="status">
                    <?php foreach (['sent' => 'Mark sent', 'accepted' => 'Accepted', 'declined' => 'Declined', 'expired' => 'Expired'] as $k => $l): if ($k === $doc['status']) continue; ?>
                        <button type="submit" name="to" value="<?= $k ?>" class="btn btn-outline btn-sm admin-btn-on-dark"><?= e($l) ?></button>
                    <?php endforeach; ?>
                </form>
                <?php if ($invoice): ?>
                    <p class="dash-empty">Invoiced as <a class="dash-link" href="<?= path('admin/documents/' . (int) $invoice['id']) ?>"><?= e($invoice['number']) ?></a>.</p>
                <?php elseif (!in_array($doc['status'], ['declined', 'expired'], true)): ?>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="convert"><button type="submit" class="btn btn-primary btn-block">Convert to Invoice</button></form>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="dash-card glass-dark">
                <header class="dash-card-head"><div><h2>Payments</h2><p class="dash-card-sub">Balance <?= format_naira(max(0, $balance)) ?></p></div></header>
                <?php foreach ($payments as $p): ?>
                    <div class="crm-mini"><span><?= e(PAYMENT_KINDS[$p['method']]) ?><small><?= e((new DateTimeImmutable($p['paid_on']))->format('j M Y')) ?><?= $p['reference'] ? ' · ' . e($p['reference']) : '' ?></small></span><strong><?= format_naira((float) $p['amount']) ?></strong></div>
                <?php endforeach; ?>
                <?php if (in_array($doc['status'], ['draft', 'sent', 'part_paid'], true)): ?>
                    <form method="post" class="doc-pay"><?= csrf_field() ?><input type="hidden" name="action" value="payment">
                        <label>Amount (₦)<input type="number" name="amount" min="1" step="0.01" max="<?= $balance ?>" value="<?= $balance ?>" required></label>
                        <label>Paid on<input type="date" name="paid_on" value="<?= date('Y-m-d') ?>" required></label>
                        <label>Method<select name="method"><?php foreach (PAYMENT_KINDS as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></label>
                        <label>Reference<input type="text" name="reference" maxlength="120" placeholder="Bank ref / teller no."></label>
                        <button type="submit" class="btn btn-primary btn-block">Record Payment</button>
                    </form>
                    <form method="post" class="doc-status-buttons"><?= csrf_field() ?><input type="hidden" name="action" value="status">
                        <?php if ($doc['status'] === 'draft'): ?><button type="submit" name="to" value="sent" class="btn btn-outline btn-sm admin-btn-on-dark">Mark as sent</button><?php endif; ?>
                    </form>
                    <?php if ((float) $doc['amount_paid'] == 0.0): ?>
                        <form method="post" onsubmit="return confirm('Void this invoice?');"><?= csrf_field() ?><input type="hidden" name="action" value="void"><button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark">Void invoice</button></form>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        <?php if ($doc['deal_title']): ?>
            <section class="dash-card glass-dark"><header class="dash-card-head"><div><h2>Deal</h2></div></header>
                <a class="crm-mini" href="<?= path('admin/deals/' . (int) $doc['deal_id']) ?>"><span><?= e($doc['deal_title']) ?></span></a>
            </section>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
