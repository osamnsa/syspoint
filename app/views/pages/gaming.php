<?php
declare(strict_types=1);

$pageTitle = 'Gaming Lounge';
$pageDescription = 'PS5, VR, and board games — plus a VIP room and a common room you can reserve.';

$ps5Games = games_by_type('ps5');
$vrGames = games_by_type('vr');
$boardGames = games_by_type('board');
$rooms = gaming_rooms_active();

$successMessage = flash('booking_success');

require __DIR__ . '/../partials/header.php';
?>

<section class="gaming-hero">
    <div class="container gaming-hero-inner">
        <span class="eyebrow" style="color:var(--color-accent);">Play. Compete. Immerse.</span>
        <h1><?= e(content_block('gaming.hero_title', 'PS5, VR & Board Games — All Under One Roof')) ?></h1>
        <p><?= e(content_block('gaming.hero_subtitle', 'A full gaming lounge with the latest PS5 titles, immersive VR experiences, and a shelf of board games — plus a VIP room and a common room ready to book.')) ?></p>
        <div class="platform-badges">
            <span class="platform-badge">🎮 PS5</span>
            <span class="platform-badge">🥽 VR</span>
            <span class="platform-badge">🎲 Board Games</span>
        </div>
        <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:28px;">
            <a href="#rooms" class="btn btn-primary">Book a Room</a>
            <a href="#ps5" class="btn btn-outline btn-outline-light">Browse Games</a>
        </div>
    </div>
</section>

<?php if ($successMessage): ?>
<section class="section" style="padding-bottom:0;">
    <div class="container">
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    </div>
</section>
<?php endif; ?>

<section class="section" id="rooms">
    <div class="container">
        <span class="eyebrow">Reserve a Room</span>
        <h2>Rooms</h2>
        <?php if (!$rooms): ?>
            <p style="color:var(--color-text-muted);">Room details are being updated — check back soon.</p>
        <?php else: ?>
            <div class="category-grid">
                <?php foreach ($rooms as $room): ?>
                    <div class="category-card room-card">
                        <div class="category-card-img">
                            <?php if ($room['image_path']): ?>
                                <img src="<?= asset(e($room['image_path'])) ?>" alt="<?= e($room['name']) ?>">
                            <?php else: ?>
                                <span class="category-card-placeholder">🎮</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($room['name']) ?></h3>
                        <p><?= format_naira((float) $room['hourly_rate']) ?>/hour<?= $room['capacity'] ? ' · Up to ' . (int) $room['capacity'] . ' people' : '' ?></p>
                        <?php if ($room['description']): ?>
                            <p style="font-size:0.85rem;"><?= e($room['description']) ?></p>
                        <?php endif; ?>
                        <a href="<?= path('gaming/book/' . e($room['slug'])) ?>" class="btn btn-primary btn-sm" style="margin:0 14px 16px;">Book This Room</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section-soft" id="ps5">
    <div class="container">
        <span class="eyebrow">🎮 PS5</span>
        <h2>PS5 Game List</h2>
        <?php if (!$ps5Games): ?>
            <p style="color:var(--color-text-muted);">Our PS5 game list is being updated — check back soon.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($ps5Games as $game): ?>
                    <div class="product-card game-card">
                        <div class="product-card-img">
                            <?php if ($game['image_path']): ?>
                                <img src="<?= asset(e($game['image_path'])) ?>" alt="<?= e($game['name']) ?>">
                            <?php else: ?>
                                <span class="product-card-placeholder">🕹️</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($game['name']) ?></h3>
                        <?php if ($game['price'] !== null): ?><p class="product-card-price"><?= format_naira((float) $game['price']) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section" id="vr">
    <div class="container">
        <span class="eyebrow">🥽 VR</span>
        <h2>VR Experience List</h2>
        <?php if (!$vrGames): ?>
            <p style="color:var(--color-text-muted);">Our VR experience list is being updated — check back soon.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($vrGames as $game): ?>
                    <div class="product-card game-card">
                        <div class="product-card-img">
                            <?php if ($game['image_path']): ?>
                                <img src="<?= asset(e($game['image_path'])) ?>" alt="<?= e($game['name']) ?>">
                            <?php else: ?>
                                <span class="product-card-placeholder">🥽</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($game['name']) ?></h3>
                        <?php if ($game['price'] !== null): ?><p class="product-card-price"><?= format_naira((float) $game['price']) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section-soft" id="board">
    <div class="container">
        <span class="eyebrow">🎲 Board Games</span>
        <h2>Board Game List</h2>
        <?php if (!$boardGames): ?>
            <p style="color:var(--color-text-muted);">Our board game list is being updated — check back soon.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($boardGames as $game): ?>
                    <div class="product-card game-card">
                        <div class="product-card-img">
                            <?php if ($game['image_path']): ?>
                                <img src="<?= asset(e($game['image_path'])) ?>" alt="<?= e($game['name']) ?>">
                            <?php else: ?>
                                <span class="product-card-placeholder">🎲</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($game['name']) ?></h3>
                        <?php if ($game['price'] !== null): ?><p class="product-card-price"><?= format_naira((float) $game['price']) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="trust-badges">
    <div class="container trust-badges-grid">
        <div class="trust-badge">
            <span class="trust-badge-icon">🎮</span>
            <div><strong>Premium Setups</strong><span>Latest consoles &amp; VR rigs</span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🥽</span>
            <div><strong>All Platforms</strong><span>PS5, VR, and board games</span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🛋️</span>
            <div><strong>VIP &amp; Common Rooms</strong><span>Private or open shared space</span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">📅</span>
            <div><strong>Real Reservations</strong><span>Book a slot, we confirm it</span></div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
