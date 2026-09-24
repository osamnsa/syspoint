<?php
declare(strict_types=1);

$adminUser = require_admin();

$unreadMessages = (int) db()->query("SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL")->fetchColumn();

$recentMessages = db()->query(
    "SELECT id, name, subject, created_at, read_at FROM contact_messages ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Dashboard</h1>
</div>
<p style="color:var(--color-text-muted);margin-top:-14px;margin-bottom:24px;">Welcome back, <?= e(explode(' ', (string) ($adminUser['name'] ?? ''))[0] ?: 'there') ?> — Syspoint's foundation is live. Product, gaming, software-clinic, and training tools land here as each is built.</p>

<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-label">Unread Messages</div>
        <div class="admin-stat-value"><?= $unreadMessages ?></div>
    </div>
</div>

<div class="admin-header-row">
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
