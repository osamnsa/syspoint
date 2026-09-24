<?php
declare(strict_types=1);

// Paystack's server-to-server notification — the source of truth for
// "did this actually get paid", independent of whether the customer's
// browser ever made it back to checkout_callback.php. Must read the RAW
// body for the signature check (see paystack_verify_webhook_signature()),
// before anything json_decode()s it.

http_response_code(200); // Ack fast either way — Paystack retries on non-2xx.

$rawBody = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? null;

if (!paystack_verify_webhook_signature($rawBody, $signature)) {
    exit;
}

$event = json_decode($rawBody, true);
if (!is_array($event) || ($event['event'] ?? '') !== 'charge.success') {
    exit;
}

$reference = (string) ($event['data']['reference'] ?? '');
$order = $reference !== '' ? order_by_ref($reference) : null;

if ($order && order_mark_paid((int) $order['id'], $reference)) {
    order_confirmation_email($order, order_items_for((int) $order['id']));
}
