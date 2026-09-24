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
        <a class="brand" href="<?= path() ?>">
            <span class="brand-name">Syspoint</span>
        </a>
        <button class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <ul class="nav-links" id="nav-links">
            <li><a href="<?= path() ?>" class="<?= $currentPath === '' ? 'is-active' : '' ?>">Home</a></li>
            <li><a href="<?= path('shop') ?>" class="<?= str_starts_with($currentPath, 'shop') ? 'is-active' : '' ?>">Shop</a></li>
            <li><a href="<?= path('software-clinic') ?>" class="<?= $currentPath === 'software-clinic' ? 'is-active' : '' ?>">Software Clinic</a></li>
            <li><a href="<?= path('about') ?>" class="<?= $currentPath === 'about' ? 'is-active' : '' ?>">About</a></li>
            <li><a href="<?= path('cart') ?>" class="<?= $currentPath === 'cart' ? 'is-active' : '' ?>">Cart<?php $cartCount = cart_count(); if ($cartCount > 0): ?> <span class="badge badge-success"><?= $cartCount ?></span><?php endif; ?></a></li>
            <li><a href="<?= path('contact') ?>" class="btn btn-primary btn-sm">Contact Us</a></li>
        </ul>
    </div>
</header>
