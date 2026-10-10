<?php
declare(strict_types=1);

$adminUser = require_admin();

$error = null;
$posted = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $lines = [];
        foreach ((array) ($_POST['lines'] ?? []) as $l) {
            $lines[] = [
                'product_id' => (int) ($l['product_id'] ?? 0),
                'qty' => (int) ($l['qty'] ?? 0),
                'unit_ids' => array_map('intval', (array) ($l['unit_ids'] ?? [])),
            ];
        }
        $customer = [
            'name' => trim((string) ($_POST['customer_name'] ?? '')),
            'phone' => trim((string) ($_POST['customer_phone'] ?? '')),
            'email' => trim((string) ($_POST['customer_email'] ?? '')),
        ];
        $paid = trim((string) ($_POST['amount_paid'] ?? ''));
        try {
            if ($customer['email'] !== '' && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InventoryException('The customer email isn’t valid.');
            }
            $saleId = pos_create_sale($lines, $customer, (string) ($_POST['payment_method'] ?? ''),
                (float) ($_POST['discount'] ?? 0), $paid === '' ? null : (float) $paid, trim((string) ($_POST['notes'] ?? '')));
            if ($customer['name'] !== '' || $customer['phone'] !== '' || $customer['email'] !== '') {
                crm_link('pos_sales', $saleId, $customer['name'], $customer['email'], $customer['phone'], 'walk_in');
            }
            $sale = pos_sale_by_id($saleId);
            notify_staff('walk_in', '🧾 Walk-in sale ' . $sale['receipt_no'], [
                format_naira((float) $sale['total']) . ' · ' . PAYMENT_METHODS[$sale['payment_method']],
                'Served by ' . ($sale['cashier_name'] ?? '—') . ($customer['name'] !== '' ? ' · ' . $customer['name'] : ''),
            ], 'admin/pos/sales/' . $saleId);
            flash('success', 'Sale recorded.');
            header('Location: ' . path('admin/pos/sales/' . $saleId) . '?new=1');
            exit;
        } catch (InventoryException $ex) {
            $error = $ex->getMessage();
            $posted = $_POST;
        }
    }
}

$products = array_values(array_filter(inventory_products(true), fn($p) => !(int) $p['is_demo']));
$units = inventory_available_units();
$catalog = array_map(fn($p) => [
    'id' => (int) $p['id'],
    'name' => $p['name'],
    'sku' => (string) $p['sku'],
    'category' => $p['category_name'],
    'price' => (float) $p['price'],
    'stock' => (int) $p['stock_qty'],
    'serials' => (bool) $p['track_serials'],
    'units' => $p['track_serials'] ? ($units[(int) $p['id']] ?? []) : [],
    'image' => $p['image_path'] ? media_url($p['image_path']) : null,
], $products);

$today = db()->query("SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS total,
        COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total END), 0) AS cash,
        COALESCE(SUM(CASE WHEN payment_method = 'transfer' THEN total END), 0) AS transfer,
        COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total END), 0) AS card
    FROM pos_sales WHERE status = 'completed' AND DATE(created_at) = CURDATE()")->fetch();

$pageTitle = 'Point of Sale';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker"><?= e(site('site.store_name') . ' · ' . site('site.store_suite')) ?></p><h1>Point of Sale</h1></div>
    <a href="<?= path('admin/pos/sales') ?>" class="btn btn-outline btn-sm">Sales History</a>
</div>

<div class="pos-today">
    <div><span>Today’s sales</span><strong><?= (int) $today['n'] ?></strong></div>
    <div><span>Takings</span><strong><?= format_naira((float) $today['total']) ?></strong></div>
    <div><span>Cash</span><strong><?= format_naira((float) $today['cash']) ?></strong></div>
    <div><span>Transfer</span><strong><?= format_naira((float) $today['transfer']) ?></strong></div>
    <div><span>Card</span><strong><?= format_naira((float) $today['card']) ?></strong></div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="pos" data-pos>
    <?= csrf_field() ?>
    <section class="pos-catalog">
        <input type="search" class="pos-search" placeholder="Search products or SKU…" aria-label="Search products" data-pos-search autofocus>
        <div class="pos-grid" data-pos-grid></div>
        <p class="pos-empty" data-pos-none hidden>No products match.</p>
    </section>

    <aside class="pos-cart card">
        <h2>Current sale</h2>
        <div class="pos-lines" data-pos-lines><p class="pos-empty" data-pos-empty>Tap a product to add it.</p></div>
        <div class="pos-totals">
            <div><span>Subtotal</span><strong data-pos-subtotal>₦0</strong></div>
            <div class="pos-discount"><label for="discount">Discount (₦)</label><input type="number" id="discount" name="discount" min="0" step="1" value="<?= e((string) ($posted['discount'] ?? '0')) ?>" data-pos-discount></div>
            <div class="pos-grand"><span>Total</span><strong data-pos-total>₦0</strong></div>
        </div>
        <fieldset class="pos-pay">
            <legend>Payment</legend>
            <div class="pos-methods">
                <?php foreach (PAYMENT_METHODS as $key => $label): ?>
                    <label><input type="radio" name="payment_method" value="<?= $key ?>" <?= ($posted['payment_method'] ?? 'cash') === $key ? 'checked' : '' ?>> <?= e($label) ?></label>
                <?php endforeach; ?>
            </div>
            <div class="form-row">
                <div class="form-group"><label for="amount_paid">Amount received (₦)</label><input type="number" id="amount_paid" name="amount_paid" min="0" step="1" value="<?= e((string) ($posted['amount_paid'] ?? '')) ?>" data-pos-paid></div>
                <div class="form-group"><label>Change</label><output class="pos-change" data-pos-change>—</output></div>
            </div>
        </fieldset>
        <details class="pos-customer" <?= !empty($posted['customer_name']) ? 'open' : '' ?>>
            <summary>Customer details (optional — needed for warranty)</summary>
            <div class="form-group"><label for="customer_name">Name</label><input type="text" id="customer_name" name="customer_name" value="<?= e((string) ($posted['customer_name'] ?? '')) ?>"></div>
            <div class="form-row">
                <div class="form-group"><label for="customer_phone">Phone</label><input type="tel" id="customer_phone" name="customer_phone" value="<?= e((string) ($posted['customer_phone'] ?? '')) ?>"></div>
                <div class="form-group"><label for="customer_email">Email</label><input type="email" id="customer_email" name="customer_email" value="<?= e((string) ($posted['customer_email'] ?? '')) ?>"></div>
            </div>
            <div class="form-group"><label for="notes">Note</label><input type="text" id="notes" name="notes" maxlength="255" value="<?= e((string) ($posted['notes'] ?? '')) ?>"></div>
        </details>
        <button type="submit" class="btn btn-primary btn-block pos-submit" data-pos-submit disabled>Complete Sale</button>
    </aside>
    <script type="application/json" data-pos-catalog><?= json_encode($catalog, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
