<?php
declare(strict_types=1);

$adminUser = require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'block') {
            $err = blocklist_add((string) ($_POST['type'] ?? ''), (string) ($_POST['value'] ?? ''), trim((string) ($_POST['reason'] ?? '')) ?: null);
            if ($err) $errors[] = $err;
            else { flash('success', 'Blocked. Anything from it is now dropped silently.'); header('Location: ' . path('admin/security') . '#blocklist'); exit; }
        } elseif ($action === 'unblock') {
            db()->prepare('DELETE FROM blocklist WHERE id = :id')->execute(['id' => (int) ($_POST['id'] ?? 0)]);
            flash('success', 'Removed from the blocklist.');
            header('Location: ' . path('admin/security') . '#blocklist');
            exit;
        }
    }
}

// --- Security checklist ------------------------------------------------------
$defaultAdmin = db()->query("SELECT password_hash FROM users WHERE email = 'admin@syspoint.example' AND is_active = 1")->fetchColumn();
$admins = db()->query("SELECT id, email, password_hash FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll();
$weakDefault = false;
foreach ($admins as $a) if (password_verify('SyspointAdmin123!', $a['password_hash'])) $weakDefault = true;
$tg = telegram_config();
$checks = [
    [request_is_https(), 'Site served over HTTPS', 'Turn on SSL (cPanel → SSL/TLS Status → Run AutoSSL) and open the site with https://.'],
    [!$weakDefault, 'No admin uses the default password', 'Change it in Settings → My Account (the default password is published in the setup notes).'],
    [!$defaultAdmin, 'The default admin@syspoint.example login is gone', 'Give the admin account your real email in My Account, or deactivate it once you have another admin.'],
    [is_file(__DIR__ . '/../../../public/uploads/.htaccess'), 'Uploads folder can’t run scripts', 'public/uploads/.htaccess is missing — pull the latest code.'],
    [!ini_get('display_errors') || ini_get('display_errors') === 'Off' || ini_get('display_errors') === '0', 'PHP errors are hidden from visitors', 'Turn off display_errors in cPanel → MultiPHP INI Editor.'],
    [version_compare(PHP_VERSION, '8.1', '>='), 'PHP is up to date (' . PHP_VERSION . ')', 'Switch to PHP 8.2 or newer in cPanel → MultiPHP Manager.'],
    [$tg['staff_chat'] !== '' && in_array('security', $tg['events'], true), 'Telegram alerts for repeated failed sign-ins', 'Tick “Someone keeps failing to sign in” in Telegram → Settings.'],
];

// --- Activity -----------------------------------------------------------------
$since = "created_at > NOW() - INTERVAL 7 DAY";
$counts = db()->query("SELECT verdict, COUNT(*) FROM spam_log WHERE $since GROUP BY verdict")->fetchAll(PDO::FETCH_KEY_PAIR);
$failed7 = (int) db()->query("SELECT COUNT(*) FROM login_attempts WHERE $since")->fetchColumn();
$stopped = db()->query("SELECT * FROM spam_log WHERE verdict <> 'ok' ORDER BY id DESC LIMIT 40")->fetchAll();
$logins = db()->query("SELECT ip, COUNT(*) n, MAX(created_at) last_at, GROUP_CONCAT(DISTINCT email ORDER BY email SEPARATOR ', ') emails
    FROM login_attempts WHERE created_at > NOW() - INTERVAL 7 DAY GROUP BY ip ORDER BY last_at DESC LIMIT 20")->fetchAll();
$blocked = db()->query('SELECT b.*, u.name AS by_name FROM blocklist b LEFT JOIN users u ON u.id = b.created_by ORDER BY b.id DESC')->fetchAll();
$blockedSet = [];
foreach ($blocked as $b) $blockedSet[$b['type'] . ':' . $b['value']] = true;
$forms = ['contact' => 'Contact form', 'software_request' => 'Software Clinic', 'booking' => 'Room booking'];

$blockBtn = function (string $type, ?string $value, string $label) use ($blockedSet): string {
    if (!$value) return '';
    $v = blocklist_normalise($type, $value);
    if ($v === null) return '';
    if (isset($blockedSet[$type . ':' . $v])) return '<span class="badge badge-muted">' . e($label) . ' blocked</span>';
    return '<form method="post" style="display:inline;margin:0;" data-confirm="Block ' . e($v) . '?" data-confirm-button="Block" data-confirm-danger>' . csrf_field()
        . '<input type="hidden" name="action" value="block"><input type="hidden" name="type" value="' . $type . '"><input type="hidden" name="value" value="' . e($v) . '">'
        . '<input type="hidden" name="reason" value="Blocked from the spam log"><button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark">Block ' . e($label) . '</button></form>';
};

$pageTitle = 'Security & Spam';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row"><div><p class="admin-kicker">Settings</p><h1>Security &amp; Spam</h1></div></div>

<?php if ($errors): ?><div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="dash-kpis">
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Spam &amp; bots stopped</p><p class="dash-kpi-value"><?= (int) ($counts['blocked'] ?? 0) + (int) ($counts['spam'] ?? 0) ?></p><p class="dash-kpi-note">last 7 days</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Sent to the Spam folder</p><p class="dash-kpi-value"><?= (int) ($counts['spam'] ?? 0) ?></p><p class="dash-kpi-note">for you to review</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Genuine submissions</p><p class="dash-kpi-value"><?= (int) ($counts['ok'] ?? 0) ?></p><p class="dash-kpi-note">last 7 days</p></div>
    <div class="dash-kpi glass-dark"><p class="dash-kpi-label">Failed admin sign-ins</p><p class="dash-kpi-value"><?= $failed7 ?></p><p class="dash-kpi-note">last 7 days</p></div>
</div>

<section class="dash-card glass-dark sec-panel">
    <h2>Security checklist</h2>
    <ul class="sec-checks">
        <?php foreach ($checks as [$ok, $label, $fix]): ?>
            <li class="<?= $ok ? 'is-ok' : 'is-bad' ?>"><span class="sec-mark" aria-hidden="true"><?= $ok ? '✓' : '!' ?></span><span><strong><?= e($label) ?></strong><?php if (!$ok): ?><small><?= e($fix) ?></small><?php endif; ?></span></li>
        <?php endforeach; ?>
    </ul>
    <p class="form-note" style="margin-top:12px;">Always on: spam filter on every public form (hidden trap field, signed form timer, rate limits, link / foreign-script / keyword checks, blocklist) · sign-in lockout after 5 wrong passwords · secure session cookies · protective browser headers · CSRF tokens on every form.</p>
</section>

<section class="dash-card glass-dark sec-panel" id="blocklist">
    <h2>Blocklist</h2>
    <p class="form-note">Anything sent from a blocked email, email domain or IP address is dropped silently — the sender still sees “thank you”, so they don’t just try again.</p>
    <form method="post" class="sec-add">
        <?= csrf_field() ?><input type="hidden" name="action" value="block">
        <select name="type" aria-label="What to block"><?php foreach (BLOCK_TYPES as $k => $l): ?><option value="<?= $k ?>"<?= ($_POST['type'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
        <input type="text" name="value" placeholder="spammer@example.ru · example.ru · 203.0.113.7 · 203.0.113.0/24" value="<?= e((string) ($_POST['value'] ?? '')) ?>" aria-label="Value" required>
        <input type="text" name="reason" placeholder="Reason (optional)" aria-label="Reason" maxlength="255">
        <button type="submit" class="btn btn-primary btn-sm">Block</button>
    </form>
    <div class="admin-table-wrap" style="margin-top:14px;">
        <table class="admin-table">
            <thead><tr><th>Blocked</th><th>Type</th><th>Reason</th><th>Stopped</th><th>Added</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($blocked as $b): ?>
                <tr>
                    <td><code><?= e($b['value']) ?></code></td>
                    <td><?= e(BLOCK_TYPES[$b['type']]) ?></td>
                    <td><?= e($b['reason'] ?: '—') ?></td>
                    <td><?= (int) $b['hits'] ?><?= $b['last_hit_at'] ? '<br><small class="muted">last ' . e(notification_time($b['last_hit_at'])) . '</small>' : '' ?></td>
                    <td><?= e((new DateTimeImmutable($b['created_at']))->format('j M Y')) ?><?= $b['by_name'] ? '<br><small class="muted">' . e($b['by_name']) . '</small>' : '' ?></td>
                    <td><form method="post" style="margin:0;" data-confirm="Unblock <?= e($b['value']) ?>?" data-confirm-button="Unblock"><?= csrf_field() ?><input type="hidden" name="action" value="unblock"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark">Unblock</button></form></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$blocked): ?><tr><td colspan="6" class="admin-empty">Nothing blocked yet. Use the form above, or “Mark as spam” on a message.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="dash-card glass-dark sec-panel">
    <h2>Recently stopped</h2>
    <p class="form-note">Bots and spam the filter caught. “Spam folder” items were saved for review in Messages / Software Clinic; “dropped” ones were never saved.</p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>When</th><th>Form</th><th>Why</th><th>From</th><th>Text</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($stopped as $s): ?>
                <tr>
                    <td style="white-space:nowrap;"><?= e(notification_time($s['created_at'])) ?></td>
                    <td><?= e($forms[$s['form']] ?? $s['form']) ?><br><span class="badge <?= $s['verdict'] === 'spam' ? 'badge-warning' : 'badge-danger' ?>"><?= $s['verdict'] === 'spam' ? 'Spam folder' : 'Dropped' ?></span></td>
                    <td><?= e((string) $s['reason']) ?></td>
                    <td><?= e($s['email'] ?: '—') ?><br><small class="muted"><?= e((string) $s['ip']) ?></small></td>
                    <td><small><?= e((string) $s['excerpt']) ?></small></td>
                    <td class="sec-actions"><?= $blockBtn('email', $s['email'], 'email') ?><?= $blockBtn('ip', $s['ip'], 'IP') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$stopped): ?><tr><td colspan="6" class="admin-empty">Nothing stopped yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="dash-card glass-dark sec-panel">
    <h2>Failed admin sign-ins (7 days)</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>IP address</th><th>Attempts</th><th>Emails tried</th><th>Last</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($logins as $l): ?>
                <tr><td><code><?= e($l['ip']) ?></code></td><td><?= (int) $l['n'] ?></td><td><small><?= e(mb_strimwidth((string) $l['emails'], 0, 120, '…')) ?></small></td><td><?= e(notification_time($l['last_at'])) ?></td><td><?= $blockBtn('ip', $l['ip'], 'IP') ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$logins): ?><tr><td colspan="5" class="admin-empty">No failed sign-ins.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="form-note" style="margin-top:10px;">An account is locked for 15 minutes after 5 wrong passwords, and an IP after 10. Blocking an IP here stops its form submissions; to stop it reaching the site at all, add it in cPanel → IP Blocker.</p>
</section>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
