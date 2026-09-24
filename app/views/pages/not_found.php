<?php
declare(strict_types=1);

$pageTitle = 'Page Not Found';
require __DIR__ . '/../partials/header.php';
?>

<section class="section">
    <div class="container" style="text-align:center;padding:80px 20px;">
        <h1>404 — Page Not Found</h1>
        <p style="color:var(--color-text-muted);">The page you're looking for doesn't exist or has moved.</p>
        <a href="<?= path() ?>" class="btn btn-primary">Back to Home</a>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
