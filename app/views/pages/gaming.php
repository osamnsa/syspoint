<?php
declare(strict_types=1);

$pageTitle = 'Gaming Lounge';
$pageDescription = 'Video games and board games, a VIP room and a common room — reserve your session.';

$videoGames = games_by_type('video');
$boardGames = games_by_type('board');
$rooms = gaming_rooms_active();

$successMessage = flash('booking_success');

require __DIR__ . '/../partials/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / Gaming Lounge</div>
        <h1>Gaming Lounge</h1>
        <p style="color:var(--color-text-muted);max-width:60ch;">Video games and board games for every kind of player, plus a VIP room and a common room you can reserve.</p>
    </div>
</section>

<?php if ($successMessage): ?>
<section class="section" style="padding-bottom:0;">
    <div class="container">
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    </div>
</section>
<?php endif; ?>

<section class="section">
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

<section class="section section-soft">
    <div class="container">
        <span class="eyebrow">Video Games</span>
        <h2>Video Game List</h2>
        <?php if (!$videoGames): ?>
            <p style="color:var(--color-text-muted);">Our video game list is being updated — check back soon.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($videoGames as $game): ?>
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

<section class="section">
    <div class="container">
        <span class="eyebrow">Board Games</span>
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

<?php require __DIR__ . '/../partials/footer.php'; ?>
