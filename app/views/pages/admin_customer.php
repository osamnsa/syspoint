<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$customerId = (int) ($params[0] ?? 0);
$customer = crm_customer_by_id($customerId);
if (!$customer) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['action'] ?? '') === 'log') {
    $type = (string) ($_POST['type'] ?? 'note');
    $body = trim((string) ($_POST['body'] ?? ''));
    if (!isset(ACTIVITY_TYPES[$type]) || $body === '') {
        $error = 'Write what happened.';
    } else {
        crm_log($customerId, null, $type, $body);
        flash('success', ACTIVITY_TYPES[$type] . ' logged.');
        header('Location: ' . path('admin/customers/' . $customerId));
        exit;
    }
}

$stats = crm_customer_stats($customer);
$timeline = crm_timeline($customer);
$ids = implode(',', crm_customer_ids($customer));
$deals = db()->query("SELECT * FROM deals WHERE customer_id IN ($ids) ORDER BY FIELD(stage, 'negotiation', 'proposal', 'contacted', 'lead', 'won', 'lost'), id DESC")->fetchAll();
$docs = db()->query("SELECT * FROM crm_documents WHERE customer_id IN ($ids) ORDER BY id DESC LIMIT 12")->fetchAll();
$tasks = db()->query("SELECT t.*, u.name AS assignee FROM crm_tasks t LEFT JOIN users u ON u.id = t.assigned_to
                      WHERE t.customer_id = $customerId AND t.status = 'open' ORDER BY t.due_date IS NULL, t.due_date")->fetchAll();
$people = $customer['type'] === 'organisation'
    ? db()->query("SELECT id, name, email, phone FROM customers WHERE organisation_id = $customerId ORDER BY name")->fetchAll() : [];
$lifetime = $stats['online'] + $stats['walk_in'] + $stats['invoiced_paid'];
$wa = $customer['phone_digits'] ? 'https://wa.me/234' . ltrim(substr($customer['phone_digits'], -10), '0') : null;

$kindLabel = [
    'order' => ['Online order', 'bag'], 'pos' => ['Walk-in purchase', 'till'], 'booking' => ['Room booking', 'calendar'],
    'request' => ['Software request', 'inbox'], 'message' => ['Message', 'mail'], 'deal' => ['Deal opened', 'funnel'],
    'quote' => ['Quote', 'doc'], 'invoice' => ['Invoice', 'receipt'], 'payment' => ['Payment received', 'check'],
];
$link = fn($row) => match ($row['kind']) {
    'order' => path('admin/orders/' . (int) $row['id']),
    'pos' => path('admin/pos/sales/' . (int) $row['id']),
    'booking' => path('admin/bookings/' . (int) $row['id']),
    'request' => path('admin/software-requests/' . (int) $row['id']),
    'message' => path('admin/messages/' . (int) $row['id']),
    'deal' => path('admin/deals/' . (int) $row['id']),
    'quote', 'invoice' => path('admin/documents/' . (int) $row['id']),
    'payment' => path('admin/documents/' . (int) $row['ref']),
    default => null,
};

$pageTitle = $customer['name'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="crm-head">
    <span class="crm-avatar crm-avatar-lg<?= $customer['type'] === 'organisation' ? ' is-org' : '' ?>"><?= e(initials($customer['name'])) ?></span>
    <div class="crm-head-text">
        <p class="admin-kicker"><?= $customer['type'] === 'organisation' ? 'Organisation' : 'Customer' ?> · <?= e(CUSTOMER_SOURCES[$customer['source']]) ?></p>
        <h1><?= e($customer['name']) ?></h1>
        <p class="crm-head-meta">
            <?php if ($customer['organisation_name']): ?><a href="<?= path('admin/customers/' . (int) $customer['organisation_id']) ?>"><?= e($customer['organisation_name']) ?></a> · <?php endif; ?>
            Owner: <?= e($customer['owner_name'] ?? 'unassigned') ?>
            <?php foreach (array_filter(array_map('trim', explode(',', (string) $customer['tags']))) as $tag): ?> <span class="crm-tag"><?= e($tag) ?></span><?php endforeach; ?>
        </p>
        <div class="crm-contact">
            <?php if ($customer['phone']): ?><a class="crm-chip" href="tel:<?= e($customer['phone']) ?>">Call <?= e($customer['phone']) ?></a><?php endif; ?>
            <?php if ($wa): ?><a class="crm-chip" href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
            <?php if ($customer['email']): ?><a class="crm-chip" href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a><?php endif; ?>
        </div>
    </div>
    <div class="admin-header-actions">
        <a href="<?= path('admin/deals/new') ?>?customer=<?= $customerId ?>" class="btn btn-primary btn-sm">New Deal</a>
        <a href="<?= path('admin/documents/new') ?>?type=quote&amp;customer=<?= $customerId ?>" class="btn btn-outline btn-sm">New Quote</a>
        <a href="<?= path('admin/documents/new') ?>?type=invoice&amp;customer=<?= $customerId ?>" class="btn btn-outline btn-sm">New Invoice</a>
        <a href="<?= path('admin/customers/' . $customerId . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="dash-kpis">
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Lifetime value</p><p class="dash-kpi-value"><?= e(naira_short($lifetime)) ?></p><p class="dash-kpi-note">Online <?= e(naira_short($stats['online'])) ?> · walk-in <?= e(naira_short($stats['walk_in'])) ?> · invoices <?= e(naira_short($stats['invoiced_paid'])) ?></p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Outstanding</p><p class="dash-kpi-value<?= $stats['outstanding'] > 0 ? ' is-warn' : '' ?>"><?= e(naira_short($stats['outstanding'])) ?></p><p class="dash-kpi-note">Unpaid invoices</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Open deals</p><p class="dash-kpi-value"><?= e(naira_short($stats['open_deals'])) ?></p><p class="dash-kpi-note">In the pipeline</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Room bookings</p><p class="dash-kpi-value"><?= (int) $stats['bookings'] ?></p><p class="dash-kpi-note">Gaming Hub</p></div>
</div>

<div class="crm-layout">
    <div>
        <form method="post" class="admin-form crm-logger">
            <?= csrf_field() ?><input type="hidden" name="action" value="log">
            <div class="crm-logger-types">
                <?php foreach (ACTIVITY_TYPES as $k => $l): ?><label><input type="radio" name="type" value="<?= $k ?>" <?= $k === 'note' ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?>
            </div>
            <textarea name="body" rows="2" placeholder="Log a call, meeting or note — e.g. “Called about the school ERP, wants a demo next week”" required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Add to timeline</button>
        </form>

        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>Timeline</h2><p class="dash-card-sub">Everything with <?= e($customer['name']) ?><?= $customer['type'] === 'organisation' ? ' and its people' : '' ?></p></div></header>
            <?php if ($timeline): ?>
                <ol class="crm-timeline">
                    <?php foreach ($timeline as $row):
                        $isAct = $row['kind'] === 'activity';
                        [$label, $icon] = $isAct ? [($row['sub'] === 'stage' ? 'Deal update' : ACTIVITY_TYPES[$row['sub']] ?? 'Note'), $row['sub'] === 'stage' ? 'funnel' : 'quote'] : $kindLabel[$row['kind']];
                        $href = $isAct ? ($row['ref'] ? path('admin/deals/' . (int) $row['ref']) : null) : $link($row); ?>
                        <li class="crm-tl-<?= e($row['kind']) ?>">
                            <span class="crm-tl-icon"><?= admin_icon($icon) ?></span>
                            <div>
                                <p class="crm-tl-head"><strong><?= e($label) ?></strong>
                                    <?php if (!$isAct): ?><span class="crm-tl-status"><?= e(str_replace('_', ' ', (string) $row['sub'])) ?></span><?php endif; ?>
                                    <?php if ($row['amount'] !== null): ?><span class="crm-tl-amount"><?= format_naira((float) $row['amount']) ?></span><?php endif; ?>
                                </p>
                                <p class="crm-tl-text"><?= $href ? '<a href="' . $href . '">' . e((string) $row['text']) . '</a>' : nl2br(e((string) $row['text'])) ?></p>
                                <small><?= e((new DateTimeImmutable($row['at']))->format('j M Y, g:i A')) ?><?= $row['who'] ? ' · ' . e($row['who']) : '' ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?><p class="dash-empty">Nothing yet — log the first call or note above.</p><?php endif; ?>
        </section>
    </div>

    <aside class="crm-side">
        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>Tasks</h2></div></header>
            <?php foreach ($tasks as $t): $late = $t['due_date'] && $t['due_date'] < date('Y-m-d'); ?>
                <form method="post" action="<?= path('admin/tasks') ?>" class="crm-task">
                    <?= csrf_field() ?><input type="hidden" name="action" value="done"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><input type="hidden" name="return" value="admin/customers/<?= $customerId ?>">
                    <button type="submit" class="crm-check" title="Mark done" aria-label="Mark done"></button>
                    <span><?= e($t['title']) ?><small class="<?= $late ? 'is-late' : '' ?>"><?= $t['due_date'] ? ($late ? 'Overdue · ' : 'Due ') . e((new DateTimeImmutable($t['due_date']))->format('j M')) : 'No due date' ?><?= $t['assignee'] ? ' · ' . e($t['assignee']) : '' ?></small></span>
                </form>
            <?php endforeach; ?>
            <form method="post" action="<?= path('admin/tasks') ?>" class="crm-task-add">
                <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="customer_id" value="<?= $customerId ?>"><input type="hidden" name="return" value="admin/customers/<?= $customerId ?>">
                <input type="text" name="title" placeholder="Add a follow-up…" required aria-label="Task">
                <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" aria-label="Due date">
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </form>
        </section>

        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>Deals</h2></div><a class="dash-link" href="<?= path('admin/deals/new') ?>?customer=<?= $customerId ?>">New →</a></header>
            <?php foreach ($deals as $d): ?>
                <a class="crm-mini" href="<?= path('admin/deals/' . (int) $d['id']) ?>"><span><?= e($d['title']) ?><small><?= e(DEAL_STAGES[$d['stage']]['label']) ?></small></span><strong><?= e(naira_short((float) $d['value'])) ?></strong></a>
            <?php endforeach; ?>
            <?php if (!$deals): ?><p class="dash-empty">No deals yet.</p><?php endif; ?>
        </section>

        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>Quotes &amp; invoices</h2></div></header>
            <?php foreach ($docs as $d): [$cls, $lab] = doc_badge($d); ?>
                <a class="crm-mini" href="<?= path('admin/documents/' . (int) $d['id']) ?>"><span><?= e($d['number']) ?><small><span class="badge <?= $cls ?>"><?= e($lab) ?></span></small></span><strong><?= e(naira_short((float) $d['total'])) ?></strong></a>
            <?php endforeach; ?>
            <?php if (!$docs): ?><p class="dash-empty">None yet.</p><?php endif; ?>
        </section>

        <?php if ($customer['type'] === 'organisation'): ?>
        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>People</h2></div><a class="dash-link" href="<?= path('admin/customers/new') ?>?organisation=<?= $customerId ?>">Add →</a></header>
            <?php foreach ($people as $p): ?>
                <a class="crm-mini" href="<?= path('admin/customers/' . (int) $p['id']) ?>"><span><?= e($p['name']) ?><small><?= e($p['email'] ?: ($p['phone'] ?: '')) ?></small></span></a>
            <?php endforeach; ?>
            <?php if (!$people): ?><p class="dash-empty">No contacts linked yet.</p><?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($customer['notes'] || $customer['address']): ?>
        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>Details</h2></div></header>
            <?php if ($customer['address']): ?><p class="crm-note"><?= nl2br(e($customer['address'])) ?></p><?php endif; ?>
            <?php if ($customer['notes']): ?><p class="crm-note"><?= nl2br(e($customer['notes'])) ?></p><?php endif; ?>
        </section>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
