<?php
declare(strict_types=1);

$pageTitle = 'Checkout';

$items = cart_items();
if (!$items) {
    flash('error', 'Your cart is empty.');
    header('Location: ' . path('shop'));
    exit;
}

$subtotal = cart_subtotal();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try submitting the form again.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));

        if ($name === '') $errors[] = 'Please enter your name.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($phone === '') $errors[] = 'Please enter a phone number.';
        if ($address === '') $errors[] = 'Please enter a delivery address.';

        if (!$errors) {
            $result = order_create_from_cart($name, $email, $phone, $address);

            if (!$result['ok']) {
                $errors[] = $result['error'];
                if ($result['unavailable']) {
                    $errors[] = 'Not enough stock for: ' . implode(', ', $result['unavailable']) . '.';
                }
            } else {
                header('Location: ' . path('checkout/pay/' . $result['order']['order_ref']));
                exit;
            }
        }

        if ($errors) {
            $_SESSION['old_input'] = compact('name', 'email', 'phone', 'address');
        }
    }
}

require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / <a href="<?= path('cart') ?>">Cart</a> / Checkout</div>
        <h1>Checkout</h1>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul style="margin:0;padding-left:1.2em;">
                    <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <div class="card form-card" style="margin:0;">
                <h3>Delivery Details</h3>
                <form method="post" action="<?= path('checkout') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?= old('name') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?= old('phone') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <textarea id="address" name="address" required><?= old('address') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Continue to Payment</button>
                </form>
            </div>

            <div class="card order-summary" style="margin:0;">
                <h3>Order Summary</h3>
                <?php foreach ($items as $item): ?>
                    <div class="order-summary-line">
                        <span><?= e($item['product']['name']) ?> &times; <?= (int) $item['qty'] ?></span>
                        <span><?= format_naira((float) $item['line_total']) ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="order-summary-line order-summary-total">
                    <span>Total</span>
                    <span><?= format_naira($subtotal) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
