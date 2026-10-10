<?php
/** Search box. Set $searchAction (URL), $searchValue, $searchPlaceholder before including. */
?>
<form class="shop-search" method="get" action="<?= e($searchAction) ?>" role="search">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
    <input type="search" name="q" value="<?= e($searchValue ?? '') ?>" placeholder="<?= e($searchPlaceholder ?? 'Search laptops, earbuds, chargers…') ?>" aria-label="Search products" autocomplete="off">
    <button type="submit" class="btn btn-primary">Search</button>
</form>
