<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$id = (int) ($params[0] ?? 0);
$s = db()->prepare('SELECT * FROM contact_messages WHERE id = :id');
$s->execute(['id' => $id]);
$msg = $s->fetch();
if (!$msg) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}
if (!$msg['read_at']) {
    db()->prepare('UPDATE contact_messages SET read_at = NOW() WHERE id = :id')->execute(['id' => $id]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    if (($_POST['action'] ?? '') === 'unread') {
        db()->prepare('UPDATE contact_messages SET read_at = NULL WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'Marked as unread.');
        header('Location: ' . path('admin/messages'));
        exit;
    }
    if (($_POST['action'] ?? '') === 'link') {
        crm_link('contact_messages', $id, $msg['name'], $msg['email'], null);
        flash('success', 'Linked to a customer record.');
        header('Location: ' . path('admin/messages/' . $id));
        exit;
    }
}
$customer = $msg['customer_id'] ? crm_customer_by_id((int) $msg['customer_id']) : null;

$pageTitle = $msg['subject'] ?: 'Message from ' . $msg['name'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Message · <?= e((new DateTimeImmutable($msg['created_at']))->format('j M Y, g:i A')) ?></p><h1><?= e($msg['subject'] ?: '(no subject)') ?></h1></div>
    <div class="admin-header-actions">
        <a href="mailto:<?= e($msg['email']) ?>?subject=<?= rawurlencode('Re: ' . ($msg['subject'] ?: 'Your message to Syspoint')) ?>" class="btn btn-primary btn-sm">Reply by email</a>
        <form method="post" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="action" value="unread"><button type="submit" class="btn btn-outline btn-sm">Mark unread</button></form>
        <a href="<?= path('admin/messages') ?>" class="btn btn-outline btn-sm">All Messages</a>
    </div>
</div>

<div class="crm-layout">
    <div class="card admin-doc">
        <p class="admin-doc-meta" style="display:block;"><strong><?= e($msg['name']) ?></strong> &lt;<?= e($msg['email']) ?>&gt;</p>
        <div class="message-body"><?= nl2br(e($msg['message'])) ?></div>
    </div>
    <aside class="crm-side">
        <section class="dash-card glass-dark">
            <header class="dash-card-head"><div><h2>Customer</h2></div></header>
            <?php if ($customer): ?>
                <a class="crm-mini" href="<?= path('admin/customers/' . (int) $customer['id']) ?>"><span><?= e($customer['name']) ?><small><?= e($customer['email'] ?? '') ?></small></span><strong>Open →</strong></a>
                <a class="dash-link" href="<?= path('admin/deals/new') ?>?customer=<?= (int) $customer['id'] ?>">Start a deal from this →</a>
            <?php else: ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="link"><button type="submit" class="btn btn-primary btn-sm">Add to customers</button></form>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
