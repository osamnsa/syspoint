<?php
/**
 * Dashboard notifications (the bell in the admin top bar).
 *
 * notify_staff() records a notification for the staff area it belongs to
 * and also sends the Telegram staff alert (telegram_notify), so every event
 * reaches both. Each user sees only the areas they have access to; read
 * state is per user.
 */

declare(strict_types=1);

/** Event => [area, icon]. Area decides who sees it ('admin' = administrators only). */
const NOTIFY_EVENTS = [
    'order_paid' => ['store', 'cart'],
    'booking' => ['gaming', 'calendar'],
    'software_request' => ['sales', 'code'],
    'contact' => ['sales', 'mail'],
    'low_stock' => ['store', 'box'],
    'walk_in' => ['store', 'cart'],
    'security' => ['admin', 'shield'],
];

function notify_staff(string $event, string $title, array $lines = [], ?string $adminPath = null, bool $bell = true): void
{
    if ($bell && isset(NOTIFY_EVENTS[$event]) && $event !== 'walk_in') {
        try {
            db()->prepare('INSERT INTO notifications (event, area, title, body, link) VALUES (:e, :a, :t, :b, :l)')->execute([
                'e' => $event, 'a' => NOTIFY_EVENTS[$event][0], 't' => mb_substr($title, 0, 190),
                'b' => ($b = trim(implode(' · ', array_filter(array_map('strval', $lines), fn($l) => $l !== '')))) !== '' ? mb_strimwidth($b, 0, 500, '…') : null,
                'l' => $adminPath,
            ]);
        } catch (Throwable $e) {
            error_log('notify_staff failed: ' . $e->getMessage());
        }
    }
    telegram_notify($event, $title, $lines, $adminPath);
}

/** SQL condition limiting notifications to the areas this user can open. */
function notifications_scope(array $user): array
{
    if (($user['role'] ?? '') === 'admin') return ['1 = 1', []];
    $areas = admin_user_areas($user);
    if (!$areas) return ['1 = 0', []];
    $in = []; $args = [];
    foreach (array_values($areas) as $i => $a) { $in[] = ":ar$i"; $args["ar$i"] = $a; }
    return ['n.area IN (' . implode(',', $in) . ')', $args];
}

function notifications_list(array $user, int $limit = 12, bool $unreadOnly = false, int $offset = 0): array
{
    [$scope, $args] = notifications_scope($user);
    $args['uid'] = $user['id'];
    $stmt = db()->prepare("SELECT n.*, r.read_at FROM notifications n
        LEFT JOIN notification_reads r ON r.notification_id = n.id AND r.user_id = :uid
        WHERE $scope" . ($unreadOnly ? ' AND r.read_at IS NULL' : '') . " AND n.created_at > NOW() - INTERVAL 60 DAY
        ORDER BY n.id DESC LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset));
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function notifications_unread_count(array $user): int
{
    [$scope, $args] = notifications_scope($user);
    $args['uid'] = $user['id'];
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications n
        LEFT JOIN notification_reads r ON r.notification_id = n.id AND r.user_id = :uid
        WHERE $scope AND r.read_at IS NULL AND n.created_at > NOW() - INTERVAL 60 DAY");
    $stmt->execute($args);
    return (int) $stmt->fetchColumn();
}

/** Mark one ($id) or all visible notifications read for this user. */
function notifications_mark_read(array $user, ?int $id = null): void
{
    if ($id) {
        db()->prepare('INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (:n, :u)')->execute(['n' => $id, 'u' => $user['id']]);
        return;
    }
    [$scope, $args] = notifications_scope($user);
    $args['uid'] = $user['id'];
    db()->prepare("INSERT IGNORE INTO notification_reads (notification_id, user_id)
        SELECT n.id, :uid FROM notifications n WHERE $scope AND n.created_at > NOW() - INTERVAL 60 DAY")->execute($args);
}

function notification_time(string $ts): string
{
    $s = time() - strtotime($ts);
    if ($s < 60) return 'just now';
    if ($s < 3600) return floor($s / 60) . ' min ago';
    if ($s < 86400) return floor($s / 3600) . ' h ago';
    if ($s < 172800) return 'yesterday';
    return date('j M', strtotime($ts));
}
