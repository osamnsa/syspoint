<?php
declare(strict_types=1);

$adminUser = require_admin();

$days = 30;
$since = date('Y-m-d H:i:s', strtotime("-$days days"));
$stmt = db()->prepare('SELECT action, COUNT(*) n, SUM(analytics) an, SUM(marketing) mk FROM cookie_consents WHERE created_at >= :s GROUP BY action');
$stmt->execute(['s' => $since]);
$by = ['accept_all' => 0, 'reject_all' => 0, 'custom' => 0];
$analytics = $marketing = 0;
foreach ($stmt->fetchAll() as $r) { $by[$r['action']] = (int) $r['n']; $analytics += (int) $r['an']; $marketing += (int) $r['mk']; }
$total = array_sum($by);
$pct = fn(int $n) => $total ? round($n * 100 / $total) . '%' : '—';

$log = db()->query('SELECT * FROM cookie_consents ORDER BY id DESC LIMIT 100')->fetchAll();
$labels = ['accept_all' => ['Accepted all', 'badge-success'], 'reject_all' => ['Rejected all', 'badge-muted'], 'custom' => ['Customised', 'badge-warning']];
$ga = cookie_ga_id();
$px = cookie_pixel_id();

$pageTitle = 'Cookie Consent';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Website</p><h1>Cookie Consent</h1></div>
    <div class="admin-header-actions">
        <a href="<?= path('admin/website') ?>?group=cookies" class="btn btn-outline btn-sm admin-btn-on-dark">Banner &amp; tracking settings</a>
        <a href="<?= path('cookie-policy') ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm admin-btn-on-dark">View Cookie Policy ↗</a>
    </div>
</div>

<div class="dash-kpis">
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Choices recorded</p><p class="dash-kpi-value"><?= $total ?></p><p class="dash-kpi-note">last <?= $days ?> days</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Accepted all</p><p class="dash-kpi-value"><?= $pct($by['accept_all']) ?></p><p class="dash-kpi-note"><?= $by['accept_all'] ?> visitors</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Rejected all</p><p class="dash-kpi-value"><?= $pct($by['reject_all']) ?></p><p class="dash-kpi-note"><?= $by['reject_all'] ?> visitors</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Allowed analytics</p><p class="dash-kpi-value"><?= $pct($analytics) ?></p><p class="dash-kpi-note">marketing: <?= $pct($marketing) ?></p></div>
</div>

<section class="dash-card glass-dark" style="margin-bottom:18px;"><p style="margin:0;font-size:0.9rem;">
    Google Analytics: <strong><?= $ga ? e($ga) . ' — loads only for visitors who allow Analytics' : 'not set up' ?></strong> ·
    Meta Pixel: <strong><?= $px ? e($px) . ' — loads only for visitors who allow Marketing' : 'not set up' ?></strong>.
    Each choice is kept as proof of consent with a random ID and a scrambled IP address — never the address itself.
</p></section>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>When</th><th>Choice</th><th>Analytics</th><th>Marketing</th><th>Functional</th><th>Consent ID</th><th>Page</th><th>Browser</th></tr></thead>
        <tbody>
        <?php foreach ($log as $r): [$l, $b] = $labels[$r['action']] ?? [$r['action'], 'badge-muted']; ?>
            <tr>
                <td style="white-space:nowrap;"><?= e((new DateTimeImmutable($r['created_at']))->format('j M Y, g:i A')) ?></td>
                <td style="white-space:nowrap;"><span class="badge <?= $b ?>"><?= e($l) ?></span></td>
                <td><?= $r['analytics'] ? 'Yes' : 'No' ?></td>
                <td><?= $r['marketing'] ? 'Yes' : 'No' ?></td>
                <td><?= $r['functional'] ? 'Yes' : 'No' ?></td>
                <td><code style="font-size:0.78rem;"><?= e(substr($r['consent_id'], 0, 8)) ?>…</code></td>
                <td><?= e($r['page'] ?: '/') ?></td>
                <td><small class="muted"><?= e(mb_strimwidth((string) $r['user_agent'], 0, 60, '…')) ?></small></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$log): ?><tr><td colspan="8" class="admin-empty">No choices recorded yet. They appear here as visitors use the cookie banner.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
