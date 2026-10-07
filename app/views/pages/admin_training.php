<?php
declare(strict_types=1);

$adminUser = require_admin();

$range = (string) ($_GET['range'] ?? '90d');
$period = dashboard_period($range);
$range = $period['range'];
$labels = array_column($period['buckets'], 'label');

$counts = array_merge(array_fill_keys(array_keys(ENROLMENT_STATUSES), 0), db()->query('SELECT status, COUNT(*) FROM enrolments GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR));
$interns = (int) db()->query("SELECT COUNT(*) FROM enrolments WHERE kind = 'internship' AND status = 'enrolled'")->fetchColumn();
$outstanding = (float) db()->query("SELECT COALESCE(SUM(fee - amount_paid), 0) FROM enrolments WHERE status IN ('enrolled', 'completed') AND fee > amount_paid")->fetchColumn();
$decided = $counts['completed'] + $counts['dropped'];
$completion = $decided ? $counts['completed'] / $decided * 100 : null;

$feeRows = dashboard_stream_rows('training', $period['start'], $period['end']);
$fees = [];
foreach ($period['buckets'] as $b) {
    $sum = 0.0;
    foreach ($feeRows as $r) if ($r['day'] >= $b['from']->format('Y-m-d') && $r['day'] <= $b['to']->format('Y-m-d')) $sum += (float) $r['amount'];
    $fees[] = $sum;
}
$feesTotal = array_sum($fees);
$feesPrev = array_sum(array_map(fn($r) => (float) $r['amount'], dashboard_stream_rows('training', $period['prev_start'], $period['prev_end'])));
$newQ = 'SELECT COUNT(*) FROM enrolments WHERE created_at BETWEEN :from AND :to';
$newNow = dashboard_count($newQ, $period['start'], $period['end']);

$byCourse = db()->query("SELECT COALESCE(t.title, e.track, IF(e.kind = 'internship', 'Internship', 'Other')) AS name,
        SUM(e.status = 'enquiry') enquiries, SUM(e.status = 'enrolled') enrolled, SUM(e.status = 'completed') completed, COALESCE(SUM(e.amount_paid), 0) paid
    FROM enrolments e LEFT JOIN training_courses t ON t.id = e.course_id GROUP BY name ORDER BY enrolled DESC, enquiries DESC LIMIT 8")->fetchAll();
$intakes = db()->query("SELECT e.start_date, COALESCE(t.title, e.track, 'Internship') AS name, COUNT(*) n, SUM(e.status = 'enrolled') confirmed
    FROM enrolments e LEFT JOIN training_courses t ON t.id = e.course_id
    WHERE e.start_date >= CURDATE() AND e.status IN ('enquiry', 'enrolled') GROUP BY e.start_date, name ORDER BY e.start_date LIMIT 6")->fetchAll();
$enquiries = db()->query("SELECT e.id, e.created_at, c.name, c.phone, COALESCE(t.title, e.track, 'Internship') AS programme FROM enrolments e
    JOIN customers c ON c.id = e.customer_id LEFT JOIN training_courses t ON t.id = e.course_id WHERE e.status = 'enquiry' ORDER BY e.id DESC LIMIT 6")->fetchAll();
$owing = db()->query("SELECT e.id, c.name, e.fee - e.amount_paid AS balance, COALESCE(t.title, e.track, 'Internship') AS programme FROM enrolments e
    JOIN customers c ON c.id = e.customer_id LEFT JOIN training_courses t ON t.id = e.course_id
    WHERE e.status IN ('enrolled', 'completed') AND e.fee > e.amount_paid ORDER BY balance DESC LIMIT 6")->fetchAll();
$funnel = [['label' => 'Enquiry', 'value' => $counts['enquiry']], ['label' => 'Enrolled', 'value' => $counts['enrolled']], ['label' => 'Completed', 'value' => $counts['completed']], ['label' => 'Dropped', 'value' => $counts['dropped']]];
$fMax = max(1, ...array_column($funnel, 'value'));

$changeHtml = function (?float $c): string {
    if ($c === null) return '<span class="dash-change is-flat">No earlier data</span>';
    return '<span class="dash-change ' . ($c >= 0 ? 'is-up' : 'is-down') . '">' . ($c >= 0 ? '▲' : '▼') . ' ' . number_format(abs($c), 1) . '% <small>vs previous</small></span>';
};

$pageTitle = 'Training';
require __DIR__ . '/../partials/admin_header.php';
?>

<section class="dash-head">
    <div><p class="admin-kicker">Training &amp; Internship</p><h1>Training</h1></div>
    <nav class="dash-range" aria-label="Date range">
        <?php foreach (DASH_RANGES as $key => $label): ?>
            <a href="<?= path('admin/training') ?>?range=<?= e($key) ?>"<?= $key === $range ? ' class="is-active" aria-current="true"' : '' ?>><?= e(['7d' => '7 days', '30d' => '30 days', '90d' => '90 days', '12m' => '12 months'][$key]) ?></a>
        <?php endforeach; ?>
    </nav>
</section>

<nav class="admin-quick" aria-label="Training shortcuts">
    <a class="admin-quick-link glass-dark" href="<?= path('admin/students/new') ?>">Add student</a>
    <a class="admin-quick-link glass-dark" href="<?= path('admin/students/new') ?>?kind=internship">Add intern</a>
    <a class="admin-quick-link glass-dark" href="<?= path('admin/courses/new') ?>">Add course</a>
    <a class="admin-quick-link glass-dark is-plain" href="<?= path('admin/students') ?>">All students</a>
</nav>

<div class="dash-kpis">
    <a class="dash-kpi glass-dark" href="<?= path('admin/students') ?>?status=enrolled"><p class="dash-kpi-label">Active students</p><p class="dash-kpi-value"><?= (int) $counts['enrolled'] ?></p><p class="dash-kpi-note"><?= $interns ?> of them interns</p></a>
    <a class="dash-kpi glass-dark" href="<?= path('admin/students') ?>?status=enquiry"><p class="dash-kpi-label">Open enquiries</p><p class="dash-kpi-value<?= $counts['enquiry'] ? ' is-warn' : '' ?>"><?= (int) $counts['enquiry'] ?></p><p class="dash-kpi-note"><?= $newNow ?> new in <?= e(strtolower(DASH_RANGES[$range])) ?></p></a>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Fees received</p><p class="dash-kpi-value"><?= e(naira_short($feesTotal)) ?></p><?= $changeHtml(dashboard_change($feesTotal, $feesPrev)) ?></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Fees outstanding</p><p class="dash-kpi-value<?= $outstanding > 0 ? ' is-warn' : '' ?>"><?= e(naira_short($outstanding)) ?></p><p class="dash-kpi-note">Completion rate <?= $completion === null ? '—' : round($completion) . '%' ?></p></div>
</div>

<div class="dash-grid dash-grid-2">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Fees received</h2><p class="dash-card-sub"><?= e(DASH_RANGES[$range]) ?> · by <?= e($period['unit']) ?></p></div><strong class="dash-figure-sm"><?= e(naira_short($feesTotal)) ?></strong></header>
        <?= chart_bars($labels, $fees, ['color' => CHART_SERIES[3], 'format' => fn($v) => naira_short((float) $v), 'integer' => false, 'width' => 460, 'height' => 220, 'label' => 'Training fees received']) ?>
    </section>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Student journey</h2><p class="dash-card-sub">All enrolments by status</p></div></header>
        <ol class="dash-funnel">
            <?php foreach ($funnel as $f): ?>
                <li data-tip="<?= e($f['label'] . ': ' . $f['value']) ?>"><span class="dash-funnel-label"><?= e($f['label']) ?></span><span class="dash-funnel-track"><span class="dash-funnel-bar" style="width:<?= $f['value'] ? max(6, round($f['value'] / $fMax * 100)) : 0 ?>%"></span></span><span class="dash-funnel-value"><?= (int) $f['value'] ?></span><span class="dash-funnel-pct"></span></li>
            <?php endforeach; ?>
        </ol>
    </section>
</div>

<section class="dash-card glass-dark">
    <header class="dash-card-head"><div><h2>By course</h2><p class="dash-card-sub">Enquiries, students and fees per programme</p></div><a class="dash-link" href="<?= path('admin/courses') ?>">Courses →</a></header>
    <?php if ($byCourse): ?>
        <div class="dash-table-wrap"><table class="dash-table">
            <thead><tr><th>Programme</th><th>Enquiries</th><th>Enrolled</th><th>Completed</th><th>Fees paid</th></tr></thead>
            <tbody><?php foreach ($byCourse as $c): ?><tr><td><?= e($c['name']) ?></td><td><?= (int) $c['enquiries'] ?></td><td><?= (int) $c['enrolled'] ?></td><td><?= (int) $c['completed'] ?></td><td><?= format_naira((float) $c['paid']) ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
    <?php else: ?><p class="dash-empty">No students yet — add enquiries and enrolments under Students &amp; Interns.</p><?php endif; ?>
</section>

<div class="dash-grid dash-grid-2">
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Follow up these enquiries</h2></div><a class="dash-link" href="<?= path('admin/students') ?>?status=enquiry">All →</a></header>
        <?php foreach ($enquiries as $q): ?><a class="crm-mini" href="<?= path('admin/students/' . (int) $q['id']) ?>"><span><?= e($q['name']) ?><small><?= e($q['programme']) ?><?= $q['phone'] ? ' · ' . e($q['phone']) : '' ?> · <?= e((new DateTimeImmutable($q['created_at']))->format('j M')) ?></small></span><strong>→</strong></a><?php endforeach; ?>
        <?php if (!$enquiries): ?><p class="dash-empty">No open enquiries.</p><?php endif; ?>
    </section>
    <section class="dash-card glass-dark">
        <header class="dash-card-head"><div><h2>Fees owed</h2></div></header>
        <?php foreach ($owing as $o): ?><a class="crm-mini" href="<?= path('admin/students/' . (int) $o['id']) ?>"><span><?= e($o['name']) ?><small><?= e($o['programme']) ?></small></span><strong><?= e(naira_short((float) $o['balance'])) ?></strong></a><?php endforeach; ?>
        <?php if (!$owing): ?><p class="dash-empty">Nobody owes fees.</p><?php endif; ?>
        <header class="dash-card-head" style="margin-top:16px;"><div><h2>Upcoming intakes</h2></div></header>
        <?php foreach ($intakes as $i): ?><div class="crm-mini"><span><?= e($i['name']) ?><small><?= e((new DateTimeImmutable($i['start_date']))->format('D j M Y')) ?></small></span><strong><?= (int) $i['confirmed'] ?>/<?= (int) $i['n'] ?></strong></div><?php endforeach; ?>
        <?php if (!$intakes): ?><p class="dash-empty">No upcoming start dates.</p><?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
