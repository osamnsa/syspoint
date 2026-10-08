<?php
declare(strict_types=1);

$adminUser = require_admin();

$cfg = telegram_config();
$errors = [];
$chats = null;
$info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'save') {
            $token = trim((string) ($_POST['bot_token'] ?? ''));
            $channel = trim((string) ($_POST['channel'] ?? ''));
            $staff = trim((string) ($_POST['staff_chat'] ?? ''));
            $events = array_values(array_intersect(array_keys(TELEGRAM_EVENTS), (array) ($_POST['events'] ?? [])));
            if ($channel !== '' && !preg_match('/^(@[A-Za-z0-9_]{5,}|-?\d{5,})$/', $channel)) $errors[] = 'Channel: use its @username (e.g. @syspointhub) or its numeric ID.';
            if ($staff !== '' && !preg_match('/^-?\d{5,}$/', $staff)) $errors[] = 'Staff group: use the numeric chat ID (use “Find chat IDs” below).';
            if (!$cfg['token_from_env'] && $token !== '') {
                if (!preg_match('/^\d{6,}:[A-Za-z0-9_-]{30,}$/', $token)) {
                    $errors[] = 'That doesn’t look like a bot token — copy it exactly from @BotFather (numbers, a colon, then letters).';
                } else {
                    $me = telegram_api('getMe', [], null, $token);
                    if (!$me['ok']) $errors[] = 'Telegram didn’t accept that token: ' . $me['error'];
                }
            }
            if (!$errors) {
                if (!$cfg['token_from_env'] && $token !== '') content_block_save('telegram.bot_token', $token);
                if (isset($_POST['forget_token'])) content_block_delete('telegram.bot_token');
                $channel === '' ? content_block_delete('telegram.channel') : content_block_save('telegram.channel', $channel);
                $staff === '' ? content_block_delete('telegram.staff_chat') : content_block_save('telegram.staff_chat', $staff);
                content_block_save('telegram.events', $events ? implode(',', $events) : SITE_EMPTY);
                $topics = [];
                foreach ((array) ($_POST['topics'] ?? []) as $ev => $tid) {
                    if (isset(TELEGRAM_EVENTS[$ev]) && ctype_digit((string) $tid) && (int) $tid > 1) $topics[$ev] = (int) $tid;
                }
                content_block_save('telegram.topics', json_encode($topics ?: new stdClass()));
                flash('success', 'Telegram settings saved.');
                header('Location: ' . path('admin/telegram/settings'));
                exit;
            }
        } elseif ($action === 'test_staff') {
            // One test per destination: General, plus each topic in use, naming what lands there.
            $dest = [0 => []];
            foreach (TELEGRAM_EVENTS as $ev => $label) $dest[$cfg['topics'][$ev] ?? 0][] = $label;
            $by = htmlspecialchars($adminUser['name'], ENT_NOQUOTES, 'UTF-8');
            foreach ($dest as $tid => $labels) {
                if ($tid && !$labels) continue;
                $text = "<b>✅ Syspoint admin is connected</b>\n" . ($labels
                    ? "This is where these alerts arrive:\n• " . htmlspecialchars(implode("\n• ", $labels), ENT_NOQUOTES, 'UTF-8')
                    : 'Staff notifications will arrive here.') . "\nSent by $by.";
                $r = telegram_api('sendMessage', ['chat_id' => $cfg['staff_chat'], 'parse_mode' => 'HTML', 'text' => $text] + ($tid ? ['message_thread_id' => $tid] : []));
                if (!$r['ok']) break;
            }
            if ($r['ok']) {
                flash('success', 'Test message sent to the staff group.');
                header('Location: ' . path('admin/telegram/settings'));
                exit;
            }
            $errors[] = 'Couldn’t send: ' . $r['error'];
        } elseif ($action === 'find_chats') {
            $found = telegram_recent_chats();
            if ($found['error']) $errors[] = $found['error'];
            $chats = $found['chats'];
            // Remember topic names so the alert list can offer them by name.
            $known = json_decode(content_block('telegram.known_topics', '{}'), true) ?: [];
            foreach ($chats as $c) if ($c['topics']) $known[$c['id']] = array_replace((array) ($known[$c['id']] ?? []), $c['topics']);
            content_block_save('telegram.known_topics', json_encode($known ?: new stdClass(), JSON_UNESCAPED_UNICODE));
        } elseif ($action === 'use_chat') {
            $id = (string) ($_POST['chat_id'] ?? '');
            if (preg_match('/^-?\d{5,}$/', $id)) {
                content_block_save('telegram.staff_chat', $id);
                flash('success', 'Staff group set — send a test message to check.');
            }
            header('Location: ' . path('admin/telegram/settings'));
            exit;
        }
    }
}

// Live checks (only when a token exists)
$cfg = telegram_config();
$bot = null; $channelCheck = null;
if ($cfg['token'] !== '') {
    $me = telegram_api('getMe');
    $bot = $me['ok'] ? $me['result'] : ['error' => $me['error']];
    if ($me['ok'] && $cfg['channel'] !== '') {
        $member = telegram_api('getChatMember', ['chat_id' => $cfg['channel'], 'user_id' => $me['result']['id']]);
        $channelCheck = !$member['ok']
            ? ['ok' => false, 'text' => 'The bot can’t see this channel: ' . $member['error']]
            : (in_array($member['result']['status'] ?? '', ['administrator', 'creator'], true) && (($member['result']['can_post_messages'] ?? true) !== false)
                ? ['ok' => true, 'text' => 'The bot is an admin of the channel and can post.']
                : ['ok' => false, 'text' => 'Make the bot an administrator of the channel with “Post messages”.']);
    }
}

$pageTitle = 'Telegram Settings';
$activeNav = 'admin/telegram';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Telegram</p><h1>Telegram Settings</h1></div>
    <a href="<?= path('admin/telegram') ?>" class="btn btn-outline btn-sm">Announcements</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="admin-split tg-settings">
    <form method="post" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="save">
        <h2 class="admin-form-title">Connection</h2>
        <div class="form-group">
            <label for="bot_token">Bot token</label>
            <?php if ($cfg['token_from_env']): ?>
                <input type="text" id="bot_token" value="Set on the server (.env)" disabled>
            <?php else: ?>
                <input type="password" id="bot_token" name="bot_token" autocomplete="off" placeholder="<?= $cfg['token'] !== '' ? 'Saved — paste a new one to replace it' : '123456789:ABCdef…' ?>">
                <?php if ($cfg['token'] !== ''): ?><label class="admin-choice" style="margin-top:8px !important;"><input type="checkbox" name="forget_token" value="1"> <span>Remove the saved token</span></label><?php endif; ?>
            <?php endif; ?>
            <?php if (is_array($bot) && isset($bot['username'])): ?><div class="form-note tg-ok">Connected as <strong>@<?= e($bot['username']) ?></strong></div>
            <?php elseif (is_array($bot)): ?><div class="form-note tg-bad"><?= e($bot['error']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="channel">Announcement channel</label>
            <input type="text" id="channel" name="channel" value="<?= e($cfg['channel']) ?>" placeholder="@syspointhub">
            <?php if ($channelCheck): ?><div class="form-note <?= $channelCheck['ok'] ? 'tg-ok' : 'tg-bad' ?>"><?= e($channelCheck['text']) ?></div><?php endif; ?>
            <?php if ($link = telegram_channel_url()): ?><div class="form-note">Public link on the website: <a href="<?= e($link) ?>" target="_blank" rel="noopener"><?= e($link) ?></a></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="staff_chat">Staff group chat ID (for notifications)</label>
            <input type="text" id="staff_chat" name="staff_chat" value="<?= e($cfg['staff_chat']) ?>" placeholder="-1001234567890">
        </div>
        <fieldset class="form-group admin-fieldset">
            <legend>Notify the staff group when…</legend>
            <?php $known = telegram_known_topics(); ?>
            <?php foreach (TELEGRAM_EVENTS as $k => $l): $tid = $cfg['topics'][$k] ?? 0; ?>
                <div class="tg-event-row">
                    <label class="admin-choice"><input type="checkbox" name="events[]" value="<?= $k ?>" <?= in_array($k, $cfg['events'], true) ? 'checked' : '' ?>> <span><?= e($l) ?></span></label>
                    <?php if ($known): ?>
                        <select name="topics[<?= $k ?>]" aria-label="Topic for: <?= e($l) ?>">
                            <option value="">General</option>
                            <?php foreach ($known + ($tid && !isset($known[$tid]) ? [$tid => 'Topic ' . $tid] : []) as $id => $name): ?><option value="<?= (int) $id ?>" <?= $tid === (int) $id ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" inputmode="numeric" name="topics[<?= $k ?>]" value="<?= $tid ?: '' ?>" placeholder="Topic: General" aria-label="Topic ID for: <?= e($l) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <p class="form-note">Using Topics in the staff group? Pick where each alert goes, so the gaming team can follow bookings and mute the rest. <?= $known ? '' : 'Send a message in each topic, then “Find chat IDs”, to choose them by name.' ?></p>
        </fieldset>
        <button type="submit" class="btn btn-primary">Save Settings</button>

        <div class="tg-tools">
            <button type="submit" form="tg-test" class="btn btn-outline btn-sm" <?= $cfg['staff_chat'] === '' || $cfg['token'] === '' ? 'disabled' : '' ?>>Send test to staff group</button>
            <button type="submit" form="tg-find" class="btn btn-outline btn-sm" <?= $cfg['token'] === '' ? 'disabled' : '' ?>>Find chat IDs</button>
        </div>
        <?php if ($chats !== null): ?>
            <div class="tg-chats">
                <?php if (!$chats): ?><p class="form-note">No chats seen yet. Add the bot to your staff group, send any message there (e.g. “hello”), then click “Find chat IDs” again.</p><?php endif; ?>
                <?php foreach ($chats as $c): ?>
                    <div class="tg-chat"><span><strong><?= e($c['title'] ?: 'Chat') ?></strong><small><?= e($c['type']) ?> · <?= e($c['id']) ?><?= $c['topics'] ? ' · topics: ' . e(implode(', ', $c['topics'])) : '' ?></small></span>
                        <?php if (in_array($c['type'], ['group', 'supergroup'], true)): ?><button type="submit" form="tg-use-<?= e(ltrim($c['id'], '-')) ?>" class="btn btn-primary btn-sm">Use for staff</button><?php endif; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </form>
    <form id="tg-test" method="post" hidden><?= csrf_field() ?><input type="hidden" name="action" value="test_staff"></form>
    <form id="tg-find" method="post" hidden><?= csrf_field() ?><input type="hidden" name="action" value="find_chats"></form>
    <?php foreach ($chats ?? [] as $c): ?><form id="tg-use-<?= e(ltrim($c['id'], '-')) ?>" method="post" hidden><?= csrf_field() ?><input type="hidden" name="action" value="use_chat"><input type="hidden" name="chat_id" value="<?= e($c['id']) ?>"></form><?php endforeach; ?>

    <div class="admin-form tg-guide">
        <h2 class="admin-form-title">Set up in 5 minutes</h2>
        <ol>
            <li>In Telegram, open <strong>@BotFather</strong>, send <code>/newbot</code>, give it a name (e.g. “Syspoint Hub”) and a username ending in <em>bot</em>. Copy the token it gives you into <em>Bot token</em>.</li>
            <li>Create a <strong>public channel</strong> (e.g. <code>@syspointhub</code>) — this is where customers follow your announcements. Put its @username in <em>Announcement channel</em>.</li>
            <li>In the channel: <em>Administrators → Add admin →</em> your bot, with <strong>Post messages</strong> on.</li>
            <li>For staff alerts: create a <strong>group</strong> for the team, add the bot, send “hello” in the group, then click <strong>Find chat IDs</strong> and <strong>Use for staff</strong>.</li>
            <li>Optional: turn on <strong>Topics</strong> in the group (Edit → Topics) and make e.g. <em>Orders</em>, <em>Gaming</em>, <em>Requests</em>, <em>Stock</em>. Send a message in each, click <strong>Find chat IDs</strong>, then choose a topic for each alert.</li>
            <li>Save, then <strong>Send test to staff group</strong>. The website footer and contact page now show “Join us on Telegram”.</li>
        </ol>
        <p class="form-note" style="margin-top:14px;">Prefer to keep the token off the database? Put <code>TELEGRAM_BOT_TOKEN</code>, <code>TELEGRAM_CHANNEL</code> and <code>TELEGRAM_ADMIN_CHAT_ID</code> in the server’s <code>.env</code> instead.</p>
    </div>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
