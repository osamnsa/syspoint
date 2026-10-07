<?php
declare(strict_types=1);

$adminUser = require_admin();

$range = (string) ($_GET['range'] ?? '30d');
$period = dashboard_period($range);
$range = $period['range'];
$labels = array_column($period['buckets'], 'label');
$from = $period['start']->format('Y-m-d 00:00:00');
$to = $period['end']->format('Y-m-d 23:59:59');
$pFrom = $period['prev_start']->format('Y-m-d 00:00:00');
$pTo = $period['prev_end']->format('Y-m-d 23:59:59');

// Pipeline by stage (open deals, all time) — the funnel
$stages = [];
foreach (DEAL_OPEN_STAGES as $k) $stages[$k] = ['count' => 0, 'value' => 0.0];
foreach (db()->query("SELECT stage, COUNT(*) n, COALESCE(SUM(value), 0) v FROM deals WHERE stage IN ('lead', 'contacted', 'proposal', 'negotiation') GROUP BY stage") as $r) {
    $stages[$r['stage']] = ['count' => (int) $r['n'], 'value' => (float) $r['v']];
}
$pipeline = array_sum(array_column($stages, 'value'));
$weighted = 0.0;
foreach ($stages as $k => $st) $weighted += $st['value'] * DEAL_STAGES[$k]['chance'] / 100;
$funnelMax = max(1, ...array_column($stages, 'value'));

$closed = function (string $f, string $t): array {
    $s = db()->prepare("SELECT SUM(stage = 'won') won, SUM(stage = 'lost') lost, COALESCE(SUM(IF(stage = 'won', value, 0)), 0) won_value FROM deals WHERE closed_at BETWEEN :f AND :t");
    $s->execute(['f' => $f, 't' => $t]);
    return array_map('floatval', $s->fetch());
};
$now = $closed($from, $to);
$prev = $closed($pFrom, $pTo);
$winRate = ($now['won'] + $now['lost']) > 0 ? $now['won'] / ($now['won'] + $now['lost']) * 100 : null;

$newCustomersQ = 'SELECT COUNT(*) FROM customers WHERE created_at BETWEEN :from AND :to';
$newCustomers = dashboard_count($newCustomersQ, $period['start'], $period['end']);
$newCustomersPrev = dashboard_count($newCustomersQ, $period['prev_start'], $period['prev_end']);

$inv = db()->query("SELECT COALESCE(SUM(total - amount_paid), 0) outstanding,
    COALESCE(SUM(IF(due_date < CURDATE(), total - amount_paid, 0)), 0) overdue, SUM(due_date < CURDATE()) overdue_n
    FROM crm_documents WHERE type = 'invoice' AND status IN ('sent', 'part_paid')")->fetch();

// Payments received over time (services revenue)
$payRows = dashboard_stream_rows('services', $period['start'], $period['end']);
$payments = [];
foreach ($period['buckets'] as $b) {
    $sum = 0.0;
    foreach ($payRows as $r) if ($r['day'] >= $b['from']->format('Y-m-d') && $r['day'] <= $b['to']->format('Y-m-d')) $sum += (float) $r['amount'];
    $payments[] = $sum;
}
// New customers per bucket
$custRows = db()->prepare('SELECT DATE(created_at) day, COUNT(*) n FROM customers WHERE created_at BETWEEN :f AND :t GROUP BY DATE(created_at)');
$custRows->execute(['f' => $from, 't' => $to]);
$custRows = $custRows->fetchAll();
$custSeries = [];
foreach ($period['buckets'] as $b) {
    $n = 0;
    foreach ($custRows as $r) if ($r['day'] >= $b['from']->format('Y-m-d') && $r['day'] <= $b['to']->format('Y-m-d')) $n += (int) $r['n'];
    $custSeries[] = $n;
}
// Where customers come from
$sources = db()->query('SELECT source, COUNT(*) n FROM customers GROUP BY source ORDER BY n DESC')->fetchAll();
$top = db()->query("SELECT c.id, c.name, c.type,
        (SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE payment_status = 'paid' AND customer_id = c.id)
      + (SELECT COALESCE(SUM(total), 0) FROM pos_sales WHERE status = 'completed' AND customer_id = c.id)
      + (SELECT COALESCE(SUM(amount_paid), 0) FROM crm_documents WHERE type = 'invoice' AND customer_id = c.id) AS lifetime
    FROM customers c ORDER BY lifetime DESC LIMIT 6")->fetchAll();
$top = array_values(array_filter($top, fn($t) => (float) $t['lifetime'] > 0));
$myTasks = db()->query("SELECT t.*, c.name AS customer_name FROM crm_tasks t LEFT JOIN customers c ON c.id = t.customer_id
    WHERE t.status = 'open' AND t.assigned_to = " . (int) $adminUser['id'] . ' ORDER BY t.due_date IS NULL, t.due_date LIMIT 6')->fetchAll();
$closing = db()->query("SELECT d.id, d.title, d.value, d.stage, d.expected_close, c.name AS customer_name FROM deals d JOIN customers c ON c.id = d.customer_id
    WHERE d.stage IN ('lead', 'contacted', 'proposal', 'negotiation') AND d.expected_close IS NOT NULL ORDER BY d.expected_close LIMIT 6")->fetchAll();
$activity = db()->query("SELECT a.*, c.name AS customer_name, u.name AS user_name FROM crm_activities a LEFT JOIN customers c ON c.id = a.customer_id
    LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 8")->fetchAll();

$changeHtml = function (?float $c): string {
    if ($c === null) return '<span class="dash-change is-flat">No earlier data</span>';
    $up = $c >= 0;
    return '<span class="dash-change ' . ($up ? 'is-up' : 'is-down') . '">' . ($up ? '▲' : '▼') . ' ' . number_format(abs($c), 1) . '% <small>vs previous</small></span>';
};
$sourceColors = [CHART_SERIES[0], CHART_SERIES[1], CHART_SERIES[2], CHART_SERIES[3]];

$pageTitle = 'CRM';
require __DIR__ . '/../partials/admin_header.php';
?>

<section class="dash-head">
    <div><p class="admin-kicker">Sales &amp; CRM</p><h1>CRM</h1></div>
    <nav class="dash-range" aria-label="Date range">
        <?php foreach (DASH_RANGES as $key => $label): ?>
            <a href="<?= path('admin/crm') ?>?range=<?= e($key) ?>"<?= $key === $range ? ' class="is-active" aria-current="true"' : '' ?>><?= e(['7d' => '7 days', '30d' => '30 days', '90d' => '90 days', '12m' => '12 months'][$key]) ?></a>
        <?php endforeach; ?>
    </nav>
</section>

<nav class="admin-quick" aria-label="CRM shortcuts">
    <a class="admin-quick-link glass-dark" href="<?= path('admin/deals/new') ?>">New deal</a>
    <a class="admin-quick-link glass-dark" href="<?= path('admin/customers/new') ?>">New customer</a>
    <a class="admin-quick-link glass-dark" href="<?= path('admin/documents/new') ?>?type=quote">New quote</a>
    <a class="admin-quick-link glass-dark" href="<?= path('admin/documents/new') ?>?type=invoice">New invoice</a>
    <a class="admin-quick-link glass-dark is-plain" href="<?= path('admin/deals') ?>">Pipeline board</a>
</nav>

<div class="dash-kpis">
    <a class="dash-kpi glass-dark" href="<?= path('admin/deals') ?>"><p class="dash-kpi-label">Open pipeline</p><p class="dash-kpi-value"><?= e(naira_short($pipeline)) ?></p><p class="dash-kpi-note">Forecast <?= e(naira_short($weighted)) ?> (weighted)</p></a>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Won</p><p class="dash-kpi-value"><?= e(naira_short($now['won_value'])) ?></p><?= $changeHtml(dashboard_change($now['won_value'], $prev['won_value'])) ?><p class="dash-kpi-note"><?= (int) $now['won'] ?> deals · win rate <?= $winRate === null ? '—' : round($winRate) . '%' ?></p></div>
    <a class="dash-kpi glass-dark" href="<?= path('admin/invoices') ?>?status=unpaid"><p class="dash-kpi-label">Unpaid invoices</p><p class="dash-kpi-value<?= $inv['overdue'] > 0 ? ' is-warn' : '' ?>"><?= e(naira_short((float) $inv['outstanding'])) ?></p><p class="dash-kpi-note"><?= (int) $inv['overdue_n'] ?> overdue · <?= e(naira_short((float) $inv['overdue'])) ?></p></a>
    <a class="dash-kpi glass-dark" href="<?= path('admin/customers') ?>"><p class="dash-kpi-label">New customers</p><p class="dash-kpi-value"><?= $newCustomers ?></p><?= $changeHtml(dashboard_change($newCustomers, $newCustomersPrev)) ?></a>
</div>

<div class="dash-grid dash-grid-2">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Sales funnel</h2><p class="dash-card-sub">Open deals by stage · value</p></div><a class="dash-link" href="<?= path('admin/deals') ?>">Board →</a></header>
        <ol class="dash-funnel">
            <?php foreach ($stages as $k => $st): ?>
                <li data-tip="<?= e(DEAL_STAGES[$k]['label'] . ': ' . $st['count'] . ' deals · ' . format_naira($st['value'])) ?>">
                    <span class="dash-funnel-label"><?= e(DEAL_STAGES[$k]['label']) ?></span>
                    <span class="dash-funnel-track"><span class="dash-funnel-bar" style="width:<?= $st['value'] ? max(6, round($st['value'] / $funnelMax * 100)) : 0 ?>%"></span></span>
                    <span class="dash-funnel-value"><?= $st['count'] ?></span>
                    <span class="dash-funnel-pct"><?= e(naira_short($st['value'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Invoice payments</h2><p class="dash-card-sub"><?= e(DASH_RANGES[$range]) ?> · by <?= e($period['unit']) ?></p></div><strong class="dash-figure-sm"><?= e(naira_short(array_sum($payments))) ?></strong></header>
        <?= chart_bars($labels, $payments, ['color' => CHART_SERIES[2], 'format' => fn($v) => naira_short((float) $v), 'integer' => false, 'width' => 460, 'height' => 220, 'label' => 'Invoice payments received']) ?>
    </section>
</div>

<div class="dash-grid dash-grid-main">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>New customers</h2><p class="dash-card-sub">From orders, bookings, requests, messages, walk-ins and manual entry</p></div></header>
        <?= chart_bars($labels, $custSeries, ['color' => CHART_SERIES[1], 'label' => 'New customers']) ?>
    </section>
    <section class="dash-card glass-dark dash-mix">
        <header class="dash-card-head"><div><h2>Where they come from</h2><p class="dash-card-sub">All customers</p></div></header>
        <?php
        // Top 3 sources keep their colour; the rest fold into "Other" so colours never cycle.
        $parts = [];
        foreach ($sources as $i => $s) {
            if ($i < 3) $parts[] = ['name' => CUSTOMER_SOURCES[$s['source']], 'value' => (int) $s['n'], 'color' => $sourceColors[$i]];
            else { if (!isset($parts[3])) $parts[3] = ['name' => 'Other', 'value' => 0, 'color' => $sourceColors[3]]; $parts[3]['value'] += (int) $s['n']; }
        }
        $totalCust = array_sum(array_column($parts, 'value'));
        ?>
        <?= chart_donut($parts, (string) $totalCust, 'customers', ['label' => 'Customers by source']) ?>
        <?= chart_legend(array_map(fn($p) => ['name' => $p['name'], 'color' => $p['color'], 'value' => $p['value']], $parts)) ?>
    </section>
</div>

<div class="dash-grid dash-grid-2">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>My tasks</h2><p class="dash-card-sub">Open, soonest first</p></div><a class="dash-link" href="<?= path('admin/tasks') ?>">All tasks →</a></header>
        <?php foreach ($myTasks as $t): $late = $t['due_date'] && $t['due_date'] < date('Y-m-d'); ?>
            <form method="post" action="<?= path('admin/tasks') ?>" class="crm-task">
                <?= csrf_field() ?><input type="hidden" name="action" value="done"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><input type="hidden" name="return" value="admin/crm">
                <button type="submit" class="crm-check" aria-label="Mark done"></button>
                <span><?= e($t['title']) ?><small class="<?= $late ? 'is-late' : '' ?>"><?= $t['due_date'] ? ($late ? 'Overdue · ' : '') . e((new DateTimeImmutable($t['due_date']))->format('D j M')) : 'No due date' ?><?= $t['customer_name'] ? ' · ' . e($t['customer_name']) : '' ?></small></span>
            </form>
        <?php endforeach; ?>
        <?php if (!$myTasks): ?><p class="dash-empty">No open tasks. Add follow-ups from any customer or deal.</p><?php endif; ?>
    </section>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Closing soon</h2><p class="dash-card-sub">Open deals by expected close date</p></div></header>
        <?php foreach ($closing as $d): $late = $d['expected_close'] < date('Y-m-d'); ?>
            <a class="crm-mini" href="<?= path('admin/deals/' . (int) $d['id']) ?>"><span><?= e($d['title']) ?><small><?= e($d['customer_name']) ?> · <?= e(DEAL_STAGES[$d['stage']]['label']) ?> · <span class="<?= $late ? 'is-late' : '' ?>"><?= e((new DateTimeImmutable($d['expected_close']))->format('j M')) ?></span></small></span><strong><?= e(naira_short((float) $d['value'])) ?></strong></a>
        <?php endforeach; ?>
        <?php if (!$closing): ?><p class="dash-empty">No open deals with a close date.</p><?php endif; ?>
    </section>
</div>

<div class="dash-grid dash-grid-2">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Top customers</h2><p class="dash-card-sub">Lifetime value</p></div></header>
        <?php foreach ($top as $t): ?>
            <a class="crm-mini" href="<?= path('admin/customers/' . (int) $t['id']) ?>"><span><?= e($t['name']) ?><small><?= $t['type'] === 'organisation' ? 'Organisation' : 'Person' ?></small></span><strong><?= e(naira_short((float) $t['lifetime'])) ?></strong></a>
        <?php endforeach; ?>
        <?php if (!$top): ?><p class="dash-empty">No sales linked to customers yet.</p><?php endif; ?>
    </section>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Recent activity</h2><p class="dash-card-sub">Notes, calls and deal updates</p></div></header>
        <?php foreach ($activity as $a): ?>
            <div class="crm-mini"><span><?= e(mb_strimwidth($a['body'], 0, 90, '…')) ?><small><?= $a['customer_name'] ? '<a href="' . path('admin/customers/' . (int) $a['customer_id']) . '">' . e($a['customer_name']) . '</a> · ' : '' ?><?= e((new DateTimeImmutable($a['created_at']))->format('j M, g:i A')) ?><?= $a['user_name'] ? ' · ' . e($a['user_name']) : '' ?></small></span></div>
        <?php endforeach; ?>
        <?php if (!$activity): ?><p class="dash-empty">Nothing logged yet.</p><?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
