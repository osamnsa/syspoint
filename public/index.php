<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$routes = [
    '#^$#' => __DIR__ . '/../app/views/pages/home.php',
    '#^about$#' => __DIR__ . '/../app/views/pages/about.php',
    '#^contact$#' => __DIR__ . '/../app/views/pages/contact.php',

    '#^admin/login$#' => __DIR__ . '/../app/views/pages/admin_login.php',
    '#^admin/logout$#' => __DIR__ . '/../app/views/pages/admin_logout.php',
    '#^admin$#' => __DIR__ . '/../app/views/pages/admin_dashboard.php',
];

$requestPath = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''), '/');
$base = trim(base_path(), '/');
if ($base !== '' && str_starts_with($requestPath, $base)) {
    $requestPath = trim(substr($requestPath, strlen($base)), '/');
}

foreach ($routes as $pattern => $file) {
    if (preg_match($pattern, $requestPath, $matches)) {
        $params = array_slice($matches, 1);
        require $file;
        return;
    }
}

http_response_code(404);
require __DIR__ . '/../app/views/pages/not_found.php';
