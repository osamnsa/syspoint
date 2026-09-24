<?php
declare(strict_types=1);

/** @var array $params [id] */

$adminUser = require_admin();

$bookingId = (int) ($params[0] ?? 0);
$stmt = db()->prepare(
    "SELECT b.*, r.name AS room_name
     FROM room_bookings b JOIN gaming_rooms r ON r.id = b.room_id
     WHERE b.id = :id LIMIT 1"
);
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $newStatus = (string) ($_POST['status'] ?? '');
    if (in_array($newStatus, $statuses, true)) {
        db()->prepare('UPDATE room_bookings SET status = :status WHERE id = :id')
            ->execute(['status' => $newStatus, 'id' => $bookingId]);
        flash('success', 'Booking status updated.');
        header('Location: ' . path('admin/bookings/' . $bookingId));
        exit;
    }
}

$successMessage = flash('success');

$pageTitle = 'Booking — ' . $booking['room_name'];
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($booking['room_name']) ?> — <?= e((new DateTimeImmutable($booking['booking_date']))->format('M j, Y')) ?></h1>
    <a href="<?= path('admin/bookings') ?>" class="btn btn-outline btn-sm">Back to Bookings</a>
</div>

<?php if ($successMessage): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <p style="margin:0;">
        <strong><?= e($booking['customer_name']) ?></strong><br>
        <?= e($booking['customer_email']) ?><br>
        <?= e($booking['customer_phone']) ?><br><br>
        <?= e(substr($booking['start_time'], 0, 5)) ?>–<?= e(substr($booking['end_time'], 0, 5)) ?>
        <?php if ($booking['party_size']): ?> · <?= (int) $booking['party_size'] ?> people<?php endif; ?>
    </p>
    <?php if ($booking['notes']): ?>
        <p style="margin-top:12px;white-space:pre-wrap;"><?= e($booking['notes']) ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-top:0;">Status</h3>
    <form method="post" action="<?= path('admin/bookings/' . $bookingId) ?>" style="display:flex;gap:10px;align-items:center;">
        <?= csrf_field() ?>
        <select name="status">
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status) ?>" <?= $booking['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
    </form>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
