<?php
declare(strict_types=1);

$adminUser = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    notifications_mark_read($adminUser, ($_POST['action'] ?? '') === 'all' ? null : ((int) ($_POST['id'] ?? 0) ?: null));
    header('Location: ' . path('admin/notifications') . (isset($_GET['view']) ? '?view=' . rawurlencode((string) $_GET['view']) : ''));
    exit;
}

$view = ($_GET['view'] ?? '') === 'unread' ? 'unread' : 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$items = notifications_list($adminUser, 51, $view === 'unread', ($page - 1) * 50);
$more = count($items) > 50; $items = array_slice($items, 0, 50);
$unread = notifications_unread_count($adminUser);
$labels = ['order_paid' => 'Order', 'booking' => 'Booking', 'software_request' => 'Software Clinic', 'contact' => 'Message', 'low_stock' => 'Stock', 'security' => 'Security'];

$pageTitle = 'Notifications';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Inbox</p><h1>Notifications</h1></div>
    <?php if ($unread): ?>
        <form method="post" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="action" value="all"><button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark">Mark all as read</button></form>
    <?php endif; ?>
</div>

<nav class="admin-filters" aria-label="Filter">
    <a href="<?= path('admin/notifications') ?>"<?= $view === 'all' ? ' class="is-active"' : '' ?>>All</a>
    <a href="<?= path('admin/notifications') ?>?view=unread"<?= $view === 'unread' ? ' class="is-active"' : '' ?>>Unread (<?= $unread ?>)</a>
</nav>

<div class="admin-notif-list">
    <?php foreach ($items as $n): ?>
        <div class="admin-notif glass-dark<?= $n['read_at'] ? '' : ' is-unread' ?>">
            <span class="admin-notif-icon is-<?= e($n['event']) ?>"><?= admin_icon(NOTIFY_EVENTS[$n['event']][1] ?? 'bell') ?></span>
            <div class="admin-notif-body">
                <p class="admin-notif-meta"><span><?= e($labels[$n['event']] ?? ucfirst($n['event'])) ?></span> · <?= e(notification_time($n['created_at'])) ?></p>
                <strong><?= e($n['title']) ?></strong>
                <?php if ($n['body']): ?><p><?= e($n['body']) ?></p><?php endif; ?>
            </div>
            <div class="admin-notif-actions">
                <?php if ($n['link']): ?><a href="<?= path($n['link']) ?>" class="btn btn-primary btn-sm" data-notif-open="<?= (int) $n['id'] ?>">Open</a><?php endif; ?>
                <?php if (!$n['read_at']): ?>
                    <form method="post" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $n['id'] ?>"><button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark">Mark read</button></form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$items): ?><div class="admin-empty glass-dark" style="padding:28px;border-radius:16px;"><?= $view === 'unread' ? 'You’re all caught up.' : 'No notifications yet. New orders, bookings, requests, messages and stock alerts will appear here.' ?></div><?php endif; ?>
</div>
<?php if ($page > 1 || $more): ?>
    <nav class="admin-filters" style="margin-top:16px;">
        <?php if ($page > 1): ?><a href="?<?= http_build_query(['view' => $view === 'unread' ? 'unread' : null, 'page' => $page - 1]) ?>">← Newer</a><?php endif; ?>
        <?php if ($more): ?><a href="?<?= http_build_query(['view' => $view === 'unread' ? 'unread' : null, 'page' => $page + 1]) ?>">Older →</a><?php endif; ?>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
