<?php
declare(strict_types=1);

$currentPath = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''), '/');
$base = trim(base_path(), '/');
if ($base !== '' && str_starts_with($currentPath, $base)) {
    $currentPath = trim(substr($currentPath, strlen($base)), '/');
}
?>
<header class="site-header">
    <div class="container">
        <a class="brand" href="<?= path() ?>" aria-label="<?= e(site('site.brand_first') . ' ' . site('site.brand_second')) ?> home">
            <img class="brand-logo" src="<?= asset('assets/img/logo.png') ?>" alt="" width="40" height="40">
            <span class="brand-text">
                <span class="brand-name"><?= e(site('site.brand_first')) ?> <em><?= e(site('site.brand_second')) ?></em></span>
                <span class="brand-tagline"><?= e(site('site.tagline')) ?></span>
            </span>
        </a>
        <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <ul class="nav-links" id="nav-links">
            <li><a href="<?= path() ?>" class="<?= $currentPath === '' ? 'is-active' : '' ?>">Home</a></li>
            <li><a href="<?= path('about') ?>" class="<?= $currentPath === 'about' ? 'is-active' : '' ?>">About</a></li>
            <li><a href="<?= path('software-clinic') ?>" class="<?= $currentPath === 'software-clinic' ? 'is-active' : '' ?>">Software Clinic</a></li>
            <li><a href="<?= path('gaming') ?>" class="<?= str_starts_with($currentPath, 'gaming') ? 'is-active' : '' ?>">Gaming</a></li>
            <li><a href="<?= path('training') ?>" class="<?= $currentPath === 'training' ? 'is-active' : '' ?>">Training</a></li>
            <li><a href="<?= path('shop') ?>" class="<?= str_starts_with($currentPath, 'shop') ? 'is-active' : '' ?>">Shop</a></li>
            <?php if (site('site.consulting_url')): ?>
                <li><a href="<?= e(site('site.consulting_url')) ?>" target="_blank" rel="noopener">Consulting</a></li>
            <?php endif; ?>
            <?php $cartCount = cart_count(); ?>
            <li><a href="<?= path('cart') ?>" class="nav-cart <?= $currentPath === 'cart' ? 'is-active' : '' ?>" aria-label="Cart<?= $cartCount > 0 ? ', ' . $cartCount . ' item' . ($cartCount === 1 ? '' : 's') : '' ?>">
                <span class="nav-cart-icon" aria-hidden="true"></span>
                <?php if ($cartCount > 0): ?><span class="nav-cart-count" aria-hidden="true"><?= $cartCount ?></span><?php endif; ?>
            </a></li>
            <li><a href="<?= path('contact') ?>" class="btn btn-primary btn-sm">Contact Us</a></li>
        </ul>
    </div>
</header>
