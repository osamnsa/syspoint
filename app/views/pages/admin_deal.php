<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$dealId = (int) ($params[0] ?? 0);
$deal = deal_by_id($dealId);
if (!$deal) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'stage') {
        $stage = (string) ($_POST['stage'] ?? '');
        $reason = trim((string) ($_POST['lost_reason'] ?? ''));
        if ($stage === 'lost' && $reason === '') {
            $error = 'Say briefly why it was lost — it helps spot patterns.';
        } else {
            deal_set_stage($deal, $stage, $reason ?: null);
            flash('success', 'Moved to ' . DEAL_STAGES[$stage]['label'] . '.');
        }
    } elseif ($action === 'log') {
        $type = (string) ($_POST['type'] ?? 'note');
        $body = trim((string) ($_POST['body'] ?? ''));
        if (isset(ACTIVITY_TYPES[$type]) && $body !== '') {
            crm_log((int) $deal['customer_id'], $dealId, $type, $body);
            flash('success', 'Logged.');
        }
    } elseif ($action === 'delete' && admin_is_admin()) {
        db()->prepare('DELETE FROM deals WHERE id = :id')->execute(['id' => $dealId]);
        flash('success', 'Deal deleted.');
        header('Location: ' . path('admin/deals'));
        exit;
    }
    if (!$error) {
        header('Location: ' . path('admin/deals/' . $dealId));
        exit;
    }
}

$acts = db()->prepare('SELECT a.*, u.name AS user_name FROM crm_activities a LEFT JOIN users u ON u.id = a.user_id WHERE a.deal_id = :id ORDER BY a.id DESC');
$acts->execute(['id' => $dealId]);
$acts = $acts->fetchAll();
$tasks = db()->query("SELECT t.*, u.name AS assignee FROM crm_tasks t LEFT JOIN users u ON u.id = t.assigned_to WHERE t.deal_id = $dealId AND t.status = 'open' ORDER BY t.due_date IS NULL, t.due_date")->fetchAll();
$docs = db()->query("SELECT * FROM crm_documents WHERE deal_id = $dealId ORDER BY id DESC")->fetchAll();
$request = null;
if ($deal['request_id']) {
    $r = db()->prepare('SELECT * FROM software_requests WHERE id = :id');
    $r->execute(['id' => $deal['request_id']]);
    $request = $r->fetch() ?: null;
}

$pageTitle = $deal['title'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div>
        <p class="admin-kicker">Deal · <?= e(DEAL_SERVICES[$deal['service']]) ?></p>
        <h1><?= e($deal['title']) ?></h1>
        <p class="crm-head-meta"><a href="<?= path('admin/customers/' . (int) $deal['customer_id']) ?>"><?= e($deal['customer_name']) ?></a> · Owner: <?= e($deal['owner_name'] ?? 'unassigned') ?><?= $deal['expected_close'] ? ' · Expected to close ' . e((new DateTimeImmutable($deal['expected_close']))->format('j M Y')) : '' ?></p>
    </div>
    <div class="admin-header-actions">
        <a href="<?= path('admin/documents/new') ?>?type=quote&amp;customer=<?= (int) $deal['customer_id'] ?>&amp;deal=<?= $dealId ?>" class="btn btn-primary btn-sm">Create Quote</a>
        <a href="<?= path('admin/documents/new') ?>?type=invoice&amp;customer=<?= (int) $deal['customer_id'] ?>&amp;deal=<?= $dealId ?>" class="btn btn-outline btn-sm">Create Invoice</a>
        <a href="<?= path('admin/deals/' . $dealId . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="crm-stepper" aria-label="Deal stage">
    <?= csrf_field() ?><input type="hidden" name="action" value="stage">
    <?php foreach (DEAL_STAGES as $k => $s):
        $order = array_search($k, array_keys(DEAL_STAGES), true);
        $cur = array_search($deal['stage'], array_keys(DEAL_STAGES), true);
        $cls = $k === $deal['stage'] ? 'is-current' : (in_array($deal['stage'], ['won'], true) || ($order < $cur && !in_array($k, ['won', 'lost'], true) && $deal['stage'] !== 'lost') ? 'is-past' : ''); ?>
        <button type="submit" name="stage" value="<?= $k ?>" class="crm-step crm-step-<?= $k ?> <?= $cls ?>" <?= $k === 'lost' ? 'formnovalidate data-needs-reason' : '' ?>><?= e($s['label']) ?></button>
    <?php endforeach; ?>
    <input type="text" name="lost_reason" class="crm-lost-reason" placeholder="Reason, if lost (e.g. price, went with another vendor)" value="<?= e((string) $deal['lost_reason']) ?>" aria-label="Reason lost">
</form>

<div class="dash-kpis">
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Value</p><p class="dash-kpi-value"><?= e(naira_short((float) $deal['value'])) ?></p><p class="dash-kpi-note"><?= format_naira((float) $deal['value']) ?></p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Likelihood</p><p class="dash-kpi-value"><?= DEAL_STAGES[$deal['stage']]['chance'] ?>%</p><p class="dash-kpi-note">Forecast <?= e(naira_short($deal['value'] * DEAL_STAGES[$deal['stage']]['chance'] / 100)) ?></p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Stage</p><p class="dash-kpi-value dash-kpi-value-sm"><?= e(DEAL_STAGES[$deal['stage']]['label']) ?></p><p class="dash-kpi-note"><?= $deal['stage'] === 'lost' && $deal['lost_reason'] ? e($deal['lost_reason']) : 'Opened ' . e((new DateTimeImmutable($deal['created_at']))->format('j M Y')) ?></p></div>
</div>

<div class="crm-layout">
    <div>
        <form method="post" class="admin-form crm-logger">
            <?= csrf_field() ?><input type="hidden" name="action" value="log">
            <div class="crm-logger-types"><?php foreach (ACTIVITY_TYPES as $k => $l): ?><label><input type="radio" name="type" value="<?= $k ?>" <?= $k === 'note' ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?></div>
            <textarea name="body" rows="2" placeholder="What happened on this deal?" required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Add to timeline</button>
        </form>
        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>Deal history</h2></div></header>
            <ol class="crm-timeline">
                <?php foreach ($acts as $a): ?>
                    <li><span class="crm-tl-icon"><?= admin_icon($a['type'] === 'stage' ? 'funnel' : 'quote') ?></span><div>
                        <p class="crm-tl-head"><strong><?= e($a['type'] === 'stage' ? 'Deal update' : ACTIVITY_TYPES[$a['type']]) ?></strong></p>
                        <p class="crm-tl-text"><?= nl2br(e($a['body'])) ?></p>
                        <small><?= e((new DateTimeImmutable($a['created_at']))->format('j M Y, g:i A')) ?><?= $a['user_name'] ? ' · ' . e($a['user_name']) : '' ?></small>
                    </div></li>
                <?php endforeach; ?>
                <?php if ($request): ?>
                    <li><span class="crm-tl-icon"><?= admin_icon('inbox') ?></span><div>
                        <p class="crm-tl-head"><strong>From a Software Clinic request</strong></p>
                        <p class="crm-tl-text"><a href="<?= path('admin/software-requests/' . (int) $request['id']) ?>"><?= e(mb_strimwidth($request['description'], 0, 220, '…')) ?></a></p>
                        <small><?= e((new DateTimeImmutable($request['created_at']))->format('j M Y')) ?></small>
                    </div></li>
                <?php endif; ?>
            </ol>
        </section>
    </div>
    <aside class="crm-side">
        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>Next steps</h2></div></header>
            <?php foreach ($tasks as $t): $late = $t['due_date'] && $t['due_date'] < date('Y-m-d'); ?>
                <form method="post" action="<?= path('admin/tasks') ?>" class="crm-task">
                    <?= csrf_field() ?><input type="hidden" name="action" value="done"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><input type="hidden" name="return" value="admin/deals/<?= $dealId ?>">
                    <button type="submit" class="crm-check" aria-label="Mark done"></button>
                    <span><?= e($t['title']) ?><small class="<?= $late ? 'is-late' : '' ?>"><?= $t['due_date'] ? e((new DateTimeImmutable($t['due_date']))->format('j M')) : 'No date' ?><?= $t['assignee'] ? ' · ' . e($t['assignee']) : '' ?></small></span>
                </form>
            <?php endforeach; ?>
            <form method="post" action="<?= path('admin/tasks') ?>" class="crm-task-add">
                <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="deal_id" value="<?= $dealId ?>"><input type="hidden" name="customer_id" value="<?= (int) $deal['customer_id'] ?>"><input type="hidden" name="return" value="admin/deals/<?= $dealId ?>">
                <input type="text" name="title" placeholder="e.g. Send proposal" required aria-label="Task">
                <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" aria-label="Due date">
                <button type="submit" class="btn btn-primary btn-sm">Add</button>
            </form>
        </section>
        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>Quotes &amp; invoices</h2></div></header>
            <?php foreach ($docs as $d): [$cls, $lab] = doc_badge($d); ?>
                <a class="crm-mini" href="<?= path('admin/documents/' . (int) $d['id']) ?>"><span><?= e($d['number']) ?><small><span class="badge <?= $cls ?>"><?= e($lab) ?></span></small></span><strong><?= e(naira_short((float) $d['total'])) ?></strong></a>
            <?php endforeach; ?>
            <?php if (!$docs): ?><p class="dash-empty">None yet.</p><?php endif; ?>
        </section>
        <?php if (admin_is_admin()): ?>
        <details class="admin-danger-zone"><summary>Delete deal</summary>
            <form method="post" onsubmit="return confirm('Delete this deal and its history?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark">Delete Deal</button></form>
        </details>
        <?php endif; ?>
    </aside>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
