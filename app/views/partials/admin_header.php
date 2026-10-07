<?php
declare(strict_types=1);

/** @var string|null $pageTitle */
/** @var array $adminUser Set by require_admin() in the calling page */

$titleTag = 'Admin' . (isset($pageTitle) && $pageTitle !== '' ? ' — ' . $pageTitle : '') . ' | Syspoint';

$adminNav = [
    'admin' => 'Dashboard',
    'admin/products' => 'Products',
    'admin/categories' => 'Categories',
    'admin/orders' => 'Orders',
    'admin/software-requests' => 'Requests',
    'admin/businesses' => 'Clients',
    'admin/games' => 'Games',
    'admin/rooms' => 'Rooms',
    'admin/bookings' => 'Bookings',
    'admin/courses' => 'Courses',
    'admin/testimonials' => 'Testimonials',
    'admin/home-stats' => 'Home Stats',
];
$currentAdminPath = $requestPath ?? '';
$flashSuccess = flash('success');
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
<?php require __DIR__ . '/admin_backdrop.php'; ?>
<header class="admin-topbar glass-dark">
    <a class="brand" href="<?= path('admin') ?>"><img class="brand-logo" src="<?= asset('assets/img/logo.png') ?>" alt="" width="36" height="36"><span class="brand-name">Syspoint <em>Hub</em> <small>Admin</small></span></a>
    <nav class="admin-nav" aria-label="Admin">
        <?php foreach ($adminNav as $navPath => $navLabel):
            $isCurrent = $navPath === 'admin'
                ? $currentAdminPath === 'admin'
                : ($currentAdminPath === $navPath || str_starts_with($currentAdminPath, $navPath . '/')); ?>
            <a href="<?= path($navPath) ?>"<?= $isCurrent ? ' class="is-active" aria-current="page"' : '' ?>><?= e($navLabel) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="admin-topbar-user">
        <a class="admin-view-site" href="<?= path() ?>" target="_blank" rel="noopener">View site &nearr;</a>
        <span class="admin-user-name"><?= e($adminUser['name'] ?? '') ?></span>
        <form method="post" action="<?= path('admin/logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm admin-logout">Log out</button>
        </form>
    </div>
</header>
<main class="admin-main">
<?php if ($flashSuccess): ?><div class="alert alert-success"><?= e($flashSuccess) ?></div><?php endif; ?>
