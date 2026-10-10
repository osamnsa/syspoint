<?php
/**
 * Shared bootstrap: config, PDO connection, session, helpers.
 * Every entry point (public/index.php, future cron/webhook endpoints)
 * requires this one file and gets the whole app.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
$config = config();

date_default_timezone_set('Africa/Lagos');

if (session_status() === PHP_SESSION_NONE) {
    // Session cookie: not readable by JavaScript, not sent on cross-site posts, HTTPS-only when on HTTPS.
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_name('syspoint_sid');
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/content_blocks.php';
require_once __DIR__ . '/site_content.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/inventory.php';
require_once __DIR__ . '/crm.php';
require_once __DIR__ . '/events.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/paystack.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/software_clinic.php';
require_once __DIR__ . '/gaming.php';
require_once __DIR__ . '/training.php';
require_once __DIR__ . '/showcase.php';
require_once __DIR__ . '/charts.php';
require_once __DIR__ . '/dashboard.php';

send_security_headers();
// Each phase adds its own domain file here as it's built (telegram) — same
// incremental-require pattern as every prior build, so an unfinished phase
// never breaks the pages that already work.

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = config();
        $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        // Same clock as PHP (Lagos), so NOW()/CURDATE() and TIMESTAMP columns
        // read in local time whatever zone the MySQL server itself runs in.
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }

    return $pdo;
}
