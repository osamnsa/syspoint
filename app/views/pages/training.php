<?php
declare(strict_types=1);

$pageTitle = 'Training & Internship';
$pageDescription = 'Our training centre, the courses we offer, and our internship program.';

$courses = training_courses_active();

require __DIR__ . '/../partials/header.php';
?>

<section class="training-hero">
    <div class="container training-hero-inner">
        <div class="training-hero-copy">
            <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / Training &amp; Internship</div>
            <span class="training-kicker">Unlock your tech potential</span>
            <h1 class="training-title">
                <span class="training-script">The</span>
                <span class="training-line">IT Training</span>
                <span class="training-line">&amp; Internship</span>
                <span class="training-line">Program</span>
            </h1>
            <p class="training-intro"><?= e(content_block('training.intro', 'Hands-on courses and an internship program built to get you job-ready.')) ?></p>
            <div class="training-actions">
                <a href="#courses" class="btn btn-primary">See Our Courses</a>
                <a href="<?= path('contact') ?>" class="btn btn-outline btn-outline-light">Ask About Internships</a>
            </div>
        </div>

        <div class="training-hero-art">
            <svg class="training-badge" viewBox="0 0 120 120" aria-hidden="true">
                <defs><path id="badgeRing" d="M60,60 m-44,0 a44,44 0 1,1 88,0 a44,44 0 1,1 -88,0"/></defs>
                <circle cx="60" cy="60" r="58" fill="#F7CB1E"/>
                <text font-family="IBM Plex Mono, monospace" font-size="8.6" font-weight="600" fill="#14143A">
                    <textPath href="#badgeRing" textLength="272" lengthAdjust="spacing">SYSPOINT HUB • IT TRAINING • INTERNSHIP •</textPath>
                </text>
                <text x="60" y="69" text-anchor="middle" font-family="Nunito, sans-serif" font-weight="900" font-size="26" fill="#14143A">IT</text>
            </svg>
            <picture>
                <source srcset="<?= asset('assets/img/training/hero-student.webp') ?>" type="image/webp">
                <img src="<?= asset('assets/img/training/hero-student.jpg') ?>" alt="A student, seen from above, working on a laptop showing the Syspoint Hub website" width="920" height="832" fetchpriority="high">
            </picture>
        </div>
    </div>

    <div class="training-strip">
        <div class="container">
            <p>
                <?php if ($courses): ?>
                    <?= implode(' <span aria-hidden="true">|</span> ', array_map(fn ($c) => e($c['title']), array_slice($courses, 0, 6))) ?>
                <?php else: ?>
                    Hands-on courses <span aria-hidden="true">|</span> Real projects <span aria-hidden="true">|</span> Mentorship <span aria-hidden="true">|</span> Internships
                <?php endif; ?>
            </p>
        </div>
    </div>
</section>

<section class="section" id="courses">
    <div class="container">
        <span class="eyebrow">Courses</span>
        <h2>What We Teach</h2>
        <?php if (!$courses): ?>
            <p style="color:var(--color-text-muted);">Our course list is being updated — check back soon.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="product-card course-card">
                        <div class="product-card-img">
                            <?php if ($course['image_path']): ?>
                                <img src="<?= asset(e($course['image_path'])) ?>" alt="<?= e($course['title']) ?>">
                            <?php else: ?>
                                <span class="product-card-placeholder">🎓</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($course['title']) ?></h3>
                        <?php if ($course['description']): ?>
                            <p style="font-size:0.85rem;margin:0 14px 8px;"><?= e($course['description']) ?></p>
                        <?php endif; ?>
                        <p class="product-card-price">
                            <?php if ($course['duration_label']): ?><?= e($course['duration_label']) ?><?php endif; ?>
                            <?php if ($course['duration_label'] && $course['price'] !== null): ?> · <?php endif; ?>
                            <?php if ($course['price'] !== null): ?><?= format_naira((float) $course['price']) ?><?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section-soft">
    <div class="container" style="max-width:760px;">
        <span class="eyebrow">Internship Program</span>
        <h2><?= e(content_block('training.internship_title', 'Our Internship Concept')) ?></h2>
        <div style="color:var(--color-text-muted);">
            <?= nl2br(e(content_block('training.internship_body', "We take on interns to work alongside our team across gadget sales, software development, and IT consulting — real work on real projects, with mentorship built in. Reach out through our Contact page if you'd like to apply."))) ?>
        </div>
        <a href="<?= path('contact') ?>" class="btn btn-primary" style="margin-top:20px;">Ask About Internships</a>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
