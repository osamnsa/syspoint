<?php
declare(strict_types=1);

$adminUser = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete') {
        db()->prepare('DELETE FROM events WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'Event deleted.');
    } elseif (in_array($action, ['announce', 'remind'], true) && telegram_can_post() && ($d = telegram_draft('event', $id, $action === 'remind'))) {
        $r = telegram_announce($d['title'], $d['body'], $d['photo_path'] ?: null, $d['button_text'], $d['button_url'], 'event', $id);
        $r['ok'] ? flash('success', ($action === 'remind' ? 'Reminder posted to ' : 'Posted to ') . telegram_config()['channel'] . '.')
                 : flash('warning', 'Not posted: ' . $r['error']);
    }
    header('Location: ' . path('admin/events'));
    exit;
}

$show = ($_GET['show'] ?? '') === 'past' ? 'past' : 'upcoming';
$now = date('Y-m-d H:i:s');
$endExpr = "COALESCE(ends_at, TIMESTAMP(DATE(starts_at), '23:59:59'))";
$stmt = db()->prepare($show === 'past'
    ? "SELECT * FROM events WHERE $endExpr < :now ORDER BY starts_at DESC LIMIT 100"
    : "SELECT * FROM events WHERE $endExpr >= :now ORDER BY starts_at ASC");
$stmt->execute(['now' => $now]);
$events = $stmt->fetchAll();
$upcomingCount = (int) db()->query("SELECT COUNT(*) FROM events WHERE $endExpr >= " . db()->quote($now))->fetchColumn();
$canPost = telegram_can_post();

$pageTitle = 'Events';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Website</p><h1>Events</h1></div>
    <div class="admin-header-actions">
        <a href="<?= path('events') ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">View on site ↗</a>
        <a href="<?= path('admin/events/new') ?>" class="btn btn-primary btn-sm">Schedule an Event</a>
    </div>
</div>

<div class="admin-toolbar">
    <nav class="admin-filters" aria-label="Filter">
        <a href="<?= path('admin/events') ?>"<?= $show === 'upcoming' ? ' class="is-active"' : '' ?>>Upcoming (<?= $upcomingCount ?>)</a>
        <a href="<?= path('admin/events') ?>?show=past"<?= $show === 'past' ? ' class="is-active"' : '' ?>>Past</a>
    </nav>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th></th><th>Event</th><th>When</th><th>Type</th><th>Price</th><th>Telegram</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($events as $ev):
            $last = telegram_last_post('event', (int) $ev['id']); ?>
            <tr class="is-clickable" data-href="<?= path('admin/events/' . (int) $ev['id'] . '/edit') ?>">
                <td><?php if ($ev['image_path']): ?><img src="<?= e(media_url($ev['image_path'])) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;"><?php endif; ?></td>
                <td><strong><?= e($ev['title']) ?></strong><?= $ev['is_active'] ? '' : ' <span class="badge badge-muted">Hidden</span>' ?><?= $ev['venue'] ? '<br><small class="muted">' . e($ev['venue']) . '</small>' : '' ?></td>
                <td style="white-space:nowrap;"><?= e(event_when($ev)) ?></td>
                <td><?= e(EVENT_KINDS[$ev['kind']] ?? $ev['kind']) ?></td>
                <td><?= e(event_price_label($ev) ?: '—') ?></td>
                <td style="white-space:nowrap;"><?= $last ? '<span class="badge badge-success">Posted</span><br><small class="muted">' . e((new DateTimeImmutable($last['created_at']))->format('j M, g:i A')) . '</small>' : '<span class="badge badge-muted">Not yet</span>' ?></td>
                <td style="white-space:nowrap;">
                    <?php if ($canPost && $show === 'upcoming' && $ev['is_active']): ?>
                        <form method="post" style="display:inline;" data-confirm="<?= $last ? 'Post a reminder' : 'Announce this' ?> on <?= e(telegram_config()['channel']) ?>?" data-confirm-button="Post">
                            <?= csrf_field() ?><input type="hidden" name="action" value="<?= $last ? 'remind' : 'announce' ?>"><input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm"><?= $last ? 'Send reminder' : 'Announce' ?></button>
                        </form>
                        <?php if (admin_can('website')): ?><a href="<?= path('admin/telegram') ?>?event=<?= (int) $ev['id'] ?><?= $last ? '&amp;reminder=1' : '' ?>" class="btn btn-outline btn-sm" title="Change the wording or photo before posting">Edit first</a><?php endif; ?>
                    <?php endif; ?>
                    <form method="post" style="display:inline;" data-confirm="Delete “<?= e($ev['title']) ?>”? It disappears from the website." data-confirm-button="Delete" data-confirm-danger>
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$events): ?>
            <tr><td colspan="7" class="admin-empty"><?= $show === 'past' ? 'No past events.' : 'Nothing scheduled. <a href="' . path('admin/events/new') . '">Schedule an event</a> — it goes on the website and, if you like, straight to Telegram.' ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
