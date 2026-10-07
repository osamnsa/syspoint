<?php
declare(strict_types=1);

$pageTitle = 'Training & Internship';
$pageDescription = 'Our training centre, the courses we offer, and our internship program.';

$courses = training_courses_active();

require __DIR__ . '/../partials/header.php';
?>

<section class="training-hero">
    <!-- Desktop: one swoosh running top to bottom, behind the student photo,
         across the course cards and down behind Charles's photos (text stays clear). -->
    <svg class="training-swoosh" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
        <path d="M75 -2 C 76 12, 76 26, 70 36 C 62 48, 34 46, 28 58 C 22 70, 26 82, 22 92 C 20 97, 17 100, 15 102" fill="none" stroke="#34348A" stroke-width="170" stroke-linecap="round" opacity="0.5" vector-effect="non-scaling-stroke" class="home-swoosh-band"/>
        <path d="M75 -2 C 76 12, 76 26, 70 36 C 62 48, 34 46, 28 58 C 22 70, 26 82, 22 92 C 20 97, 17 100, 15 102" fill="none" stroke="#F7CB1E" stroke-width="2.5" stroke-linecap="round" opacity="0.6" vector-effect="non-scaling-stroke" class="home-swoosh-line"/>
    </svg>
    <!-- Phones: the stacked layout keeps the home page's swoosh. -->
    <svg class="home-swoosh training-swoosh-mobile" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#34348A" stroke-width="170" stroke-linecap="round" opacity="0.5" class="home-swoosh-band"/>
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#F7CB1E" stroke-width="2.5" stroke-linecap="round" opacity="0.6" class="home-swoosh-line"/>
    </svg>
    <div class="container training-hero-inner">
        <div class="training-hero-copy">
            <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / Training &amp; Internship</div>
            <span class="training-kicker"><?= e(site('training.kicker')) ?></span>
            <h1 class="training-title">
                <?php if (site('training.title_script') !== ''): ?><span class="training-script"><?= e(site('training.title_script')) ?></span><?php endif; ?>
                <?php foreach (site_list('training.title_lines', "\n") as $line): ?><span class="training-line"><?= e($line) ?></span><?php endforeach; ?>
            </h1>
            <p class="training-intro"><?= e(site('training.intro')) ?></p>
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
            <?= site_picture('training.hero_image', 'A student, seen from above, working on a laptop showing the Syspoint Hub website', ['width' => 920, 'height' => 832, 'fetchpriority' => 'high']) ?>
        </div>
    </div>

    <div class="container">
        <?php if ($courses): ?>
            <div class="home-services training-courses" id="courses">
                <?php foreach ($courses as $i => $course): ?>
                    <div class="home-service<?= $i === 0 ? ' is-featured' : '' ?>">
                        <span class="home-service-icon">
                            <?php if ($course['image_path']): ?>
                                <img src="<?= asset(e($course['image_path'])) ?>" alt="">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5M22 9v6"/></svg>
                            <?php endif; ?>
                        </span>
                        <strong><?= e($course['title']) ?></strong>
                        <?php if ($course['description']): ?><span class="home-service-text"><?= e($course['description']) ?></span><?php endif; ?>
                        <?php if ($course['duration_label'] || $course['price'] !== null): ?>
                            <span class="training-course-meta"><?= $course['duration_label'] ? e($course['duration_label']) : '' ?><?= $course['duration_label'] && $course['price'] !== null ? ' · ' : '' ?><?= $course['price'] !== null ? format_naira((float) $course['price']) : '' ?></span>
                        <?php endif; ?>
                        <span class="home-service-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="training-empty" id="courses"><?= e(site('training.empty')) ?></p>
        <?php endif; ?>

        <!-- Mentor feature: Charles Onuoha, CEO -->
        <div class="mentor" id="mentor">
            <div class="mentor-photos">
                <?= site_picture('training.mentor_photo_alt', '', ['width' => 520, 'height' => 522, 'loading' => 'lazy'], 'mentor-photo-alt') ?>
                <?= site_picture('training.mentor_photo', preg_replace('/^Learn with /', '', site('training.external_title')), ['width' => 720, 'height' => 790, 'loading' => 'lazy'], 'mentor-photo-main') ?>
                <span class="mentor-tag"><?= e(site('training.mentor_tag')) ?></span>
            </div>
            <div class="mentor-copy">
                <span class="home-hero-kicker"><?= e(site('training.mentor_kicker')) ?></span>
                <h2><?= e(site('training.external_title')) ?></h2>
                <p class="mentor-role"><?= e(site('training.mentor_role')) ?><?php if (site('training.mentor_org') !== ''): ?><br>Founder, <?= site('training.mentor_org_url') ? '<a href="' . e(site('training.mentor_org_url')) . '" target="_blank" rel="noopener">' . e(site('training.mentor_org')) . '</a>' : e(site('training.mentor_org')) ?><?php endif; ?></p>
                <blockquote class="mentor-quote"><?= e(site('training.mentor_quote')) ?></blockquote>
                <p class="mentor-text"><?= e(site('training.external_body')) ?></p>
                <ul class="mentor-points">
                    <?php foreach (site_list('training.mentor_points', "\n") as $point): ?><li><?= e($point) ?></li><?php endforeach; ?>
                </ul>
                <div class="mentor-actions">
                    <a href="<?= e(site('training.mentor_btn_url')) ?>" class="btn btn-primary" target="_blank" rel="noopener"><span class="label-full"><?= e(site('training.mentor_btn')) ?></span><span class="label-short"><?= e(site('training.mentor_btn_short')) ?></span></a>
                    <a href="<?= path('contact') ?>" class="btn btn-outline btn-outline-light"><span class="label-full"><?= e(site('training.call_btn')) ?></span><span class="label-short"><?= e(site('training.call_btn_short')) ?></span></a>
                </div>
            </div>
        </div>

        <div class="home-stats training-bar">
            <div class="training-bar-item">
                <strong><?= e(site('training.internship_title')) ?></strong>
                <span><?= e(site('training.internship_short')) ?></span>
            </div>
            <a href="<?= e(site('training.internship_url')) ?>" class="btn btn-primary training-bar-cta" target="_blank" rel="noopener"><?= e(site('training.internship_btn')) ?></a>
        </div>

        <p class="home-hero-tagline"><?= implode(' • ', array_map(fn($w) => '<span>' . e($w) . '</span>', site_list('training.tagline'))) ?></p>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
