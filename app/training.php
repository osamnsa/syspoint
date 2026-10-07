<?php
declare(strict_types=1);

function training_courses_active(): array
{
    return db()->query(
        'SELECT * FROM training_courses WHERE is_active = 1 ORDER BY sort_order ASC, title ASC'
    )->fetchAll();
}

function training_course_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM training_courses WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

// --- Students & internships (Training dashboard) ------------------------------

const ENROLMENT_STATUSES = ['enquiry' => 'Enquiry', 'enrolled' => 'Enrolled', 'completed' => 'Completed', 'dropped' => 'Dropped'];
const ENROLMENT_KINDS = ['course' => 'Course', 'internship' => 'Internship'];

function enrolment_by_id(int $id): ?array
{
    $s = db()->prepare('SELECT e.*, c.name AS student_name, c.email AS student_email, c.phone AS student_phone, t.title AS course_title
                        FROM enrolments e JOIN customers c ON c.id = e.customer_id LEFT JOIN training_courses t ON t.id = e.course_id WHERE e.id = :id');
    $s->execute(['id' => $id]);
    return $s->fetch() ?: null;
}

/** Record a fee payment against an enrolment (can't exceed the balance). */
function enrolment_record_payment(array $enrolment, float $amount, string $method, string $paidOn, ?string $reference): void
{
    $balance = round((float) $enrolment['fee'] - (float) $enrolment['amount_paid'], 2);
    if ($amount <= 0 || $amount > $balance + 0.001) throw new InventoryException('Enter an amount up to ' . format_naira($balance) . '.');
    if (!in_array($method, ['transfer', 'cash', 'card', 'other'], true)) throw new InventoryException('Choose how it was paid.');
    inventory_tx(function (PDO $pdo) use ($enrolment, $amount, $method, $paidOn, $reference) {
        $pdo->prepare('INSERT INTO enrolment_payments (enrolment_id, amount, method, paid_on, reference, user_id) VALUES (:e, :a, :m, :p, :r, :u)')
            ->execute(['e' => $enrolment['id'], 'a' => $amount, 'm' => $method, 'p' => $paidOn, 'r' => $reference ?: null, 'u' => admin_user()['id'] ?? null]);
        $pdo->prepare('UPDATE enrolments SET amount_paid = amount_paid + :a WHERE id = :id')->execute(['a' => $amount, 'id' => $enrolment['id']]);
        crm_log((int) $enrolment['customer_id'], null, 'note', 'Training fee of ' . format_naira($amount) . ' paid' . ($enrolment['course_title'] ? ' for ' . $enrolment['course_title'] : ''));
    });
}
