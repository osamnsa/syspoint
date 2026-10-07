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

        if (admin_attempt_login($email, $password)) {
            $redirectTo = $_SESSION['admin_redirect_to'] ?? path('admin');
            unset($_SESSION['admin_redirect_to']);
            header('Location: ' . $redirectTo);
            exit;
        }

        $error = 'Incorrect email or password.';
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
    <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;900&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap">
    <link rel="stylesheet" href="<?= versioned_asset('assets/css/style.css') ?>">
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
