<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    db()->prepare('DELETE FROM testimonials WHERE id = :id')->execute(['id' => (int) ($params[0] ?? 0)]);
    flash('success', 'Testimonial deleted.');
}

header('Location: ' . path('admin/testimonials'));
exit;
