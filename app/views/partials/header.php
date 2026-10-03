<?php
declare(strict_types=1);

/** @var string|null $pageTitle */
/** @var string|null $pageDescription */

$titleTag = (isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | ' : '') . 'Syspoint Hub';
$metaDescription = $pageDescription ?? 'Syspoint — computers & accessories, software clinic, gaming lounge, IT consulting, and internship training.';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titleTag) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,700;0,900;1,900&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&family=Press+Start+2P&display=swap">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/style.css') ?>">
</head>
<body>
<?php require __DIR__ . '/nav.php'; ?>
<main>
