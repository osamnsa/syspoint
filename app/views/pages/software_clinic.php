<?php
declare(strict_types=1);

$pageTitle = 'Software Clinic';
$pageDescription = 'Tell us what your business needs built or deployed — we quote and build it.';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try submitting the form again.';
    } else {
        $businessName = trim((string) ($_POST['business_name'] ?? ''));
        $contactName = trim((string) ($_POST['contact_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($businessName === '') $errors[] = 'Please enter your business name.';
        if ($contactName === '') $errors[] = 'Please enter your name.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($description === '') $errors[] = 'Please describe what you need.';

        if (!$errors) {
            $spam = spam_check('software_request', $email, [$businessName, $contactName, $description]);
            if ($spam['verdict'] !== 'blocked') {
                db()->prepare(
                    'INSERT INTO software_requests (business_name, contact_name, email, phone, description, is_spam, spam_reason, ip, user_agent)
                     VALUES (:business_name, :contact_name, :email, :phone, :description, :is_spam, :reason, :ip, :ua)'
                )->execute([
                    'business_name' => mb_substr($businessName, 0, 190),
                    'contact_name' => mb_substr($contactName, 0, 150),
                    'email' => mb_substr($email, 0, 190),
                    'phone' => $phone !== '' ? mb_substr($phone, 0, 30) : null,
                    'description' => mb_substr($description, 0, 10000),
                    'is_spam' => $spam['verdict'] === 'spam' ? 1 : 0,
                    'reason' => $spam['reason'],
                    'ip' => client_ip(),
                    'ua' => user_agent(),
                ]);
                $requestId = (int) db()->lastInsertId();
            }
            if ($spam['verdict'] === 'ok') {
                crm_link('software_requests', $requestId, $contactName, $email, $phone, 'website', $businessName);
                notify_staff('software_request', '💻 Software Clinic request — ' . $businessName, [
                    $contactName . ($phone !== '' ? ' · ' . $phone : '') . ' · ' . $email,
                    mb_strimwidth($description, 0, 300, '…'),
                ], 'admin/software-requests/' . $requestId);
            }

            flash('success', "Thanks {$contactName}, we've received your request and will be in touch soon.");
            header('Location: ' . path('software-clinic'));
            exit;
        }

        $_SESSION['old_input'] = compact('businessName', 'contactName', 'email', 'phone', 'description');
    }
}

$successMessage = flash('success');
$businesses = deployed_businesses_active();

require __DIR__ . '/../partials/header.php';
?>

<section class="page-hero clinic-hero">
    <?php require __DIR__ . '/../partials/page_hero_swoosh.php'; ?>
    <div class="container page-hero-inner">
        <div class="page-hero-copy">
            <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / Software Clinic</div>
            <span class="page-hero-kicker"><?= e(site('clinic.kicker')) ?></span>
            <h1><?= e(site('clinic.title')) ?><?php if (site('clinic.title_highlight') !== ''): ?> <span class="page-hero-highlight"><?= e(site('clinic.title_highlight')) ?></span><?php endif; ?></h1>
            <p class="page-hero-intro"><?= e(site('clinic.intro')) ?></p>
            <?php if ($chips = site_list('clinic.chips')): ?>
                <ul class="clinic-chips"><?php foreach ($chips as $chip): ?><li><?= e($chip) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <div class="page-hero-actions">
                <a href="#request" class="btn btn-primary"><?= e(site('clinic.btn_primary')) ?></a>
                <?php if ($businesses): ?><a href="#portfolio" class="btn btn-outline btn-outline-light"><?= e(site('clinic.btn_secondary')) ?></a><?php endif; ?>
            </div>
        </div>
        <div class="clinic-art">
            <div class="clinic-photo">
                <?= site_picture('clinic.hero_image', 'Hands typing code on a laptop', ['width' => 736, 'height' => 760, 'fetchpriority' => 'high']) ?>
            </div>
            <div class="clinic-code" aria-hidden="true">
                <div class="clinic-code-bar"><i></i><i></i><i></i><span>deploy.js</span></div>
                <div class="clinic-code-body">
                    <p class="cl cl1"><b>const</b> app = <em>build</em>(yourIdea);</p>
                    <p class="cl cl2"><b>await</b> app.<em>test</em>();</p>
                    <p class="cl cl3"><b>await</b> app.<em>deploy</em>(<s>'your-domain'</s>);</p>
                    <p class="cl cl4"><u>// ✓ live — and we look after it</u><span class="clinic-caret"></span></p>
                </div>
            </div>
            <div class="clinic-toast" aria-hidden="true"><span class="clinic-toast-icon">✓</span><span><strong>Deployed</strong><small>Live on your domain</small></span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="checkout-grid">
            <div class="card form-card" style="margin:0;" id="request">
                <h3><?= e(site('clinic.form_title')) ?></h3>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success"><?= e($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="alert alert-error">
                        <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= path('software-clinic') ?>" novalidate>
                    <?= csrf_field() ?>
                    <?= spam_fields() ?>
                    <div class="form-group">
                        <label for="business_name">Business Name</label>
                        <input type="text" id="business_name" name="business_name" value="<?= old('businessName') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_name">Your Name</label>
                        <input type="text" id="contact_name" name="contact_name" value="<?= old('contactName') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number (optional)</label>
                        <input type="text" id="phone" name="phone" value="<?= old('phone') ?>">
                    </div>
                    <div class="form-group">
                        <label for="description">What do you need built or deployed?</label>
                        <textarea id="description" name="description" required><?= old('description') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?= e(site('clinic.form_button')) ?></button>
                    <p class="form-legal">We use these details only to reply to you. See our <a href="<?= path('privacy-policy') ?>">Privacy Policy</a>.</p>
                </form>
            </div>

            <div id="portfolio">
                <h3 style="margin-top:0;"><?= e(site('clinic.portfolio_title')) ?></h3>
                <?php if (!$businesses): ?>
                    <p style="color:var(--color-text-muted);">Our portfolio is being updated — check back soon.</p>
                <?php else: ?>
                    <div class="portfolio-list">
                        <?php foreach ($businesses as $business): ?>
                            <div class="portfolio-item">
                                <?php if ($business['logo_path']): ?>
                                    <img src="<?= media_url($business['logo_path']) ?>" alt="<?= e($business['name']) ?>">
                                <?php else: ?>
                                    <span class="portfolio-item-placeholder">🏢</span>
                                <?php endif; ?>
                                <div>
                                    <strong><?= $business['website_url'] ? '<a href="' . e($business['website_url']) . '" target="_blank" rel="noopener">' . e($business['name']) . '</a>' : e($business['name']) ?></strong>
                                    <?php if ($business['description']): ?>
                                        <p><?= e($business['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
