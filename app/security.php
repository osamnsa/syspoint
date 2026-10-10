<?php
/**
 * Security: response headers, the client's IP, admin login throttling, and
 * the spam filter + blocklist used by every public form.
 *
 * Public forms add spam_fields() inside the <form> and call spam_check()
 * before saving. Verdicts:
 *   blocked — honeypot filled, sent too fast, no form token, rate limit hit,
 *             or sender on the blocklist: nothing is saved; the visitor
 *             still sees the normal "thank you" (bots learn nothing).
 *   spam    — content looks like spam (links, foreign script, spam words):
 *             saved to the Spam folder for review, no staff notification.
 *   ok      — saved and notified as usual.
 */

declare(strict_types=1);

function client_ip(): string
{
    // REMOTE_ADDR only: proxy headers are trivially forged unless a known proxy sets them.
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (config()['app']['trust_proxy'] ?? false) {
        $fwd = trim(explode(',', (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0]);
        if (filter_var($fwd, FILTER_VALIDATE_IP)) $ip = $fwd;
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function request_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
}

/** Sent on every response. */
function send_security_headers(): void
{
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self "https://checkout.paystack.com")');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    if (request_is_https()) header('Strict-Transport-Security: max-age=31536000');
    header_remove('X-Powered-By');
}

/** A per-install secret (created on first use) for signing form tokens. */
function app_secret(): string
{
    static $secret = null;
    if ($secret !== null) return $secret;
    $secret = (string) (config()['app']['key'] ?? '');
    if ($secret === '') {
        $secret = content_block('security.secret', '');
        if ($secret === '') {
            $secret = bin2hex(random_bytes(32));
            content_block_save('security.secret', $secret);
        }
    }
    return $secret;
}

// --- Admin login throttling --------------------------------------------------

const LOGIN_MAX_PER_IP = 10;      // failed attempts per IP per 15 minutes
const LOGIN_MAX_PER_EMAIL = 5;    // failed attempts per account per 15 minutes

/** Minutes until this IP/email may try again (0 = allowed now). */
function login_locked_minutes(string $email): int
{
    $q = db()->prepare("SELECT COUNT(*), MIN(created_at) FROM login_attempts WHERE created_at > NOW() - INTERVAL 15 MINUTE AND ip = :ip");
    $q->execute(['ip' => client_ip()]);
    [$byIp, $firstIp] = $q->fetch(PDO::FETCH_NUM);
    $q = db()->prepare("SELECT COUNT(*), MIN(created_at) FROM login_attempts WHERE created_at > NOW() - INTERVAL 15 MINUTE AND email = :e");
    $q->execute(['e' => mb_strtolower($email)]);
    [$byEmail, $firstEmail] = $q->fetch(PDO::FETCH_NUM);
    $first = null;
    if ((int) $byIp >= LOGIN_MAX_PER_IP) $first = $firstIp;
    if ((int) $byEmail >= LOGIN_MAX_PER_EMAIL) $first = max((string) $first, (string) $firstEmail);
    if (!$first) return 0;
    return max(1, (int) ceil((strtotime($first) + 900 - time()) / 60));
}

function login_record_failure(string $email): void
{
    db()->prepare('INSERT INTO login_attempts (ip, email) VALUES (:ip, :e)')->execute(['ip' => client_ip(), 'e' => mb_strtolower(mb_substr($email, 0, 190))]);
    $q = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND created_at > NOW() - INTERVAL 15 MINUTE');
    $q->execute(['ip' => client_ip()]);
    $n = (int) $q->fetchColumn();
    if ($n === LOGIN_MAX_PER_EMAIL || $n === LOGIN_MAX_PER_IP) {
        notify_staff('security', '🔐 Repeated failed admin sign-ins', [
            $n . ' failed attempts in 15 minutes from IP ' . client_ip(),
            $email !== '' ? 'Last email tried: ' . mb_strimwidth($email, 0, 80, '…') : null,
            'Sign-in from this IP is paused for 15 minutes. Block it in Security if it continues.',
        ], 'admin/security');
    }
    if (random_int(1, 50) === 1) db()->exec('DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 7 DAY');
}

function login_clear_failures(string $email): void
{
    db()->prepare('DELETE FROM login_attempts WHERE email = :e OR ip = :ip')->execute(['e' => mb_strtolower($email), 'ip' => client_ip()]);
}

// --- Blocklist ---------------------------------------------------------------

const BLOCK_TYPES = ['email' => 'Email address', 'domain' => 'Email domain', 'ip' => 'IP address / range'];

/** Normalise a blocklist value for its type, or null if it isn't valid. */
function blocklist_normalise(string $type, string $value): ?string
{
    $v = mb_strtolower(trim($value));
    if ($type === 'email') return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    if ($type === 'domain') {
        $v = ltrim(preg_replace('#^(https?://|.*@)#', '', $v), '.');
        return preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/', $v) ? $v : null;
    }
    if ($type === 'ip') {
        if (filter_var($v, FILTER_VALIDATE_IP)) return $v;
        if (preg_match('#^(\d{1,3}(\.\d{1,3}){3})/(\d{1,2})$#', $v, $m) && filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && (int) $m[3] <= 32) return $v;
        return null;
    }
    return null;
}

function ip_in_cidr(string $ip, string $cidr): bool
{
    if (!str_contains($cidr, '/')) return $ip === $cidr;
    [$net, $bits] = explode('/', $cidr);
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    $mask = $bits == 0 ? 0 : (-1 << (32 - (int) $bits)) & 0xFFFFFFFF;
    return (ip2long($ip) & $mask) === (ip2long($net) & $mask);
}

/** The blocklist row that matches this sender, or null. Counts the hit. */
function blocklist_match(?string $email, ?string $ip): ?array
{
    $email = mb_strtolower(trim((string) $email));
    $domain = str_contains($email, '@') ? substr(strrchr($email, '@'), 1) : '';
    foreach (db()->query('SELECT * FROM blocklist')->fetchAll() as $row) {
        $hit = match ($row['type']) {
            'email' => $email !== '' && $email === $row['value'],
            'domain' => $domain !== '' && ($domain === $row['value'] || str_ends_with($domain, '.' . $row['value'])),
            'ip' => $ip && ip_in_cidr($ip, $row['value']),
            default => false,
        };
        if ($hit) {
            db()->prepare('UPDATE blocklist SET hits = hits + 1, last_hit_at = NOW() WHERE id = :id')->execute(['id' => $row['id']]);
            return $row;
        }
    }
    return null;
}

/** Add to the blocklist (ignores duplicates). Returns an error message or null. */
function blocklist_add(string $type, string $value, ?string $reason = null): ?string
{
    if (!isset(BLOCK_TYPES[$type])) return 'Choose what to block.';
    $v = blocklist_normalise($type, $value);
    if ($v === null) return 'That doesn’t look like a valid ' . mb_strtolower(BLOCK_TYPES[$type]) . '.';
    if ($type === 'ip' && ip_in_cidr(client_ip(), $v)) return 'That would block your own connection (' . client_ip() . ').';
    if ($type === 'domain' && in_array($v, ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com'], true)) {
        return 'Blocking all of ' . $v . ' would stop real customers too — block the single email address instead.';
    }
    db()->prepare('INSERT IGNORE INTO blocklist (type, value, reason, created_by) VALUES (:t, :v, :r, :u)')
        ->execute(['t' => $type, 'v' => $v, 'r' => $reason ? mb_substr($reason, 0, 255) : null, 'u' => admin_user()['id'] ?? null]);
    return null;
}

// --- Spam filter for public forms --------------------------------------------

/** Hidden fields for a public form: a honeypot and a signed timestamp. */
function spam_fields(): string
{
    $t = (string) time();
    return '<div class="hp-field" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
        . '<input type="hidden" name="_ft" value="' . $t . '.' . substr(hash_hmac('sha256', $t, app_secret()), 0, 24) . '">';
}

/** Share of letters written in non-Latin scripts (Cyrillic, CJK, Arabic, …). */
function non_latin_share(string $text): float
{
    $letters = preg_match_all('/\p{L}/u', $text);
    if ($letters < 12) return 0.0;
    $foreign = preg_match_all('/[\p{Cyrillic}\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}\p{Arabic}\p{Hebrew}\p{Thai}\p{Greek}\p{Devanagari}\p{Armenian}\p{Georgian}]/u', $text);
    return $foreign / $letters;
}

/**
 * Judge a public form submission. $texts = the free-text fields.
 * Returns ['verdict' => 'ok'|'spam'|'blocked', 'reason' => ?string]. Logs every verdict.
 */
function spam_check(string $form, ?string $email, array $texts): array
{
    $ip = client_ip();
    $text = trim(implode("\n", array_map('strval', $texts)));
    $verdict = 'ok'; $reason = null;

    $ft = (string) ($_POST['_ft'] ?? '');
    [$ts, $sig] = array_pad(explode('.', $ft, 2), 2, '');
    $age = ctype_digit($ts) ? time() - (int) $ts : -1;

    if (trim((string) ($_POST['website'] ?? '')) !== '') { $verdict = 'blocked'; $reason = 'Hidden field filled (bot)'; }
    elseif (!ctype_digit($ts) || !hash_equals(substr(hash_hmac('sha256', $ts, app_secret()), 0, 24), $sig)) { $verdict = 'blocked'; $reason = 'Missing or forged form token (bot)'; }
    elseif ($age < 3) { $verdict = 'blocked'; $reason = 'Sent ' . max(0, $age) . 's after the page loaded (bot)'; }
    elseif ($age > 172800) { $verdict = 'blocked'; $reason = 'Form older than 2 days'; }
    elseif ($row = blocklist_match($email, $ip)) { $verdict = 'blocked'; $reason = 'Blocklist: ' . BLOCK_TYPES[$row['type']] . ' ' . $row['value']; }
    else {
        $q = db()->prepare("SELECT COUNT(*) FROM spam_log WHERE ip = :ip AND verdict <> 'ok' AND created_at > NOW() - INTERVAL 1 DAY");
        $q->execute(['ip' => $ip]);
        $recentBad = (int) $q->fetchColumn();
        $q = db()->prepare("SELECT COUNT(*) FROM spam_log WHERE ip = :ip AND form = :f AND created_at > NOW() - INTERVAL 10 MINUTE");
        $q->execute(['ip' => $ip, 'f' => $form]);
        if ((int) $q->fetchColumn() >= 4) { $verdict = 'blocked'; $reason = 'Too many submissions from this IP (rate limit)'; }
        elseif ($recentBad >= 3) { $verdict = 'blocked'; $reason = 'IP sent spam repeatedly today'; }
        else {
            $links = preg_match_all('~(https?://|www\.)~i', $text);
            $share = non_latin_share($text);
            $words = '/\b(viagra|cialis|casino|porn|crypto ?currency|bitcoin|forex|seo services?|backlinks?|guest post|rank (your|on) google|loan offer|escort|xxx|onlyfans|betting|airdrop|web ?3 wallet)\b/i';
            if ($share > 0.3) { $verdict = 'spam'; $reason = 'Written in a non-English script (' . round($share * 100) . '% of letters)'; }
            elseif (preg_match('~\[(url|link)[=\]]|<a\s+href~i', $text)) { $verdict = 'spam'; $reason = 'Contains forum/HTML link code'; }
            elseif ($links >= 3) { $verdict = 'spam'; $reason = "Contains $links links"; }
            elseif (preg_match($words, $text, $m)) { $verdict = 'spam'; $reason = 'Spam keyword “' . mb_strtolower($m[1]) . '”'; }
            elseif ($email && preg_match('/@.+\.(ru|cn|xyz|top|click|loan|work|icu|buzz|monster|rest)$/i', $email) && $links >= 1) { $verdict = 'spam'; $reason = 'Link from a high-spam email domain'; }
        }
    }

    db()->prepare('INSERT INTO spam_log (form, verdict, reason, ip, email, excerpt) VALUES (:f, :v, :r, :ip, :e, :x)')
        ->execute(['f' => $form, 'v' => $verdict, 'r' => $reason, 'ip' => $ip, 'e' => $email ? mb_substr($email, 0, 190) : null, 'x' => $text !== '' ? mb_strimwidth($text, 0, 250, '…') : null]);
    if (random_int(1, 100) === 1) db()->exec('DELETE FROM spam_log WHERE created_at < NOW() - INTERVAL 90 DAY');
    return ['verdict' => $verdict, 'reason' => $reason];
}

function user_agent(): ?string
{
    $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return $ua === '' ? null : mb_substr($ua, 0, 255);
}

/**
 * "Mark as spam" / "Not spam" from a message or request page.
 * $table: contact_messages | software_requests. Returns a flash message, or null if no action.
 */
function spam_handle_admin_action(string $table, array $row, string $email): ?string
{
    if (!in_array($table, ['contact_messages', 'software_requests'], true)) return null;
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'spam') {
        db()->prepare("UPDATE $table SET is_spam = 1, spam_reason = COALESCE(spam_reason, :r) WHERE id = :id")
            ->execute(['r' => 'Marked as spam by ' . (admin_user()['name'] ?? 'staff'), 'id' => $row['id']]);
        $done = [];
        $domain = str_contains($email, '@') ? substr(strrchr($email, '@'), 1) : '';
        foreach (['email' => $email, 'domain' => $domain, 'ip' => (string) ($row['ip'] ?? '')] as $type => $value) {
            if (!empty($_POST['block_' . $type]) && $value !== '') {
                $err = blocklist_add($type, $value, 'Marked as spam (' . ($table === 'contact_messages' ? 'message' : 'software request') . ' #' . $row['id'] . ')');
                $done[] = $err ? $err : 'blocked ' . $value;
            }
        }
        return 'Moved to Spam' . ($done ? ' — ' . implode('; ', $done) : '') . '.';
    }
    if ($action === 'not_spam') {
        db()->prepare("UPDATE $table SET is_spam = 0, spam_reason = NULL WHERE id = :id")->execute(['id' => $row['id']]);
        $unblocked = 0;
        if (!empty($_POST['unblock'])) {
            $s = db()->prepare("DELETE FROM blocklist WHERE (type = 'email' AND value = :e) OR (type = 'ip' AND value = :ip)");
            $s->execute(['e' => mb_strtolower($email), 'ip' => (string) ($row['ip'] ?? '')]);
            $unblocked = $s->rowCount();
        }
        return 'Moved back to the inbox' . ($unblocked ? ' and unblocked the sender' : '') . '.';
    }
    return null;
}

/** Like blocklist_match() but without counting a hit (for display). */
function blocklist_match_peek(?string $email, ?string $ip): ?array
{
    $email = mb_strtolower(trim((string) $email));
    $domain = str_contains($email, '@') ? substr(strrchr($email, '@'), 1) : '';
    foreach (db()->query('SELECT * FROM blocklist')->fetchAll() as $row) {
        if (($row['type'] === 'email' && $email === $row['value'])
            || ($row['type'] === 'domain' && $domain !== '' && ($domain === $row['value'] || str_ends_with($domain, '.' . $row['value'])))
            || ($row['type'] === 'ip' && $ip && ip_in_cidr($ip, $row['value']))) return $row;
    }
    return null;
}
