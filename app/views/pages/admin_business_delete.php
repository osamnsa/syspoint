<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    db()->prepare('DELETE FROM deployed_businesses WHERE id = :id')->execute(['id' => (int) ($params[0] ?? 0)]);
    flash('success', 'Business deleted.');
}

header('Location: ' . path('admin/businesses'));
exit;
