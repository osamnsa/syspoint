<?php
declare(strict_types=1);

/** @var array $params [id] when editing */

$adminUser = require_admin();

$docId = isset($params[0]) ? (int) $params[0] : 0;
$doc = $docId ? doc_by_id($docId) : null;
if ($docId && !$doc) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}
if ($doc && ((float) $doc['amount_paid'] > 0 || in_array($doc['status'], ['void', 'paid', 'accepted'], true))) {
    flash('success', 'This document can no longer be edited.');
    header('Location: ' . path('admin/documents/' . $docId));
    exit;
}
$type = $doc['type'] ?? (($_GET['type'] ?? '') === 'quote' ? 'quote' : 'invoice');
$customers = db()->query('SELECT c.id, c.name, o.name AS org FROM customers c LEFT JOIN customers o ON o.id = c.organisation_id ORDER BY c.name')->fetchAll();
$deals = db()->query("SELECT d.id, d.title, d.customer_id FROM deals d WHERE d.stage <> 'lost' ORDER BY d.id DESC")->fetchAll();
$products = array_values(array_filter(inventory_products(true), fn($p) => !(int) $p['is_demo']));

$defaultTerms = doc_default_terms($type);
$values = [
    'customer_id' => (string) ($doc['customer_id'] ?? ($_GET['customer'] ?? '')),
    'deal_id' => (string) ($doc['deal_id'] ?? ($_GET['deal'] ?? '')),
    'issue_date' => $doc['issue_date'] ?? date('Y-m-d'),
    'due_date' => $doc['due_date'] ?? date('Y-m-d', strtotime($type === 'quote' ? '+30 days' : '+14 days')),
    'discount' => $doc['discount'] ?? '0',
    'tax_rate' => (string) ($doc['tax_rate'] ?? '0'),
    'notes' => $doc['notes'] ?? '',
    'terms' => $doc['terms'] ?? $defaultTerms,
];
$lines = $doc ? array_map(fn($i) => ['description' => $i['description'], 'qty' => $i['quantity'], 'price' => $i['unit_price']], doc_items($docId)) : [];
if (!$lines && !empty($_GET['deal'])) {
    $d = deal_by_id((int) $_GET['deal']);
    if ($d) $lines[] = ['description' => $d['title'], 'qty' => 1, 'price' => $d['value']];
}
if (!$lines) $lines[] = ['description' => '', 'qty' => 1, 'price' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        foreach (['customer_id', 'deal_id', 'issue_date', 'due_date', 'discount', 'notes', 'terms'] as $k) $values[$k] = trim((string) ($_POST[$k] ?? ''));
        $values['tax_rate'] = isset($_POST['vat']) ? (string) VAT_RATE : '0';
        $lines = [];
        foreach ((array) ($_POST['description'] ?? []) as $i => $desc) {
            $desc = trim((string) $desc);
            $qty = trim((string) ($_POST['qty'][$i] ?? ''));
            $price = trim((string) ($_POST['price'][$i] ?? ''));
            if ($desc === '' && $price === '') continue;
            $lines[] = ['description' => $desc, 'qty' => $qty, 'price' => $price];
            if ($desc === '') $errors[] = 'Every line needs a description.';
            if (!is_numeric($qty) || (float) $qty <= 0) $errors[] = 'Quantities must be more than 0.';
            if (!is_numeric($price) || (float) $price < 0) $errors[] = 'Enter a price on every line.';
        }
        $errors = array_values(array_unique($errors));
        if (!$lines) $errors[] = 'Add at least one line.';
        if (!array_filter($customers, fn($c) => (string) $c['id'] === $values['customer_id'])) $errors[] = 'Choose the customer.';
        if ($values['deal_id'] !== '' && !array_filter($deals, fn($d) => (string) $d['id'] === $values['deal_id'] && (string) $d['customer_id'] === $values['customer_id'])) $values['deal_id'] = '';
        if (!DateTimeImmutable::createFromFormat('Y-m-d', $values['issue_date'])) $errors[] = 'Enter a valid issue date.';
        if ($values['due_date'] !== '' && !DateTimeImmutable::createFromFormat('Y-m-d', $values['due_date'])) $errors[] = 'Enter a valid due date.';
        if ($values['discount'] === '') $values['discount'] = '0';
        if (!is_numeric($values['discount']) || (float) $values['discount'] < 0) $errors[] = 'Enter a valid discount.';

        if (!$errors) {
            $id = doc_save($type, $doc, $values, $lines);
            flash('success', ($type === 'quote' ? 'Quote' : 'Invoice') . ' saved.');
            header('Location: ' . path('admin/documents/' . $id));
            exit;
        }
    }
}

$pageTitle = $doc ? 'Edit ' . $doc['number'] : 'New ' . ($type === 'quote' ? 'Quote' : 'Invoice');
$activeNav = $type === 'quote' ? 'admin/quotes' : 'admin/invoices';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path($doc ? 'admin/documents/' . $docId : ($type === 'quote' ? 'admin/quotes' : 'admin/invoices')) ?>" class="btn btn-outline btn-sm">Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="admin-form admin-form-wide" data-doc-form>
    <?= csrf_field() ?>
    <div class="form-row form-row-3">
        <div class="form-group"><label for="customer_id">Customer</label>
            <select id="customer_id" name="customer_id" required><option value="">Choose…</option>
                <?php foreach ($customers as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $values['customer_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?><?= $c['org'] ? ' (' . e($c['org']) . ')' : '' ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group"><label for="issue_date">Date</label><input type="date" id="issue_date" name="issue_date" value="<?= e((string) $values['issue_date']) ?>" required></div>
        <div class="form-group"><label for="due_date"><?= $type === 'quote' ? 'Valid until' : 'Payment due' ?></label><input type="date" id="due_date" name="due_date" value="<?= e((string) $values['due_date']) ?>"></div>
    </div>
    <div class="form-group"><label for="deal_id">Deal (optional)</label>
        <select id="deal_id" name="deal_id"><option value="">—</option>
            <?php foreach ($deals as $d): ?><option value="<?= (int) $d['id'] ?>" data-customer="<?= (int) $d['customer_id'] ?>" <?= $values['deal_id'] === (string) $d['id'] ? 'selected' : '' ?>><?= e($d['title']) ?></option><?php endforeach; ?>
        </select></div>

    <datalist id="doc-products"><?php foreach ($products as $p): ?><option value="<?= e($p['name']) ?>" data-price="<?= e((string) $p['price']) ?>"></option><?php endforeach; ?></datalist>
    <table class="admin-table po-lines" data-repeater>
        <thead><tr><th>Description</th><th style="width:100px;">Qty</th><th style="width:170px;">Unit price (₦)</th><th style="width:150px;">Amount</th><th style="width:40px;"></th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
            <tr data-repeater-row>
                <td><input type="text" name="description[]" value="<?= e((string) $l['description']) ?>" list="doc-products" placeholder="Service or product" data-doc-desc></td>
                <td><input type="number" name="qty[]" value="<?= e((string) $l['qty']) ?>" min="0.01" step="0.01" data-po-qty></td>
                <td><input type="number" name="price[]" value="<?= e((string) $l['price']) ?>" min="0" step="0.01" data-po-cost></td>
                <td data-po-total>—</td>
                <td><button type="button" class="admin-row-remove" data-repeater-remove aria-label="Remove line">×</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3"><button type="button" class="btn btn-outline btn-sm" data-repeater-add>+ Add line</button></td><td colspan="2"></td></tr>
            <tr><td colspan="3" class="doc-total-label">Subtotal</td><td colspan="2"><strong data-po-grand>—</strong></td></tr>
            <tr><td colspan="3" class="doc-total-label">Discount (₦)</td><td colspan="2"><input type="number" name="discount" min="0" step="0.01" value="<?= e((string) $values['discount']) ?>" data-doc-discount></td></tr>
            <tr><td colspan="3" class="doc-total-label"><label><input type="checkbox" name="vat" value="1" <?= (float) $values['tax_rate'] > 0 ? 'checked' : '' ?> data-doc-vat style="width:auto;"> Add VAT (<?= VAT_RATE ?>%)</label></td><td colspan="2" data-doc-tax>—</td></tr>
            <tr class="doc-grand"><td colspan="3" class="doc-total-label">Total</td><td colspan="2"><strong data-doc-total>—</strong></td></tr>
        </tfoot>
    </table>

    <div class="form-row" style="margin-top:18px;">
        <div class="form-group"><label for="notes">Note to customer</label><textarea id="notes" name="notes" rows="3"><?= e((string) $values['notes']) ?></textarea></div>
        <div class="form-group"><label for="terms">Terms</label><textarea id="terms" name="terms" rows="3"><?= e((string) $values['terms']) ?></textarea></div>
    </div>
    <button type="submit" class="btn btn-primary">Save <?= $type === 'quote' ? 'Quote' : 'Invoice' ?></button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
