<?php
declare(strict_types=1);

/** @var array $params [slug] — privacy-policy, returns-policy or terms */

$slug = (string) ($params[0] ?? '');
$group = LEGAL_PAGES[$slug] ?? null;
if ($group === null) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$page = legal_render($group);
$pageTitle = site($group . '.title');
$pageDescription = $pageTitle . ' for ' . site('company.name') . ' — the Syspoint website, Gadget Store and Syspoint Hub.';

require __DIR__ . '/../partials/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / <?= e($pageTitle) ?></div>
        <h1><?= e($pageTitle) ?></h1>
        <?php if (site($group . '.updated') !== ''): ?><p class="legal-updated">Last updated <?= e(site($group . '.updated')) ?></p><?php endif; ?>
    </div>
</section>

<section class="section legal-section">
    <div class="container legal-layout">
        <aside class="legal-aside">
            <?php if (count($page['toc']) > 2): ?>
                <nav class="legal-toc" aria-label="On this page">
                    <p class="legal-aside-label">On this page</p>
                    <ol>
                        <?php foreach ($page['toc'] as $id => $heading): ?><li><a href="#<?= e($id) ?>"><?= e($heading) ?></a></li><?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
            <nav class="legal-others" aria-label="Policies">
                <p class="legal-aside-label">Policies</p>
                <ul>
                    <?php foreach (LEGAL_PAGES as $s => $g): ?>
                        <li><a href="<?= path($s) ?>"<?= $s === $slug ? ' aria-current="page"' : '' ?>><?= e(site($g . '.title')) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>
        <article class="legal-body">
            <?= $page['html'] ?>
            <p class="legal-contact">Questions about this page? Email <a href="mailto:<?= e(site('company.email') ?: site('site.email')) ?>"><?= e(site('company.email') ?: site('site.email')) ?></a> or <a href="<?= path('contact') ?>">send us a message</a>.</p>
        </article>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
