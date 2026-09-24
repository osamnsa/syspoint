<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    header('Location: ' . path('cart'));
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);

if (isset($_POST['remove'])) {
    cart_remove($productId);
} else {
    cart_set_qty($productId, (int) ($_POST['qty'] ?? 0));
}

header('Location: ' . path('cart'));
exit;
