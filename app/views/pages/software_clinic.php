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
        // Basic honeypot — a real visitor never sees or fills this field.
        $looksHuman = trim((string) ($_POST['website'] ?? '')) === '';

        if ($businessName === '') $errors[] = 'Please enter your business name.';
        if ($contactName === '') $errors[] = 'Please enter your name.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($description === '') $errors[] = 'Please describe what you need.';

        if (!$errors) {
            if ($looksHuman) {
                db()->prepare(
                    'INSERT INTO software_requests (business_name, contact_name, email, phone, description)
                     VALUES (:business_name, :contact_name, :email, :phone, :description)'
                )->execute([
                    'business_name' => $businessName,
                    'contact_name' => $contactName,
                    'email' => $email,
                    'phone' => $phone !== '' ? $phone : null,
                    'description' => $description,
                ]);
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

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / Software Clinic</div>
        <h1>Software Clinic</h1>
        <p style="color:var(--color-text-muted);max-width:60ch;">Tell us what your business needs — a website, an app, a system to run your operations — and we'll follow up with a plan and a quote.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="checkout-grid">
            <div class="card form-card" style="margin:0;">
                <h3>Request Software</h3>

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
                    <div style="position:absolute;left:-9999px;" aria-hidden="true">
                        <label for="website">Leave this field empty</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>
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
                    <button type="submit" class="btn btn-primary btn-block">Send Request</button>
                </form>
            </div>

            <div>
                <h3 style="margin-top:0;">Businesses We've Deployed For</h3>
                <?php if (!$businesses): ?>
                    <p style="color:var(--color-text-muted);">Our portfolio is being updated — check back soon.</p>
                <?php else: ?>
                    <div class="portfolio-list">
                        <?php foreach ($businesses as $business): ?>
                            <div class="portfolio-item">
                                <?php if ($business['logo_path']): ?>
                                    <img src="<?= asset(e($business['logo_path'])) ?>" alt="<?= e($business['name']) ?>">
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
