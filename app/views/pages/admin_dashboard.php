<?php
declare(strict_types=1);

$adminUser = require_admin();

$range = (string) ($_GET['range'] ?? '30d');
$period = dashboard_period($range);
$range = $period['range'];

$canStore = admin_can('store');
$canGaming = admin_can('gaming');
$canSales = admin_can('sales');
$canMoney = $canStore || $canGaming;

$fmtNaira = fn($v) => naira_short((float) $v);

// Revenue, only for the streams this person can see.
$revenue = [];
if ($canMoney) {
    foreach (dashboard_revenue($period) as $key => $stream) {
        if (($key === 'shop' && $canStore) || ($key === 'gaming' && $canGaming)) {
            $revenue[$key] = $stream;
        }
    }
}
$revTotal = array_sum(array_column($revenue, 'total'));
$revPrev = array_sum(array_column($revenue, 'prev'));
$seriesColors = ['shop' => CHART_SERIES[0], 'gaming' => CHART_SERIES[1]];

$labels = array_column($period['buckets'], 'label');
$s = $period['start']; $e = $period['end']; $ps = $period['prev_start']; $pe = $period['prev_end'];

$kpis = [];
if ($canMoney) {
    $kpis[] = ['label' => 'Revenue', 'value' => naira_short($revTotal), 'change' => dashboard_change($revTotal, $revPrev), 'note' => 'Shop (online + walk-in) + gaming'];
}
if ($canStore) {
    $q = "SELECT COUNT(*) FROM orders WHERE payment_status = 'paid' AND created_at BETWEEN :from AND :to";
    $now = dashboard_count($q, $s, $e);
    $kpis[] = ['label' => 'Paid Orders', 'value' => number_format($now), 'change' => dashboard_change($now, dashboard_count($q, $ps, $pe)), 'note' => 'Online shop'];
}
if ($canGaming) {
    $q = "SELECT COUNT(*) FROM room_bookings WHERE status <> 'cancelled' AND booking_date BETWEEN DATE(:from) AND DATE(:to)";
    $now = dashboard_count($q, $s, $e);
    $kpis[] = ['label' => 'Room Bookings', 'value' => number_format($now), 'change' => dashboard_change($now, dashboard_count($q, $ps, $pe)), 'note' => 'Common + VIP rooms'];
}
if ($canSales) {
    $q = 'SELECT COUNT(*) FROM software_requests WHERE created_at BETWEEN :from AND :to';
    $now = dashboard_count($q, $s, $e);
    $kpis[] = ['label' => 'New Leads', 'value' => number_format($now), 'change' => dashboard_change($now, dashboard_count($q, $ps, $pe)), 'note' => 'Software Clinic requests'];
}

// Needs attention
$attention = [];
if ($canStore) {
    $n = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'paid', 'processing')")->fetchColumn();
    $attention[] = ['label' => 'Orders to fulfil', 'value' => $n, 'href' => path('admin/orders')];
    $n = (int) db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND is_demo = 0 AND stock_qty <= 3')->fetchColumn();
    $attention[] = ['label' => 'Products low on stock (3 or fewer)', 'value' => $n, 'href' => path('admin/products')];
}
if ($canGaming) {
    $n = (int) db()->query("SELECT COUNT(*) FROM room_bookings WHERE status = 'pending'")->fetchColumn();
    $attention[] = ['label' => 'Bookings to confirm', 'value' => $n, 'href' => path('admin/bookings')];
}
if ($canSales) {
    $n = (int) db()->query("SELECT COUNT(*) FROM software_requests WHERE status = 'new'")->fetchColumn();
    $attention[] = ['label' => 'New software requests', 'value' => $n, 'href' => path('admin/software-requests')];
    $n = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL')->fetchColumn();
    $attention[] = ['label' => 'Unread messages', 'value' => $n, 'href' => path('admin') . '#recent-messages'];
}

$upcoming = [];
if ($canGaming) {
    $upcoming = db()->query(
        "SELECT b.id, b.customer_name, b.booking_date, b.start_time, b.end_time, b.status, b.party_size, r.name AS room
         FROM room_bookings b JOIN gaming_rooms r ON r.id = b.room_id
         WHERE b.status IN ('pending', 'confirmed') AND b.booking_date >= CURDATE()
         ORDER BY b.booking_date, b.start_time LIMIT 5"
    )->fetchAll();
}

$funnel = $canSales ? dashboard_request_funnel() : [];
$funnelMax = max(1, ...array_column($funnel ?: [['value' => 0]], 'value'));
$funnelTotal = array_sum(array_column($funnel, 'value'));

$bookingSeries = $canGaming ? dashboard_bookings_series($period) : [];
$monthly = $canMoney ? dashboard_monthly_summary(6) : [];

$recentMessages = $canSales ? db()->query(
    'SELECT id, name, subject, created_at, read_at FROM contact_messages ORDER BY created_at DESC LIMIT 5'
)->fetchAll() : [];

$firstName = explode(' ', (string) ($adminUser['name'] ?? ''))[0] ?: 'there';
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$pageTitle = 'Overview';
require __DIR__ . '/../partials/admin_header.php';

$changeHtml = function (?float $c): string {
    if ($c === null) return '<span class="dash-change is-flat">No earlier data</span>';
    $up = $c >= 0;
    return '<span class="dash-change ' . ($up ? 'is-up' : 'is-down') . '">' . ($up ? '▲' : '▼') . ' ' . number_format(abs($c), 1) . '% <small>vs previous</small></span>';
};
?>

<section class="dash-head">
    <div>
        <p class="admin-kicker">Overview</p>
        <h1><?= e($greeting) ?>, <em><?= e($firstName) ?></em></h1>
    </div>
    <nav class="dash-range" aria-label="Date range">
        <?php foreach (DASH_RANGES as $key => $label): ?>
            <a href="<?= path('admin') ?>?range=<?= e($key) ?>"<?= $key === $range ? ' class="is-active" aria-current="true"' : '' ?>><?= e(['7d' => '7 days', '30d' => '30 days', '90d' => '90 days', '12m' => '12 months'][$key]) ?></a>
        <?php endforeach; ?>
    </nav>
</section>

<?php if ($canMoney): ?>
<div class="dash-grid dash-grid-main">
    <section class="dash-card glass-dark">
        <header class="dash-card-head">
            <div>
                <h2>Revenue</h2>
                <p class="dash-card-sub"><?= e(DASH_RANGES[$range]) ?> · by <?= e($period['unit']) ?></p>
            </div>
            <div class="dash-figure">
                <strong><?= e(naira_short($revTotal)) ?></strong>
                <?= $changeHtml(dashboard_change($revTotal, $revPrev)) ?>
            </div>
        </header>
        <?php if (count($revenue) > 1): ?>
            <?= chart_legend(array_map(fn($k, $r) => ['name' => $r['name'], 'color' => $seriesColors[$k]], array_keys($revenue), $revenue)) ?>
        <?php endif; ?>
        <?php $revSeries = array_map(fn($k, $r) => ['name' => $r['name'], 'values' => $r['values'], 'color' => count($revenue) > 1 ? $seriesColors[$k] : CHART_GOLD], array_keys($revenue), $revenue); ?>
        <div class="chart-wide"><?= chart_line($labels, $revSeries, ['format' => $fmtNaira, 'label' => 'Revenue over time']) ?></div>
        <div class="chart-narrow"><?= chart_line($labels, $revSeries, ['format' => $fmtNaira, 'label' => 'Revenue over time', 'width' => 360, 'height' => 240]) ?></div>
        <details class="dash-table-toggle">
            <summary>View as table</summary>
            <table class="dash-table">
                <thead><tr><th><?= e(ucfirst($period['unit'])) ?></th><?php foreach ($revenue as $r): ?><th><?= e($r['name']) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php foreach ($labels as $i => $lab): ?>
                    <tr><td><?= e($lab) ?></td><?php foreach ($revenue as $r): ?><td><?= format_naira($r['values'][$i]) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </details>
    </section>

    <section class="dash-card glass-dark dash-mix">
        <header class="dash-card-head"><div><h2>Revenue mix</h2><p class="dash-card-sub">Share of <?= e(strtolower(DASH_RANGES[$range])) ?></p></div></header>
        <?php
        $parts = array_map(fn($k, $r) => ['name' => $r['name'], 'value' => $r['total'], 'color' => $seriesColors[$k]], array_keys($revenue), $revenue);
        $lead = $revTotal > 0 ? max(array_column($parts, 'value')) / $revTotal * 100 : 0;
        $leadName = $revTotal > 0 ? $parts[array_search(max(array_column($parts, 'value')), array_column($parts, 'value'))]['name'] : '';
        ?>
        <?= chart_donut($parts, $revTotal > 0 ? round($lead) . '%' : '—', $revTotal > 0 ? $leadName : 'No revenue yet', ['format' => fn($v) => format_naira((float) $v), 'label' => 'Revenue by stream']) ?>
        <?= chart_legend(array_map(fn($p) => ['name' => $p['name'], 'color' => $p['color'], 'value' => naira_short($p['value'])], $parts)) ?>
    </section>
</div>
<?php endif; ?>

<?php if ($kpis): ?>
<div class="dash-kpis">
    <?php foreach ($kpis as $k): ?>
        <div class="dash-kpi glass-dark">
            <p class="dash-kpi-label"><?= e($k['label']) ?></p>
            <p class="dash-kpi-value"><?= e($k['value']) ?></p>
            <?= $changeHtml($k['change']) ?>
            <p class="dash-kpi-note"><?= e($k['note']) ?></p>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="dash-grid dash-grid-2">
    <?php if ($attention): ?>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Needs attention</h2><p class="dash-card-sub">Right now</p></div></header>
        <ul class="dash-attention">
            <?php foreach ($attention as $a): ?>
                <li><a href="<?= $a['href'] ?>"<?= $a['value'] > 0 ? ' class="is-hot"' : '' ?>><span><?= e($a['label']) ?></span><strong><?= (int) $a['value'] ?></strong></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if ($canGaming): ?>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Coming up at the Hub</h2><p class="dash-card-sub">Next room bookings</p></div><a class="dash-link" href="<?= path('admin/bookings') ?>">All bookings →</a></header>
        <?php if ($upcoming): ?>
            <ul class="dash-upcoming">
                <?php foreach ($upcoming as $b):
                    $d = new DateTimeImmutable($b['booking_date']); ?>
                    <li>
                        <a href="<?= path('admin/bookings/' . (int) $b['id']) ?>">
                            <span class="dash-date"><strong><?= e($d->format('j')) ?></strong><?= e($d->format('M')) ?></span>
                            <span class="dash-upcoming-text"><strong><?= e($b['customer_name']) ?></strong><small><?= e($b['room']) ?> · <?= e(substr($b['start_time'], 0, 5)) ?>–<?= e(substr($b['end_time'], 0, 5)) ?><?= $b['party_size'] ? ' · ' . (int) $b['party_size'] . ' people' : '' ?></small></span>
                            <span class="badge <?= $b['status'] === 'confirmed' ? 'badge-success' : 'badge-warning' ?>"><?= e($b['status']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="dash-empty">No upcoming bookings.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<?php if ($canSales || $canGaming): ?>
<div class="dash-grid dash-grid-2">
    <?php if ($canSales): ?>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Software Clinic funnel</h2><p class="dash-card-sub">All requests by stage</p></div><a class="dash-link" href="<?= path('admin/software-requests') ?>">Requests →</a></header>
        <ol class="dash-funnel">
            <?php foreach ($funnel as $stage): ?>
                <li data-tip="<?= e($stage['label'] . ': ' . $stage['value']) ?>">
                    <span class="dash-funnel-label"><?= e($stage['label']) ?></span>
                    <span class="dash-funnel-track"><span class="dash-funnel-bar" style="width:<?= $stage['value'] ? max(6, round($stage['value'] / $funnelMax * 100)) : 0 ?>%"></span></span>
                    <span class="dash-funnel-value"><?= (int) $stage['value'] ?></span>
                    <span class="dash-funnel-pct"><?= $funnelTotal ? round($stage['value'] / $funnelTotal * 100) . '%' : '—' ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
    <?php endif; ?>

    <?php if ($canGaming): ?>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Bookings over time</h2><p class="dash-card-sub"><?= e(DASH_RANGES[$range]) ?> · by <?= e($period['unit']) ?></p></div><strong class="dash-figure-sm"><?= array_sum($bookingSeries) ?></strong></header>
        <?= chart_bars($labels, $bookingSeries, ['color' => CHART_SERIES[1], 'label' => 'Bookings over time', 'width' => 460, 'height' => 220]) ?>
    </section>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($monthly): ?>
<section class="dash-card glass-dark">
    <header class="dash-card-head"><div><h2>Monthly summary</h2><p class="dash-card-sub">Last 6 months</p></div></header>
    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead><tr><th>Month</th><?php if ($canStore): ?><th>Shop revenue</th><th>Paid orders</th><?php endif; ?><?php if ($canGaming): ?><th>Gaming revenue</th><th>Bookings</th><?php endif; ?><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($monthly as $m): ?>
                <tr>
                    <td><?= e($m['month']) ?></td>
                    <?php if ($canStore): ?><td><?= format_naira($m['shop']) ?></td><td><?= (int) $m['orders'] ?></td><?php endif; ?>
                    <?php if ($canGaming): ?><td><?= format_naira($m['gaming']) ?></td><td><?= (int) $m['bookings'] ?></td><?php endif; ?>
                    <td><strong><?= format_naira(($canStore ? $m['shop'] : 0) + ($canGaming ? $m['gaming'] : 0)) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if ($canSales): ?>
<section class="dash-card glass-dark" id="recent-messages">
    <header class="dash-card-head"><div><h2>Recent messages</h2><p class="dash-card-sub">From the contact form</p></div></header>
    <?php if ($recentMessages): ?>
        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead><tr><th></th><th>From</th><th>Subject</th><th>Received</th></tr></thead>
                <tbody>
                    <?php foreach ($recentMessages as $m): ?>
                        <tr>
                            <td><?php if (!$m['read_at']): ?><span class="badge badge-warning">New</span><?php endif; ?></td>
                            <td><?= e($m['name']) ?></td>
                            <td><?= e($m['subject'] ?: '(no subject)') ?></td>
                            <td><?= e((new DateTimeImmutable($m['created_at']))->format('M j, g:i A')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="dash-empty">No messages yet.</p>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if (!$canMoney && !$canSales && !admin_can('training') && !admin_can('website')): ?>
<section class="dash-card glass-dark"><p class="dash-empty">Your account doesn’t have any areas yet. Ask an administrator to add them in Staff &amp; Roles.</p></section>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
