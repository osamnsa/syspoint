<?php
/**
 * CRM: customers (people and organisations), the deals pipeline, the
 * activity timeline, tasks, and quotes & invoices with payments.
 *
 * Every public form that captures a person (checkout, room booking,
 * Software Clinic request, contact message) and every walk-in sale with
 * customer details links its row to a customer via crm_customer_for():
 * matched by email, else by phone number, else created. Linking never
 * blocks the form itself — failures are logged and the visitor carries on.
 */

declare(strict_types=1);

const DEAL_STAGES = [
    'lead' => ['label' => 'Lead', 'chance' => 10],
    'contacted' => ['label' => 'Contacted', 'chance' => 25],
    'proposal' => ['label' => 'Proposal sent', 'chance' => 50],
    'negotiation' => ['label' => 'Negotiation', 'chance' => 75],
    'won' => ['label' => 'Won', 'chance' => 100],
    'lost' => ['label' => 'Lost', 'chance' => 0],
];
const DEAL_OPEN_STAGES = ['lead', 'contacted', 'proposal', 'negotiation'];
const DEAL_SERVICES = ['software' => 'Software / app', 'consulting' => 'IT consulting', 'hardware' => 'Hardware supply', 'training' => 'Training', 'gaming' => 'Gaming / events', 'other' => 'Other'];
const CUSTOMER_SOURCES = ['website' => 'Website', 'walk_in' => 'Walk-in', 'referral' => 'Referral', 'social' => 'Social media', 'event' => 'Event', 'phone' => 'Phone call', 'other' => 'Other'];
const ACTIVITY_TYPES = ['note' => 'Note', 'call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting', 'whatsapp' => 'WhatsApp'];
const DOC_STATUSES = [
    'quote' => ['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'declined' => 'Declined', 'expired' => 'Expired'],
    'invoice' => ['draft' => 'Draft', 'sent' => 'Sent', 'part_paid' => 'Part paid', 'paid' => 'Paid', 'void' => 'Void'],
];
const PAYMENT_KINDS = ['transfer' => 'Bank transfer', 'cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque', 'other' => 'Other'];
const VAT_RATE = 7.5;

/** Last 10 digits of a phone number, so +234 803…, 234803… and 0803… match. */
function crm_phone_digits(?string $phone): ?string
{
    $d = preg_replace('/\D+/', '', (string) $phone);
    return strlen($d) >= 7 ? substr($d, -10) : null;
}

/**
 * Find or create the customer for a person. Matches email (case-insensitive)
 * first, then phone. Fills in a missing email/phone on a match. Returns the
 * customer id, or null if there's nothing to match on.
 */
function crm_customer_for(?string $name, ?string $email, ?string $phone, string $source = 'website', ?string $organisation = null): ?int
{
    $name = trim((string) $name);
    $email = strtolower(trim((string) $email));
    $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    $digits = crm_phone_digits($phone);
    if ($email === '' && $digits === null) return null;

    $id = null;
    if ($email !== '') {
        $s = db()->prepare('SELECT id FROM customers WHERE email = :e ORDER BY id LIMIT 1');
        $s->execute(['e' => $email]);
        $id = $s->fetchColumn() ?: null;
    }
    if (!$id && $digits !== null) {
        $s = db()->prepare('SELECT id FROM customers WHERE phone_digits = :d ORDER BY id LIMIT 1');
        $s->execute(['d' => $digits]);
        $id = $s->fetchColumn() ?: null;
    }

    if ($id) {
        db()->prepare('UPDATE customers SET email = COALESCE(email, :e), phone = COALESCE(phone, :p),
                       phone_digits = COALESCE(phone_digits, :d), last_activity_at = NOW() WHERE id = :id')
            ->execute(['e' => $email ?: null, 'p' => trim((string) $phone) ?: null, 'd' => $digits, 'id' => $id]);
        return (int) $id;
    }

    $orgId = null;
    $organisation = trim((string) $organisation);
    if ($organisation !== '' && strcasecmp($organisation, $name) !== 0) {
        $s = db()->prepare("SELECT id FROM customers WHERE type = 'organisation' AND name = :n LIMIT 1");
        $s->execute(['n' => $organisation]);
        $orgId = $s->fetchColumn() ?: null;
        if (!$orgId) {
            db()->prepare("INSERT INTO customers (type, name, source, last_activity_at) VALUES ('organisation', :n, :s, NOW())")
                ->execute(['n' => $organisation, 's' => $source]);
            $orgId = (int) db()->lastInsertId();
        }
    }
    db()->prepare('INSERT INTO customers (type, name, organisation_id, email, phone, phone_digits, source, last_activity_at)
                   VALUES (\'person\', :n, :o, :e, :p, :d, :s, NOW())')
        ->execute(['n' => $name !== '' ? $name : ($email ?: (string) $phone), 'o' => $orgId, 'e' => $email ?: null,
                   'p' => trim((string) $phone) ?: null, 'd' => $digits, 's' => $source]);
    return (int) db()->lastInsertId();
}

/** Link a captured row to its customer. Never throws (public forms must not fail because of the CRM). */
function crm_link(string $table, int $rowId, ?string $name, ?string $email, ?string $phone, string $source = 'website', ?string $organisation = null): void
{
    if (!in_array($table, ['orders', 'room_bookings', 'software_requests', 'contact_messages', 'pos_sales'], true)) return;
    try {
        $cid = crm_customer_for($name, $email, $phone, $source, $organisation);
        if ($cid) db()->prepare("UPDATE $table SET customer_id = :c WHERE id = :id")->execute(['c' => $cid, 'id' => $rowId]);
    } catch (Throwable $e) {
        error_log('crm_link failed for ' . $table . '#' . $rowId . ': ' . $e->getMessage());
    }
}

/** Link every existing row that has contact details but no customer yet. Returns how many were linked. */
function crm_sync_existing(): int
{
    $n = 0;
    $sources = [
        'orders' => ['SELECT id, customer_name AS name, customer_email AS email, customer_phone AS phone, NULL AS org FROM orders WHERE customer_id IS NULL', 'website'],
        'room_bookings' => ['SELECT id, customer_name AS name, customer_email AS email, customer_phone AS phone, NULL AS org FROM room_bookings WHERE customer_id IS NULL', 'website'],
        'software_requests' => ['SELECT id, contact_name AS name, email, phone, business_name AS org FROM software_requests WHERE customer_id IS NULL', 'website'],
        'contact_messages' => ['SELECT id, name, email, NULL AS phone, NULL AS org FROM contact_messages WHERE customer_id IS NULL', 'website'],
        'pos_sales' => ['SELECT id, customer_name AS name, customer_email AS email, customer_phone AS phone, NULL AS org FROM pos_sales WHERE customer_id IS NULL', 'walk_in'],
    ];
    foreach ($sources as $table => [$sql, $source]) {
        foreach (db()->query($sql . ' ORDER BY id')->fetchAll() as $r) {
            $cid = crm_customer_for($r['name'], $r['email'], $r['phone'], $source, $r['org']);
            if ($cid) {
                db()->prepare("UPDATE $table SET customer_id = :c WHERE id = :id")->execute(['c' => $cid, 'id' => $r['id']]);
                $n++;
            }
        }
    }
    return $n;
}

function crm_customer_by_id(int $id): ?array
{
    $s = db()->prepare('SELECT c.*, o.name AS organisation_name, u.name AS owner_name FROM customers c
                        LEFT JOIN customers o ON o.id = c.organisation_id LEFT JOIN users u ON u.id = c.owner_id WHERE c.id = :id');
    $s->execute(['id' => $id]);
    return $s->fetch() ?: null;
}

/** IDs of a customer plus, for an organisation, its people (so an org's history includes its staff). */
function crm_customer_ids(array $customer): array
{
    $ids = [(int) $customer['id']];
    if ($customer['type'] === 'organisation') {
        $s = db()->prepare('SELECT id FROM customers WHERE organisation_id = :id');
        $s->execute(['id' => $customer['id']]);
        $ids = array_merge($ids, array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN)));
    }
    return $ids;
}

/** Lifetime numbers for a customer. */
function crm_customer_stats(array $customer): array
{
    $in = implode(',', crm_customer_ids($customer));
    $q = fn($sql) => (float) db()->query($sql)->fetchColumn();
    return [
        'online' => $q("SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE payment_status = 'paid' AND customer_id IN ($in)"),
        'walk_in' => $q("SELECT COALESCE(SUM(total), 0) FROM pos_sales WHERE status = 'completed' AND customer_id IN ($in)"),
        'invoiced_paid' => $q("SELECT COALESCE(SUM(amount_paid), 0) FROM crm_documents WHERE type = 'invoice' AND status <> 'void' AND customer_id IN ($in)"),
        'outstanding' => $q("SELECT COALESCE(SUM(total - amount_paid), 0) FROM crm_documents WHERE type = 'invoice' AND status IN ('sent', 'part_paid') AND customer_id IN ($in)"),
        'bookings' => $q("SELECT COUNT(*) FROM room_bookings WHERE status <> 'cancelled' AND customer_id IN ($in)"),
        'open_deals' => $q("SELECT COALESCE(SUM(value), 0) FROM deals WHERE stage IN ('lead', 'contacted', 'proposal', 'negotiation') AND customer_id IN ($in)"),
    ];
}

/**
 * Everything that has happened with a customer, newest first: notes and
 * calls, orders, walk-in purchases, bookings, requests and messages. Deals,
 * quotes, invoices and payments appear through the activity entries they log
 * (linked to the deal), so nothing shows twice.
 */
function crm_timeline(array $customer, int $limit = 80): array
{
    $in = implode(',', crm_customer_ids($customer));
    $sql = "
        SELECT 'activity' AS kind, a.id, a.created_at AS at, a.type AS sub, a.body AS text, NULL AS amount, u.name AS who, a.deal_id AS ref
          FROM crm_activities a LEFT JOIN users u ON u.id = a.user_id WHERE a.customer_id IN ($in)
        UNION ALL SELECT 'order', id, created_at, status, order_ref, subtotal, NULL, NULL FROM orders WHERE customer_id IN ($in)
        UNION ALL SELECT 'pos', id, created_at, status, receipt_no, total, NULL, NULL FROM pos_sales WHERE customer_id IN ($in)
        UNION ALL SELECT 'booking', b.id, b.created_at, b.status, CONCAT(r.name, ' · ', DATE_FORMAT(b.booking_date, '%e %b %Y'), ' ', TIME_FORMAT(b.start_time, '%H:%i'), '–', TIME_FORMAT(b.end_time, '%H:%i')), NULL, NULL, NULL
          FROM room_bookings b JOIN gaming_rooms r ON r.id = b.room_id WHERE b.customer_id IN ($in)
        UNION ALL SELECT 'request', id, created_at, status, LEFT(description, 200), NULL, NULL, NULL FROM software_requests WHERE customer_id IN ($in)
        UNION ALL SELECT 'message', id, created_at, IF(read_at IS NULL, 'unread', 'read'), CONCAT(COALESCE(subject, ''), ' — ', LEFT(message, 180)), NULL, NULL, NULL FROM contact_messages WHERE customer_id IN ($in)
        UNION ALL SELECT 'enrolment', e.id, e.created_at, e.status, COALESCE(t.title, e.track, IF(e.kind = 'internship', 'Internship', 'Course')), e.fee, NULL, NULL
          FROM enrolments e LEFT JOIN training_courses t ON t.id = e.course_id WHERE e.customer_id IN ($in)
        ORDER BY at DESC, id DESC LIMIT $limit";
    return db()->query($sql)->fetchAll();
}

/** Add a note / call / email / meeting / WhatsApp to the timeline. */
function crm_log(?int $customerId, ?int $dealId, string $type, string $body): void
{
    db()->prepare('INSERT INTO crm_activities (customer_id, deal_id, type, body, user_id) VALUES (:c, :d, :t, :b, :u)')
        ->execute(['c' => $customerId, 'd' => $dealId, 't' => $type, 'b' => $body, 'u' => admin_user()['id'] ?? null]);
    if ($customerId) db()->prepare('UPDATE customers SET last_activity_at = NOW() WHERE id = :id')->execute(['id' => $customerId]);
}

function crm_staff(): array
{
    return db()->query('SELECT id, name FROM users WHERE is_active = 1 ORDER BY name')->fetchAll();
}

// --- Deals ------------------------------------------------------------------

function deal_by_id(int $id): ?array
{
    $s = db()->prepare('SELECT d.*, c.name AS customer_name, c.type AS customer_type, u.name AS owner_name FROM deals d
                        JOIN customers c ON c.id = d.customer_id LEFT JOIN users u ON u.id = d.owner_id WHERE d.id = :id');
    $s->execute(['id' => $id]);
    return $s->fetch() ?: null;
}

/** Move a deal to another stage (logs it; won/lost stamp closed_at). */
function deal_set_stage(array $deal, string $stage, ?string $lostReason = null): void
{
    if (!isset(DEAL_STAGES[$stage]) || $deal['stage'] === $stage) return;
    $closed = in_array($stage, ['won', 'lost'], true);
    db()->prepare('UPDATE deals SET stage = :s, lost_reason = :r, closed_at = ' . ($closed ? 'NOW()' : 'NULL') . ' WHERE id = :id')
        ->execute(['s' => $stage, 'r' => $stage === 'lost' ? ($lostReason ?: null) : null, 'id' => $deal['id']]);
    crm_log((int) $deal['customer_id'], (int) $deal['id'], 'stage',
        $deal['title'] . ': ' . DEAL_STAGES[$deal['stage']]['label'] . ' → ' . DEAL_STAGES[$stage]['label'] . ($stage === 'lost' && $lostReason ? ' (' . $lostReason . ')' : ''));
}

// --- Quotes & invoices --------------------------------------------------------

function doc_by_id(int $id): ?array
{
    $s = db()->prepare('SELECT d.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address,
                               o.name AS organisation_name, dl.title AS deal_title, u.name AS created_by_name
                        FROM crm_documents d JOIN customers c ON c.id = d.customer_id LEFT JOIN customers o ON o.id = c.organisation_id
                        LEFT JOIN deals dl ON dl.id = d.deal_id LEFT JOIN users u ON u.id = d.created_by WHERE d.id = :id');
    $s->execute(['id' => $id]);
    return $s->fetch() ?: null;
}

function doc_items(int $docId): array
{
    $s = db()->prepare('SELECT * FROM crm_document_items WHERE document_id = :id ORDER BY sort_order, id');
    $s->execute(['id' => $docId]);
    return $s->fetchAll();
}

function doc_payments(int $docId): array
{
    $s = db()->prepare('SELECT p.*, u.name AS user_name FROM crm_payments p LEFT JOIN users u ON u.id = p.user_id WHERE p.document_id = :id ORDER BY p.paid_on, p.id');
    $s->execute(['id' => $docId]);
    return $s->fetchAll();
}

/** Totals from lines: [['qty' => , 'price' => ], ...], discount, VAT on/off. */
function doc_totals(array $lines, float $discount, float $taxRate): array
{
    $subtotal = 0.0;
    foreach ($lines as $l) $subtotal += round((float) $l['qty'] * (float) $l['price'], 2);
    $discount = max(0.0, min($discount, $subtotal));
    $tax = round(($subtotal - $discount) * $taxRate / 100, 2);
    return ['subtotal' => $subtotal, 'discount' => $discount, 'tax' => $tax, 'total' => $subtotal - $discount + $tax];
}

/**
 * Create or update a quote/invoice with its lines. $data: customer_id,
 * deal_id, issue_date, due_date, discount, tax_rate, notes, terms.
 * Returns the document id.
 */
function doc_save(string $type, ?array $existing, array $data, array $lines, ?int $sourceId = null): int
{
    return inventory_tx(function (PDO $pdo) use ($type, $existing, $data, $lines, $sourceId) {
        $t = doc_totals($lines, (float) $data['discount'], (float) $data['tax_rate']);
        $params = [
            'c' => $data['customer_id'], 'd' => $data['deal_id'] ?: null, 'i' => $data['issue_date'], 'due' => $data['due_date'] ?: null,
            'sub' => $t['subtotal'], 'disc' => $t['discount'], 'rate' => $data['tax_rate'], 'tax' => $t['tax'], 'tot' => $t['total'],
            'notes' => $data['notes'] ?: null, 'terms' => $data['terms'] ?: null,
        ];
        if ($existing) {
            $pdo->prepare('UPDATE crm_documents SET customer_id = :c, deal_id = :d, issue_date = :i, due_date = :due, subtotal = :sub, discount = :disc,
                           tax_rate = :rate, tax = :tax, total = :tot, notes = :notes, terms = :terms WHERE id = :id')->execute($params + ['id' => $existing['id']]);
            $pdo->prepare('DELETE FROM crm_document_items WHERE document_id = :id')->execute(['id' => $existing['id']]);
            $id = (int) $existing['id'];
        } else {
            $number = inventory_next_number('crm_documents', 'number', $type === 'quote' ? 'Q' : 'INV', 4);
            $pdo->prepare('INSERT INTO crm_documents (type, number, customer_id, deal_id, issue_date, due_date, subtotal, discount, tax_rate, tax, total, notes, terms, source_id, created_by)
                           VALUES (:type, :num, :c, :d, :i, :due, :sub, :disc, :rate, :tax, :tot, :notes, :terms, :src, :u)')
                ->execute($params + ['type' => $type, 'num' => $number, 'src' => $sourceId, 'u' => admin_user()['id'] ?? null]);
            $id = (int) $pdo->lastInsertId();
            crm_log((int) $data['customer_id'], $data['deal_id'] ? (int) $data['deal_id'] : null, 'note', ($type === 'quote' ? 'Quote ' : 'Invoice ') . $number . ' created · ' . format_naira($t['total']));
        }
        $ins = $pdo->prepare('INSERT INTO crm_document_items (document_id, description, quantity, unit_price, sort_order) VALUES (:d, :desc, :q, :p, :s)');
        foreach (array_values($lines) as $i => $l) {
            $ins->execute(['d' => $id, 'desc' => $l['description'], 'q' => $l['qty'], 'p' => $l['price'], 's' => $i]);
        }
        return $id;
    });
}

/** Record a payment against an invoice and move it to part paid / paid. */
function doc_record_payment(array $doc, float $amount, string $method, string $paidOn, ?string $reference): void
{
    if ($doc['type'] !== 'invoice' || in_array($doc['status'], ['void', 'paid'], true)) throw new InventoryException('This invoice can’t take payments.');
    $due = round((float) $doc['total'] - (float) $doc['amount_paid'], 2);
    if ($amount <= 0 || $amount > $due + 0.001) throw new InventoryException('Enter an amount up to ' . format_naira($due) . '.');
    if (!isset(PAYMENT_KINDS[$method])) throw new InventoryException('Choose how it was paid.');
    inventory_tx(function (PDO $pdo) use ($doc, $amount, $method, $paidOn, $reference, $due) {
        $pdo->prepare('INSERT INTO crm_payments (document_id, amount, method, paid_on, reference, user_id) VALUES (:d, :a, :m, :p, :r, :u)')
            ->execute(['d' => $doc['id'], 'a' => $amount, 'm' => $method, 'p' => $paidOn, 'r' => $reference ?: null, 'u' => admin_user()['id'] ?? null]);
        $status = $amount >= $due - 0.001 ? 'paid' : 'part_paid';
        $pdo->prepare('UPDATE crm_documents SET amount_paid = amount_paid + :a, status = :s WHERE id = :id')->execute(['a' => $amount, 's' => $status, 'id' => $doc['id']]);
        crm_log((int) $doc['customer_id'], $doc['deal_id'] ? (int) $doc['deal_id'] : null, 'note', 'Payment of ' . format_naira($amount) . ' received on ' . $doc['number'] . ($status === 'paid' ? ' — paid in full' : ''));
    });
}

/** Turn an accepted (or any non-declined) quote into a draft invoice. Returns the invoice id. */
function doc_convert_to_invoice(array $quote): int
{
    $existing = db()->prepare("SELECT id FROM crm_documents WHERE type = 'invoice' AND source_id = :id LIMIT 1");
    $existing->execute(['id' => $quote['id']]);
    if ($id = $existing->fetchColumn()) return (int) $id;
    $lines = array_map(fn($i) => ['description' => $i['description'], 'qty' => $i['quantity'], 'price' => $i['unit_price']], doc_items((int) $quote['id']));
    $id = doc_save('invoice', null, [
        'customer_id' => $quote['customer_id'], 'deal_id' => $quote['deal_id'], 'issue_date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+14 days')), 'discount' => $quote['discount'], 'tax_rate' => $quote['tax_rate'],
        'notes' => $quote['notes'], 'terms' => doc_default_terms('invoice'),
    ], $lines, (int) $quote['id']);
    if ($quote['status'] !== 'accepted') db()->prepare("UPDATE crm_documents SET status = 'accepted' WHERE id = :id")->execute(['id' => $quote['id']]);
    return $id;
}

/** Starting terms for a new quote or invoice — editable in Website → Settings. */
function doc_default_terms(string $type): string
{
    return site($type === 'quote' ? 'company.quote_terms' : 'company.invoice_terms');
}

/** Badge class and label, including "Overdue" for unpaid invoices past their due date. */
function doc_badge(array $doc): array
{
    $overdue = $doc['type'] === 'invoice' && in_array($doc['status'], ['sent', 'part_paid'], true) && $doc['due_date'] && $doc['due_date'] < date('Y-m-d');
    if ($overdue) return ['badge-danger', 'Overdue'];
    $label = DOC_STATUSES[$doc['type']][$doc['status']] ?? $doc['status'];
    $class = match ($doc['status']) {
        'paid', 'accepted' => 'badge-success',
        'sent', 'part_paid' => 'badge-warning',
        'declined', 'void', 'expired' => 'badge-danger',
        default => 'badge-muted',
    };
    return [$class, $label];
}

/** Letterhead for quotes, invoices and receipts — edited in Website → Settings. */
function company_details(): array
{
    return [
        'name' => site('company.name'),
        'address' => site('company.address'),
        'phone' => site('company.phone'),
        'email' => site('company.email'),
        'bank' => site('company.bank_details'),
    ];
}
