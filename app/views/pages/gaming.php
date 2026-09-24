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

<div class="gaming-theme">

<section class="gaming-hero">
    <div class="container gaming-hero-inner">
        <div class="gaming-hero-copy">
            <span class="eyebrow">Play. Compete. Immerse.</span>
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
        <div class="gaming-hero-art" aria-hidden="true">
            <svg viewBox="0 0 440 380" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Illustration of a gaming monitor, controller, and VR headset">
                <defs>
                    <linearGradient id="gamingGlow" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#4fd1ff"/>
                        <stop offset="100%" stop-color="#9b6bff"/>
                    </linearGradient>
                    <linearGradient id="gamingBase" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#3a3d46"/>
                        <stop offset="100%" stop-color="#1c1e24"/>
                    </linearGradient>
                    <radialGradient id="gamingShadow" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#000000" stop-opacity="0.4"/>
                        <stop offset="100%" stop-color="#000000" stop-opacity="0"/>
                    </radialGradient>
                </defs>

                <ellipse cx="210" cy="300" rx="160" ry="20" fill="url(#gamingShadow)"/>

                <circle cx="50" cy="50" r="5" fill="#4fd1ff" opacity="0.5"/>
                <circle cx="400" cy="90" r="4" fill="#9b6bff" opacity="0.5"/>
                <circle cx="30" cy="230" r="3" fill="#ffffff" opacity="0.2"/>

                <!-- Monitor stand -->
                <rect x="196" y="196" width="28" height="24" fill="url(#gamingBase)"/>
                <path d="M150 220 L270 220 L282 234 Q284 238 279 238 L141 238 Q136 238 138 234 Z" fill="url(#gamingBase)"/>

                <!-- Monitor -->
                <rect x="56" y="26" width="308" height="176" rx="12" fill="#0c0e14"/>
                <rect x="70" y="40" width="280" height="148" rx="4" fill="#0a0d1a"/>

                <!-- HUD: crosshair -->
                <circle cx="210" cy="112" r="22" fill="none" stroke="url(#gamingGlow)" stroke-width="2" opacity="0.8"/>
                <line x1="210" y1="90" x2="210" y2="100" stroke="url(#gamingGlow)" stroke-width="2"/>
                <line x1="210" y1="124" x2="210" y2="134" stroke="url(#gamingGlow)" stroke-width="2"/>
                <line x1="188" y1="112" x2="198" y2="112" stroke="url(#gamingGlow)" stroke-width="2"/>
                <line x1="222" y1="112" x2="232" y2="112" stroke="url(#gamingGlow)" stroke-width="2"/>

                <!-- HUD: health bar -->
                <rect x="84" y="52" width="90" height="8" rx="4" fill="#1c2030"/>
                <rect x="84" y="52" width="66" height="8" rx="4" fill="#4fd1ff"/>

                <!-- HUD: minimap -->
                <circle cx="322" cy="70" r="24" fill="#1c2030"/>
                <circle cx="322" cy="70" r="24" fill="none" stroke="url(#gamingGlow)" stroke-width="1.5"/>
                <circle cx="316" cy="64" r="3" fill="#4fd1ff"/>
                <circle cx="330" cy="76" r="3" fill="#9b6bff"/>

                <!-- HUD: score chip -->
                <rect x="270" y="148" width="64" height="18" rx="9" fill="#1c2030"/>
                <rect x="278" y="153" width="30" height="6" rx="3" fill="url(#gamingGlow)"/>

                <!-- VR headset, floating lower-left, clear of the monitor -->
                <g transform="translate(0,196)">
                    <path d="M-16 30 Q-24 44 -12 58" fill="none" stroke="url(#gamingBase)" stroke-width="10" stroke-linecap="round"/>
                    <rect x="0" y="6" width="88" height="52" rx="20" fill="url(#gamingBase)"/>
                    <circle cx="24" cy="32" r="15" fill="#0a0d1a"/>
                    <circle cx="64" cy="32" r="15" fill="#0a0d1a"/>
                    <circle cx="24" cy="32" r="7" fill="url(#gamingGlow)" opacity="0.75"/>
                    <circle cx="64" cy="32" r="7" fill="url(#gamingGlow)" opacity="0.75"/>
                    <rect x="38" y="26" width="12" height="4" rx="2" fill="#0a0d1a"/>
                </g>

                <!-- Controller, floating front-center -->
                <g transform="translate(140,244)">
                    <path d="M10 20 Q10 0 34 0 L106 0 Q130 0 130 20 L130 34 Q130 58 108 54 L98 40 L42 40 L32 54 Q10 58 10 34 Z" fill="url(#gamingBase)"/>
                    <circle cx="40" cy="20" r="10" fill="#0d0e12"/>
                    <rect x="36" y="16" width="8" height="8" fill="#4a4e5a"/>
                    <rect x="33" y="17" width="14" height="6" fill="#4a4e5a"/>
                    <circle cx="100" cy="14" r="5" fill="#4fd1ff" opacity="0.85"/>
                    <circle cx="114" cy="20" r="5" fill="#9b6bff" opacity="0.85"/>
                    <circle cx="100" cy="26" r="5" fill="#ffffff" opacity="0.5"/>
                    <circle cx="86" cy="20" r="5" fill="#ffffff" opacity="0.7"/>
                    <circle cx="70" cy="30" r="9" fill="#0d0e12"/>
                    <circle cx="70" cy="30" r="5" fill="#2a2d38"/>
                </g>
            </svg>
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
                        <?php if ($game['price'] !== null): ?><p class="product-card-price"><?= format_naira((float) $game['price']) ?><?= $game['is_demo'] ? demo_badge() : '' ?></p><?php endif; ?>
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
                        <?php if ($game['price'] !== null): ?><p class="product-card-price"><?= format_naira((float) $game['price']) ?><?= $game['is_demo'] ? demo_badge() : '' ?></p><?php endif; ?>
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
                <h3>Ready to Play?</h3>
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

</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
