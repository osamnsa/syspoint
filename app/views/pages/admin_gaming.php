<?php
declare(strict_types=1);

$adminUser = require_admin();

/** Opening hours used for occupancy (9am–10pm, as on the website). */
const HUB_OPEN_HOUR = 9;
const HUB_CLOSE_HOUR = 22;

$range = (string) ($_GET['range'] ?? '30d');
$period = dashboard_period($range);
$range = $period['range'];
$labels = array_column($period['buckets'], 'label');
$from = $period['start']->format('Y-m-d');
$to = $period['end']->format('Y-m-d');

$rooms = db()->query('SELECT id, name, hourly_rate FROM gaming_rooms WHERE is_active = 1 ORDER BY hourly_rate DESC, name')->fetchAll();
$rooms = array_slice($rooms, 0, 4); // fixed colour order, never cycled

$stmt = db()->prepare("SELECT b.room_id, b.booking_date, b.start_time, b.end_time, b.party_size, r.hourly_rate
    FROM room_bookings b JOIN gaming_rooms r ON r.id = b.room_id
    WHERE b.status IN ('confirmed', 'completed') AND b.booking_date BETWEEN :f AND :t");
$stmt->execute(['f' => $from, 't' => $to]);
$bookings = $stmt->fetchAll();

$hours = fn($b) => max(0, (strtotime($b['end_time']) - strtotime($b['start_time'])) / 3600);
$revByRoom = [];
foreach ($rooms as $r) $revByRoom[$r['id']] = array_fill(0, count($period['buckets']), 0.0);
$heat = array_fill(0, 7, array_fill(HUB_OPEN_HOUR, HUB_CLOSE_HOUR - HUB_OPEN_HOUR, 0)); // [weekday Mon=0][hour]
$totalHours = 0.0; $revenue = 0.0; $party = [];
foreach ($bookings as $b) {
    $h = $hours($b);
    $totalHours += $h;
    $revenue += $h * (float) $b['hourly_rate'];
    if ($b['party_size']) $party[] = (int) $b['party_size'];
    foreach ($period['buckets'] as $i => $bk) {
        if ($b['booking_date'] >= $bk['from']->format('Y-m-d') && $b['booking_date'] <= $bk['to']->format('Y-m-d') && isset($revByRoom[$b['room_id']])) {
            $revByRoom[$b['room_id']][$i] += $h * (float) $b['hourly_rate'];
        }
    }
    $wd = (int) (new DateTimeImmutable($b['booking_date']))->format('N') - 1;
    for ($hr = (int) substr($b['start_time'], 0, 2); $hr < (int) ceil($hours($b)) + (int) substr($b['start_time'], 0, 2); $hr++) {
        if (isset($heat[$wd][$hr])) $heat[$wd][$hr]++;
    }
}
$days = (int) $period['start']->diff($period['end'])->days + 1;
$capacity = max(1, count($rooms) * $days * (HUB_CLOSE_HOUR - HUB_OPEN_HOUR));
$occupancy = $totalHours / $capacity * 100;
$prevRev = array_sum(array_map(fn($r) => (float) $r['amount'], dashboard_stream_rows('gaming', $period['prev_start'], $period['prev_end'])));
$pending = (int) db()->query("SELECT COUNT(*) FROM room_bookings WHERE status = 'pending'")->fetchColumn();
$heatMax = max(1, ...array_map('max', $heat));

$today = db()->query("SELECT b.*, r.name AS room FROM room_bookings b JOIN gaming_rooms r ON r.id = b.room_id
    WHERE b.booking_date = CURDATE() AND b.status IN ('pending', 'confirmed', 'completed') ORDER BY b.start_time")->fetchAll();
$upcoming = db()->query("SELECT b.*, r.name AS room FROM room_bookings b JOIN gaming_rooms r ON r.id = b.room_id
    WHERE b.booking_date > CURDATE() AND b.status IN ('pending', 'confirmed') ORDER BY b.booking_date, b.start_time LIMIT 6")->fetchAll();
$equip = array_merge(['in_use' => 0, 'spare' => 0, 'in_repair' => 0, 'retired' => 0], db()->query('SELECT status, COUNT(*) FROM assets GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR));
$regulars = db()->query("SELECT c.id, c.name, COUNT(*) n FROM room_bookings b JOIN customers c ON c.id = b.customer_id
    WHERE b.status IN ('confirmed', 'completed') AND b.booking_date >= CURDATE() - INTERVAL 90 DAY GROUP BY c.id, c.name ORDER BY n DESC LIMIT 5")->fetchAll();

$changeHtml = function (?float $c): string {
    if ($c === null) return '<span class="dash-change is-flat">No earlier data</span>';
    return '<span class="dash-change ' . ($c >= 0 ? 'is-up' : 'is-down') . '">' . ($c >= 0 ? '▲' : '▼') . ' ' . number_format(abs($c), 1) . '% <small>vs previous</small></span>';
};
$series = [];
foreach ($rooms as $i => $r) $series[] = ['name' => $r['name'], 'values' => $revByRoom[$r['id']], 'color' => CHART_SERIES[$i]];
$fmt = fn($v) => naira_short((float) $v);

$pageTitle = 'Gaming';
require __DIR__ . '/../partials/admin_header.php';
?>

<section class="dash-head">
    <div><p class="admin-kicker"><?= e(site('site.hub_name') . ' · ' . site('site.hub_suite')) ?></p><h1>Gaming</h1></div>
    <nav class="dash-range" aria-label="Date range">
        <?php foreach (DASH_RANGES as $key => $label): ?>
            <a href="<?= path('admin/gaming') ?>?range=<?= e($key) ?>"<?= $key === $range ? ' class="is-active" aria-current="true"' : '' ?>><?= e(['7d' => '7 days', '30d' => '30 days', '90d' => '90 days', '12m' => '12 months'][$key]) ?></a>
        <?php endforeach; ?>
    </nav>
</section>

<nav class="admin-quick" aria-label="Gaming shortcuts">
    <a class="admin-quick-link glass-dark is-plain" href="<?= path('admin/bookings') ?>">Bookings</a>
    <a class="admin-quick-link glass-dark is-plain" href="<?= path('admin/rooms') ?>">Rooms &amp; rates</a>
    <a class="admin-quick-link glass-dark" href="<?= path('admin/games/new') ?>">Add game</a>
    <a class="admin-quick-link glass-dark" href="<?= path('admin/equipment/new') ?>">Add equipment</a>
</nav>

<div class="dash-kpis">
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Room revenue</p><p class="dash-kpi-value"><?= e(naira_short($revenue)) ?></p><?= $changeHtml(dashboard_change($revenue, $prevRev)) ?></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Occupancy</p><p class="dash-kpi-value"><?= round($occupancy) ?>%</p><p class="dash-kpi-note"><?= number_format($totalHours, 1) ?> of <?= number_format($capacity) ?> room-hours (<?= HUB_OPEN_HOUR ?>am–<?= HUB_CLOSE_HOUR - 12 ?>pm)</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Sessions</p><p class="dash-kpi-value"><?= count($bookings) ?></p><p class="dash-kpi-note">Avg party <?= $party ? number_format(array_sum($party) / count($party), 1) : '—' ?> people</p></div>
    <a class="dash-kpi glass-dark" href="<?= path('admin/bookings') ?>"><p class="dash-kpi-label">To confirm</p><p class="dash-kpi-value<?= $pending ? ' is-warn' : '' ?>"><?= $pending ?></p><p class="dash-kpi-note">Pending booking requests</p></a>
</div>

<div class="dash-grid dash-grid-main">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Revenue by room</h2><p class="dash-card-sub"><?= e(DASH_RANGES[$range]) ?> · confirmed &amp; completed bookings</p></div></header>
        <?php if (count($series) > 1): ?><?= chart_legend(array_map(fn($s) => ['name' => $s['name'], 'color' => $s['color']], $series)) ?><?php endif; ?>
        <div class="chart-wide"><?= chart_line($labels, $series, ['format' => $fmt, 'label' => 'Room revenue over time']) ?></div>
        <div class="chart-narrow"><?= chart_line($labels, $series, ['format' => $fmt, 'label' => 'Room revenue over time', 'width' => 360, 'height' => 240]) ?></div>
    </section>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Today at the Hub</h2><p class="dash-card-sub"><?= e((new DateTimeImmutable())->format('l j F')) ?></p></div></header>
        <div class="hub-day">
            <?php foreach (db()->query('SELECT id, name FROM gaming_rooms WHERE is_active = 1 ORDER BY name') as $room): ?>
                <div class="hub-day-row">
                    <span class="hub-day-room"><?= e($room['name']) ?></span>
                    <span class="hub-day-track">
                        <?php foreach ($today as $b): if ((int) $b['room_id'] !== (int) $room['id']) continue;
                            $s = (strtotime($b['start_time']) - strtotime(HUB_OPEN_HOUR . ':00')) / 3600;
                            $len = (strtotime($b['end_time']) - strtotime($b['start_time'])) / 3600;
                            $span = HUB_CLOSE_HOUR - HUB_OPEN_HOUR; ?>
                            <a class="hub-day-block is-<?= e($b['status']) ?>" href="<?= path('admin/bookings/' . (int) $b['id']) ?>" style="left:<?= max(0, $s / $span * 100) ?>%;width:<?= max(4, $len / $span * 100) ?>%" data-tip="<?= e($b['customer_name'] . ' · ' . substr($b['start_time'], 0, 5) . '–' . substr($b['end_time'], 0, 5) . ' · ' . $b['status']) ?>"></a>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php endforeach; ?>
            <div class="hub-day-scale"><span></span><span><?php for ($h = HUB_OPEN_HOUR; $h <= HUB_CLOSE_HOUR; $h += 3): ?><i style="left:<?= ($h - HUB_OPEN_HOUR) / (HUB_CLOSE_HOUR - HUB_OPEN_HOUR) * 100 ?>%"><?= $h > 12 ? $h - 12 . 'pm' : ($h === 12 ? '12pm' : $h . 'am') ?></i><?php endfor; ?></span></div>
        </div>
        <p class="dash-card-sub" style="margin-top:12px;"><?= count($today) ?> booking<?= count($today) === 1 ? '' : 's' ?> today · <span class="hub-key is-confirmed"></span> confirmed <span class="hub-key is-pending"></span> pending</p>
    </section>
</div>

<section class="dash-card glass-dark">
    <header class="dash-card-head"><div><h2>Busiest times</h2><p class="dash-card-sub">Booked room-hours by day and hour · <?= e(strtolower(DASH_RANGES[$range])) ?></p></div></header>
    <div class="heat" role="table" aria-label="Booked hours by weekday and hour">
        <div class="heat-row heat-head" role="row"><span role="columnheader"></span><?php for ($h = HUB_OPEN_HOUR; $h < HUB_CLOSE_HOUR; $h++): ?><span role="columnheader"><?= $h > 12 ? $h - 12 : $h ?><small><?= $h >= 12 ? 'pm' : 'am' ?></small></span><?php endfor; ?></div>
        <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d => $dayName): ?>
            <div class="heat-row" role="row"><span role="rowheader"><?= $dayName ?></span>
                <?php for ($h = HUB_OPEN_HOUR; $h < HUB_CLOSE_HOUR; $h++): $v = $heat[$d][$h]; ?>
                    <span role="cell" class="heat-cell" style="--a:<?= $v ? round(0.15 + 0.85 * $v / $heatMax, 2) : 0 ?>" data-tip="<?= e($dayName . ' ' . ($h > 12 ? $h - 12 . 'pm' : ($h === 12 ? '12pm' : $h . 'am')) . ': ' . $v . ' booked room-hour' . ($v === 1 ? '' : 's')) ?>"><span class="sr-only"><?= $v ?></span></span>
                <?php endfor; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="heat-legend">Fewer <span class="heat-cell" style="--a:0.15"></span><span class="heat-cell" style="--a:0.5"></span><span class="heat-cell" style="--a:1"></span> More</p>
</section>

<div class="dash-grid dash-grid-2">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Coming up</h2></div><a class="dash-link" href="<?= path('admin/bookings') ?>">All bookings →</a></header>
        <?php foreach ($upcoming as $b): ?><a class="crm-mini" href="<?= path('admin/bookings/' . (int) $b['id']) ?>"><span><?= e($b['customer_name']) ?><small><?= e($b['room']) ?> · <?= e((new DateTimeImmutable($b['booking_date']))->format('D j M')) ?> · <?= e(substr($b['start_time'], 0, 5)) ?>–<?= e(substr($b['end_time'], 0, 5)) ?></small></span><span class="badge <?= $b['status'] === 'confirmed' ? 'badge-success' : 'badge-warning' ?>"><?= e($b['status']) ?></span></a><?php endforeach; ?>
        <?php if (!$upcoming): ?><p class="dash-empty">No upcoming bookings.</p><?php endif; ?>
    </section>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Equipment</h2></div><a class="dash-link" href="<?= path('admin/equipment') ?>">Register →</a></header>
        <ul class="dash-attention">
            <?php foreach (['in_use' => 'In use', 'spare' => 'Spare', 'in_repair' => 'In repair'] as $k => $l): ?>
                <li><a href="<?= path('admin/equipment') ?>?status=<?= $k ?>"<?= $k === 'in_repair' && $equip[$k] ? ' class="is-hot"' : '' ?>><span><?= e($l) ?></span><strong><?= (int) $equip[$k] ?></strong></a></li>
            <?php endforeach; ?>
        </ul>
        <header class="dash-card-head" style="margin-top:16px;"><div><h2>Regulars</h2><p class="dash-card-sub">Most bookings, last 90 days</p></div></header>
        <?php foreach ($regulars as $r): ?><a class="crm-mini" href="<?= path('admin/customers/' . (int) $r['id']) ?>"><span><?= e($r['name']) ?></span><strong><?= (int) $r['n'] ?></strong></a><?php endforeach; ?>
        <?php if (!$regulars): ?><p class="dash-empty">No repeat customers yet.</p><?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
