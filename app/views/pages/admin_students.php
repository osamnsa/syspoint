<?php
declare(strict_types=1);

$adminUser = require_admin();

$status = (string) ($_GET['status'] ?? '');
$kind = (string) ($_GET['kind'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['1 = 1'];
$params = [];
if (isset(ENROLMENT_STATUSES[$status])) { $where[] = 'e.status = :s'; $params['s'] = $status; }
if (isset(ENROLMENT_KINDS[$kind])) { $where[] = 'e.kind = :k'; $params['k'] = $kind; }
if ($q !== '') { $where[] = '(c.name LIKE :q OR c.email LIKE :q2 OR c.phone LIKE :q3 OR t.title LIKE :q4 OR e.track LIKE :q5)'; foreach (['q', 'q2', 'q3', 'q4', 'q5'] as $p) $params[$p] = "%$q%"; }
$stmt = db()->prepare('SELECT e.*, c.name AS student_name, c.phone AS student_phone, c.email AS student_email, t.title AS course_title
    FROM enrolments e JOIN customers c ON c.id = e.customer_id LEFT JOIN training_courses t ON t.id = e.course_id
    WHERE ' . implode(' AND ', $where) . " ORDER BY FIELD(e.status, 'enquiry', 'enrolled', 'completed', 'dropped'), e.start_date IS NULL, e.start_date DESC, e.id DESC LIMIT 400");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$counts = db()->query("SELECT status, COUNT(*) n FROM enrolments GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Students & Interns';
require __DIR__ . '/../partials/admin_header.php';
$badge = ['enquiry' => 'badge-warning', 'enrolled' => 'badge-success', 'completed' => 'badge-muted', 'dropped' => 'badge-danger'];
?>

<div class="admin-header-row">
    <div><p class="admin-kicker">Training</p><h1>Students &amp; Interns</h1></div>
    <div class="admin-header-actions">
        <a href="<?= path('admin/students/new') ?>?kind=internship" class="btn btn-outline btn-sm">Add Intern</a>
        <a href="<?= path('admin/students/new') ?>" class="btn btn-primary btn-sm">Add Student</a>
    </div>
</div>

<div class="admin-toolbar">
    <nav class="admin-filters" aria-label="Filter">
        <a href="<?= path('admin/students') ?>"<?= $status === '' && $kind === '' ? ' class="is-active"' : '' ?>>All</a>
        <?php foreach (ENROLMENT_STATUSES as $k => $l): ?><a href="<?= path('admin/students') ?>?status=<?= $k ?>"<?= $status === $k ? ' class="is-active"' : '' ?>><?= e($l) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a><?php endforeach; ?>
        <a href="<?= path('admin/students') ?>?kind=internship"<?= $kind === 'internship' ? ' class="is-active"' : '' ?>>Interns</a>
    </nav>
    <form method="get" class="admin-search"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, phone, course" aria-label="Search students"></form>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Student</th><th>Programme</th><th>Starts</th><th>Fee</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $bal = (float) $r['fee'] - (float) $r['amount_paid']; ?>
            <tr class="is-clickable" data-href="<?= path('admin/students/' . (int) $r['id']) ?>">
                <td><a href="<?= path('admin/students/' . (int) $r['id']) ?>"><strong><?= e($r['student_name']) ?></strong></a><br><small class="muted"><?= e($r['student_phone'] ?: ($r['student_email'] ?? '')) ?></small></td>
                <td><?= $r['kind'] === 'internship' ? '<span class="badge badge-muted">Intern</span> ' : '' ?><?= e($r['course_title'] ?? ($r['track'] ?: '—')) ?></td>
                <td><?= $r['start_date'] ? e((new DateTimeImmutable($r['start_date']))->format('j M Y')) : '—' ?></td>
                <td><?= (float) $r['fee'] > 0 ? format_naira((float) $r['fee']) : '—' ?></td>
                <td><?= $bal > 0 ? '<strong class="qty-out">' . format_naira($bal) . '</strong>' : ((float) $r['fee'] > 0 ? '<span class="qty-in">Paid</span>' : '—') ?></td>
                <td><span class="badge <?= $badge[$r['status']] ?>"><?= e(ENROLMENT_STATUSES[$r['status']]) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="admin-empty">No students here yet. <a href="<?= path('admin/students/new') ?>">Add the first one</a>.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
