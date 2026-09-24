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
