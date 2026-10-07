<?php
declare(strict_types=1);

// PHP's built-in dev server (php -S ... index.php) treats this file as a
// router for every request — unlike Apache, it does NOT skip the router
// for a file that already exists on disk (Apache's job normally, via the
// RewriteCond in .htaccess). Without this, every CSS/JS/image request
// locally would 404 through the app router instead of being served as-is.
// This is the standard idiom for PHP's built-in server and is a no-op
// under Apache, where .htaccess already routes real files around this
// script entirely.
if (PHP_SAPI === 'cli-server') {
    $assetPath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($assetPath !== __DIR__ . '/index.php' && is_file($assetPath)) {
        return false;
    }
}

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

    '#^software-clinic$#' => __DIR__ . '/../app/views/pages/software_clinic.php',

    '#^gaming$#' => __DIR__ . '/../app/views/pages/gaming.php',
    '#^gaming/book/([a-z0-9-]+)$#' => __DIR__ . '/../app/views/pages/gaming_room_book.php',

    '#^training$#' => __DIR__ . '/../app/views/pages/training.php',

    '#^admin/login$#' => __DIR__ . '/../app/views/pages/admin_login.php',
    '#^admin/logout$#' => __DIR__ . '/../app/views/pages/admin_logout.php',
    '#^admin$#' => __DIR__ . '/../app/views/pages/admin_dashboard.php',
    '#^admin/staff$#' => __DIR__ . '/../app/views/pages/admin_staff.php',
    '#^admin/staff/new$#' => __DIR__ . '/../app/views/pages/admin_staff_form.php',
    '#^admin/staff/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_staff_form.php',
    '#^admin/account$#' => __DIR__ . '/../app/views/pages/admin_account.php',
    '#^admin/website$#' => __DIR__ . '/../app/views/pages/admin_website.php',
    '#^admin/gaming$#' => __DIR__ . '/../app/views/pages/admin_gaming.php',
    '#^admin/training$#' => __DIR__ . '/../app/views/pages/admin_training.php',
    '#^admin/students$#' => __DIR__ . '/../app/views/pages/admin_students.php',
    '#^admin/students/new$#' => __DIR__ . '/../app/views/pages/admin_student_form.php',
    '#^admin/students/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_student.php',
    '#^admin/students/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_student_form.php',
    '#^admin/crm$#' => __DIR__ . '/../app/views/pages/admin_crm.php',
    '#^admin/customers$#' => __DIR__ . '/../app/views/pages/admin_customers.php',
    '#^admin/customers/new$#' => __DIR__ . '/../app/views/pages/admin_customer_form.php',
    '#^admin/customers/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_customer.php',
    '#^admin/customers/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_customer_form.php',
    '#^admin/deals$#' => __DIR__ . '/../app/views/pages/admin_deals.php',
    '#^admin/deals/new$#' => __DIR__ . '/../app/views/pages/admin_deal_form.php',
    '#^admin/deals/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_deal.php',
    '#^admin/deals/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_deal_form.php',
    '#^admin/tasks$#' => __DIR__ . '/../app/views/pages/admin_tasks.php',
    '#^admin/quotes$#' => __DIR__ . '/../app/views/pages/admin_documents.php',
    '#^admin/invoices$#' => __DIR__ . '/../app/views/pages/admin_documents.php',
    '#^admin/documents/new$#' => __DIR__ . '/../app/views/pages/admin_document_form.php',
    '#^admin/documents/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_document.php',
    '#^admin/documents/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_document_form.php',
    '#^admin/messages$#' => __DIR__ . '/../app/views/pages/admin_messages.php',
    '#^admin/messages/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_message.php',
    '#^admin/inventory$#' => __DIR__ . '/../app/views/pages/admin_inventory.php',
    '#^admin/inventory/stock$#' => __DIR__ . '/../app/views/pages/admin_inventory_stock.php',
    '#^admin/inventory/movements$#' => __DIR__ . '/../app/views/pages/admin_inventory_movements.php',
    '#^admin/inventory/products/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_inventory_product.php',
    '#^admin/suppliers$#' => __DIR__ . '/../app/views/pages/admin_suppliers.php',
    '#^admin/suppliers/new$#' => __DIR__ . '/../app/views/pages/admin_supplier_form.php',
    '#^admin/suppliers/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_supplier_form.php',
    '#^admin/purchase-orders$#' => __DIR__ . '/../app/views/pages/admin_purchase_orders.php',
    '#^admin/purchase-orders/new$#' => __DIR__ . '/../app/views/pages/admin_purchase_order_form.php',
    '#^admin/purchase-orders/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_purchase_order_form.php',
    '#^admin/purchase-orders/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_purchase_order.php',
    '#^admin/pos$#' => __DIR__ . '/../app/views/pages/admin_pos.php',
    '#^admin/pos/sales$#' => __DIR__ . '/../app/views/pages/admin_pos_sales.php',
    '#^admin/pos/sales/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_pos_sale.php',
    '#^admin/serials$#' => __DIR__ . '/../app/views/pages/admin_serials.php',
    '#^admin/equipment$#' => __DIR__ . '/../app/views/pages/admin_equipment.php',
    '#^admin/equipment/new$#' => __DIR__ . '/../app/views/pages/admin_equipment_form.php',
    '#^admin/equipment/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_equipment_form.php',
    '#^admin/equipment/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_equipment_item.php',

    '#^admin/products$#' => __DIR__ . '/../app/views/pages/admin_products.php',
    '#^admin/products/new$#' => __DIR__ . '/../app/views/pages/admin_product_form.php',
    '#^admin/products/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_product_form.php',
    '#^admin/products/(\d+)/delete$#' => __DIR__ . '/../app/views/pages/admin_product_delete.php',
    '#^admin/categories$#' => __DIR__ . '/../app/views/pages/admin_categories.php',
    '#^admin/categories/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_category_form.php',
    '#^admin/orders$#' => __DIR__ . '/../app/views/pages/admin_orders.php',
    '#^admin/orders/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_order_detail.php',

    '#^admin/software-requests$#' => __DIR__ . '/../app/views/pages/admin_software_requests.php',
    '#^admin/software-requests/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_software_request_detail.php',
    '#^admin/businesses$#' => __DIR__ . '/../app/views/pages/admin_businesses.php',
    '#^admin/businesses/new$#' => __DIR__ . '/../app/views/pages/admin_business_form.php',
    '#^admin/businesses/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_business_form.php',
    '#^admin/businesses/(\d+)/delete$#' => __DIR__ . '/../app/views/pages/admin_business_delete.php',

    '#^admin/testimonials$#' => __DIR__ . '/../app/views/pages/admin_testimonials.php',
    '#^admin/testimonials/new$#' => __DIR__ . '/../app/views/pages/admin_testimonial_form.php',
    '#^admin/testimonials/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_testimonial_form.php',
    '#^admin/testimonials/(\d+)/delete$#' => __DIR__ . '/../app/views/pages/admin_testimonial_delete.php',
    '#^admin/home-stats$#' => __DIR__ . '/../app/views/pages/admin_home_stats.php',

    '#^admin/games$#' => __DIR__ . '/../app/views/pages/admin_games.php',
    '#^admin/games/new$#' => __DIR__ . '/../app/views/pages/admin_game_form.php',
    '#^admin/games/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_game_form.php',
    '#^admin/games/(\d+)/delete$#' => __DIR__ . '/../app/views/pages/admin_game_delete.php',
    '#^admin/rooms$#' => __DIR__ . '/../app/views/pages/admin_rooms.php',
    '#^admin/rooms/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_room_form.php',
    '#^admin/bookings$#' => __DIR__ . '/../app/views/pages/admin_bookings.php',
    '#^admin/bookings/(\d+)$#' => __DIR__ . '/../app/views/pages/admin_booking_detail.php',

    '#^admin/courses$#' => __DIR__ . '/../app/views/pages/admin_courses.php',
    '#^admin/courses/new$#' => __DIR__ . '/../app/views/pages/admin_course_form.php',
    '#^admin/courses/(\d+)/edit$#' => __DIR__ . '/../app/views/pages/admin_course_form.php',
    '#^admin/courses/(\d+)/delete$#' => __DIR__ . '/../app/views/pages/admin_course_delete.php',
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
