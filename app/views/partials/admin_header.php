<?php
declare(strict_types=1);

/** @var string|null $pageTitle */
/** @var array $adminUser Set by require_admin() in the calling page */

$titleTag = 'Admin' . (isset($pageTitle) && $pageTitle !== '' ? ' — ' . $pageTitle : '') . ' | Syspoint';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titleTag) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/admin.css') ?>">
</head>
<body class="admin-body">
<header class="admin-topbar">
    <a class="brand" href="<?= path('admin') ?>"><span class="brand-name">Syspoint <em style="color:var(--color-accent);font-style:normal;">Admin</em></span></a>
    <nav class="admin-nav">
        <a href="<?= path('admin') ?>">Dashboard</a>
        <a href="<?= path('admin/products') ?>">Products</a>
        <a href="<?= path('admin/categories') ?>">Categories</a>
        <a href="<?= path('admin/orders') ?>">Orders</a>
        <a href="<?= path('admin/software-requests') ?>">Requests</a>
        <a href="<?= path('admin/businesses') ?>">Businesses</a>
        <a href="<?= path('admin/games') ?>">Games</a>
        <a href="<?= path('admin/rooms') ?>">Rooms</a>
        <a href="<?= path('admin/bookings') ?>">Bookings</a>
        <a href="<?= path('admin/courses') ?>">Courses</a>
    </nav>
    <div class="admin-topbar-user">
        <span><?= e($adminUser['name'] ?? '') ?></span>
        <form method="post" action="<?= path('admin/logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline btn-sm" style="background:transparent;border-color:rgba(255,255,255,0.3);color:#fff;">Log out</button>
        </form>
    </div>
</header>
<main class="admin-main">
