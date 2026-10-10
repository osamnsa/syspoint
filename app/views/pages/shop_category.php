<?php
declare(strict_types=1);

/** @var array $params [category_slug] */

$category = product_category_by_slug($params[0] ?? '');
if (!$category) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$hero = category_hero($category);
$f = shop_filters_from_request($category);
$res = shop_search($f);
$allCategories = product_categories();
$lockCategory = $category;
$baseUrl = path('shop/' . $category['slug']);

// headline stats for the whole category (not the current filters)
$stats = db()->prepare('SELECT COUNT(*), MIN(price), SUM(stock_qty > 0) FROM products WHERE category_id = :id AND is_active = 1');
$stats->execute(['id' => $category['id']]);
[$catCount, $catFrom, $catInStock] = $stats->fetch(PDO::FETCH_NUM);

$pageTitle = $category['name'];
$pageDescription = $hero['text'];

require __DIR__ . '/../partials/header.php';
?>

<div class="shop-theme">
<section class="shop-cat-hero">
    <?php require __DIR__ . '/../partials/page_hero_swoosh.php'; ?>
    <div class="container shop-cat-hero-inner">
        <div class="shop-cat-hero-copy">
            <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / <a href="<?= path('shop') ?>">Shop</a> / <?= e($category['name']) ?></div>
            <span class="shop-kicker"><?= e($hero['kicker']) ?></span>
            <h1><?= e($hero['title']) ?><?php if ($hero['highlight'] !== ''): ?> <span class="shop-script"><?= e($hero['highlight']) ?></span><?php endif; ?></h1>
            <p class="shop-cat-hero-text"><?= e($hero['text']) ?></p>
            <?php $searchAction = $baseUrl; $searchValue = $f['q']; $searchPlaceholder = 'Search ' . mb_strtolower($category['name']) . '…'; require __DIR__ . '/../partials/shop_searchbar.php'; ?>
            <ul class="shop-cat-stats">
                <li><strong><?= (int) $catCount ?></strong><span>product<?= (int) $catCount === 1 ? '' : 's' ?></span></li>
                <?php if ($catFrom !== null): ?><li><strong><?= format_naira((float) $catFrom) ?></strong><span>starting from</span></li><?php endif; ?>
                <li><strong><?= (int) $catInStock ?></strong><span>in stock now</span></li>
            </ul>
        </div>
        <div class="shop-cat-hero-art">
            <span class="shop-hero-ring" aria-hidden="true"></span>
            <?php if ($hero['image']): ?>
                <img src="<?= media_url($hero['image']) ?>" alt="<?= e($category['name']) ?>" class="<?= str_ends_with($hero['image'], '.png') ? 'is-cutout' : 'is-photo' ?>" fetchpriority="high">
            <?php endif; ?>
        </div>
    </div>
    <nav class="container shop-cat-tabs" aria-label="Categories">
        <a href="<?= path('shop') ?>">All</a>
        <?php foreach ($allCategories as $c): ?><a href="<?= path('shop/' . e($c['slug'])) ?>"<?= $c['id'] === $category['id'] ? ' aria-current="page"' : '' ?>><?= e($c['name']) ?></a><?php endforeach; ?>
    </nav>
</section>

<section class="section">
    <div class="container">
        <?php require __DIR__ . '/../partials/shop_results.php'; ?>
    </div>
</section>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
