<?php
declare(strict_types=1);

// POST /cookie-consent — consent.js reports each choice here for the consent log.
header('Content-Type: application/json');
header('Cache-Control: no-store');
$body = json_decode((string) file_get_contents('php://input', false, null, 0, 2048), true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_array($body)) {
    http_response_code(400);
    echo '{"ok":false}';
    return;
}
// Only from our own pages.
$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
    http_response_code(403);
    echo '{"ok":false}';
    return;
}
$ok = cookie_consent_log((string) ($body['id'] ?? ''), (string) ($body['action'] ?? ''), is_array($body['c'] ?? null) ? $body['c'] : []);
echo json_encode(['ok' => $ok]);
