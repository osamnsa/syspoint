<?php
declare(strict_types=1);

$pageTitle = 'Training & Internship';
$pageDescription = 'Our training centre, the courses we offer, and our internship program.';

$courses = training_courses_active();

require __DIR__ . '/../partials/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / Training &amp; Internship</div>
        <h1>Training &amp; Internship</h1>
        <p style="color:var(--color-text-muted);max-width:60ch;"><?= e(content_block('training.intro', 'Hands-on courses and an internship program built to get you job-ready.')) ?></p>
    </div>
</section>

<section class="section">
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
