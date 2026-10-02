<?php
declare(strict_types=1);

$pageTitle = 'Gaming Lounge';
$pageDescription = 'PS5, a VR arena, and board games — plus free internet for every gamer and a VIP room and common room you can reserve.';

$ps5Games = games_by_type('ps5');
$vrGames = games_by_type('vr');
$boardGames = games_by_type('board');
$rooms = gaming_rooms_active();

$successMessage = flash('booking_success');

require __DIR__ . '/../partials/header.php';
?>

<div class="gaming-theme gaming-arcade">

<section class="arcade-hero">
    <div class="arcade-floor" aria-hidden="true"></div>
    <div class="container arcade-hero-inner">
        <div class="arcade-hero-copy">
            <span class="eyebrow">...challenging conventions</span>
            <h1 class="arcade-title"><?= e(content_block('gaming.hero_title', 'Play. Immerse. Learn.')) ?></h1>
            <p><?= e(content_block('gaming.hero_subtitle', 'PS5, VR and board games — all under one roof, with free internet for every gamer. Grab a controller, step into VR, or book the VIP room for your squad.')) ?></p>
        </div>

        <p class="arcade-select-label">Select your game</p>
        <div class="arcade-select">
            <a class="arcade-card" href="#ps5">
                <picture>
                    <source srcset="<?= asset('assets/img/gaming/ps5.webp') ?>" type="image/webp">
                    <img src="<?= asset('assets/img/gaming/ps5.jpg') ?>" alt="PS5 console and controller" width="680" height="907" fetchpriority="high">
                </picture>
                <span class="arcade-card-body">
                    <span class="arcade-card-name">PS5</span>
                    <span class="arcade-card-sub">The latest PS5 titles</span>
                </span>
            </a>
            <a class="arcade-card" href="#vr">
                <picture>
                    <source srcset="<?= asset('assets/img/gaming/vr.webp') ?>" type="image/webp">
                    <img src="<?= asset('assets/img/gaming/vr.jpg') ?>" alt="Player wearing a VR headset" width="406" height="473">
                </picture>
                <span class="arcade-card-body">
                    <span class="arcade-card-name">VR Arena</span>
                    <span class="arcade-card-sub">Step inside the game</span>
                </span>
            </a>
            <a class="arcade-card" href="#board">
                <picture>
                    <source srcset="<?= asset('assets/img/gaming/board.webp') ?>" type="image/webp">
                    <img src="<?= asset('assets/img/gaming/board.jpg') ?>" alt="Dice and game pieces over a ludo board" width="640" height="695">
                </picture>
                <span class="arcade-card-body">
                    <span class="arcade-card-name">Board Games</span>
                    <span class="arcade-card-sub">Classic &amp; modern table games</span>
                </span>
            </a>
        </div>

        <div class="arcade-actions">
            <a href="#rooms" class="btn btn-primary">Book a Room</a>
            <span class="gold-tag">Free internet for all gamers</span>
        </div>
    </div>

    <div class="arcade-marquee" aria-hidden="true">
        <div class="arcade-marquee-track">
            <?php for ($i = 0; $i < 2; $i++): ?>
                <span>PS5</span><span>★</span><span>VR Arena</span><span>★</span><span>Board Games</span><span>★</span><span>Free internet for all gamers</span><span>★</span><span>VIP &amp; Common Rooms</span><span>★</span><span>Open 9am – 10pm</span><span>★</span>
            <?php endfor; ?>
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
        <span class="eyebrow">Stage 01 · Reserve a room</span>
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
                        <p><?= format_naira((float) $room['hourly_rate']) ?>/hour<?= $room['is_demo'] ? demo_badge() : '' ?><?= $room['capacity'] ? ' · Up to ' . (int) $room['capacity'] . ' people' : '' ?></p>
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
        <span class="eyebrow">Stage 02 · PS5</span>
        <h2>PS5 Game List</h2>
        <p class="section-lede">The latest PS5 titles, ready to play.</p>
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
                                <span class="product-card-placeholder">🎮</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($game['name']) ?></h3>
                        <?php if ($game['price'] !== null): ?><p class="product-card-price"><?= format_naira((float) $game['price']) ?><?= $game['is_demo'] ? demo_badge() : '' ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section" id="vr">
    <div class="container">
        <span class="eyebrow">Stage 03 · VR Arena</span>
        <h2>VR Experience List</h2>
        <p class="section-lede">Step inside the game with virtual reality.</p>
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
                        <?php if ($game['price'] !== null): ?><p class="product-card-price"><?= format_naira((float) $game['price']) ?><?= $game['is_demo'] ? demo_badge() : '' ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section-soft" id="board">
    <div class="container">
        <span class="eyebrow">Stage 04 · Board Games</span>
        <h2>Board Game List</h2>
        <p class="section-lede">Classic and modern indoor table games.</p>
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
                        <?php if ($game['price'] !== null): ?><p class="product-card-price"><?= format_naira((float) $game['price']) ?><?= $game['is_demo'] ? demo_badge() : '' ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="gaming-cta-banner">
            <div>
                <h3>Ready, Player One?</h3>
                <p>Grab a PS5 controller, strap in for VR, or pull up a board — book your room now.</p>
            </div>
            <a href="#rooms" class="btn btn-primary">Book Your Session →</a>
        </div>
    </div>
</section>

<section class="trust-badges">
    <div class="container trust-badges-grid">
        <div class="trust-badge">
            <span class="trust-badge-icon">🎮</span>
            <div><strong>Premium Setups</strong><span>Latest PS5 consoles &amp; VR rigs</span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🥽</span>
            <div><strong>Free Internet</strong><span>For every gamer, every visit</span></div>
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

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
