<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    header('Location: ' . path('shop'));
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
$qty = (int) ($_POST['qty'] ?? 1);

$product = product_by_id($productId);

// redirect_to must be an internal, single-slash path this app generated —
// never trust it blindly. Reject "//evil.com" (protocol-relative) and
// "https://evil.com" (absolute) alike, or a crafted POST could bounce a
// visitor off-site.
$redirectTo = (string) ($_POST['redirect_to'] ?? '');
if ($redirectTo === '' || $redirectTo[0] !== '/' || str_starts_with($redirectTo, '//') || !str_starts_with($redirectTo, path())) {
    $redirectTo = path('cart');
}

if ($product && $product['is_active'] && $qty > 0) {
    cart_add($productId, $qty);
    flash('cart_added', 'Added ' . $product['name'] . ' to your cart.');
}

header('Location: ' . $redirectTo);
exit;
