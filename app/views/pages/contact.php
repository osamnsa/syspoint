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

        if ($name === '') $errors[] = 'Please enter your name.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($message === '') $errors[] = 'Please enter a message.';

        if (!$errors) {
            $spam = spam_check('contact', $email, [$name, $subject, $message]);
            if ($spam['verdict'] !== 'blocked') {
                db()->prepare(
                    'INSERT INTO contact_messages (name, email, subject, message, is_spam, spam_reason, ip, user_agent)
                     VALUES (:name, :email, :subject, :message, :is_spam, :reason, :ip, :ua)'
                )->execute([
                    'name' => mb_substr($name, 0, 150),
                    'email' => mb_substr($email, 0, 190),
                    'subject' => $subject !== '' ? mb_substr($subject, 0, 190) : null,
                    'message' => mb_substr($message, 0, 10000),
                    'is_spam' => $spam['verdict'] === 'spam' ? 1 : 0,
                    'reason' => $spam['reason'],
                    'ip' => client_ip(),
                    'ua' => user_agent(),
                ]);
                $messageId = (int) db()->lastInsertId();
            }
            if ($spam['verdict'] === 'ok') {
                crm_link('contact_messages', $messageId, $name, $email, null);
                notify_staff('contact', '✉️ New message' . ($subject !== '' ? ' — ' . $subject : ''), [
                    $name . ' · ' . $email,
                    mb_strimwidth($message, 0, 300, '…'),
                ], 'admin/messages/' . $messageId);
            }

            flash('success', str_replace('{name}', $name, site('contact.success')));
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
        <h1><?= e(site('contact.title')) ?></h1>
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
                <?= spam_fields() ?>
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
                <button type="submit" class="btn btn-primary btn-block"><?= e(site('contact.button')) ?></button>
                <p class="form-legal">We use these details only to reply to you. See our <a href="<?= path('privacy-policy') ?>">Privacy Policy</a>.</p>
            </form>
        </div>

        <div class="card" style="margin-top:24px;">
            <span class="eyebrow">Find us</span>
            <p style="margin:4px 0 14px;">Both are in <?= e(site('site.plaza')) ?>.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                <address style="font-style:normal;line-height:1.6;">
                    <strong><?= e(site('site.hub_name')) ?></strong><br>
                    <?= e(site('site.hub_short')) ?><br>
                    <?= e(site('site.hub_suite')) ?>
                </address>
                <address style="font-style:normal;line-height:1.6;">
                    <strong><?= e(site('site.store_name')) ?></strong><br>
                    <?= e(site('site.store_short')) ?><br>
                    <?= e(site('site.store_suite')) ?>
                </address>
            </div>
            <p style="margin:14px 0 0;color:var(--color-text-muted);font-size:0.9rem;">
                <a href="mailto:<?= e(site('site.email')) ?>"><?= e(site('site.email')) ?></a><?php if (site('site.phone')): ?> · <a href="tel:<?= e(preg_replace('/[^\d+]/', '', site('site.phone'))) ?>"><?= e(site('site.phone')) ?></a><?php endif; ?><?php if ($wa = site_whatsapp_url()): ?> · <a href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?><?php if ($tg = telegram_channel_url()): ?> · <a href="<?= e($tg) ?>" target="_blank" rel="noopener">Join us on Telegram</a><?php endif; ?> · Open <?= e(site('site.hours')) ?>
            </p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
