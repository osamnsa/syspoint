<?php
declare(strict_types=1);

if (admin_user() !== null) {
    header('Location: ' . path('admin'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (blocklist_match(null, client_ip())) {
            usleep(500000);
            $error = 'Incorrect email or password.';
        } elseif ($wait = login_locked_minutes($email)) {
            $error = 'Too many failed sign-in attempts. Please try again in ' . $wait . ' minute' . ($wait === 1 ? '' : 's') . '.';
        } elseif (admin_attempt_login($email, $password)) {
            login_clear_failures($email);
            // only ever redirect to a page on this site
            $redirectTo = (string) ($_SESSION['admin_redirect_to'] ?? '');
            if ($redirectTo === '' || $redirectTo[0] !== '/' || str_starts_with($redirectTo, '//')) $redirectTo = path('admin');
            unset($_SESSION['admin_redirect_to']);
            header('Location: ' . $redirectTo);
            exit;
        } else {
            login_record_failure($email);
            usleep(random_int(300000, 700000));   // slows scripted guessing
            $error = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Admin Login';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Syspoint Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <?php require __DIR__ . '/../partials/favicons.php'; ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;900&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap">
    <link rel="stylesheet" href="<?= versioned_asset('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/style.css') ?>">
    <script src="<?= versioned_asset('assets/vendor/sweetalert2/sweetalert2.min.js') ?>" defer></script>
    <script src="<?= versioned_asset('assets/js/alerts.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/admin.css') ?>">
</head>
<body class="admin-body admin-login-body">
<?php require __DIR__ . '/../partials/admin_backdrop.php'; ?>
<main class="admin-login-wrap">
    <div class="admin-login-card glass-dark">
        <div class="admin-login-brand">
            <img src="<?= asset('assets/img/logo.png') ?>" alt="" width="64" height="64">
            <span class="brand-name">Syspoint <em>Hub</em></span>
        </div>
        <p class="admin-kicker">Admin sign in</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= path('admin/login') ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>
        <a class="admin-login-back" href="<?= path() ?>">&larr; Back to the website</a>
    </div>
</main>
</body>
</html>
