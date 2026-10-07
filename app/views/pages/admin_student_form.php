<?php
declare(strict_types=1);

/** @var array $params [id] when editing */

$adminUser = require_admin();

$id = isset($params[0]) ? (int) $params[0] : 0;
$enrolment = $id ? enrolment_by_id($id) : null;
if ($id && !$enrolment) {
    http_response_code(404);
    require __DIR__ . '/not_found.php';
    return;
}
$courses = db()->query('SELECT id, title, price, duration_label FROM training_courses ORDER BY is_active DESC, sort_order, title')->fetchAll();

$values = [
    'name' => $enrolment['student_name'] ?? '',
    'email' => $enrolment['student_email'] ?? '',
    'phone' => $enrolment['student_phone'] ?? '',
    'kind' => $enrolment['kind'] ?? (($_GET['kind'] ?? '') === 'internship' ? 'internship' : 'course'),
    'course_id' => (string) ($enrolment['course_id'] ?? ($_GET['course'] ?? '')),
    'track' => $enrolment['track'] ?? '',
    'status' => $enrolment['status'] ?? 'enquiry',
    'start_date' => $enrolment['start_date'] ?? '',
    'end_date' => $enrolment['end_date'] ?? '',
    'fee' => $enrolment['fee'] ?? '',
    'notes' => $enrolment['notes'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        foreach ($values as $k => $_) $values[$k] = trim((string) ($_POST[$k] ?? ''));
        if (!isset(ENROLMENT_KINDS[$values['kind']])) $values['kind'] = 'course';
        if (!isset(ENROLMENT_STATUSES[$values['status']])) $values['status'] = 'enquiry';
        if (!$enrolment && $values['name'] === '') $errors[] = 'Enter the student’s name.';
        if (!$enrolment && $values['email'] === '' && $values['phone'] === '') $errors[] = 'Enter a phone number or email so we can reach them.';
        if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if ($values['course_id'] !== '' && !array_filter($courses, fn($c) => (string) $c['id'] === $values['course_id'])) $values['course_id'] = '';
        if ($values['kind'] === 'course' && $values['course_id'] === '' && $values['track'] === '') $errors[] = 'Choose a course (or describe it).';
        if ($values['fee'] === '') $values['fee'] = '0';
        if (!is_numeric($values['fee']) || (float) $values['fee'] < 0) $errors[] = 'Enter a valid fee (0 if free).';
        if ($enrolment && (float) $values['fee'] < (float) $enrolment['amount_paid']) $errors[] = 'The fee can’t be less than what has already been paid.';
        foreach (['start_date', 'end_date'] as $d) if ($values[$d] !== '' && !DateTimeImmutable::createFromFormat('Y-m-d', $values[$d])) $errors[] = 'Enter valid dates.';

        if (!$errors) {
            $customerId = $enrolment ? (int) $enrolment['customer_id'] : crm_customer_for($values['name'], $values['email'], $values['phone'], 'walk_in');
        }
        if (!$errors && !$customerId) {
            $errors[] = 'Enter a full phone number or a valid email.';
        }
        if (!$errors) {
            $data = ['k' => $values['kind'], 'c' => $values['course_id'] ?: null, 't' => $values['track'] ?: null, 's' => $values['status'],
                     'sd' => $values['start_date'] ?: null, 'ed' => $values['end_date'] ?: null, 'fee' => $values['fee'], 'n' => $values['notes'] ?: null];
            if ($enrolment) {
                db()->prepare('UPDATE enrolments SET kind = :k, course_id = :c, track = :t, status = :s, start_date = :sd, end_date = :ed, fee = :fee, notes = :n WHERE id = :id')
                    ->execute($data + ['id' => $id]);
                if ($enrolment['status'] !== $values['status']) crm_log($customerId, null, 'note', 'Training: ' . ENROLMENT_STATUSES[$enrolment['status']] . ' → ' . ENROLMENT_STATUSES[$values['status']]);
                $newId = $id;
            } else {
                db()->prepare('INSERT INTO enrolments (customer_id, kind, course_id, track, status, start_date, end_date, fee, notes, created_by)
                               VALUES (:cust, :k, :c, :t, :s, :sd, :ed, :fee, :n, :u)')->execute($data + ['cust' => $customerId, 'u' => $adminUser['id']]);
                $newId = (int) db()->lastInsertId();
                crm_log($customerId, null, 'note', ($values['kind'] === 'internship' ? 'Internship' : 'Training') . ' ' . strtolower(ENROLMENT_STATUSES[$values['status']]) . ' added');
            }
            flash('success', 'Saved.');
            header('Location: ' . path('admin/students/' . $newId));
            exit;
        }
    }
}

$pageTitle = $enrolment ? 'Edit Enrolment' : ($values['kind'] === 'internship' ? 'Add Intern' : 'Add Student');
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row">
    <h1><?= e($pageTitle) ?></h1>
    <a href="<?= path($enrolment ? 'admin/students/' . $id : 'admin/students') ?>" class="btn btn-outline btn-sm">Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.2em;"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="admin-form">
    <?= csrf_field() ?>
    <?php if ($enrolment): ?>
        <p class="form-note" style="margin-top:0;">Student: <strong><?= e($enrolment['student_name']) ?></strong><?php if (admin_can('sales')): ?> — edit their contact details on their <a href="<?= path('admin/customers/' . (int) $enrolment['customer_id']) ?>">customer page</a><?php endif; ?>.</p>
    <?php else: ?>
        <div class="form-group"><label for="name">Student name</label><input type="text" id="name" name="name" value="<?= e((string) $values['name']) ?>" required></div>
        <div class="form-row">
            <div class="form-group"><label for="phone">Phone / WhatsApp</label><input type="tel" id="phone" name="phone" value="<?= e((string) $values['phone']) ?>"></div>
            <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e((string) $values['email']) ?>"></div>
        </div>
        <p class="form-note" style="margin-top:-8px;">If they’re already a customer (same phone or email), this joins their existing record.</p>
    <?php endif; ?>
    <fieldset class="form-group admin-fieldset">
        <legend>Programme</legend>
        <div class="admin-choice-row">
            <?php foreach (ENROLMENT_KINDS as $k => $l): ?><label class="admin-choice"><input type="radio" name="kind" value="<?= $k ?>" <?= $values['kind'] === $k ? 'checked' : '' ?>> <span><?= e($l) ?></span></label><?php endforeach; ?>
        </div>
    </fieldset>
    <div class="form-row">
        <div class="form-group"><label for="course_id">Course</label>
            <select id="course_id" name="course_id" data-course-fee><option value="">—</option>
                <?php foreach ($courses as $c): ?><option value="<?= (int) $c['id'] ?>" data-price="<?= e((string) ($c['price'] ?? '')) ?>" <?= $values['course_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?><?= $c['duration_label'] ? ' · ' . e($c['duration_label']) : '' ?></option><?php endforeach; ?>
            </select></div>
        <div class="form-group"><label for="track">Track / department (internships)</label><input type="text" id="track" name="track" value="<?= e((string) $values['track']) ?>" placeholder="e.g. Software, Sales, IT consulting"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="status">Status</label>
            <select id="status" name="status"><?php foreach (ENROLMENT_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $values['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="fee">Fee (₦)</label><input type="number" id="fee" name="fee" min="0" step="0.01" value="<?= e((string) $values['fee']) ?>" data-fee-input><div class="form-note">Fills in from the course price; change it for discounts or scholarships.</div></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="start_date">Start date / intake</label><input type="date" id="start_date" name="start_date" value="<?= e((string) $values['start_date']) ?>"></div>
        <div class="form-group"><label for="end_date">End date</label><input type="date" id="end_date" name="end_date" value="<?= e((string) $values['end_date']) ?>"></div>
    </div>
    <div class="form-group"><label for="notes">Notes</label><textarea id="notes" name="notes"><?= e((string) $values['notes']) ?></textarea></div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
<script>
document.querySelector('[data-course-fee]').addEventListener('change', function (e) {
    var price = e.target.selectedOptions[0] && e.target.selectedOptions[0].getAttribute('data-price');
    var fee = document.querySelector('[data-fee-input]');
    if (price && (!fee.value || fee.value === '0')) fee.value = price;
});
</script>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
