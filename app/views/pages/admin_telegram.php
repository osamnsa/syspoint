<?php
declare(strict_types=1);

$adminUser = require_admin();

$cfg = telegram_config();
$ready = $cfg['token'] !== '' && $cfg['channel'] !== '';
$siteName = site('site.brand_first') . ' ' . site('site.brand_second');
$values = ['title' => '', 'body' => '', 'photo_path' => '', 'button_text' => 'Visit ' . $siteName, 'button_url' => url(), 'product_id' => ''];

// Starters
$template = (string) ($_GET['template'] ?? '');
if (!empty($_GET['product']) && ($p = product_by_id((int) $_GET['product']))) {
    $cat = product_category_by_id((int) $p['category_id']);
    $values = [
        'title' => 'New in the shop: ' . $p['name'],
        'body' => trim(format_naira((float) $p['price']) . "\n\n" . mb_strimwidth(trim((string) $p['description']), 0, 400, '…') . "\n\nIn store now at " . site('site.store_suite') . ', ' . site('site.plaza') . '.'),
        'photo_path' => (string) $p['image_path'],
        'button_text' => 'View in shop',
        'button_url' => url('shop/' . ($cat['slug'] ?? '') . '/' . $p['slug']),
        'product_id' => (string) $p['id'],
    ];
} elseif ($template === 'gaming') {
    $values = array_merge($values, ['title' => '🎮 Game night at ' . site('site.hub_name'), 'body' => "Bring your squad this weekend — PS5, VR arena and board games, with free internet for every gamer.\n\nBook the VIP room before it’s gone.", 'button_text' => 'Book a room', 'button_url' => url('gaming') . '#rooms']);
} elseif ($template === 'training') {
    $values = array_merge($values, ['title' => '🎓 New training intake', 'body' => "Hands-on courses and internships that get you job-ready. Limited seats for the next intake — reserve yours today.", 'button_text' => 'See courses', 'button_url' => url('training')]);
} elseif ($template === 'hours') {
    $values = array_merge($values, ['title' => '🕘 Opening hours', 'body' => 'We’re open ' . site('site.hours') . ' at ' . site('site.plaza') . '.', 'button_text' => 'Get directions', 'button_url' => site('site.maps_url')]);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        foreach (['title', 'body', 'photo_path', 'button_text', 'button_url', 'product_id'] as $k) $values[$k] = trim(str_replace("\r\n", "\n", (string) ($_POST[$k] ?? '')));
        if ($values['title'] === '' && $values['body'] === '') $errors[] = 'Write a title or a message.';
        if (($values['button_text'] === '') !== ($values['button_url'] === '')) $errors[] = 'A button needs both its text and its link (or leave both empty).';
        if ($values['button_url'] !== '' && !filter_var($values['button_url'], FILTER_VALIDATE_URL)) $errors[] = 'The button link must be a full address starting with https://';
        // A carried-over photo must be one of ours (product image or earlier upload).
        if ($values['photo_path'] !== '' && !preg_match('#^(uploads|assets)/[A-Za-z0-9_./-]+\.(jpe?g|png|webp)$#i', $values['photo_path'])) $values['photo_path'] = '';
        if (isset($_POST['no_photo'])) $values['photo_path'] = '';
        $upload = handle_image_upload('photo', 'telegram');
        if (!$upload['ok']) $errors[] = $upload['error'];
        elseif ($upload['path']) $values['photo_path'] = $upload['path'];

        if (!$errors) {
            $r = telegram_announce($values['title'], $values['body'], $values['photo_path'] ?: null, $values['button_text'] ?: null, $values['button_url'] ?: null, (int) $values['product_id'] ?: null);
            if ($r['ok']) {
                flash('success', 'Posted to ' . $cfg['channel'] . '.');
                header('Location: ' . path('admin/telegram'));
                exit;
            }
            $errors[] = 'Not posted: ' . $r['error'];
        }
    }
}

$posts = db()->query('SELECT t.*, u.name AS user_name FROM telegram_posts t LEFT JOIN users u ON u.id = t.user_id ORDER BY t.id DESC LIMIT 30')->fetchAll();
$products = array_values(array_filter(inventory_products(true), fn($p) => !(int) $p['is_demo']));

$pageTitle = 'Telegram';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Telegram</p><h1>Announcements</h1></div>
    <div class="admin-header-actions">
        <?php if ($link = telegram_channel_url()): ?><a href="<?= e($link) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">Open channel ↗</a><?php endif; ?>
        <?php if (admin_is_admin()): ?><a href="<?= path('admin/telegram/settings') ?>" class="btn btn-outline btn-sm">Settings</a><?php endif; ?>
    </div>
</div>

<?php if (!$ready): ?>
    <div class="alert alert-warning alert-static tg-setup">
        Telegram isn’t connected yet.
        <?= admin_is_admin() ? '<a href="' . path('admin/telegram/settings') . '">Set it up in Settings</a> — it takes about five minutes.' : 'Ask an administrator to connect it in Telegram → Settings.' ?>
    </div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<nav class="admin-quick" aria-label="Starters">
    <a class="admin-quick-link glass-dark is-plain" href="<?= path('admin/telegram') ?>?template=gaming">Gaming event</a>
    <a class="admin-quick-link glass-dark is-plain" href="<?= path('admin/telegram') ?>?template=training">Training intake</a>
    <a class="admin-quick-link glass-dark is-plain" href="<?= path('admin/telegram') ?>?template=hours">Opening hours</a>
    <form method="get" class="tg-product-pick">
        <select name="product" aria-label="Announce a product"><option value="">Announce a product…</option><?php foreach ($products as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select>
        <button type="submit" class="btn btn-outline btn-sm admin-btn-on-dark">Use</button>
    </form>
</nav>

<div class="tg-compose">
    <form method="post" enctype="multipart/form-data" class="admin-form" data-tg-form data-confirm="Post this to <?= e($cfg['channel'] ?: 'the channel') ?>?" data-confirm-button="Post">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= e($values['product_id']) ?>">
        <input type="hidden" name="photo_path" value="<?= e($values['photo_path']) ?>">
        <div class="form-group"><label for="tg-title">Title (bold)</label><input type="text" id="tg-title" name="title" maxlength="190" value="<?= e($values['title']) ?>" data-tg="title"></div>
        <div class="form-group"><label for="tg-body">Message</label><textarea id="tg-body" name="body" rows="7" data-tg="body"><?= e($values['body']) ?></textarea><div class="form-note"><span data-tg-count>0</span> characters · up to 1,024 with a photo, 4,096 without</div></div>
        <div class="form-group">
            <label for="tg-photo">Photo (optional)</label>
            <?php if ($values['photo_path']): ?>
                <div class="site-image"><img src="<?= e(media_url($values['photo_path'])) ?>" alt=""><label class="admin-choice"><input type="checkbox" name="no_photo" value="1" data-tg-nophoto> <span>Post without this photo</span></label></div>
            <?php endif; ?>
            <input type="file" id="tg-photo" name="photo" accept="image/jpeg,image/png,image/webp" data-tg="photo">
        </div>
        <div class="form-row">
            <div class="form-group"><label for="tg-btext">Button text</label><input type="text" id="tg-btext" name="button_text" maxlength="60" value="<?= e($values['button_text']) ?>" data-tg="btext"></div>
            <div class="form-group"><label for="tg-burl">Button link</label><input type="url" id="tg-burl" name="button_url" value="<?= e($values['button_url']) ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary" <?= $ready ? '' : 'disabled' ?>>Post to <?= e($cfg['channel'] ?: 'channel') ?></button>
    </form>

    <aside class="tg-preview-wrap">
        <p class="admin-kicker">Preview</p>
        <div class="tg-preview">
            <div class="tg-chan"><img src="<?= asset('assets/img/logo.png') ?>" alt=""><span><strong><?= e($siteName) ?></strong><small><?= e($cfg['channel'] ?: '@yourchannel') ?></small></span></div>
            <div class="tg-bubble">
                <img class="tg-photo" data-tg-photo-preview src="<?= $values['photo_path'] ? e(media_url($values['photo_path'])) : '' ?>" alt=""<?= $values['photo_path'] ? '' : ' hidden' ?>>
                <div class="tg-text"><strong data-tg-preview="title"></strong><span data-tg-preview="body"></span></div>
                <span class="tg-time"><?= date('g:i A') ?></span>
            </div>
            <div class="tg-button" data-tg-preview="btext"></div>
        </div>
    </aside>
</div>

<div class="admin-header-row" style="margin-top:28px;"><h2>Posted</h2></div>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>When</th><th>Announcement</th><th>Status</th><th>By</th></tr></thead>
        <tbody>
        <?php foreach ($posts as $p): ?>
            <tr>
                <td style="white-space:nowrap;"><?= e((new DateTimeImmutable($p['created_at']))->format('j M, g:i A')) ?></td>
                <td><strong><?= e($p['title'] ?: mb_strimwidth((string) $p['body'], 0, 60, '…')) ?></strong><?= $p['photo_path'] ? ' <span class="badge badge-muted">Photo</span>' : '' ?><?= $p['button_text'] ? '<br><small class="muted">Button: ' . e($p['button_text']) . '</small>' : '' ?></td>
                <td><?= $p['status'] === 'sent' ? '<span class="badge badge-success">Posted</span>' : '<span class="badge badge-danger">Failed</span><br><small class="muted">' . e((string) $p['error']) . '</small>' ?></td>
                <td><?= e($p['user_name'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$posts): ?><tr><td colspan="4" class="admin-empty">Nothing posted yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
