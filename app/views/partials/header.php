<?php
declare(strict_types=1);

/** @var string|null $pageTitle */
/** @var string|null $pageDescription */

$titleTag = (isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | ' : '') . 'Syspoint';
$metaDescription = $pageDescription ?? 'Syspoint — computers & accessories, software clinic, gaming lounge, IT consulting, and internship training.';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titleTag) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/style.css') ?>">
</head>
<body>
<?php require __DIR__ . '/nav.php'; ?>
<main>
