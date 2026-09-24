<?php
/**
 * Admin authentication: session-based, backed by the `users` table.
 * Deliberately minimal — no roles/permissions system yet (role column
 * exists on `users` for future use), just "logged in as a valid user or not".
 */

declare(strict_types=1);

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

    $stmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['admin_user_id']]);
    $user = $stmt->fetch();

    return $cache = ($user ?: null);
}

/** Call at the top of any admin page file. Redirects to login and exits if not authenticated. */
function require_admin(): array
{
    $user = admin_user();
    if ($user === null) {
        $_SESSION['admin_redirect_to'] = $_SERVER['REQUEST_URI'] ?? null;
        header('Location: ' . path('admin/login'));
        exit;
    }
    return $user;
}

function admin_attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int) $user['id'];
    return true;
}

function admin_logout(): void
{
    unset($_SESSION['admin_user_id']);
    session_regenerate_id(true);
}
