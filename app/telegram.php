<?php
/**
 * Telegram: a public announcement channel the team posts to from the admin,
 * and notifications to a staff group (new paid order, booking request,
 * software request, contact message, low stock).
 *
 * Setup (Admin → Telegram → Settings, administrators only):
 *   1. In Telegram, message @BotFather → /newbot → copy the bot token.
 *   2. Create a public channel (e.g. @syspointhub) and add the bot as an
 *      administrator with "Post messages".
 *   3. Optional: create a staff group, add the bot, send any message in the
 *      group, then use "Find chat IDs" to pick it.
 * TELEGRAM_BOT_TOKEN / TELEGRAM_CHANNEL / TELEGRAM_ADMIN_CHAT_ID in .env
 * override the admin settings (handy to keep the token off the database).
 */

declare(strict_types=1);

const TELEGRAM_EVENTS = [
    'order_paid' => 'An online order is paid',
    'booking' => 'Someone requests a gaming room',
    'software_request' => 'A Software Clinic request comes in',
    'contact' => 'A contact-form message arrives',
    'low_stock' => 'A sale takes a product to its reorder level',
    'walk_in' => 'A walk-in sale is recorded',
];

/** Effective settings: .env wins, then the admin settings. */
function telegram_config(): array
{
    $env = config()['telegram'] ?? [];
    $events = content_block('telegram.events', 'order_paid,booking,software_request,contact,low_stock');
    return [
        'token' => (string) ($env['bot_token'] ?? '') ?: content_block('telegram.bot_token', ''),
        'token_from_env' => (string) ($env['bot_token'] ?? '') !== '',
        'channel' => (string) ($env['channel'] ?? '') ?: content_block('telegram.channel', ''),
        'staff_chat' => (string) ($env['admin_chat_id'] ?? '') ?: content_block('telegram.staff_chat', ''),
        'events' => $events === SITE_EMPTY ? [] : array_filter(explode(',', $events)),
        // Staff group with Topics: event => topic (message_thread_id); missing = General.
        'topics' => array_filter(array_map('intval', json_decode(content_block('telegram.topics', '{}'), true) ?: [])),
    ];
}

/** Public link to the channel (for the website), e.g. https://t.me/syspointhub. */
function telegram_channel_url(): ?string
{
    $custom = site('site.telegram');
    if ($custom !== '') return $custom;
    $ch = telegram_config()['channel'];
    return str_starts_with($ch, '@') ? 'https://t.me/' . substr($ch, 1) : null;
}

/**
 * Call the Bot API. Returns ['ok' => bool, 'result' => mixed, 'error' => ?string].
 * $file: ['field' => 'photo', 'path' => '/abs/file.jpg'] for uploads.
 */
function telegram_api(string $method, array $params = [], ?array $file = null, ?string $token = null): array
{
    $token ??= telegram_config()['token'];
    if ($token === '') return ['ok' => false, 'result' => null, 'error' => 'Telegram isn’t set up yet — add the bot token in Telegram → Settings.'];
    $base = rtrim((string) (config()['telegram']['api_base'] ?? '') ?: 'https://api.telegram.org', '/');
    $ch = curl_init($base . '/bot' . $token . '/' . $method);
    if ($file) {
        $params[$file['field']] = new CURLFile($file['path']);
        foreach ($params as $k => $v) if (is_array($v)) $params[$k] = json_encode($v);
        $body = $params;
    } else {
        $body = json_encode($params);
    }
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => $file ? [] : ['Content-Type: application/json'],
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) return ['ok' => false, 'result' => null, 'error' => 'Couldn’t reach Telegram: ' . $err];
    $json = json_decode((string) $raw, true);
    if (!is_array($json)) return ['ok' => false, 'result' => null, 'error' => 'Unexpected reply from Telegram.'];
    return ['ok' => (bool) ($json['ok'] ?? false), 'result' => $json['result'] ?? null, 'error' => ($json['ok'] ?? false) ? null : ($json['description'] ?? 'Telegram refused the message.')];
}

/** Telegram HTML: escape everything, then the title in bold. */
function telegram_format(?string $title, ?string $body): string
{
    $esc = fn($s) => htmlspecialchars((string) $s, ENT_NOQUOTES, 'UTF-8');
    $parts = [];
    if (trim((string) $title) !== '') $parts[] = '<b>' . $esc(trim($title)) . '</b>';
    if (trim((string) $body) !== '') $parts[] = $esc(trim($body));
    return implode("\n\n", $parts);
}

/**
 * Post an announcement to the channel (photo + caption, or a text message),
 * with an optional link button. Records it in telegram_posts either way.
 * $refType/$refId: what it's about (product, course, game, room, event).
 * Returns ['ok' => bool, 'error' => ?string].
 */
function telegram_announce(?string $title, ?string $body, ?string $photoPath, ?string $buttonText, ?string $buttonUrl, ?string $refType = null, ?int $refId = null): array
{
    $cfg = telegram_config();
    $text = telegram_format($title, $body);
    $params = ['chat_id' => $cfg['channel'], 'parse_mode' => 'HTML'];
    if ($buttonText && $buttonUrl) {
        $params['reply_markup'] = ['inline_keyboard' => [[['text' => $buttonText, 'url' => $buttonUrl]]]];
    }
    if ($cfg['channel'] === '') {
        $res = ['ok' => false, 'result' => null, 'error' => 'Add your channel in Telegram → Settings first.'];
    } elseif ($photoPath) {
        $abs = __DIR__ . '/../public/' . ltrim($photoPath, '/');
        if (mb_strlen($text) > 1024) return ['ok' => false, 'error' => 'With a photo, the message must be under 1,024 characters (Telegram’s limit).'];
        $res = is_file($abs)
            ? telegram_api('sendPhoto', $params + ['caption' => $text], ['field' => 'photo', 'path' => $abs])
            : telegram_api('sendPhoto', $params + ['caption' => $text, 'photo' => media_url($photoPath)]);
    } else {
        if (mb_strlen($text) > 4096) return ['ok' => false, 'error' => 'Messages must be under 4,096 characters (Telegram’s limit).'];
        $res = telegram_api('sendMessage', $params + ['text' => $text, 'link_preview_options' => ['is_disabled' => false]]);
    }
    db()->prepare('INSERT INTO telegram_posts (title, body, photo_path, button_text, button_url, product_id, ref_type, ref_id, status, error, message_id, user_id)
                   VALUES (:t, :b, :p, :bt, :bu, :pid, :rt, :rid, :s, :e, :m, :u)')
        ->execute(['t' => $title ?: null, 'b' => $body ?: null, 'p' => $photoPath ?: null, 'bt' => $buttonText ?: null, 'bu' => $buttonUrl ?: null,
                   'pid' => $refType === 'product' ? $refId : null, 'rt' => $refId ? $refType : null, 'rid' => $refId ?: null, 's' => $res['ok'] ? 'sent' : 'failed', 'e' => $res['ok'] ? null : mb_substr((string) $res['error'], 0, 255),
                   'm' => $res['result']['message_id'] ?? null, 'u' => admin_user()['id'] ?? null]);
    return ['ok' => $res['ok'], 'error' => $res['error']];
}

/**
 * Tell the staff group something happened. Queued and sent after the page
 * has gone to the visitor, so Telegram can never slow down or break a
 * checkout, booking or form. Never throws.
 */
function telegram_notify(string $event, string $title, array $lines = [], ?string $adminPath = null): void
{
    static $queue = null;
    $cfg = telegram_config();
    if ($cfg['token'] === '' || $cfg['staff_chat'] === '' || !in_array($event, $cfg['events'], true)) return;
    $text = '<b>' . htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8') . '</b>';
    foreach ($lines as $l) if ($l !== null && $l !== '') $text .= "\n" . htmlspecialchars((string) $l, ENT_NOQUOTES, 'UTF-8');
    $msg = ['chat_id' => $cfg['staff_chat'], 'text' => $text, 'parse_mode' => 'HTML', 'link_preview_options' => ['is_disabled' => true]];
    if (!empty($cfg['topics'][$event])) $msg['message_thread_id'] = $cfg['topics'][$event];
    if ($adminPath) $msg['reply_markup'] = ['inline_keyboard' => [[['text' => 'Open in admin', 'url' => url($adminPath)]]]];
    if ($queue === null) {
        $queue = [];
        register_shutdown_function(function () use (&$queue) {
            if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
            foreach ($queue as $m) {
                try {
                    $r = telegram_api('sendMessage', $m);
                    if (!$r['ok']) error_log('telegram_notify failed: ' . $r['error']);
                } catch (Throwable $e) {
                    error_log('telegram_notify failed: ' . $e->getMessage());
                }
            }
        });
    }
    $queue[] = $msg;
}

/**
 * Chats the bot has seen in the last day (to find the staff group's ID),
 * with any group Topics it saw messages in: ['topics' => [thread_id => name]].
 */
function telegram_recent_chats(): array
{
    $r = telegram_api('getUpdates', ['limit' => 100, 'allowed_updates' => ['message', 'channel_post', 'my_chat_member']]);
    if (!$r['ok']) return ['error' => $r['error'], 'chats' => []];
    $chats = [];
    foreach ((array) $r['result'] as $u) {
        $m = $u['message'] ?? null;
        $c = $m['chat'] ?? $u['channel_post']['chat'] ?? $u['my_chat_member']['chat'] ?? null;
        if (!$c) continue;
        $id = (string) $c['id'];
        $chats[$id] ??= ['id' => $id, 'title' => $c['title'] ?? trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')), 'type' => $c['type'] ?? '', 'topics' => []];
        if ($m && !empty($m['is_topic_message']) && !empty($m['message_thread_id'])) {
            $name = $m['forum_topic_created']['name'] ?? $m['reply_to_message']['forum_topic_created']['name'] ?? null;
            $tid = (int) $m['message_thread_id'];
            if ($name !== null || !isset($chats[$id]['topics'][$tid])) $chats[$id]['topics'][$tid] = $name ?? 'Topic ' . $tid;
        }
    }
    return ['error' => null, 'chats' => array_values($chats)];
}

/** Topics remembered for the staff group by the last "Find chat IDs": [thread_id => name]. */
function telegram_known_topics(): array
{
    $known = json_decode(content_block('telegram.known_topics', '{}'), true) ?: [];
    return (array) ($known[telegram_config()['staff_chat']] ?? []);
}

// --- Announcing straight from an edit page -----------------------------------

const TELEGRAM_REF_TYPES = ['product', 'course', 'game', 'room', 'event'];

/**
 * Channel connected. Whoever can edit an item may announce it from its page
 * (page access is already checked); the free-form composer is Website-only.
 */
function telegram_can_post(): bool
{
    $cfg = telegram_config();
    return $cfg['token'] !== '' && $cfg['channel'] !== '';
}

/**
 * A ready-to-post announcement for a product, course, game, room or event:
 * ['title', 'body', 'photo_path', 'button_text', 'button_url', 'ref_type', 'ref_id'],
 * or null if it doesn't exist. $reminder words an event as a reminder.
 */
function telegram_draft(string $type, int $id, bool $reminder = false): ?array
{
    $clip = fn(?string $s, int $n = 400) => mb_strimwidth(trim((string) $s), 0, $n, '…');
    $join = fn(array $parts) => trim(implode("\n\n", array_filter(array_map('trim', $parts), fn($p) => $p !== '')));
    $hub = site('site.hub_name');
    $d = null;

    if ($type === 'product' && ($p = product_by_id($id))) {
        $cat = product_category_by_id((int) $p['category_id']);
        $d = ['New in the shop: ' . $p['name'],
            $join([format_naira((float) $p['price']), $clip($p['description']), 'In store now at ' . site('site.store_suite') . ', ' . site('site.plaza') . '.']),
            $p['image_path'], 'View in shop', url('shop/' . ($cat['slug'] ?? '') . '/' . $p['slug'])];
    } elseif ($type === 'course' && ($c = training_course_by_id($id))) {
        $meta = implode(' · ', array_filter([(string) $c['duration_label'], $c['price'] !== null ? format_naira((float) $c['price']) : '']));
        $d = ['🎓 ' . $c['title'], $join([$meta, $clip($c['description']), 'Seats are limited — reserve yours at ' . $hub . '.']),
            $c['image_path'], 'See courses', url('training') . '#courses'];
    } elseif ($type === 'game' && ($g = game_by_id($id))) {
        $lead = ['ps5' => '🎮 New on PS5', 'vr' => '🥽 New in the VR arena', 'board' => '🎲 New board game'][$g['type']] ?? '🎮 New game';
        $d = [$lead . ': ' . $g['name'], $join([$clip($g['description']), 'Come play it at ' . $hub . ' — free internet for every gamer.']),
            $g['image_path'], 'Book a room', url('gaming') . '#rooms'];
    } elseif ($type === 'room' && ($r = gaming_room_by_id($id))) {
        $meta = format_naira((float) $r['hourly_rate']) . ' per hour' . ($r['capacity'] ? ' · up to ' . (int) $r['capacity'] . ' people' : '');
        $d = ['🛋️ ' . $r['name'] . ' at ' . $hub, $join([$meta, $clip($r['description'])]),
            $r['image_path'], 'Book this room', url('gaming/book/' . $r['slug'])];
    } elseif ($type === 'event' && ($ev = event_by_id($id))) {
        $icon = ['gaming' => '🎮', 'training' => '🎓'][$ev['kind']] ?? '📅';
        $title = $icon . ' ' . $ev['title'];
        if ($reminder) {
            $date = substr((string) $ev['starts_at'], 0, 10);
            $soon = $date === date('Y-m-d') ? 'Today' : ($date === date('Y-m-d', strtotime('+1 day')) ? 'Tomorrow' : 'Reminder');
            $title = '⏰ ' . $soon . ': ' . $ev['title'];
        }
        $facts = '🗓 ' . event_when($ev)
            . ($ev['venue'] ? "\n📍 " . $ev['venue'] : '')
            . (event_price_label($ev) !== '' ? "\n🎟 " . event_price_label($ev) : '');
        $d = [$title, $join([$facts, $clip($ev['description'], 600)]),
            $ev['image_path'], $ev['button_text'] ?: 'Details', event_link($ev)];
    }
    if ($d === null) return null;
    return ['title' => $d[0], 'body' => $d[1], 'photo_path' => (string) $d[2], 'button_text' => $d[3], 'button_url' => $d[4],
            'ref_type' => $type, 'ref_id' => $id];
}

/** Most recent successful post about this item, or null. */
function telegram_last_post(string $type, int $id): ?array
{
    $stmt = db()->prepare("SELECT * FROM telegram_posts WHERE ref_type = :t AND ref_id = :i AND status = 'sent' ORDER BY id DESC LIMIT 1");
    $stmt->execute(['t' => $type, 'i' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Call after an edit page has saved and set its success flash: if the
 * "Post to Telegram" box was ticked, announce the item and add the outcome
 * to the message. A failed post never undoes the save; it shows a warning.
 */
function telegram_announce_after_save(string $type, int $id): void
{
    if (empty($_POST['tg_announce']) || !telegram_can_post()) return;
    $d = telegram_draft($type, $id);
    if (!$d) return;
    $table = ['product' => 'products', 'course' => 'training_courses', 'game' => 'games', 'room' => 'gaming_rooms', 'event' => 'events'][$type];
    $visible = db()->prepare("SELECT is_active FROM $table WHERE id = :id");
    $visible->execute(['id' => $id]);
    if (!(int) $visible->fetchColumn()) {
        flash('warning', 'Saved, but not posted to Telegram: it’s hidden on the website, so the link wouldn’t work. Make it visible, then save again with the box ticked.');
        return;
    }
    $r = telegram_announce($d['title'], $d['body'], $d['photo_path'] ?: null, $d['button_text'], $d['button_url'], $type, $id);
    if ($r['ok']) {
        flash('success', trim((flash('success') ?? 'Saved.') . ' Posted to ' . telegram_config()['channel'] . '.'));
    } else {
        flash('warning', 'Saved, but the Telegram post didn’t go out: ' . $r['error'] . ' You can post it from Website → Telegram.');
    }
}
