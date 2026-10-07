<?php
declare(strict_types=1);

$adminUser = require_admin();

$sections = site_content();
$sectionKey = (string) ($_GET['group'] ?? 'site');
if (!isset($sections[$sectionKey])) $sectionKey = 'site';
$section = $sections[$sectionKey];
$errors = [];
$pending = []; // what was typed, kept on screen if the save is rejected

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (isset($_POST['reset']) && isset($section['fields'][$_POST['reset']])) {
        content_block_delete((string) $_POST['reset']);
        flash('success', '“' . $section['fields'][$_POST['reset']]['label'] . '” is back to the original.');
        header('Location: ' . path('admin/website') . '?group=' . $sectionKey . '#f-' . str_replace('.', '-', (string) $_POST['reset']));
        exit;
    } else {
        $pending = [];
        foreach ($section['fields'] as $key => $field) {
            $name = str_replace('.', '__', $key);
            if ($field['type'] === 'image') {
                if (!empty($_FILES[$name]['name'])) {
                    $upload = handle_image_upload($name, 'site');
                    if (!$upload['ok']) $errors[] = $field['label'] . ': ' . $upload['error'];
                    elseif ($upload['path']) $pending[$key] = $upload['path'];
                }
                continue;
            }
            if (!array_key_exists($name, $_POST)) continue;
            $value = trim(str_replace("\r\n", "\n", (string) $_POST[$name]));
            if ($field['type'] === 'html') $value = site_sanitize_html($value);
            if ($field['type'] === 'url' && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) $errors[] = $field['label'] . ': enter a full link starting with https://';
            if ($field['type'] === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) $errors[] = $field['label'] . ': enter a valid email address.';
            if ($field['type'] !== 'textarea' && $field['type'] !== 'html') $value = preg_replace('/\s+/', ' ', $value);
            $pending[$key] = $value;
        }
        if (!$errors) {
            foreach ($pending as $key => $value) site_save($key, $value);
            flash('success', $section['label'] . ' saved — the website is updated.');
            header('Location: ' . path('admin/website') . '?group=' . $sectionKey);
            exit;
        }
    }
}

$pageTitle = 'Website Editor';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Website</p><h1>Website Editor</h1></div>
    <?php if ($sectionKey !== 'company'): ?><a href="<?= path($section['page']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm admin-btn-on-dark">View <?= $section['page'] === '' && $sectionKey !== 'site' ? 'Home page' : ($sectionKey === 'site' ? 'site' : e($section['label']) . ' page') ?> ↗</a><?php endif; ?>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="site-editor">
    <nav class="site-editor-tabs" aria-label="Sections">
        <?php foreach ($sections as $k => $g):
            $changed = count(array_filter(array_keys($g['fields']), 'site_is_changed')); ?>
            <a href="<?= path('admin/website') ?>?group=<?= $k ?>"<?= $k === $sectionKey ? ' class="is-active" aria-current="page"' : '' ?>>
                <span><?= e($g['label']) ?></span><?php if ($changed): ?><small><?= $changed ?> edited</small><?php endif; ?>
            </a>
        <?php endforeach; ?>
        <p class="site-editor-more">Also editable: <a href="<?= path('admin/businesses') ?>">Clients</a>, <a href="<?= path('admin/testimonials') ?>">Testimonials</a>, <a href="<?= path('admin/home-stats') ?>">Home stats</a>, <a href="<?= path('admin/products') ?>">Products</a>, <a href="<?= path('admin/rooms') ?>">Rooms</a>, <a href="<?= path('admin/games') ?>">Games</a>, <a href="<?= path('admin/courses') ?>">Courses</a>.</p>
    </nav>

    <form method="post" enctype="multipart/form-data" class="admin-form site-editor-form" data-site-editor>
        <?= csrf_field() ?>
        <h2 class="admin-form-title"><?= e($section['label']) ?></h2>
        <?php if ($section['intro']): ?><p class="form-note" style="margin-top:-8px;"><?= e($section['intro']) ?></p><?php endif; ?>

        <?php foreach ($section['fields'] as $key => $field):
            $name = str_replace('.', '__', $key);
            $id = 'f-' . str_replace('.', '-', $key);
            $value = $pending[$key] ?? site($key);
            $changed = site_is_changed($key); ?>
            <div class="form-group site-field<?= $changed ? ' is-changed' : '' ?>" id="<?= $id ?>">
                <div class="site-field-head">
                    <label for="<?= $id ?>-in"><?= e($field['label']) ?></label>
                    <?php if ($changed): ?>
                        <span class="badge badge-warning">Edited</span>
                        <button type="submit" name="reset" value="<?= e($key) ?>" class="site-reset" formnovalidate data-confirm="Put back the original?" data-confirm-button="Reset">Reset</button>
                    <?php endif; ?>
                </div>
                <?php if ($field['type'] === 'image'): ?>
                    <div class="site-image">
                        <img src="<?= e(media_url($value)) ?>" alt="" loading="lazy">
                        <div>
                            <input type="file" id="<?= $id ?>-in" name="<?= $name ?>" accept="image/jpeg,image/png,image/webp">
                            <div class="form-note">JPG, PNG or WEBP up to 5MB. <?= $changed ? 'Reset puts back the original picture.' : '' ?></div>
                        </div>
                    </div>
                <?php elseif ($field['type'] === 'html'): ?>
                    <div class="rte" data-rte>
                        <div class="rte-bar" role="toolbar" aria-label="Formatting">
                            <button type="button" data-cmd="formatBlock" data-arg="h2">Heading</button>
                            <button type="button" data-cmd="bold"><strong>B</strong></button>
                            <button type="button" data-cmd="italic"><em>I</em></button>
                            <button type="button" data-cmd="insertUnorderedList">• List</button>
                            <button type="button" data-cmd="createLink">Link</button>
                            <button type="button" data-cmd="formatBlock" data-arg="p">Paragraph</button>
                        </div>
                        <div class="rte-area prose" contenteditable="true" data-rte-area><?= site_sanitize_html($value) ?></div>
                        <textarea id="<?= $id ?>-in" name="<?= $name ?>" hidden data-rte-input><?= e($value) ?></textarea>
                    </div>
                <?php elseif ($field['type'] === 'textarea'): ?>
                    <textarea id="<?= $id ?>-in" name="<?= $name ?>" rows="<?= max(2, min(6, substr_count($value, "\n") + 2)) ?>"><?= e($value) ?></textarea>
                <?php else: ?>
                    <input type="<?= $field['type'] === 'url' ? 'url' : ($field['type'] === 'email' ? 'email' : 'text') ?>" id="<?= $id ?>-in" name="<?= $name ?>" value="<?= e($value) ?>">
                <?php endif; ?>
                <?php if ($field['help']): ?><div class="form-note"><?= e($field['help']) ?></div><?php endif; ?>
                <?php if ($changed && $field['type'] !== 'image' && $field['type'] !== 'html' && $field['default'] !== ''): ?><div class="form-note site-original">Original: <?= e(mb_strimwidth($field['default'], 0, 140, '…')) ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="site-editor-save">
            <button type="submit" class="btn btn-primary">Save <?= e($section['label']) ?></button>
            <span class="form-note">Changes go live as soon as you save.</span>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
