<?php
declare(strict_types=1);

$adminUser = require_admin();

$unreadMessages = (int) db()->query("SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL")->fetchColumn();
$pendingOrders = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$newRequests = (int) db()->query("SELECT COUNT(*) FROM software_requests WHERE status = 'new'")->fetchColumn();
$pendingBookings = (int) db()->query("SELECT COUNT(*) FROM room_bookings WHERE status = 'pending'")->fetchColumn();

$recentMessages = db()->query(
    "SELECT id, name, subject, created_at, read_at FROM contact_messages ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/../partials/admin_header.php';
?>

<?php
$firstName = explode(' ', (string) ($adminUser['name'] ?? ''))[0] ?: 'there';
$dashStats = [
    ['label' => 'Unread Messages', 'value' => $unreadMessages, 'href' => path('admin') . '#recent-messages'],
    ['label' => 'Pending Orders', 'value' => $pendingOrders, 'href' => path('admin/orders')],
    ['label' => 'New Software Requests', 'value' => $newRequests, 'href' => path('admin/software-requests')],
    ['label' => 'Pending Bookings', 'value' => $pendingBookings, 'href' => path('admin/bookings')],
];
$quickActions = [
    ['label' => 'Add product', 'href' => path('admin/products/new')],
    ['label' => 'Add client', 'href' => path('admin/businesses/new')],
    ['label' => 'Add course', 'href' => path('admin/courses/new')],
    ['label' => 'Add testimonial', 'href' => path('admin/testimonials/new')],
    ['label' => 'Home stats', 'href' => path('admin/home-stats')],
];
?>
<section class="admin-dash-hero">
    <p class="admin-kicker"><?= e((new DateTimeImmutable())->format('l, j F Y')) ?></p>
    <h1>Welcome back, <em><?= e($firstName) ?></em></h1>
    <p class="admin-dash-lede">Here’s what needs your attention across the Hub, the Gadget Store and the Software Clinic.</p>
</section>

<div class="admin-stats">
    <?php foreach ($dashStats as $stat): ?>
        <a class="admin-stat glass-dark<?= $stat['value'] > 0 ? ' is-hot' : '' ?>" href="<?= $stat['href'] ?>">
            <div class="admin-stat-label"><?= e($stat['label']) ?></div>
            <div class="admin-stat-value"><?= (int) $stat['value'] ?></div>
            <div class="admin-stat-hint"><?= $stat['value'] > 0 ? 'Needs attention &rarr;' : 'All clear' ?></div>
        </a>
    <?php endforeach; ?>
</div>

<nav class="admin-quick" aria-label="Quick actions">
    <?php foreach ($quickActions as $action): ?>
        <a class="admin-quick-link glass-dark" href="<?= $action['href'] ?>"><?= e($action['label']) ?></a>
    <?php endforeach; ?>
</nav>

<div class="admin-header-row" id="recent-messages">
    <h2 style="font-size:1.1rem;">Recent Messages</h2>
</div>
<div class="admin-table-wrap">
    <?php if ($recentMessages): ?>
        <table class="admin-table">
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
    <?php else: ?>
        <div class="admin-empty">No messages yet.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
