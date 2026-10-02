<?php
declare(strict_types=1);

function games_by_type(string $type): array
{
    $stmt = db()->prepare(
        'SELECT * FROM games WHERE type = :type AND is_active = 1 ORDER BY sort_order ASC, name ASC'
    );
    $stmt->execute(['type' => $type]);
    return $stmt->fetchAll();
}

function game_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM games WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

/** Display label for a games.type value — ucfirst() alone can't get "PS5" or "VR" right. */
function game_type_label(string $type): string
{
    return match ($type) {
        'ps5' => 'PS5',
        'vr' => 'VR',
        'board' => 'Board Game',
        default => ucfirst($type),
    };
}

function gaming_rooms_active(): array
{
    return db()->query('SELECT * FROM gaming_rooms WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
}

function gaming_room_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM gaming_rooms WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    return $stmt->fetch() ?: null;
}

function gaming_room_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM gaming_rooms WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function room_generate_slug(string $name, int $excludeId = 0): string
{
    $base = slugify($name) ?: 'room';
    $slug = $base;
    $i = 2;

    $stmt = db()->prepare('SELECT id FROM gaming_rooms WHERE slug = :slug AND id != :exclude LIMIT 1');
    while (true) {
        $stmt->execute(['slug' => $slug, 'exclude' => $excludeId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

/**
 * True if the requested time range overlaps an existing pending/confirmed
 * booking for the same room on the same date — the standard
 * (StartA < EndB) AND (EndA > StartB) interval-overlap test. Backed by
 * idx_room_bookings_date (room_id, booking_date) in the schema. Cancelled
 * bookings free the slot back up, so they're excluded here.
 */
function room_booking_overlaps(int $roomId, string $date, string $startTime, string $endTime, int $excludeId = 0): bool
{
    $stmt = db()->prepare(
        "SELECT id FROM room_bookings
         WHERE room_id = :room_id AND booking_date = :date AND status IN ('pending', 'confirmed')
         AND id != :exclude_id AND start_time < :end_time AND end_time > :start_time
         LIMIT 1"
    );
    $stmt->execute([
        'room_id' => $roomId,
        'date' => $date,
        'exclude_id' => $excludeId,
        'start_time' => $startTime,
        'end_time' => $endTime,
    ]);
    return (bool) $stmt->fetch();
}
