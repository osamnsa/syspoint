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
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/content_blocks.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/paystack.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/software_clinic.php';
// Each phase adds its own domain file here as it's built (games/rooms/
// training/telegram) — same incremental-require pattern as every prior
// build, so an unfinished phase never breaks the pages that already work.

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
    }

    return $pdo;
}
