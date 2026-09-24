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
    <div class="admin-topbar-user">
        <span><?= e($adminUser['name'] ?? '') ?></span>
        <form method="post" action="<?= path('admin/logout') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline btn-sm" style="background:transparent;border-color:rgba(255,255,255,0.3);color:#fff;">Log out</button>
        </form>
    </div>
</header>
<main class="admin-main">
