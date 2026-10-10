<?php
declare(strict_types=1);

// JSON for the bell: GET = latest + unread count; POST action=read (id) / all.
$adminUser = require_admin();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) { http_response_code(403); echo json_encode(['error' => 'csrf']); exit; }
    $id = (int) ($_POST['id'] ?? 0);
    notifications_mark_read($adminUser, ($_POST['action'] ?? '') === 'all' ? null : ($id ?: null));
}

$items = array_map(fn($n) => [
    'id' => (int) $n['id'],
    'event' => $n['event'],
    'icon' => NOTIFY_EVENTS[$n['event']][1] ?? 'bell',
    'title' => $n['title'],
    'body' => $n['body'],
    'link' => $n['link'] ? path($n['link']) : null,
    'time' => notification_time($n['created_at']),
    'read' => $n['read_at'] !== null,
], notifications_list($adminUser, 10));
echo json_encode(['count' => notifications_unread_count($adminUser), 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
