<?php
/**
 * Inventory: stock ledger, purchase orders (restocking), walk-in POS sales,
 * serial-numbered units and warranty, and the Hub's equipment register.
 *
 * Rule: products.stock_qty is only ever changed through inventory_move(),
 * always inside a transaction, so every change has a matching
 * stock_movements row and the ledger explains the on-hand number.
 */

declare(strict_types=1);

const STOCK_REASONS = [
    'opening' => 'Opening stock',
    'purchase' => 'Received from supplier',
    'online_sale' => 'Online sale',
    'pos_sale' => 'Walk-in sale',
    'adjustment' => 'Stock count adjustment',
    'return' => 'Customer return',
    'damage' => 'Damaged / written off',
    'order_cancelled' => 'Online order cancelled',
    'sale_voided' => 'Walk-in sale voided',
];

/** Reasons staff can pick when adjusting stock by hand. */
const STOCK_MANUAL_REASONS = ['adjustment', 'return', 'damage'];

const PAYMENT_METHODS = ['cash' => 'Cash', 'transfer' => 'Bank transfer', 'card' => 'Card (POS terminal)', 'split' => 'Split payment'];

const ASSET_CATEGORIES = [
    'console' => 'Console', 'controller' => 'Controller', 'vr_headset' => 'VR headset', 'pc' => 'PC / laptop',
    'display' => 'TV / display', 'audio' => 'Audio', 'network' => 'Network', 'furniture' => 'Furniture', 'other' => 'Other',
];
const ASSET_STATUSES = ['in_use' => 'In use', 'spare' => 'Spare', 'in_repair' => 'In repair', 'retired' => 'Retired'];

/** Runs $fn inside a transaction (or the one already open) and returns its result. */
function inventory_tx(callable $fn): mixed
{
    $pdo = db();
    if ($pdo->inTransaction()) {
        return $fn($pdo);
    }
    $pdo->beginTransaction();
    try {
        $result = $fn($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Change a product's stock by $change (negative = out) and record why.
 * Must run inside a transaction; locks the product row. Throws
 * InventoryException if stock would go below zero.
 */
function inventory_move(int $productId, int $change, string $reason, ?string $refType = null, ?int $refId = null, ?string $note = null): int
{
    $pdo = db();
    if (!$pdo->inTransaction()) {
        throw new LogicException('inventory_move() must run inside a transaction');
    }
    $stmt = $pdo->prepare('SELECT id, name, stock_qty, reorder_level FROM products WHERE id = :id FOR UPDATE');
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();
    if (!$product) {
        throw new InventoryException('That product no longer exists.');
    }
    $balance = (int) $product['stock_qty'] + $change;
    if ($balance < 0) {
        throw new InventoryException('Not enough stock of ' . $product['name'] . ' (' . (int) $product['stock_qty'] . ' on hand).');
    }
    $pdo->prepare('UPDATE products SET stock_qty = :qty WHERE id = :id')->execute(['qty' => $balance, 'id' => $productId]);
    // A sale that takes stock to (or below) the reorder level for the first time.
    if (in_array($reason, ['online_sale', 'pos_sale'], true) && isset($product['reorder_level'])
        && (int) $product['stock_qty'] > (int) $product['reorder_level'] && $balance <= (int) $product['reorder_level']) {
        telegram_notify('low_stock', '📦 Low stock — ' . $product['name'], [
            $balance === 0 ? 'Now OUT OF STOCK.' : 'Only ' . $balance . ' left (reorder level ' . (int) $product['reorder_level'] . ').',
        ], 'admin/inventory/products/' . $productId);
    }
    $pdo->prepare(
        'INSERT INTO stock_movements (product_id, change_qty, balance_after, reason, ref_type, ref_id, note, user_id)
         VALUES (:pid, :chg, :bal, :reason, :rtype, :rid, :note, :uid)'
    )->execute([
        'pid' => $productId, 'chg' => $change, 'bal' => $balance, 'reason' => $reason,
        'rtype' => $refType, 'rid' => $refId, 'note' => $note, 'uid' => admin_user()['id'] ?? null,
    ]);
    return $balance;
}

final class InventoryException extends RuntimeException {}

/** Next document number like PO-2026-0007 / R-2026-00012 (per year, gap-free enough for paperwork). */
function inventory_next_number(string $table, string $col, string $prefix, int $pad): string
{
    $year = date('Y');
    $stmt = db()->prepare("SELECT $col FROM $table WHERE $col LIKE :p ORDER BY id DESC LIMIT 1");
    $stmt->execute(['p' => "$prefix-$year-%"]);
    $last = (string) $stmt->fetchColumn();
    $n = $last ? (int) substr($last, strrpos($last, '-') + 1) + 1 : 1;
    return sprintf('%s-%s-%0' . $pad . 'd', $prefix, $year, $n);
}

/** Products for pickers: id, name, sku, price, cost, stock, track_serials. */
function inventory_products(bool $activeOnly = false): array
{
    $sql = 'SELECT p.id, p.name, p.sku, p.price, p.cost_price, p.stock_qty, p.reorder_level, p.track_serials, p.warranty_months,
                   p.is_active, p.is_demo, p.image_path, c.name AS category_name
            FROM products p JOIN product_categories c ON c.id = p.category_id';
    if ($activeOnly) $sql .= ' WHERE p.is_active = 1';
    return db()->query($sql . ' ORDER BY p.name')->fetchAll();
}

/** Headline numbers for the Inventory dashboard (real stock only, demo products excluded). */
function inventory_summary(): array
{
    $row = db()->query(
        "SELECT COUNT(*) AS products,
                COALESCE(SUM(stock_qty), 0) AS units,
                COALESCE(SUM(stock_qty * COALESCE(cost_price, 0)), 0) AS value_cost,
                COALESCE(SUM(stock_qty * price), 0) AS value_retail,
                SUM(stock_qty = 0) AS out_of_stock,
                SUM(stock_qty > 0 AND stock_qty <= reorder_level) AS low_stock,
                SUM(cost_price IS NULL AND stock_qty > 0) AS missing_cost
         FROM products WHERE is_demo = 0"
    )->fetch();
    return array_map(fn($v) => (float) $v, $row);
}

// --- Purchase orders --------------------------------------------------------

const PO_STATUSES = ['draft' => 'Draft', 'ordered' => 'Ordered', 'partially_received' => 'Part received', 'received' => 'Received', 'cancelled' => 'Cancelled'];

function po_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT po.*, s.name AS supplier_name, s.phone AS supplier_phone, s.email AS supplier_email
                           FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id WHERE po.id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function po_items(int $poId): array
{
    $stmt = db()->prepare('SELECT i.*, p.name AS product_name, p.sku, p.track_serials, p.stock_qty
                           FROM purchase_order_items i JOIN products p ON p.id = i.product_id WHERE i.po_id = :id ORDER BY i.id');
    $stmt->execute(['id' => $poId]);
    return $stmt->fetchAll();
}

/**
 * Receive goods against a PO. $qtys: [item_id => qty received now];
 * $serials: [item_id => [serial, ...]] (required, one per unit, for
 * serial-tracked products). Adds stock, updates the product's cost price
 * to this purchase's unit cost, creates serial units, and moves the PO to
 * part received / received.
 */
function po_receive(int $poId, array $qtys, array $serials): void
{
    inventory_tx(function (PDO $pdo) use ($poId, $qtys, $serials) {
        $po = $pdo->prepare('SELECT * FROM purchase_orders WHERE id = :id FOR UPDATE');
        $po->execute(['id' => $poId]);
        $po = $po->fetch();
        if (!$po || in_array($po['status'], ['received', 'cancelled'], true)) {
            throw new InventoryException('This purchase order can’t be received.');
        }
        $any = false;
        foreach (po_items($poId) as $item) {
            $qty = max(0, (int) ($qtys[$item['id']] ?? 0));
            if ($qty === 0) continue;
            $remaining = (int) $item['qty_ordered'] - (int) $item['qty_received'];
            if ($qty > $remaining) {
                throw new InventoryException('You can receive at most ' . $remaining . ' more of ' . $item['product_name'] . '.');
            }
            $itemSerials = array_values(array_unique(array_filter(array_map('trim', (array) ($serials[$item['id']] ?? [])))));
            if ($item['track_serials'] && count($itemSerials) !== $qty) {
                throw new InventoryException('Enter exactly ' . $qty . ' serial number(s) for ' . $item['product_name'] . ' — one per line.');
            }
            foreach ($itemSerials as $sn) {
                $dupe = $pdo->prepare('SELECT COUNT(*) FROM product_units WHERE serial = :s');
                $dupe->execute(['s' => $sn]);
                if ((int) $dupe->fetchColumn() > 0) throw new InventoryException('Serial ' . $sn . ' is already in the system.');
                $pdo->prepare('INSERT INTO product_units (product_id, serial, po_id) VALUES (:p, :s, :po)')
                    ->execute(['p' => $item['product_id'], 's' => $sn, 'po' => $poId]);
            }
            inventory_move((int) $item['product_id'], $qty, 'purchase', 'purchase_order', $poId, $po['po_number']);
            $pdo->prepare('UPDATE purchase_order_items SET qty_received = qty_received + :q WHERE id = :id')->execute(['q' => $qty, 'id' => $item['id']]);
            $pdo->prepare('UPDATE products SET cost_price = :c WHERE id = :id')->execute(['c' => $item['unit_cost'], 'id' => $item['product_id']]);
            $any = true;
        }
        if (!$any) throw new InventoryException('Enter a quantity received for at least one line.');
        $left = $pdo->prepare('SELECT COALESCE(SUM(qty_ordered - qty_received), 0) FROM purchase_order_items WHERE po_id = :id');
        $left->execute(['id' => $poId]);
        $status = (int) $left->fetchColumn() === 0 ? 'received' : 'partially_received';
        $pdo->prepare('UPDATE purchase_orders SET status = :s, order_date = COALESCE(order_date, CURDATE()) WHERE id = :id')->execute(['s' => $status, 'id' => $poId]);
    });
}

// --- Walk-in POS ------------------------------------------------------------

/**
 * Record a walk-in sale. $lines: [['product_id' => int, 'qty' => int, 'unit_ids' => [int, ...]], ...]
 * (unit_ids required for serial-tracked products, one per unit sold).
 * Prices come from the product record; a sale-level $discount is allowed.
 * Returns the new sale id.
 */
function pos_create_sale(array $lines, array $customer, string $method, float $discount, ?float $amountPaid, ?string $notes): int
{
    if (!$lines) throw new InventoryException('Add at least one item to the sale.');
    if (!isset(PAYMENT_METHODS[$method])) throw new InventoryException('Choose a payment method.');

    return inventory_tx(function (PDO $pdo) use ($lines, $customer, $method, $discount, $amountPaid, $notes) {
        $prepared = [];
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $qty = (int) ($line['qty'] ?? 0);
            if ($qty < 1) continue;
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => (int) $line['product_id']]);
            $p = $stmt->fetch();
            if (!$p) throw new InventoryException('A product in this sale no longer exists.');
            if ((int) $p['is_demo']) throw new InventoryException($p['name'] . ' is a demo listing and can’t be sold.');
            $unitIds = array_values(array_unique(array_map('intval', (array) ($line['unit_ids'] ?? []))));
            if ($p['track_serials'] && count($unitIds) !== $qty) {
                throw new InventoryException('Pick ' . $qty . ' serial number(s) for ' . $p['name'] . '.');
            }
            $subtotal += (float) $p['price'] * $qty;
            $prepared[] = ['product' => $p, 'qty' => $qty, 'unit_ids' => $p['track_serials'] ? $unitIds : []];
        }
        if (!$prepared) throw new InventoryException('Add at least one item to the sale.');
        $discount = max(0.0, min($discount, $subtotal));
        $total = $subtotal - $discount;

        $receipt = inventory_next_number('pos_sales', 'receipt_no', 'R', 5);
        $pdo->prepare(
            'INSERT INTO pos_sales (receipt_no, customer_name, customer_phone, customer_email, subtotal, discount, total, payment_method, amount_paid, notes, cashier_id)
             VALUES (:r, :n, :ph, :em, :sub, :disc, :tot, :m, :paid, :notes, :uid)'
        )->execute([
            'r' => $receipt, 'n' => $customer['name'] ?: null, 'ph' => $customer['phone'] ?: null, 'em' => $customer['email'] ?: null,
            'sub' => $subtotal, 'disc' => $discount, 'tot' => $total, 'm' => $method, 'paid' => $amountPaid,
            'notes' => $notes ?: null, 'uid' => admin_user()['id'] ?? null,
        ]);
        $saleId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO pos_sale_items (sale_id, product_id, product_name, unit_price, unit_cost, quantity) VALUES (:s, :p, :n, :price, :cost, :q)');
        foreach ($prepared as $l) {
            $p = $l['product'];
            $itemStmt->execute(['s' => $saleId, 'p' => $p['id'], 'n' => $p['name'], 'price' => $p['price'], 'cost' => $p['cost_price'], 'q' => $l['qty']]);
            inventory_move((int) $p['id'], -$l['qty'], 'pos_sale', 'pos_sale', $saleId, $receipt);
            foreach ($l['unit_ids'] as $unitId) {
                $u = $pdo->prepare("UPDATE product_units SET status = 'sold', sold_at = NOW(), sale_type = 'pos', sale_id = :sale,
                                     customer_name = :cn, customer_phone = :cp,
                                     warranty_until = IF(:wm > 0, DATE_ADD(CURDATE(), INTERVAL :wm2 MONTH), NULL)
                                   WHERE id = :id AND product_id = :pid AND status IN ('in_stock', 'returned')");
                $wm = (int) ($p['warranty_months'] ?? 0);
                $u->execute(['sale' => $saleId, 'cn' => $customer['name'] ?: null, 'cp' => $customer['phone'] ?: null, 'wm' => $wm, 'wm2' => $wm, 'id' => $unitId, 'pid' => $p['id']]);
                if ($u->rowCount() !== 1) throw new InventoryException('A selected serial for ' . $p['name'] . ' is no longer available.');
            }
        }
        return $saleId;
    });
}

/** Void a walk-in sale: stock goes back, its serial units return to stock. */
function pos_void_sale(int $saleId, string $reason): void
{
    inventory_tx(function (PDO $pdo) use ($saleId, $reason) {
        $s = $pdo->prepare('SELECT * FROM pos_sales WHERE id = :id FOR UPDATE');
        $s->execute(['id' => $saleId]);
        $sale = $s->fetch();
        if (!$sale || $sale['status'] !== 'completed') throw new InventoryException('This sale can’t be voided.');
        $items = $pdo->prepare('SELECT * FROM pos_sale_items WHERE sale_id = :id');
        $items->execute(['id' => $saleId]);
        foreach ($items->fetchAll() as $it) {
            if ($it['product_id']) inventory_move((int) $it['product_id'], (int) $it['quantity'], 'sale_voided', 'pos_sale', $saleId, $sale['receipt_no']);
        }
        $pdo->prepare("UPDATE product_units SET status = 'in_stock', sold_at = NULL, sale_type = NULL, sale_id = NULL, customer_name = NULL, customer_phone = NULL, warranty_until = NULL
                       WHERE sale_type = 'pos' AND sale_id = :id")->execute(['id' => $saleId]);
        $pdo->prepare("UPDATE pos_sales SET status = 'voided', void_reason = :r WHERE id = :id")->execute(['r' => $reason, 'id' => $saleId]);
    });
}

function pos_sale_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT s.*, u.name AS cashier_name FROM pos_sales s LEFT JOIN users u ON u.id = s.cashier_id WHERE s.id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function pos_sale_items(int $saleId): array
{
    $stmt = db()->prepare('SELECT * FROM pos_sale_items WHERE sale_id = :id ORDER BY id');
    $stmt->execute(['id' => $saleId]);
    return $stmt->fetchAll();
}

function pos_sale_units(int $saleId): array
{
    $stmt = db()->prepare("SELECT u.*, p.name AS product_name FROM product_units u JOIN products p ON p.id = u.product_id
                           WHERE u.sale_type = 'pos' AND u.sale_id = :id ORDER BY p.name, u.serial");
    $stmt->execute(['id' => $saleId]);
    return $stmt->fetchAll();
}

/** In-stock serial units grouped by product id, for the POS picker. */
function inventory_available_units(): array
{
    $out = [];
    foreach (db()->query("SELECT id, product_id, serial FROM product_units WHERE status IN ('in_stock', 'returned') ORDER BY serial") as $u) {
        $out[(int) $u['product_id']][] = ['id' => (int) $u['id'], 'serial' => $u['serial']];
    }
    return $out;
}

/** Warranty label for a sold unit. */
function warranty_status(?string $until): array
{
    if (!$until) return ['label' => 'No warranty', 'class' => 'badge-muted'];
    $days = (int) (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($until))->format('%r%a');
    if ($days < 0) return ['label' => 'Expired ' . (new DateTimeImmutable($until))->format('j M Y'), 'class' => 'badge-muted'];
    if ($days <= 30) return ['label' => 'Ends in ' . $days . ' days', 'class' => 'badge-warning'];
    return ['label' => 'Until ' . (new DateTimeImmutable($until))->format('j M Y'), 'class' => 'badge-success'];
}

/**
 * Link serial units to an online order line (the stock already left at
 * checkout). Warranty starts today. $unitIds must be in stock and belong to
 * the product; at most the line's quantity can be assigned in total.
 */
function order_assign_units(int $orderId, int $productId, array $unitIds): void
{
    inventory_tx(function (PDO $pdo) use ($orderId, $productId, $unitIds) {
        $o = $pdo->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
        $o->execute(['id' => $orderId]);
        $order = $o->fetch();
        if (!$order || $order['status'] === 'cancelled') throw new InventoryException('This order can’t take serials.');
        $q = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = :o AND product_id = :p');
        $q->execute(['o' => $orderId, 'p' => $productId]);
        $already = $pdo->prepare("SELECT COUNT(*) FROM product_units WHERE sale_type = 'online' AND sale_id = :o AND product_id = :p");
        $already->execute(['o' => $orderId, 'p' => $productId]);
        $unitIds = array_values(array_unique(array_map('intval', $unitIds)));
        if (!$unitIds) throw new InventoryException('Choose at least one serial.');
        if ((int) $already->fetchColumn() + count($unitIds) > (int) $q->fetchColumn()) throw new InventoryException('That’s more serials than items on the order.');
        $p = $pdo->prepare('SELECT warranty_months FROM products WHERE id = :id');
        $p->execute(['id' => $productId]);
        $wm = (int) $p->fetchColumn();
        foreach ($unitIds as $uid) {
            $u = $pdo->prepare("UPDATE product_units SET status = 'sold', sold_at = NOW(), sale_type = 'online', sale_id = :o,
                                 customer_name = :cn, customer_phone = :cp,
                                 warranty_until = IF(:wm > 0, DATE_ADD(CURDATE(), INTERVAL :wm2 MONTH), NULL)
                               WHERE id = :id AND product_id = :p AND status IN ('in_stock', 'returned')");
            $u->execute(['o' => $orderId, 'cn' => $order['customer_name'], 'cp' => $order['customer_phone'], 'wm' => $wm, 'wm2' => $wm, 'id' => $uid, 'p' => $productId]);
            if ($u->rowCount() !== 1) throw new InventoryException('A chosen serial is no longer available.');
        }
    });
}

/** Serial units already linked to an online order. */
function order_units(int $orderId): array
{
    $stmt = db()->prepare("SELECT * FROM product_units WHERE sale_type = 'online' AND sale_id = :id ORDER BY serial");
    $stmt->execute(['id' => $orderId]);
    return $stmt->fetchAll();
}
