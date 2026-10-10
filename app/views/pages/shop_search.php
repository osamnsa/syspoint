<?php
declare(strict_types=1);

$f = shop_filters_from_request();
$res = shop_search($f);
$allCategories = product_categories();
$lockCategory = null;
$baseUrl = path('shop/search');

$pageTitle = $f['q'] !== '' ? 'Search: ' . $f['q'] : 'Search the Shop';
$pageDescription = 'Search Syspoint’s gadget store — laptops, phones, audio, smartwatches and accessories, filtered by price and availability.';

require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">
<section class="shop-subhero">
    <?php require __DIR__ . '/../partials/page_hero_swoosh.php'; ?>
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / <a href="<?= path('shop') ?>">Shop</a> / Search</div>
        <span class="shop-kicker">Search the shop</span>
        <h1><?= $f['q'] !== '' ? 'Results for <span class="shop-script">“' . e($f['q']) . '”</span>' : 'Find your next <span class="shop-script">gadget</span>' ?></h1>
        <?php $searchAction = $baseUrl; $searchValue = $f['q']; require __DIR__ . '/../partials/shop_searchbar.php'; ?>
        <div class="shop-subhero-cats">
            <?php foreach ($allCategories as $c): ?><a href="<?= path('shop/' . e($c['slug'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php require __DIR__ . '/../partials/shop_results.php'; ?>
    </div>
</section>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
