<?php
declare(strict_types=1);

/** @var string|null $pageTitle */
/** @var string|null $pageDescription */

$siteName = site('site.brand_first') . ' ' . site('site.brand_second');
$titleTag = (isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | ' : '') . $siteName;
$metaDescription = $pageDescription ?? site('site.meta_description');
// Canonical address: this page without tracking/filter query strings.
$reqPath = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$base = trim(base_path(), '/');
if ($base !== '' && str_starts_with($reqPath, $base)) $reqPath = trim(substr($reqPath, strlen($base)), '/');
$canonical = public_url($reqPath);
// Keep private and duplicate pages out of Google.
$noindex = $noindex ?? (bool) preg_match('#^(cart|checkout|order/|shop/search)#', $reqPath) || !empty($_SERVER['QUERY_STRING']) && preg_match('#^shop/#', $reqPath);
$gsc = trim(site('site.google_verification'));
if (preg_match('/content=["\']([^"\']+)/', $gsc, $m)) $gsc = $m[1];
$ogImage = public_url('assets/img/home/hero-desk-gaming.jpg');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titleTag) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php if ($noindex): ?><meta name="robots" content="noindex, follow"><?php else: ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
    <?php if ($gsc !== ''): ?><meta name="google-site-verification" content="<?= e($gsc) ?>"><?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($titleTag) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
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
