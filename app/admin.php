<?php
/**
 * Admin authentication and staff permissions, backed by the `users` table.
 *
 * Two roles: 'admin' sees and changes everything (including Staff); 'staff'
 * sees only the areas ticked for them (users.areas, comma-separated keys of
 * ADMIN_AREAS). Access is enforced centrally in require_admin() from the
 * request path (ADMIN_AREA_PATHS), so a page only has to call require_admin().
 */

declare(strict_types=1);

/** Areas a staff member can be given, in sidebar order. */
const ADMIN_AREAS = [
    'sales' => 'Sales & CRM',
    'store' => 'Store & Inventory',
    'gaming' => 'Gaming',
    'training' => 'Training',
    'website' => 'Website',
];

/**
 * Admin URL prefix => area that may open it ('a|b' = either). Longest prefix wins. 'admin'
 * (the Overview dashboard) is open to every signed-in user; anything not
 * listed is admin-only, so a new page is locked down until it's mapped.
 */
const ADMIN_AREA_PATHS = [
    'admin' => '*',
    'admin/account' => '*',
    'admin/notifications' => '*',
    'admin/security' => 'admin',
    'admin/software-requests' => 'sales',
    'admin/crm' => 'sales',
    'admin/customers' => 'sales',
    'admin/deals' => 'sales',
    'admin/tasks' => 'sales',
    'admin/quotes' => 'sales',
    'admin/invoices' => 'sales',
    'admin/documents' => 'sales',
    'admin/messages' => 'sales',
    'admin/products' => 'store',
    'admin/categories' => 'store',
    'admin/orders' => 'store',
    'admin/inventory' => 'store',
    'admin/suppliers' => 'store',
    'admin/purchase-orders' => 'store',
    'admin/pos' => 'store',
    'admin/serials' => 'store',
    'admin/equipment' => 'gaming',
    'admin/games' => 'gaming',
    'admin/rooms' => 'gaming',
    'admin/bookings' => 'gaming',
    'admin/courses' => 'training',
    'admin/training' => 'training',
    'admin/students' => 'training',
    'admin/gaming' => 'gaming',
    'admin/businesses' => 'website',
    'admin/testimonials' => 'website',
    'admin/home-stats' => 'website',
    'admin/website' => 'website',
    'admin/events' => 'gaming|training|website',
    'admin/telegram' => 'website',
    'admin/telegram/settings' => 'admin',
    'admin/staff' => 'admin',
];

function admin_user(): ?array
{
    static $cache = null;
    static $resolved = false;

    if ($resolved) {
        return $cache;
    }
    $resolved = true;

    if (empty($_SESSION['admin_user_id'])) {
        return $cache = null;
    }

    $stmt = db()->prepare('SELECT id, name, email, role, areas, is_active FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['admin_user_id']]);
    $user = $stmt->fetch();

    // A deactivated account is signed out on its next request.
    if (!$user || !(int) $user['is_active']) {
        unset($_SESSION['admin_user_id']);
        return $cache = null;
    }

    return $cache = $user;
}

/** Area keys this user may open; admins get every area. */
function admin_user_areas(array $user): array
{
    if (($user['role'] ?? '') === 'admin') {
        return array_keys(ADMIN_AREAS);
    }
    $areas = array_filter(array_map('trim', explode(',', (string) ($user['areas'] ?? ''))));
    return array_values(array_intersect(array_keys(ADMIN_AREAS), $areas));
}

/** Can the signed-in user open this area? 'admin' = admins only, '*' = everyone. */
function admin_can(string $area): bool
{
    $user = admin_user();
    if ($user === null) {
        return false;
    }
    if ($user['role'] === 'admin' || $area === '*') {
        return true;
    }
    // 'gaming|training' = any one of those areas.
    return $area !== 'admin' && (bool) array_intersect(explode('|', $area), admin_user_areas($user));
}

/** Area that guards an admin path (without base path or slashes), per ADMIN_AREA_PATHS. */
function admin_area_for_path(string $requestPath): string
{
    $best = null;
    foreach (ADMIN_AREA_PATHS as $prefix => $area) {
        if (($requestPath === $prefix || str_starts_with($requestPath, $prefix . '/'))
            && ($best === null || strlen($prefix) > strlen($best))) {
            $best = $prefix;
        }
    }
    return $best === null ? 'admin' : ADMIN_AREA_PATHS[$best];
}

function admin_request_path(): string
{
    $path = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''), '/');
    $base = trim(base_path(), '/');
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = trim(substr($path, strlen($base)), '/');
    }
    return $path;
}

/**
 * Call at the top of any admin page file. Redirects to login if signed out,
 * and stops with a 403 page if the user's role doesn't cover this page.
 */
function require_admin(): array
{
    $user = admin_user();
    if ($user === null) {
        $_SESSION['admin_redirect_to'] = $_SERVER['REQUEST_URI'] ?? null;
        header('Location: ' . path('admin/login'));
        exit;
    }

    if (!admin_can(admin_area_for_path(admin_request_path()))) {
        http_response_code(403);
        $adminUser = $user;
        $pageTitle = 'No access';
        require __DIR__ . '/views/partials/admin_header.php';
        echo '<div class="admin-forbidden glass-dark"><h1>No access</h1><p>Your account doesn’t include this part of the admin. Ask an administrator to add it in Staff.</p><a class="btn btn-primary btn-sm" href="' . path('admin') . '">Back to Overview</a></div>';
        require __DIR__ . '/views/partials/admin_footer.php';
        exit;
    }

    return $user;
}

/** Only admins (role 'admin'): for actions inside a page that staff mustn't take. */
function admin_is_admin(): bool
{
    return (admin_user()['role'] ?? '') === 'admin';
}

function admin_attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !(int) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int) $user['id'];
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $user['id']]);
    return true;
}

function admin_logout(): void
{
    unset($_SESSION['admin_user_id']);
    session_regenerate_id(true);
}
