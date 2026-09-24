<?php
declare(strict_types=1);

/**
 * Session-based cart — no DB table, just $_SESSION['cart'] = [product_id
 * => qty]. Nothing is "reserved" until checkout actually creates an order,
 * so stock is only ever authoritative from the products table itself;
 * cart_items() always re-reads live price/stock rather than trusting
 * anything cached in the session, and clamps qty down to available stock
 * (or drops the line entirely) if it's changed since it was added.
 */

function cart_add(int $productId, int $qty): void
{
    $qty = max(1, $qty);
    $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
}

function cart_set_qty(int $productId, int $qty): void
{
    if ($qty <= 0) {
        unset($_SESSION['cart'][$productId]);
        return;
    }
    $_SESSION['cart'][$productId] = $qty;
}

function cart_remove(int $productId): void
{
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

/**
 * @return array<int, array{product: array, qty: int, line_total: float, stock_limited: bool}>
 * A line with stock_limited=true had its qty silently clamped down to
 * what's actually in stock right now — the cart page shows a note when
 * that happens rather than just silently changing the number.
 */
function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [];
    }

    $items = [];
    foreach ($cart as $productId => $qty) {
        $product = product_by_id((int) $productId);
        if (!$product || !$product['is_active']) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $stockLimited = false;
        if ($qty > (int) $product['stock_qty']) {
            $qty = (int) $product['stock_qty'];
            $stockLimited = true;
            if ($qty <= 0) {
                unset($_SESSION['cart'][$productId]);
                continue;
            }
            $_SESSION['cart'][$productId] = $qty;
        }

        $items[] = [
            'product' => $product,
            'qty' => $qty,
            'line_total' => (float) $product['price'] * $qty,
            'stock_limited' => $stockLimited,
        ];
    }

    return $items;
}

function cart_count(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

function cart_subtotal(): float
{
    return array_sum(array_column(cart_items(), 'line_total'));
}
