<?php
declare(strict_types=1);

$pageTitle = 'Training & Internship';
$pageDescription = 'Our training centre, the courses we offer, and our internship program.';

$courses = training_courses_active();

require __DIR__ . '/../partials/header.php';
?>

<section class="training-hero">
    <svg class="home-swoosh" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#34348A" stroke-width="170" stroke-linecap="round" opacity="0.5" class="home-swoosh-band"/>
        <path d="M1320 40 C 1020 10, 820 160, 900 330 S 1040 640, 640 700 S 80 640, -120 900" fill="none" stroke="#F7CB1E" stroke-width="2.5" stroke-linecap="round" opacity="0.6" class="home-swoosh-line"/>
    </svg>
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
            <p class="training-empty" id="courses">Our course list is being updated — ask us about the next intake.</p>
        <?php endif; ?>

        <!-- Mentor feature: Charles Onuoha, CEO -->
        <div class="mentor" id="mentor">
            <div class="mentor-photos">
                <picture class="mentor-photo-alt">
                    <source srcset="<?= asset('assets/img/training/charles-duotone.webp') ?>" type="image/webp">
                    <img src="<?= asset('assets/img/training/charles-duotone.jpg') ?>" alt="" width="520" height="522" loading="lazy">
                </picture>
                <picture class="mentor-photo-main">
                    <source srcset="<?= asset('assets/img/training/charles-portrait.webp') ?>" type="image/webp">
                    <img src="<?= asset('assets/img/training/charles-portrait.jpg') ?>" alt="Charles Onuoha, CEO of Syspoint" width="720" height="790" loading="lazy">
                </picture>
                <span class="mentor-tag">CEO · Mentor</span>
            </div>
            <div class="mentor-copy">
                <span class="home-hero-kicker">Your mentor</span>
                <h2><?= e(content_block('training.external_title', 'Learn with Charles Onuoha')) ?></h2>
                <p class="mentor-role">CEO, Syspoint Solutions Consult Limited</p>
                <blockquote class="mentor-quote"><?= e(content_block('training.mentor_quote', 'Skills open doors. Mentorship shows you which ones to walk through.')) ?></blockquote>
                <p class="mentor-text"><?= e(content_block('training.external_body', 'Explore more courses, resources and mentorship at CharlesOnuoha.com.')) ?></p>
                <ul class="mentor-points">
                    <li>Hands-on guidance on real projects</li>
                    <li>Career direction from someone who builds businesses</li>
                    <li>A path from student to intern to professional</li>
                </ul>
                <div class="mentor-actions">
                    <a href="https://charlesonuoha.com" class="btn btn-primary" target="_blank" rel="noopener">Visit CharlesOnuoha.com →</a>
                    <a href="<?= path('contact') ?>" class="btn btn-outline btn-outline-light">Book a Mentorship Chat</a>
                </div>
            </div>
        </div>

        <div class="home-stats training-bar">
            <div class="training-bar-item">
                <strong><?= e(content_block('training.internship_title', 'Our Internship Concept')) ?></strong>
                <span><?= e(content_block('training.internship_short', 'Real work on real projects across sales, software and IT consulting — mentorship built in.')) ?></span>
            </div>
            <a href="<?= path('contact') ?>" class="btn btn-primary training-bar-cta">Ask About Internships</a>
        </div>

        <p class="home-hero-tagline"><span>Learn</span> • <span>Build</span> • <span>Intern</span> • <span>Grow</span></p>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
