<?php
declare(strict_types=1);

$adminUser = require_admin();

$view = (string) ($_GET['view'] ?? '');
$where = $view === 'spam' ? 'WHERE m.is_spam = 1' : ($view === 'unread' ? 'WHERE m.read_at IS NULL AND m.is_spam = 0' : 'WHERE m.is_spam = 0');
$messages = db()->query("SELECT m.*, c.name AS customer_name FROM contact_messages m LEFT JOIN customers c ON c.id = m.customer_id $where ORDER BY m.id DESC LIMIT 300")->fetchAll();
$unread = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL AND is_spam = 0')->fetchColumn();
$spamCount = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_spam = 1')->fetchColumn();

$pageTitle = 'Messages';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row"><div><p class="admin-kicker">CRM</p><h1>Messages</h1></div></div>

<nav class="admin-filters" aria-label="Filter">
    <a href="<?= path('admin/messages') ?>"<?= $view === '' ? ' class="is-active"' : '' ?>>All</a>
    <a href="<?= path('admin/messages') ?>?view=unread"<?= $view === 'unread' ? ' class="is-active"' : '' ?>>Unread (<?= $unread ?>)</a>
    <a href="<?= path('admin/messages') ?>?view=spam"<?= $view === 'spam' ? ' class="is-active"' : '' ?>>Spam (<?= $spamCount ?>)</a>
</nav>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th></th><th>From</th><th>Subject</th><th>Received</th></tr></thead>
        <tbody>
        <?php foreach ($messages as $m): ?>
            <tr class="is-clickable<?= $m['read_at'] ? '' : ' is-unread' ?>" data-href="<?= path('admin/messages/' . (int) $m['id']) ?>">
                <td><?= $m['is_spam'] ? '<span class="badge badge-danger">Spam</span>' : ($m['read_at'] ? '' : '<span class="badge badge-warning">New</span>') ?></td>
                <td><a href="<?= path('admin/messages/' . (int) $m['id']) ?>"><strong><?= e($m['name']) ?></strong></a><br><small class="muted"><?= e($m['email']) ?></small></td>
                <td><?= e($m['subject'] ?: '(no subject)') ?><br><small class="muted"><?= e(mb_strimwidth($m['message'], 0, 90, '…')) ?></small><?= $m['is_spam'] && $m['spam_reason'] ? '<br><small class="muted">Why: ' . e($m['spam_reason']) . '</small>' : '' ?></td>
                <td style="white-space:nowrap;"><?= e((new DateTimeImmutable($m['created_at']))->format('j M, g:i A')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$messages): ?><tr><td colspan="4" class="admin-empty"><?= $view === 'spam' ? 'No spam. The filter drops most bots before they reach this folder.' : 'No messages.' ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
