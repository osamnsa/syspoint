<?php
declare(strict_types=1);

$adminUser = require_admin();

$bookings = db()->query(
    "SELECT b.*, r.name AS room_name
     FROM room_bookings b JOIN gaming_rooms r ON r.id = b.room_id
     ORDER BY b.booking_date DESC, b.start_time DESC"
)->fetchAll();

$statusBadge = [
    'pending' => 'badge-warning',
    'confirmed' => 'badge-success',
    'cancelled' => 'badge-muted',
    'completed' => 'badge-success',
];

$pageTitle = 'Bookings';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1>Bookings</h1>
</div>

<div class="admin-table-wrap">
    <?php if ($bookings): ?>
        <table class="admin-table">
            <thead><tr><th>Room</th><th>Customer</th><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= e($booking['room_name']) ?></td>
                        <td><?= e($booking['customer_name']) ?></td>
                        <td><?= e((new DateTimeImmutable($booking['booking_date']))->format('M j, Y')) ?></td>
                        <td><?= e(substr($booking['start_time'], 0, 5)) ?>–<?= e(substr($booking['end_time'], 0, 5)) ?></td>
                        <td><span class="badge <?= $statusBadge[$booking['status']] ?? 'badge-muted' ?>"><?= e(ucfirst($booking['status'])) ?></span></td>
                        <td><a href="<?= path('admin/bookings/' . (int) $booking['id']) ?>" class="btn btn-outline btn-sm">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="admin-empty">No bookings yet.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
