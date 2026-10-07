<?php
declare(strict_types=1);

$adminUser = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && ($_POST['action'] ?? '') === 'move') {
    $deal = deal_by_id((int) ($_POST['id'] ?? 0));
    $stage = (string) ($_POST['stage'] ?? '');
    if ($deal && isset(DEAL_STAGES[$stage])) {
        deal_set_stage($deal, $stage, trim((string) ($_POST['lost_reason'] ?? '')) ?: null);
    }
    if (($_POST['ajax'] ?? '') === '1') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => (bool) $deal]);
        exit;
    }
    header('Location: ' . path('admin/deals'));
    exit;
}

$owner = (string) ($_GET['owner'] ?? '');
$where = $owner !== '' && ctype_digit($owner) ? 'AND d.owner_id = ' . (int) $owner : '';
$deals = db()->query("SELECT d.*, c.name AS customer_name, u.name AS owner_name FROM deals d JOIN customers c ON c.id = d.customer_id
    LEFT JOIN users u ON u.id = d.owner_id
    WHERE (d.stage IN ('lead', 'contacted', 'proposal', 'negotiation') OR d.closed_at >= NOW() - INTERVAL 30 DAY) $where
    ORDER BY d.expected_close IS NULL, d.expected_close, d.value DESC")->fetchAll();
$byStage = array_fill_keys(array_keys(DEAL_STAGES), []);
foreach ($deals as $d) $byStage[$d['stage']][] = $d;

$open = array_filter($deals, fn($d) => in_array($d['stage'], DEAL_OPEN_STAGES, true));
$pipeline = array_sum(array_column($open, 'value'));
$weighted = array_sum(array_map(fn($d) => $d['value'] * DEAL_STAGES[$d['stage']]['chance'] / 100, $open));
$closed90 = db()->query("SELECT SUM(stage = 'won') won, SUM(stage = 'lost') lost, COALESCE(SUM(IF(stage = 'won', value, 0)), 0) won_value FROM deals WHERE closed_at >= NOW() - INTERVAL 90 DAY $where")->fetch();
$winRate = ($closed90['won'] + $closed90['lost']) > 0 ? round($closed90['won'] / ($closed90['won'] + $closed90['lost']) * 100) : null;
$staff = crm_staff();

$pageTitle = 'Deals Pipeline';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">CRM</p><h1>Deals Pipeline</h1></div>
    <div class="admin-header-actions">
        <a href="<?= path('admin/deals/new') ?>" class="btn btn-primary btn-sm">New Deal</a>
    </div>
</div>

<div class="pos-today">
    <div><span>Open pipeline</span><strong><?= format_naira($pipeline) ?></strong><small><?= count($open) ?> deals</small></div>
    <div><span>Weighted forecast</span><strong><?= format_naira($weighted) ?></strong><small>Value × stage likelihood</small></div>
    <div><span>Won, last 90 days</span><strong><?= format_naira((float) $closed90['won_value']) ?></strong><small><?= (int) $closed90['won'] ?> deals</small></div>
    <div><span>Win rate, 90 days</span><strong><?= $winRate === null ? '—' : $winRate . '%' ?></strong><small><?= (int) $closed90['won'] ?> won · <?= (int) $closed90['lost'] ?> lost</small></div>
</div>

<nav class="admin-filters" aria-label="Filter by owner">
    <a href="<?= path('admin/deals') ?>"<?= $owner === '' ? ' class="is-active"' : '' ?>>Everyone’s deals</a>
    <?php foreach ($staff as $u): ?><a href="<?= path('admin/deals') ?>?owner=<?= (int) $u['id'] ?>"<?= $owner === (string) $u['id'] ? ' class="is-active"' : '' ?>><?= e($u['name']) ?></a><?php endforeach; ?>
</nav>

<div class="crm-board" data-board data-csrf="<?= e(csrf_token()) ?>" data-action="<?= path('admin/deals') ?>">
    <?php foreach (DEAL_STAGES as $key => $stage):
        $cards = $byStage[$key];
        $sum = array_sum(array_column($cards, 'value')); ?>
        <section class="crm-col crm-col-<?= $key ?>" data-stage="<?= $key ?>">
            <header><strong><?= e($stage['label']) ?></strong><span><?= count($cards) ?> · <?= e(naira_short($sum)) ?></span><?= in_array($key, ['won', 'lost'], true) ? '<small>last 30 days</small>' : '' ?></header>
            <div class="crm-col-cards" data-dropzone>
                <?php foreach ($cards as $d): $late = in_array($d['stage'], DEAL_OPEN_STAGES, true) && $d['expected_close'] && $d['expected_close'] < date('Y-m-d'); ?>
                    <article class="crm-card glass-dark" draggable="true" data-deal="<?= (int) $d['id'] ?>">
                        <a href="<?= path('admin/deals/' . (int) $d['id']) ?>" class="crm-card-title"><?= e($d['title']) ?></a>
                        <p class="crm-card-customer"><?= e($d['customer_name']) ?></p>
                        <p class="crm-card-value"><?= format_naira((float) $d['value']) ?></p>
                        <p class="crm-card-meta"><?= e(DEAL_SERVICES[$d['service']]) ?><?= $d['expected_close'] ? ' · <span class="' . ($late ? 'is-late' : '') . '">' . e((new DateTimeImmutable($d['expected_close']))->format('j M')) . '</span>' : '' ?><?= $d['owner_name'] ? ' · ' . e(initials($d['owner_name'])) : '' ?></p>
                        <form method="post" class="crm-card-move">
                            <?= csrf_field() ?><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                            <select name="stage" aria-label="Move to stage" onchange="this.form.submit()">
                                <?php foreach (DEAL_STAGES as $k => $s): ?><option value="<?= $k ?>" <?= $k === $d['stage'] ? 'selected' : '' ?>><?= e($s['label']) ?></option><?php endforeach; ?>
                            </select>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
