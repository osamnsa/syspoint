<?php
/**
 * Filters + results grid. Set before including:
 * $f (shop_filters_from_request), $res (shop_search), $baseUrl (form action / links),
 * $lockCategory (array|null: inside a category page), $allCategories (product_categories()).
 */
$naira = fn($v) => '₦' . number_format((float) $v);
$link = fn(array $change, bool $keepCats = true) => $baseUrl . shop_query($f, $change + ['page' => null], $keepCats);
$chips = [];
if ($f['q'] !== '') $chips[] = ['“' . $f['q'] . '”', $link(['q' => ''])];
if (!$lockCategory) foreach ($f['cats'] as $slug) {
    $name = ''; foreach ($allCategories as $c) if ($c['slug'] === $slug) $name = $c['name'];
    $chips[] = [$name ?: $slug, $link(['cat' => array_values(array_diff($f['cats'], [$slug]))])];
}
if ($f['min'] !== null || $f['max'] !== null) $chips[] = [($f['min'] !== null ? $naira($f['min']) : '₦0') . ' – ' . ($f['max'] !== null ? $naira($f['max']) : 'any'), $link(['min' => null, 'max' => null])];
if ($f['stock']) $chips[] = ['In stock', $link(['stock' => null])];
$bands = [[null, 50000, 'Under ₦50k'], [50000, 200000, '₦50k – ₦200k'], [200000, 1000000, '₦200k – ₦1m'], [1000000, null, 'Over ₦1m']];
?>
<div class="shop-results" id="results">
    <form class="shop-filters" method="get" action="<?= e($baseUrl) ?>#results" data-shop-filters>
        <div class="shop-filters-head">
            <strong>Filters</strong>
            <?php if ($chips): ?><a href="<?= e($baseUrl . ($f['q'] !== '' ? '?q=' . rawurlencode($f['q']) : '')) ?>#results" class="shop-filters-clear">Clear all</a><?php endif; ?>
        </div>
        <input type="hidden" name="q" value="<?= e($f['q']) ?>">
        <?php if (!$lockCategory && $allCategories): ?>
            <fieldset>
                <legend>Category</legend>
                <?php foreach ($allCategories as $c): $n = (int) ($res['facets'][$c['slug']] ?? 0); ?>
                    <label class="shop-check<?= $n ? '' : ' is-empty' ?>"><input type="checkbox" name="cat[]" value="<?= e($c['slug']) ?>" <?= in_array($c['slug'], $f['cats'], true) ? 'checked' : '' ?> data-auto> <span><?= e($c['name']) ?></span><small><?= $n ?></small></label>
                <?php endforeach; ?>
            </fieldset>
        <?php endif; ?>
        <fieldset>
            <legend>Price (₦)</legend>
            <div class="shop-price-inputs">
                <input type="number" name="min" min="0" step="1000" inputmode="numeric" value="<?= $f['min'] ?? '' ?>" placeholder="<?= $res['price_min'] !== null ? number_format($res['price_min'], 0, '.', '') : 'Min' ?>" aria-label="Minimum price">
                <span>–</span>
                <input type="number" name="max" min="0" step="1000" inputmode="numeric" value="<?= $f['max'] ?? '' ?>" placeholder="<?= $res['price_max'] !== null ? number_format($res['price_max'], 0, '.', '') : 'Max' ?>" aria-label="Maximum price">
            </div>
            <div class="shop-bands">
                <?php foreach ($bands as [$lo, $hi, $label]): $on = $f['min'] === $lo && $f['max'] === $hi; ?>
                    <a href="<?= e($link($on ? ['min' => null, 'max' => null] : ['min' => $lo, 'max' => $hi])) ?>#results" class="<?= $on ? 'is-on' : '' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <fieldset>
            <legend>Availability</legend>
            <label class="shop-check"><input type="checkbox" name="stock" value="1" <?= $f['stock'] ? 'checked' : '' ?> data-auto> <span>In stock only</span></label>
        </fieldset>
        <input type="hidden" name="sort" value="<?= e($f['sort']) ?>">
        <button type="submit" class="btn btn-primary btn-sm shop-filters-apply">Apply filters</button>
    </form>

    <div class="shop-results-main">
        <div class="shop-results-bar">
            <p class="shop-results-count"><strong><?= number_format($res['total']) ?></strong> product<?= $res['total'] === 1 ? '' : 's' ?><?= $f['q'] !== '' ? ' for “' . e($f['q']) . '”' : '' ?></p>
            <button type="button" class="btn btn-outline btn-sm shop-filters-toggle" data-shop-filters-toggle aria-expanded="false">Filters<?= $chips ? ' (' . count($chips) . ')' : '' ?></button>
            <label class="shop-sort">Sort
                <select data-shop-sort>
                    <?php foreach (SHOP_SORTS as $k => $label): if ($k === 'relevance' && $f['q'] === '') continue; ?>
                        <option value="<?= e($baseUrl . shop_query($f, ['sort' => $k, 'page' => null])) ?>#results" <?= $f['sort'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <?php if ($chips): ?>
            <div class="shop-chips">
                <?php foreach ($chips as [$label, $href]): ?><a href="<?= e($href) ?>#results"><?= e($label) ?> <span aria-label="Remove">×</span></a><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($res['items']): ?>
            <div class="product-grid">
                <?php foreach ($res['items'] as $product) require __DIR__ . '/product_card.php'; ?>
            </div>
            <?php if ($res['pages'] > 1): ?>
                <nav class="shop-pages" aria-label="Pages">
                    <?php if ($res['page'] > 1): ?><a href="<?= e($baseUrl . shop_query($f, ['page' => $res['page'] - 1])) ?>#results">← Prev</a><?php endif; ?>
                    <?php for ($i = 1; $i <= $res['pages']; $i++): ?>
                        <?php if ($i === $res['page']): ?><span aria-current="page"><?= $i ?></span><?php else: ?><a href="<?= e($baseUrl . shop_query($f, ['page' => $i > 1 ? $i : null])) ?>#results"><?= $i ?></a><?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($res['page'] < $res['pages']): ?><a href="<?= e($baseUrl . shop_query($f, ['page' => $res['page'] + 1])) ?>#results">Next →</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="shop-empty">
                <strong>No products match<?= $f['q'] !== '' ? ' “' . e($f['q']) . '”' : '' ?>.</strong>
                <p>Try fewer words, a wider price range<?= $lockCategory ? ', or <a href="' . e(path('shop/search') . ($f['q'] !== '' ? '?q=' . rawurlencode($f['q']) : '')) . '">search the whole shop</a>' : '' ?>. Looking for something specific? <a href="<?= path('contact') ?>">Ask us</a> — we can order it in.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
