<?php
declare(strict_types=1);

function order_generate_ref(): string
{
    return 'SP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Creates an order + order_items from the current session cart, inside a
 * transaction with SELECT ... FOR UPDATE on the product rows — the real
 * stock check happens here, at the moment of checkout, not just when the
 * item was added to the cart (which could have been minutes or days
 * earlier, and someone else may have bought the last unit meanwhile).
 * Decrements stock, clears the cart, and returns the created order. On any
 * stock conflict nothing is written and the specific unavailable items are
 * returned so the checkout page can show exactly what changed.
 *
 * @return array{ok: bool, order: ?array, error: ?string, unavailable: array}
 */
function order_create_from_cart(string $name, string $email, ?string $phone, ?string $address): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return ['ok' => false, 'order' => null, 'error' => 'Your cart is empty.', 'unavailable' => []];
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $lines = [];
        $unavailable = [];
        $subtotal = 0.0;

        foreach ($cart as $productId => $qty) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id AND is_active = 1 FOR UPDATE');
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch();

            if (!$product || (int) $product['stock_qty'] < $qty) {
                $unavailable[] = $product['name'] ?? ('Product #' . $productId);
                continue;
            }

            $lineTotal = (float) $product['price'] * $qty;
            $subtotal += $lineTotal;
            $lines[] = [
                'product_id' => (int) $product['id'],
                'product_name' => $product['name'],
                'unit_price' => (float) $product['price'],
                'quantity' => $qty,
            ];
        }

        if ($unavailable || !$lines) {
            $pdo->rollBack();
            return [
                'ok' => false,
                'order' => null,
                'error' => 'Some items in your cart are no longer available in the quantity requested.',
                'unavailable' => $unavailable,
            ];
        }

        $orderRef = order_generate_ref();
        $pdo->prepare(
            'INSERT INTO orders (order_ref, customer_name, customer_email, customer_phone, delivery_address, subtotal)
             VALUES (:ref, :name, :email, :phone, :address, :subtotal)'
        )->execute([
            'ref' => $orderRef,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'subtotal' => $subtotal,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity)
             VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity)'
        );
        $stockStmt = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - :qty WHERE id = :id');

        foreach ($lines as $line) {
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $line['product_id'],
                'product_name' => $line['product_name'],
                'unit_price' => $line['unit_price'],
                'quantity' => $line['quantity'],
            ]);
            $stockStmt->execute(['qty' => $line['quantity'], 'id' => $line['product_id']]);
        }

        $pdo->commit();
        cart_clear();

        $fresh = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $fresh->execute(['id' => $orderId]);

        return ['ok' => true, 'order' => $fresh->fetch(), 'error' => null, 'unavailable' => []];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function order_by_ref(string $ref): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE order_ref = :ref LIMIT 1');
    $stmt->execute(['ref' => $ref]);
    return $stmt->fetch() ?: null;
}

function order_items_for(int $orderId): array
{
    $stmt = db()->prepare('SELECT * FROM order_items WHERE order_id = :id');
    $stmt->execute(['id' => $orderId]);
    return $stmt->fetchAll();
}

/**
 * Marks an order paid — idempotent (only transitions an order that's
 * still 'pending'/unpaid), safe to call from both the webhook and the
 * browser-return callback for the same payment without double-processing.
 */
function order_mark_paid(int $orderId, string $paymentReference): bool
{
    $stmt = db()->prepare(
        "UPDATE orders SET status = 'paid', payment_status = 'paid', payment_reference = :ref
         WHERE id = :id AND payment_status != 'paid'"
    );
    $stmt->execute(['ref' => $paymentReference, 'id' => $orderId]);

    return $stmt->rowCount() > 0;
}

function order_update_status(int $orderId, string $status): void
{
    db()->prepare('UPDATE orders SET status = :status WHERE id = :id')
        ->execute(['status' => $status, 'id' => $orderId]);
}
