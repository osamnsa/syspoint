<?php
declare(strict_types=1);

/** @var string|null $pageTitle */
/** @var array $adminUser Set by require_admin() in the calling page */

require_once __DIR__ . '/admin_nav.php';

$titleTag = 'Admin' . (isset($pageTitle) && $pageTitle !== '' ? ' — ' . $pageTitle : '') . ' | Syspoint';
$currentAdminPath = admin_request_path();
$flashSuccess = flash('success');

// The menu item for this page: the longest matching path wins, so
// admin/products/3/edit lights up Products and admin/ only Overview.
// A page can name its menu item ($activeNav) when its URL isn't under one.
$activeNavPath = $activeNav ?? null;
if ($activeNavPath === null) foreach ($adminNavGroups as $group) {
    foreach ($group['items'] as $item) {
        $p = $item['path'];
        if (($currentAdminPath === $p || str_starts_with($currentAdminPath, $p . '/'))
            && ($activeNavPath === null || strlen($p) > strlen($activeNavPath))) {
            $activeNavPath = $p;
        }
    }
}
$adminInitials = initials((string) ($adminUser['name'] ?? 'A'));
$adminRoleLabel = ($adminUser['role'] ?? '') === 'admin'
    ? 'Administrator'
    : (implode(' · ', array_map(fn($a) => ADMIN_AREAS[$a], admin_user_areas($adminUser))) ?: 'Staff');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titleTag) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,700;0,900;1,900&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap">
    <link rel="stylesheet" href="<?= versioned_asset('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/style.css') ?>">
    <script src="<?= versioned_asset('assets/vendor/sweetalert2/sweetalert2.min.js') ?>" defer></script>
    <script src="<?= versioned_asset('assets/js/alerts.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/admin.css') ?>">
    <script src="<?= versioned_asset('assets/js/admin.js') ?>" defer></script>
</head>
<body class="admin-body admin-app">
<?php require __DIR__ . '/admin_backdrop.php'; ?>
<aside class="admin-sidebar glass-dark" id="admin-sidebar">
    <a class="brand admin-sidebar-brand" href="<?= path('admin') ?>"><img class="brand-logo" src="<?= asset('assets/img/logo.png') ?>" alt="" width="36" height="36"><span class="brand-name">Syspoint <em>Hub</em></span></a>
    <nav class="admin-side-nav" aria-label="Admin">
        <?php foreach ($adminNavGroups as $group):
            $visible = array_filter($group['items'], fn($it) => admin_can($it['area']));
            if (!$visible) continue; ?>
            <div class="admin-side-group">
                <?php if ($group['label']): ?><p class="admin-side-label"><?= e($group['label']) ?></p><?php endif; ?>
                <?php foreach ($visible as $item):
                    $isCurrent = $item['path'] === $activeNavPath; ?>
                    <a href="<?= path($item['path']) ?>"<?= $isCurrent ? ' class="is-active" aria-current="page"' : '' ?>><?= admin_icon($item['icon']) ?><span><?= e($item['label']) ?></span></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
    <div class="admin-side-user">
        <span class="admin-avatar"><?= e($adminInitials) ?></span>
        <span class="admin-side-user-text"><strong><?= e($adminUser['name'] ?? '') ?></strong><small><?= e($adminRoleLabel) ?></small></span>
        <form method="post" action="<?= path('admin/logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="admin-icon-btn" title="Log out" aria-label="Log out"><?= admin_icon('logout') ?></button>
        </form>
    </div>
</aside>
<div class="admin-scrim" data-admin-close></div>
<div class="admin-content">
    <header class="admin-topbar">
        <button type="button" class="admin-icon-btn admin-menu-btn" data-admin-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open menu"><?= admin_icon('menu') ?></button>
        <a class="brand admin-topbar-brand" href="<?= path('admin') ?>"><img class="brand-logo" src="<?= asset('assets/img/logo.png') ?>" alt="" width="30" height="30"><span class="brand-name">Syspoint <em>Hub</em></span></a>
        <span class="admin-topbar-date"><?= e((new DateTimeImmutable())->format('l, j F Y')) ?></span>
        <a class="admin-view-site" href="<?= path() ?>" target="_blank" rel="noopener">View site <?= admin_icon('external') ?></a>
    </header>
    <main class="admin-main">
<?php if ($flashSuccess): ?><div class="alert alert-success"><?= e($flashSuccess) ?></div><?php endif; ?>
