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
            <span class="eyebrow"><?= e(site('gaming.kicker')) ?></span>
            <h1 class="arcade-title"><?= e(site('gaming.hero_title')) ?></h1>
            <p><?= e(site('gaming.hero_subtitle')) ?></p>
        </div>

        <p class="arcade-select-label"><?= e(site('gaming.select_label')) ?></p>
        <div class="arcade-select">
            <a class="arcade-card" href="#ps5">
                <?= site_picture('gaming.card1_image', 'PS5 console and controller', ['width' => 680, 'height' => 907, 'fetchpriority' => 'high']) ?>
                <span class="arcade-card-body">
                    <span class="arcade-card-name"><?= e(site('gaming.card1_name')) ?></span>
                    <span class="arcade-card-sub"><?= e(site('gaming.card1_sub')) ?></span>
                </span>
            </a>
            <a class="arcade-card" href="#vr">
                <?= site_picture('gaming.card2_image', 'Player wearing a VR headset', ['width' => 406, 'height' => 473]) ?>
                <span class="arcade-card-body">
                    <span class="arcade-card-name"><?= e(site('gaming.card2_name')) ?></span>
                    <span class="arcade-card-sub"><?= e(site('gaming.card2_sub')) ?></span>
                </span>
            </a>
            <a class="arcade-card" href="#board">
                <?= site_picture('gaming.card3_image', 'Dice and game pieces over a ludo board', ['width' => 640, 'height' => 695]) ?>
                <span class="arcade-card-body">
                    <span class="arcade-card-name"><?= e(site('gaming.card3_name')) ?></span>
                    <span class="arcade-card-sub"><?= e(site('gaming.card3_sub')) ?></span>
                </span>
            </a>
        </div>

        <div class="arcade-actions">
            <a href="#rooms" class="btn btn-primary"><?= e(site('gaming.book_btn')) ?></a>
            <span class="gold-tag"><?= e(site('gaming.free_tag')) ?></span>
        </div>
    </div>

    <div class="arcade-marquee" aria-hidden="true">
        <div class="arcade-marquee-track">
            <?php for ($i = 0; $i < 2; $i++): ?>
                <?php foreach (site_list('gaming.marquee') as $phrase): ?><span><?= e($phrase) ?></span><span>★</span><?php endforeach; ?>
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
        <span class="eyebrow"><?= e(site('gaming.rooms_eyebrow')) ?></span>
        <h2><?= e(site('gaming.rooms_title')) ?></h2>
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
        <span class="eyebrow"><?= e(site('gaming.ps5_eyebrow')) ?></span>
        <h2><?= e(site('gaming.ps5_title')) ?></h2>
        <p class="section-lede"><?= e(site('gaming.ps5_lede')) ?></p>
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
        <span class="eyebrow"><?= e(site('gaming.vr_eyebrow')) ?></span>
        <h2><?= e(site('gaming.vr_title')) ?></h2>
        <p class="section-lede"><?= e(site('gaming.vr_lede')) ?></p>
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
        <span class="eyebrow"><?= e(site('gaming.board_eyebrow')) ?></span>
        <h2><?= e(site('gaming.board_title')) ?></h2>
        <p class="section-lede"><?= e(site('gaming.board_lede')) ?></p>
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
                <h3><?= e(site('gaming.cta_title')) ?></h3>
                <p><?= e(site('gaming.cta_text')) ?></p>
            </div>
            <a href="#rooms" class="btn btn-primary"><?= e(site('gaming.cta_btn')) ?></a>
        </div>
    </div>
</section>

<section class="trust-badges">
    <div class="container trust-badges-grid">
        <div class="trust-badge">
            <span class="trust-badge-icon">🎮</span>
            <div><strong><?= e(site('gaming.badge1_title')) ?></strong><span><?= e(site('gaming.badge1_text')) ?></span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🥽</span>
            <div><strong><?= e(site('gaming.badge2_title')) ?></strong><span><?= e(site('gaming.badge2_text')) ?></span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">🛋️</span>
            <div><strong><?= e(site('gaming.badge3_title')) ?></strong><span><?= e(site('gaming.badge3_text')) ?></span></div>
        </div>
        <div class="trust-badge">
            <span class="trust-badge-icon">📅</span>
            <div><strong><?= e(site('gaming.badge4_title')) ?></strong><span><?= e(site('gaming.badge4_text')) ?></span></div>
        </div>
    </div>
</section>

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
