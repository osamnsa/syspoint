<?php
declare(strict_types=1);

/** The only way user/DB data touches HTML — used on everything interpolated into a view, no exceptions. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function base_path(): string
{
    $base = trim(config()['app']['base_path'], '/');
    return $base === '' ? '' : '/' . $base;
}

/** Relative URL for an internal link — never hand-write a leading '/', always go through this. */
function path(string $to = ''): string
{
    $to = ltrim($to, '/');
    return base_path() . '/' . $to;
}

function asset(string $relativePath): string
{
    return path($relativePath);
}

/** Cache-busts a static asset with its filemtime so a CSS/JS edit is visible immediately, no manual version bump. */
function versioned_asset(string $relativePath): string
{
    $file = __DIR__ . '/../public/' . ltrim($relativePath, '/');
    $version = is_file($file) ? filemtime($file) : time();
    return asset($relativePath) . '?v=' . $version;
}

function url(string $to = ''): string
{
    return config()['app']['url'] . path($to);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function format_naira(float $amount): string
{
    return '₦' . number_format($amount, 0);
}

/** Small inline badge marking a price as placeholder/preview, not real inventory pricing. */
function demo_badge(): string
{
    return '<span class="demo-badge">Demo</span>';
}

// --- CSRF -------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    return $submitted !== '' && hash_equals(csrf_token(), $submitted);
}

// --- Flash messages -----------------------------------------------------------

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    $existing = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $existing;
}

function old(string $key): string
{
    return e($_SESSION['old_input'][$key] ?? '');
}

// --- Uploads ------------------------------------------------------------------

/**
 * @return array{ok: bool, path: ?string, error: ?string} path is relative
 * to public/ (e.g. "uploads/products/abc123.jpg"), suitable for asset().
 */
function handle_image_upload(string $fieldName, string $subdir): array
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null, 'error' => null];
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'error' => 'That upload failed. Please try again.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'path' => null, 'error' => 'Image must be under 5MB.'];
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'path' => null, 'error' => 'Please upload a JPG, PNG, or WEBP image.'];
    }

    $subdir = trim($subdir, '/');
    $dir = __DIR__ . '/../public/uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'path' => null, 'error' => 'Could not save the upload. Please try again.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        return ['ok' => false, 'path' => null, 'error' => 'Could not save the upload. Please try again.'];
    }

    return ['ok' => true, 'path' => 'uploads/' . $subdir . '/' . $filename, 'error' => null];
}
