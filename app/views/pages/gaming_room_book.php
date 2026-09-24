<?php
declare(strict_types=1);

/** @var array $params [room_slug] */

$room = gaming_room_by_slug($params[0] ?? '');
if (!$room || !$room['is_active']) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try submitting the form again.';
    } else {
        $name = trim((string) ($_POST['customer_name'] ?? ''));
        $email = trim((string) ($_POST['customer_email'] ?? ''));
        $phone = trim((string) ($_POST['customer_phone'] ?? ''));
        $date = trim((string) ($_POST['booking_date'] ?? ''));
        $startTime = trim((string) ($_POST['start_time'] ?? ''));
        $endTime = trim((string) ($_POST['end_time'] ?? ''));
        $partySize = trim((string) ($_POST['party_size'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($name === '') $errors[] = 'Please enter your name.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($phone === '') $errors[] = 'Please enter a phone number.';

        $today = date('Y-m-d');
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date || $date < $today) {
            $errors[] = 'Please choose a valid date, today or later.';
        }

        $startObj = DateTime::createFromFormat('H:i', $startTime);
        $endObj = DateTime::createFromFormat('H:i', $endTime);
        if (!$startObj || !$endObj || $startTime >= $endTime) {
            $errors[] = 'Please choose a valid start and end time (end after start).';
        }

        if ($partySize !== '' && !ctype_digit($partySize)) {
            $errors[] = 'Please enter a valid party size.';
        }

        if (!$errors && room_booking_overlaps((int) $room['id'], $date, $startTime . ':00', $endTime . ':00')) {
            $errors[] = 'That room is already reserved for part of that time slot. Please choose a different time.';
        }

        if (!$errors) {
            db()->prepare(
                'INSERT INTO room_bookings (room_id, customer_name, customer_email, customer_phone, booking_date, start_time, end_time, party_size, notes)
                 VALUES (:room_id, :name, :email, :phone, :date, :start_time, :end_time, :party_size, :notes)'
            )->execute([
                'room_id' => $room['id'],
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'party_size' => $partySize !== '' ? $partySize : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            flash('booking_success', "Thanks {$name} — your request to book the {$room['name']} on {$date} ({$startTime}–{$endTime}) has been received. We'll confirm shortly.");
            header('Location: ' . path('gaming'));
            exit;
        }

        $_SESSION['old_input'] = compact('name', 'email', 'phone', 'date', 'startTime', 'endTime', 'partySize', 'notes');
    }
}

$pageTitle = 'Book the ' . $room['name'];

require __DIR__ . '/../partials/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= path() ?>">Home</a> / <a href="<?= path('gaming') ?>">Gaming Lounge</a> / Book <?= e($room['name']) ?></div>
        <h1>Book the <?= e($room['name']) ?></h1>
        <p style="color:var(--color-text-muted);"><?= format_naira((float) $room['hourly_rate']) ?>/hour<?= $room['capacity'] ? ' · Up to ' . (int) $room['capacity'] . ' people' : '' ?></p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:560px;">
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="card form-card">
            <form method="post" action="<?= path('gaming/book/' . $room['slug']) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="customer_name">Full Name</label>
                    <input type="text" id="customer_name" name="customer_name" value="<?= old('name') ?>" required>
                </div>
                <div class="form-group">
                    <label for="customer_email">Email Address</label>
                    <input type="email" id="customer_email" name="customer_email" value="<?= old('email') ?>" required>
                </div>
                <div class="form-group">
                    <label for="customer_phone">Phone Number</label>
                    <input type="text" id="customer_phone" name="customer_phone" value="<?= old('phone') ?>" required>
                </div>
                <div class="form-group">
                    <label for="booking_date">Date</label>
                    <input type="date" id="booking_date" name="booking_date" value="<?= old('date') ?>" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div style="display:flex;gap:14px;">
                    <div class="form-group" style="flex:1;">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" value="<?= old('startTime') ?>" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" value="<?= old('endTime') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="party_size">Party Size (optional)</label>
                    <input type="number" id="party_size" name="party_size" value="<?= old('partySize') ?>" min="1" <?= $room['capacity'] ? 'max="' . (int) $room['capacity'] . '"' : '' ?>>
                </div>
                <div class="form-group">
                    <label for="notes">Notes (optional)</label>
                    <textarea id="notes" name="notes"><?= old('notes') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Request Booking</button>
                <div class="form-note" style="margin-top:12px;">This reserves a time slot request — we'll confirm by email or phone.</div>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
