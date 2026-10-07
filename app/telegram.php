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
 * Returns ['ok' => bool, 'error' => ?string].
 */
function telegram_announce(?string $title, ?string $body, ?string $photoPath, ?string $buttonText, ?string $buttonUrl, ?int $productId = null): array
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
    db()->prepare('INSERT INTO telegram_posts (title, body, photo_path, button_text, button_url, product_id, status, error, message_id, user_id)
                   VALUES (:t, :b, :p, :bt, :bu, :pid, :s, :e, :m, :u)')
        ->execute(['t' => $title ?: null, 'b' => $body ?: null, 'p' => $photoPath ?: null, 'bt' => $buttonText ?: null, 'bu' => $buttonUrl ?: null,
                   'pid' => $productId, 's' => $res['ok'] ? 'sent' : 'failed', 'e' => $res['ok'] ? null : mb_substr((string) $res['error'], 0, 255),
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

/** Chats the bot has seen recently (to find the staff group's ID). */
function telegram_recent_chats(): array
{
    $r = telegram_api('getUpdates', ['limit' => 50, 'allowed_updates' => ['message', 'channel_post', 'my_chat_member']]);
    if (!$r['ok']) return ['error' => $r['error'], 'chats' => []];
    $chats = [];
    foreach ((array) $r['result'] as $u) {
        $c = $u['message']['chat'] ?? $u['channel_post']['chat'] ?? $u['my_chat_member']['chat'] ?? null;
        if ($c) $chats[(string) $c['id']] = ['id' => (string) $c['id'], 'title' => $c['title'] ?? trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')), 'type' => $c['type'] ?? ''];
    }
    return ['error' => null, 'chats' => array_values($chats)];
}
