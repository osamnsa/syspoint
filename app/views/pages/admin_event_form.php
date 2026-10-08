<?php
declare(strict_types=1);

/** @var array $params [id] when editing, empty when creating */

$adminUser = require_admin();

$eventId = isset($params[0]) ? (int) $params[0] : 0;
$event = $eventId ? event_by_id($eventId) : null;
if ($eventId && !$event) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$dt = fn(?string $v) => $v ? (new DateTimeImmutable($v))->format('Y-m-d\TH:i') : '';
$kind = (string) ($_GET['kind'] ?? '');
$values = [
    'title' => $event['title'] ?? '',
    'kind' => $event['kind'] ?? (isset(EVENT_KINDS[$kind]) ? $kind : 'general'),
    'starts_at' => $dt($event['starts_at'] ?? null),
    'ends_at' => $dt($event['ends_at'] ?? null),
    'venue' => $event['venue'] ?? site('site.hub_name') . ', ' . site('site.plaza'),
    'description' => $event['description'] ?? '',
    'price' => $event['price'] ?? '',
    'button_text' => $event['button_text'] ?? '',
    'button_url' => $event['button_url'] ?? '',
    'is_active' => $event['is_active'] ?? 1,
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        foreach (['title', 'kind', 'starts_at', 'ends_at', 'venue', 'description', 'price', 'button_text', 'button_url'] as $k) {
            $values[$k] = trim(str_replace("\r\n", "\n", (string) ($_POST[$k] ?? '')));
        }
        $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        $parse = fn(string $v) => $v === '' ? null : (DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $v) ?: false);
        $start = $parse($values['starts_at']);
        $end = $parse($values['ends_at']);
        if ($values['title'] === '') $errors[] = 'Give the event a name.';
        if (!isset(EVENT_KINDS[$values['kind']])) $values['kind'] = 'general';
        if (!$start) $errors[] = 'Choose when the event starts.';
        if ($end === false) $errors[] = 'The end time isn’t a valid date and time.';
        if ($start && $end && $end <= $start) $errors[] = 'The event has to end after it starts.';
        if ($values['price'] !== '' && (!is_numeric($values['price']) || (float) $values['price'] < 0)) $errors[] = 'Enter a valid price, 0 for free, or leave it blank.';
        if (($values['button_text'] === '') !== ($values['button_url'] === '')) $errors[] = 'A button needs both its text and its link (or leave both empty to link to the events page).';
        if ($values['button_url'] !== '' && !filter_var($values['button_url'], FILTER_VALIDATE_URL)) $errors[] = 'The button link must be a full address starting with https://';

        $upload = handle_image_upload('image', 'events');
        if (!$upload['ok']) $errors[] = $upload['error'];

        if (!$errors) {
            $row = [
                'title' => $values['title'],
                'kind' => $values['kind'],
                'starts_at' => $start->format('Y-m-d H:i:s'),
                'ends_at' => $end ? $end->format('Y-m-d H:i:s') : null,
                'venue' => $values['venue'] ?: null,
                'description' => $values['description'] ?: null,
                'price' => $values['price'] === '' ? null : $values['price'],
                'image_path' => $upload['path'] ?? ($event['image_path'] ?? null),
                'button_text' => $values['button_text'] ?: null,
                'button_url' => $values['button_url'] ?: null,
                'is_active' => $values['is_active'],
            ];
            if (isset($_POST['remove_image'])) $row['image_path'] = $upload['path'] ?? null;
            if ($event) {
                db()->prepare('UPDATE events SET title = :title, kind = :kind, starts_at = :starts_at, ends_at = :ends_at, venue = :venue,
                               description = :description, price = :price, image_path = :image_path, button_text = :button_text,
                               button_url = :button_url, is_active = :is_active WHERE id = :id')
                    ->execute($row + ['id' => $event['id']]);
            } else {
                db()->prepare('INSERT INTO events (title, kind, starts_at, ends_at, venue, description, price, image_path, button_text, button_url, is_active, created_by)
                               VALUES (:title, :kind, :starts_at, :ends_at, :venue, :description, :price, :image_path, :button_text, :button_url, :is_active, :created_by)')
                    ->execute($row + ['created_by' => $adminUser['id']]);
                $eventId = (int) db()->lastInsertId();
            }
            flash('success', $event ? 'Event saved.' : 'Event scheduled.');
            telegram_announce_after_save('event', $eventId);
            header('Location: ' . path('admin/events'));
            exit;
        }
    }
}

$pageTitle = $event ? 'Edit Event' : 'Schedule an Event';
$activeNav = 'admin/events';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Events</p><h1><?= e($pageTitle) ?></h1></div>
    <a href="<?= path('admin/events') ?>" class="btn btn-outline btn-sm">Back to Events</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= path($event ? 'admin/events/' . $event['id'] . '/edit' : 'admin/events/new') ?>" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group">
        <label for="title">Event name</label>
        <input type="text" id="title" name="title" maxlength="190" value="<?= e((string) $values['title']) ?>" placeholder="FIFA 25 Tournament" required>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="kind">Type</label>
            <select id="kind" name="kind"><?php foreach (EVENT_KINDS as $k => $l): ?><option value="<?= $k ?>" <?= $values['kind'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
            <div class="form-note">Gaming events also show on the Gaming page, training events on the Training page.</div>
        </div>
        <div class="form-group">
            <label for="price">Entry fee (₦)</label>
            <input type="number" id="price" name="price" step="0.01" min="0" value="<?= e((string) $values['price']) ?>">
            <div class="form-note">0 shows “Free”; leave blank to say nothing.</div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="starts_at">Starts</label>
            <input type="datetime-local" id="starts_at" name="starts_at" value="<?= e($values['starts_at']) ?>" required>
        </div>
        <div class="form-group">
            <label for="ends_at">Ends (optional)</label>
            <input type="datetime-local" id="ends_at" name="ends_at" value="<?= e($values['ends_at']) ?>">
        </div>
    </div>
    <div class="form-group">
        <label for="venue">Where</label>
        <input type="text" id="venue" name="venue" maxlength="190" value="<?= e((string) $values['venue']) ?>">
    </div>
    <div class="form-group">
        <label for="description">Details</label>
        <textarea id="description" name="description" rows="6" placeholder="Format, prizes, what to bring, how to register…"><?= e((string) $values['description']) ?></textarea>
    </div>
    <div class="form-group">
        <label for="image">Poster or photo</label>
        <?php if (!empty($event['image_path'])): ?>
            <div class="site-image"><img src="<?= e(media_url($event['image_path'])) ?>" alt=""><label class="admin-choice"><input type="checkbox" name="remove_image" value="1"> <span>Remove this image</span></label></div>
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <div class="form-note">JPG, PNG or WEBP, up to 5MB. A poster gets far more attention on Telegram.</div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="button_text">Button text (optional)</label>
            <input type="text" id="button_text" name="button_text" maxlength="60" value="<?= e((string) $values['button_text']) ?>" placeholder="Register">
        </div>
        <div class="form-group">
            <label for="button_url">Button link</label>
            <input type="url" id="button_url" name="button_url" value="<?= e((string) $values['button_url']) ?>" placeholder="https://forms.gle/…">
            <div class="form-note">Empty: “Details” links to the event on your website.</div>
        </div>
    </div>
    <div class="form-group">
        <label class="admin-choice"><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?>> <span>Show on the website</span></label>
    </div>
    <?php $tgType = 'event'; $tgId = $eventId; $tgDefault = !$event; require __DIR__ . '/../partials/telegram_box.php'; ?>
    <button type="submit" class="btn btn-primary"><?= $event ? 'Save Event' : 'Schedule Event' ?></button>
</form>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
