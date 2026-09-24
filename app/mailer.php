<?php
declare(strict_types=1);

/**
 * Just PHP's built-in mail() — no Composer, no SMTP library, matching the
 * rest of this project. Failures here are logged and swallowed rather than
 * thrown: a broken mail server should never take down an order that already
 * succeeded and was already paid for.
 */
function send_mail(string $to, string $subject, string $htmlBody): bool
{
    $mail = config()['mail'];

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $mail['from_name'] . ' <' . $mail['from'] . '>',
    ];

    $ok = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));

    if (!$ok) {
        error_log("send_mail failed: to={$to} subject=\"{$subject}\"");
    }

    return $ok;
}

function order_confirmation_email(array $order, array $items): void
{
    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . e($item['product_name']) . '</td>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:center;">' . (int) $item['quantity'] . '</td>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right;">' . format_naira((float) $item['unit_price']) . '</td>'
            . '</tr>';
    }

    $html = '<div style="font-family:sans-serif;max-width:520px;margin:0 auto;">'
        . '<h2 style="color:#0b1420;">Thanks for your order, ' . e($order['customer_name']) . '!</h2>'
        . '<p>Your order <strong>' . e($order['order_ref']) . '</strong> has been received.</p>'
        . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
        . '<thead><tr>'
        . '<th style="text-align:left;padding:6px 8px;border-bottom:2px solid #0b1420;">Item</th>'
        . '<th style="padding:6px 8px;border-bottom:2px solid #0b1420;">Qty</th>'
        . '<th style="text-align:right;padding:6px 8px;border-bottom:2px solid #0b1420;">Price</th>'
        . '</tr></thead><tbody>' . $rows . '</tbody></table>'
        . '<p style="text-align:right;font-size:1.1em;"><strong>Total: ' . format_naira((float) $order['subtotal']) . '</strong></p>'
        . '<p>We will be in touch about delivery. If you have questions, just reply to this email.</p>'
        . '</div>';

    send_mail($order['customer_email'], 'Order Confirmation — ' . $order['order_ref'], $html);
}
