<?php
declare(strict_types=1);

/**
 * Loads .env (simple KEY=VALUE, no library) and exposes one config array via
 * config(). Every other file reads settings through config(), never
 * getenv()/$_ENV directly — and never a plain `require this file`, since
 * this file is require_once'd exactly once from bootstrap.php and every
 * other caller just calls the functions it declares. (A plain `require`
 * here — re-executed on every call site — would re-declare env()/config()
 * and fatal on the second include; require_once would dodge that but then
 * only the *first* call gets the real return value, since PHP only returns
 * a require_once'd file's value once. A cached function sidesteps both.)
 */

function env(string $key, string $default = ''): string
{
    static $loaded = false;

    if (!$loaded) {
        $envFile = __DIR__ . '/../.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v);
                // Strip a single layer of matching quotes, same as most .env parsers.
                if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[strlen($v) - 1] === $v[0]) {
                    $v = substr($v, 1, -1);
                }
                if (!array_key_exists($k, $_ENV)) {
                    $_ENV[$k] = $v;
                }
            }
        }
        $loaded = true;
    }

    return $_ENV[$key] ?? $default;
}

function config(): array
{
    static $config = null;

    if ($config === null) {
        $config = [
            'app' => [
                'name' => env('APP_NAME', 'Syspoint'),
                'url' => rtrim(env('APP_URL', 'http://localhost:8000'), '/'),
                'base_path' => env('APP_BASE_PATH', ''),
            ],
            'db' => [
                'host' => env('DB_HOST', '127.0.0.1'),
                'name' => env('DB_NAME', 'syspoint'),
                'user' => env('DB_USER', 'root'),
                'pass' => env('DB_PASS', ''),
            ],
            'paystack' => [
                'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
                'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),
            ],
            'mail' => [
                'from' => env('MAIL_FROM', 'no-reply@syspoint.example'),
                'from_name' => env('MAIL_FROM_NAME', 'Syspoint'),
                'smtp_host' => env('SMTP_HOST', ''),
                'smtp_port' => (int) env('SMTP_PORT', '587'),
                'smtp_user' => env('SMTP_USER', ''),
                'smtp_pass' => env('SMTP_PASS', ''),
            ],
            'telegram' => [
                'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
                'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID', ''),
            ],
            'cron' => [
                'secret' => env('CRON_SECRET', ''),
            ],
        ];
    }

    return $config;
}
