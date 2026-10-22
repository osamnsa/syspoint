<?php
/**
 * Cookie consent (CookieYes-style): a banner with Accept all / Reject all /
 * Customise, a preferences panel with one switch per category, a revisit
 * button, and a log of every choice for proof of consent.
 *
 * Nothing optional runs until the visitor agrees: Google Analytics and the
 * Meta Pixel are injected by consent.js only for the categories they allow.
 * The list of cookies shown in the panel and on the Cookie Policy is built
 * from what is actually switched on in Admin → Website → Cookies.
 *
 * The choice is stored in the sp_consent cookie as JSON:
 *   {"id":"<uuid>","v":"<version>","t":<unix time>,"c":{"functional":0,"analytics":1,"marketing":0}}
 * "v" changes whenever the set of cookies changes, so visitors are asked again.
 */

declare(strict_types=1);

const CONSENT_COOKIE = 'sp_consent';
const CONSENT_OPTIONAL = ['functional', 'analytics', 'marketing'];

/** Google Analytics 4 measurement ID from settings, or '' if unset/invalid. */
function cookie_ga_id(): string
{
    $id = strtoupper(trim(site('cookies.ga_id')));
    return preg_match('/^G-[A-Z0-9]{4,16}$/', $id) ? $id : '';
}

/** Meta (Facebook) Pixel ID from settings, or '' if unset/invalid. */
function cookie_pixel_id(): string
{
    $id = trim(site('cookies.pixel_id'));
    return preg_match('/^\d{6,20}$/', $id) ? $id : '';
}

/** How long a choice is remembered, in days (30–395, default 180). */
function cookie_consent_days(): int
{
    $d = (int) site('cookies.days');
    return $d >= 30 && $d <= 395 ? $d : 180;
}

/**
 * The categories with their cookies. Each cookie: name, provider, duration, purpose.
 * Only cookies that can actually be set on this site are listed.
 */
function cookie_categories(): array
{
    static $cats = null;
    if ($cats !== null) return $cats;
    $host = parse_url(public_url(), PHP_URL_HOST) ?: 'this site';
    $days = cookie_consent_days();

    $necessary = [
        ['syspoint_sid', $host, 'Session', 'Keeps your cart, checkout and forms working and secure. Deleted when you close your browser.'],
        [CONSENT_COOKIE, $host, $days . ' days', 'Remembers the cookie choices you make on this banner.'],
    ];
    $analytics = [];
    if ($ga = cookie_ga_id()) {
        $analytics[] = ['_ga', 'Google Analytics', '1 year 1 month', 'Tells visits apart so we can count visitors without knowing who you are.'];
        $analytics[] = ['_ga_' . substr($ga, 2), 'Google Analytics', '1 year 1 month', 'Keeps track of your current visit (session) for our statistics.'];
    }
    $marketing = [];
    if (cookie_pixel_id()) {
        $marketing[] = ['_fbp', 'Meta (Facebook)', '3 months', 'Lets Meta measure our adverts and show them to people likely to be interested.'];
        $marketing[] = ['_fbc', 'Meta (Facebook)', '3 months', 'Stores the advert click that brought you here, when there was one.'];
    }

    $fmt = fn(array $rows) => array_map(fn($r) => array_combine(['name', 'provider', 'duration', 'purpose'], $r), $rows);
    return $cats = [
        'necessary' => ['label' => site('cookies.necessary_label'), 'text' => site('cookies.necessary_text'), 'required' => true, 'cookies' => $fmt($necessary)],
        'functional' => ['label' => site('cookies.functional_label'), 'text' => site('cookies.functional_text'), 'required' => false, 'cookies' => []],
        'analytics' => ['label' => site('cookies.analytics_label'), 'text' => site('cookies.analytics_text'), 'required' => false, 'cookies' => $fmt($analytics)],
        'marketing' => ['label' => site('cookies.marketing_label'), 'text' => site('cookies.marketing_text'), 'required' => false, 'cookies' => $fmt($marketing)],
    ];
}

/** Changes when the cookies in use change, so earlier choices are asked again. */
function cookie_consent_version(): string
{
    $sig = [];
    foreach (cookie_categories() as $k => $c) $sig[$k] = array_column($c['cookies'], 'provider');
    return substr(sha1(json_encode($sig) . '|' . site('cookies.revision')), 0, 8);
}

/** The visitor's current choice, or null if they haven't chosen (or must choose again). */
function cookie_consent(): ?array
{
    $raw = $_COOKIE[CONSENT_COOKIE] ?? '';
    $data = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($data) || ($data['v'] ?? '') !== cookie_consent_version()) return null;
    return $data;
}

/** True when the visitor allowed this category ('necessary' is always allowed). */
function cookie_allowed(string $category): bool
{
    if ($category === 'necessary') return true;
    return !empty(cookie_consent()['c'][$category]);
}

/** Everything consent.js needs, as JSON for a data attribute. */
function cookie_banner_config(): string
{
    return json_encode([
        'cookie' => CONSENT_COOKIE,
        'version' => cookie_consent_version(),
        'days' => cookie_consent_days(),
        'secure' => request_is_https(),
        'log' => path('cookie-consent'),
        'ga' => cookie_ga_id(),
        'pixel' => cookie_pixel_id(),
    ], JSON_UNESCAPED_SLASHES);
}

/** Cookie table for the Cookie Policy page ({cookie_table}). */
function cookie_policy_table(): string
{
    $out = '';
    foreach (cookie_categories() as $c) {
        $out .= '<h3>' . e($c['label']) . ($c['required'] ? ' <small>(always on)</small>' : '') . '</h3><p>' . e($c['text']) . '</p>';
        if (!$c['cookies']) { $out .= '<p class="cc-none">We don’t use any cookies in this category at the moment.</p>'; continue; }
        $out .= '<div class="cc-table-wrap"><table class="cc-table"><thead><tr><th>Cookie</th><th>Set by</th><th>Lasts</th><th>What it does</th></tr></thead><tbody>';
        foreach ($c['cookies'] as $k) {
            $out .= '<tr><td data-label="Cookie"><code>' . e($k['name']) . '</code></td><td data-label="Set by">' . e($k['provider']) . '</td><td data-label="Lasts">' . e($k['duration']) . '</td><td data-label="What it does">' . e($k['purpose']) . '</td></tr>';
        }
        $out .= '</tbody></table></div>';
    }
    return $out;
}

/**
 * Record a choice (proof of consent). IPs are stored only as a keyed hash.
 * Returns false for malformed or flooding requests.
 */
function cookie_consent_log(string $consentId, string $action, array $choices): bool
{
    if (!preg_match('/^[a-f0-9-]{36}$/', $consentId) || !in_array($action, ['accept_all', 'reject_all', 'custom'], true)) return false;
    $ipHash = substr(hash_hmac('sha256', client_ip(), app_secret()), 0, 32);
    $recent = db()->prepare('SELECT COUNT(*) FROM cookie_consents WHERE ip_hash = :h AND created_at > (NOW() - INTERVAL 1 HOUR)');
    $recent->execute(['h' => $ipHash]);
    if ((int) $recent->fetchColumn() >= 30) return false;
    $c = [];
    foreach (CONSENT_OPTIONAL as $k) $c[$k] = !empty($choices[$k]) ? 1 : 0;
    db()->prepare('INSERT INTO cookie_consents (consent_id, action, functional, analytics, marketing, version, ip_hash, user_agent, page)
                   VALUES (:id, :a, :f, :an, :m, :v, :h, :ua, :p)')
        ->execute(['id' => $consentId, 'a' => $action, 'f' => $c['functional'], 'an' => $c['analytics'], 'm' => $c['marketing'],
                   'v' => cookie_consent_version(), 'h' => $ipHash, 'ua' => user_agent(),
                   'p' => mb_substr((string) parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH), 0, 190)]);
    return true;
}
