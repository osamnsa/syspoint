<?php
declare(strict_types=1);

$pageTitle = 'Home';
$pageDescription = 'Syspoint — computers & accessories, a software clinic, a gaming lounge, IT consulting, and internship training, all in one place.';

$stats = home_stats();
$clients = clients_active();
$testimonials = testimonials_active();

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

<?php if ($stats): ?>
<section class="stats-band" aria-label="Syspoint in numbers">
    <div class="container">
        <span class="eyebrow">Why Syspoint</span>
        <h2><?= e(content_block('home.stats_title', 'Solving problems with experience and expertise')) ?></h2>
        <dl class="stats-grid">
            <?php foreach ($stats as $label => $value): ?>
                <div class="stat">
                    <dt><?= e($label) ?></dt>
                    <dd><?= e($value) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>
<?php endif; ?>


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
            <?php $consultingUrl = config()['app']['consulting_url']; ?>
            <?php if ($consultingUrl): ?>
                <a class="offer-card" href="<?= e($consultingUrl) ?>" target="_blank" rel="noopener" style="text-decoration:none;color:inherit;">
                    <div class="offer-icon">📊</div>
                    <h3>IT Consulting</h3>
                    <p style="color:var(--color-text-muted);font-size:0.92rem;">Strategic technology consulting for individuals and businesses, via our sister consulting practice.</p>
                </a>
            <?php else: ?>
                <div class="offer-card">
                    <div class="offer-icon">📊</div>
                    <h3>IT Consulting</h3>
                    <p style="color:var(--color-text-muted);font-size:0.92rem;">Strategic technology consulting for individuals and businesses, via our sister consulting practice.</p>
                </div>
            <?php endif; ?>
            <a class="offer-card" href="<?= path('training') ?>" style="text-decoration:none;color:inherit;">
                <div class="offer-icon">🎓</div>
                <h3>Internship &amp; Training</h3>
                <p style="color:var(--color-text-muted);font-size:0.92rem;">Hands-on courses and an internship program built to get you job-ready.</p>
            </a>
        </div>
    </div>
</section>

<?php if ($clients): ?>
<section class="section section-soft">
    <div class="container">
        <span class="eyebrow">Our clients</span>
        <h2><?= e(content_block('home.clients_title', 'Organisations we have worked with')) ?></h2>
        <ul class="client-logos">
            <?php foreach ($clients as $client): ?>
                <li class="client-logo">
                    <?php $logo = $client['logo_path']
                        ? '<img src="' . media_url($client['logo_path']) . '" alt="' . e($client['name']) . '" loading="lazy">'
                        : '<span class="client-logo-name">' . e($client['name']) . '</span>'; ?>
                    <?php if ($client['website_url']): ?>
                        <a href="<?= e($client['website_url']) ?>" target="_blank" rel="noopener" title="<?= e($client['name']) ?>"><?= $logo ?></a>
                    <?php else: ?>
                        <?= $logo ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<?php if ($testimonials): ?>
<section class="section">
    <div class="container">
        <span class="eyebrow">Testimonials</span>
        <h2><?= e(content_block('home.testimonials_title', 'What our clients say')) ?></h2>
        <div class="testimonial-grid">
            <?php foreach ($testimonials as $t): ?>
                <figure class="testimonial">
                    <blockquote><?= e($t['quote']) ?></blockquote>
                    <figcaption>
                        <?php if ($t['photo_path']): ?>
                            <img class="testimonial-photo" src="<?= media_url($t['photo_path']) ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <span class="testimonial-photo testimonial-initials" aria-hidden="true"><?= e(initials($t['author_name'])) ?></span>
                        <?php endif; ?>
                        <span>
                            <strong><?= e($t['author_name']) ?></strong>
                            <?php if ($t['author_company']): ?><span class="testimonial-company"><?= e($t['author_company']) ?></span><?php endif; ?>
                        </span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
