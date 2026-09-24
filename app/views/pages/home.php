<?php
declare(strict_types=1);

$pageTitle = 'Home';
$pageDescription = 'Syspoint — computers & accessories, a software clinic, a gaming lounge, IT consulting, and internship training, all in one place.';

require __DIR__ . '/../partials/header.php';
?>

<section class="hero">
    <div class="container">
        <span class="eyebrow" style="color:var(--color-accent);">Syspoint</span>
        <h1><?= e(content_block('home.hero_title', 'Everything tech, in one place.')) ?></h1>
        <p><?= e(content_block('home.hero_subtitle', 'Computers and accessories, a software clinic that builds what your business needs, a gaming lounge with a VIP and common room, and a training centre turning out interns ready to work.')) ?></p>
        <div style="display:flex;gap:14px;margin-top:28px;flex-wrap:wrap;">
            <a href="<?= path('contact') ?>" class="btn btn-primary">Get in Touch</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <span class="eyebrow">What we do</span>
        <h2>Five ways we can help</h2>
        <div class="offer-grid">
            <a class="offer-card" href="<?= path('shop') ?>" style="text-decoration:none;color:inherit;">
                <div class="offer-icon">🖥️</div>
                <h3>Computers &amp; Accessories</h3>
                <p style="color:var(--color-text-muted);font-size:0.92rem;">Browse computers and accessories with photos, specs, and current prices — shop online now.</p>
            </a>
            <a class="offer-card" href="<?= path('software-clinic') ?>" style="text-decoration:none;color:inherit;">
                <div class="offer-icon">🛠️</div>
                <h3>Software Clinic</h3>
                <p style="color:var(--color-text-muted);font-size:0.92rem;">Tell us what your business needs built or deployed — we've done it for businesses across the region.</p>
            </a>
            <a class="offer-card" href="<?= path('gaming') ?>" style="text-decoration:none;color:inherit;">
                <div class="offer-icon">🎮</div>
                <h3>Gaming Lounge</h3>
                <p style="color:var(--color-text-muted);font-size:0.92rem;">Video games and board games, a VIP room and a common room — reserve your session.</p>
            </a>
            <div class="offer-card">
                <div class="offer-icon">📊</div>
                <h3>IT Consulting</h3>
                <p style="color:var(--color-text-muted);font-size:0.92rem;">Strategic technology consulting for individuals and businesses, via our sister consulting practice.</p>
            </div>
            <div class="offer-card">
                <div class="offer-icon">🎓</div>
                <h3>Internship &amp; Training</h3>
                <p style="color:var(--color-text-muted);font-size:0.92rem;">Hands-on courses and an internship program built to get you job-ready.</p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
