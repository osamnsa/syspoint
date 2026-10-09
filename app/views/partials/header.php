<?php
declare(strict_types=1);

/** @var string|null $pageTitle */
/** @var string|null $pageDescription */

$siteName = site('site.brand_first') . ' ' . site('site.brand_second');
$titleTag = (isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | ' : '') . $siteName;
$metaDescription = $pageDescription ?? site('site.meta_description');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titleTag) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php require __DIR__ . '/favicons.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,700;0,900;1,900&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&family=Press+Start+2P&family=Kaushan+Script&display=swap">
    <link rel="stylesheet" href="<?= versioned_asset('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/style.css') ?>">
    <script src="<?= versioned_asset('assets/vendor/sweetalert2/sweetalert2.min.js') ?>" defer></script>
    <script src="<?= versioned_asset('assets/js/alerts.js') ?>" defer></script>
</head>
<body>
<?php require __DIR__ . '/nav.php'; ?>
<main>
