<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$routes = [
    '#^$#' => __DIR__ . '/../app/views/pages/home.php',
    '#^about$#' => __DIR__ . '/../app/views/pages/about.php',
    '#^contact$#' => __DIR__ . '/../app/views/pages/contact.php',

    '#^shop$#' => __DIR__ . '/../app/views/pages/shop.php',
    '#^shop/([a-z0-9-]+)$#' => __DIR__ . '/../app/views/pages/shop_category.php',
    '#^shop/([a-z0-9-]+)/([a-z0-9-]+)$#' => __DIR__ . '/../app/views/pages/product.php',
    '#^cart$#' => __DIR__ . '/../app/views/pages/cart.php',
    '#^cart/add$#' => __DIR__ . '/../app/views/pages/cart_add.php',
    '#^cart/update$#' => __DIR__ . '/../app/views/pages/cart_update.php',
    '#^checkout$#' => __DIR__ . '/../app/views/pages/checkout.php',
    '#^checkout/pay/([A-Za-z0-9-]+)$#' => __DIR__ . '/../app/views/pages/checkout_pay.php',
    '#^checkout/callback$#' => __DIR__ . '/../app/views/pages/checkout_callback.php',
    '#^paystack/webhook$#' => __DIR__ . '/../app/views/pages/paystack_webhook.php',
    '#^order/([A-Za-z0-9-]+)$#' => __DIR__ . '/../app/views/pages/order_confirmation.php',

    '#^admin/login$#' => __DIR__ . '/../app/views/pages/admin_login.php',
    '#^admin/logout$#' => __DIR__ . '/../app/views/pages/admin_logout.php',
    '#^admin$#' => __DIR__ . '/../app/views/pages/admin_dashboard.php',

    '#^admin/products$#' => __DIR__ . '/../app/views/pages/admin_products.php',
    '#^admin/products/new$#' => __DIR__ . '/../app/views/pages/admin_product_form.php',
    '#^admin/products/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_product_form.php',
    '#^admin/products/(\d+)/delete$#' => __DIR__ . '/../app/views/pages/admin_product_delete.php',
    '#^admin/categories$#' => __DIR__ . '/../app/views/pages/admin_categories.php',
    '#^admin/categories/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_category_form.php',
    '#^admin/orders$#' => __DIR__ . '/../app/views/pages/admin_orders.php',
    '#^admin/orders/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_order_detail.php',
];

$requestPath = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''), '/');
$base = trim(base_path(), '/');
if ($base !== '' && str_starts_with($requestPath, $base)) {
    $requestPath = trim(substr($requestPath, strlen($base)), '/');
}

foreach ($routes as $pattern => $file) {
    if (preg_match($pattern, $requestPath, $matches)) {
        $params = array_slice($matches, 1);
        require $file;
        return;
    }
}

http_response_code(404);
require __DIR__ . '/../app/views/pages/not_found.php';
