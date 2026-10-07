<?php
declare(strict_types=1);

/** @var array $params [sale id] */

$adminUser = require_admin();

$saleId = (int) ($params[0] ?? 0);
$sale = pos_sale_by_id($saleId);
if (!$sale) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['action'] ?? '') === 'void') {
    $reason = trim((string) ($_POST['reason'] ?? ''));
    try {
        if ($reason === '') throw new InventoryException('Give a reason for voiding this sale.');
        pos_void_sale($saleId, $reason);
        flash('success', 'Sale voided — the items are back in stock.');
        header('Location: ' . path('admin/pos/sales/' . $saleId));
        exit;
    } catch (InventoryException $ex) {
        $error = $ex->getMessage();
    }
}

$items = pos_sale_items($saleId);
$units = pos_sale_units($saleId);
$isNew = isset($_GET['new']);

$pageTitle = 'Receipt ' . $sale['receipt_no'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row no-print">
    <div><p class="admin-kicker">Point of Sale</p><h1>Receipt <?= e($sale['receipt_no']) ?></h1></div>
    <div class="admin-header-actions">
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print Receipt</button>
        <?php if ($isNew): ?><a href="<?= path('admin/pos') ?>" class="btn btn-outline btn-sm">Next Sale</a><?php endif; ?>
        <a href="<?= path('admin/pos/sales') ?>" class="btn btn-outline btn-sm">All Sales</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="card receipt">
    <div class="receipt-head">
        <img src="<?= asset('assets/img/logo.png') ?>" alt="" width="44" height="44">
        <div>
            <strong>Syspoint Solutions Consult Limited</strong>
            <small>Gadget Store · Suite C20, Awesome Plaza, Apo Resettlement, Abuja</small>
        </div>
    </div>
    <?php if ($sale['status'] === 'voided'): ?><p class="receipt-void">VOIDED — <?= e((string) $sale['void_reason']) ?></p><?php endif; ?>
    <div class="receipt-meta">
        <span>Receipt <strong><?= e($sale['receipt_no']) ?></strong></span>
        <span><?= e((new DateTimeImmutable($sale['created_at']))->format('j M Y, g:i A')) ?></span>
        <span>Served by <?= e($sale['cashier_name'] ?? '—') ?></span>
        <?php if ($sale['customer_name']): ?><span>Customer: <?= e($sale['customer_name']) ?><?= $sale['customer_phone'] ? ' · ' . e($sale['customer_phone']) : '' ?></span><?php endif; ?>
    </div>
    <table class="receipt-items">
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i): ?>
            <tr>
                <td><?= e($i['product_name']) ?>
                    <?php foreach ($units as $u): if ((int) $u['product_id'] === (int) $i['product_id']): ?>
                        <br><small>S/N <?= e($u['serial']) ?><?= $u['warranty_until'] ? ' · warranty to ' . e((new DateTimeImmutable($u['warranty_until']))->format('j M Y')) : '' ?></small>
                    <?php endif; endforeach; ?>
                </td>
                <td><?= (int) $i['quantity'] ?></td>
                <td><?= format_naira((float) $i['unit_price']) ?></td>
                <td><?= format_naira($i['unit_price'] * $i['quantity']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3">Subtotal</td><td><?= format_naira((float) $sale['subtotal']) ?></td></tr>
            <?php if ((float) $sale['discount'] > 0): ?><tr><td colspan="3">Discount</td><td>−<?= format_naira((float) $sale['discount']) ?></td></tr><?php endif; ?>
            <tr class="receipt-total"><td colspan="3">Total</td><td><?= format_naira((float) $sale['total']) ?></td></tr>
            <tr><td colspan="3">Paid by <?= e(PAYMENT_METHODS[$sale['payment_method']]) ?></td><td><?= $sale['amount_paid'] !== null ? format_naira((float) $sale['amount_paid']) : '' ?></td></tr>
            <?php if ($sale['amount_paid'] !== null && (float) $sale['amount_paid'] > (float) $sale['total']): ?><tr><td colspan="3">Change</td><td><?= format_naira($sale['amount_paid'] - $sale['total']) ?></td></tr><?php endif; ?>
        </tfoot>
    </table>
    <p class="receipt-foot">Thank you for shopping with Syspoint. Keep this receipt for warranty claims.</p>
</div>

<?php if ($sale['status'] === 'completed'): ?>
<details class="admin-danger-zone no-print">
    <summary>Void this sale</summary>
    <form method="post" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="void">
        <div class="form-group">
            <label for="reason">Reason</label>
            <input type="text" id="reason" name="reason" maxlength="255" required placeholder="e.g. Entered by mistake / customer changed mind">
        </div>
        <button type="submit" class="btn btn-outline" onclick="return confirm('Void this sale and put the items back in stock?');">Void Sale</button>
    </form>
</details>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
