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
    <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;900&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/admin.css') ?>">
</head>
<body class="admin-body">
<header class="admin-topbar">
    <a class="brand" href="<?= path('admin') ?>"><img class="brand-logo" src="<?= asset('assets/img/logo.png') ?>" alt="" width="32" height="32" style="width:32px;height:32px;"><span class="brand-name">Syspoint <em>Hub</em> Admin</span></a>
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
        <a href="<?= path('admin/testimonials') ?>">Testimonials</a>
        <a href="<?= path('admin/home-stats') ?>">Home Stats</a>
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
