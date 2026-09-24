<?php
declare(strict_types=1);

$pageTitle = 'Contact';
$pageDescription = 'Get in touch with Syspoint.';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try submitting the form again.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        // Basic honeypot — a real visitor never sees or fills this field.
        $looksHuman = trim((string) ($_POST['website'] ?? '')) === '';

        if ($name === '') $errors[] = 'Please enter your name.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($message === '') $errors[] = 'Please enter a message.';

        if (!$errors) {
            if ($looksHuman) {
                db()->prepare(
                    'INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)'
                )->execute([
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject !== '' ? $subject : null,
                    'message' => $message,
                ]);
            }

            flash('success', "Thanks {$name}, your message has been received. We'll get back to you soon.");
            header('Location: ' . path('contact'));
            exit;
        }

        $_SESSION['old_input'] = compact('name', 'email', 'subject', 'message');
    }
}

$successMessage = flash('success');

require __DIR__ . '/../partials/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / Contact</div>
        <h1>Get in Touch</h1>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:560px;">
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= e($successMessage) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul style="margin:0;padding-left:1.2em;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card form-card">
            <form method="post" action="<?= path('contact') ?>" novalidate>
                <?= csrf_field() ?>
                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                    <label for="website">Leave this field empty</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= old('name') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject (optional)</label>
                    <input type="text" id="subject" name="subject" value="<?= old('subject') ?>">
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required><?= old('message') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send Message</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
