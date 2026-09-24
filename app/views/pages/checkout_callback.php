<?php
declare(strict_types=1);

// Paystack's browser return after a payment attempt. This is a convenience
// redirect for the customer, not the source of truth — the webhook
// (paystack_webhook.php) is what actually must mark an order paid if this
// page is never hit (closed tab, network drop). order_mark_paid() is
// idempotent so whichever of the two runs first "wins" safely.

$reference = (string) ($_GET['reference'] ?? '');
$order = $reference !== '' ? order_by_ref($reference) : null;

if (!$order) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

if ($order['payment_status'] !== 'paid') {
    $verify = paystack_verify($reference);

    if ($verify['ok'] && $verify['status'] === 'success') {
        if (order_mark_paid((int) $order['id'], $reference)) {
            order_confirmation_email($order, order_items_for((int) $order['id']));
        }
        header('Location: ' . path('order/' . $order['order_ref']));
        exit;
    }

    header('Location: ' . path('checkout/pay/' . $order['order_ref']));
    exit;
}

header('Location: ' . path('order/' . $order['order_ref']));
exit;
