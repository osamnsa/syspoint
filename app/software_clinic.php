<?php
declare(strict_types=1);

/** Active portfolio of businesses we've built/deployed software for, shown on the Software Clinic page. */
function deployed_businesses_active(): array
{
    return db()->query(
        'SELECT * FROM deployed_businesses WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
    )->fetchAll();
}

function deployed_business_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM deployed_businesses WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}
