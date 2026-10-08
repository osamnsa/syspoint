<?php
/**
 * Events: tournaments, game nights, training intakes, open days.
 * Listed on /events (gaming ones also on /gaming, training ones on
 * /training) until they end, and announced on the Telegram channel.
 */

declare(strict_types=1);

const EVENT_KINDS = [
    'gaming' => 'Gaming',
    'training' => 'Training',
    'general' => 'General',
];

function event_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Visible events that haven't finished (an event without an end time stays
 * up for the rest of its day). $kinds limits to those kinds.
 */
function events_upcoming(?array $kinds = null, int $limit = 12): array
{
    $sql = 'SELECT * FROM events WHERE is_active = 1 AND COALESCE(ends_at, TIMESTAMP(DATE(starts_at), \'23:59:59\')) >= :now';
    $args = ['now' => date('Y-m-d H:i:s')];
    if ($kinds) {
        $in = [];
        foreach (array_values($kinds) as $i => $k) {
            $in[] = ':k' . $i;
            $args['k' . $i] = $k;
        }
        $sql .= ' AND kind IN (' . implode(',', $in) . ')';
    }
    $stmt = db()->prepare($sql . ' ORDER BY starts_at ASC LIMIT ' . max(1, $limit));
    $stmt->execute($args);
    return $stmt->fetchAll();
}

/** "Sat 18 Oct, 4:00 PM – 9:00 PM" (end shown with its date only when it's another day). */
function event_when(array $ev): string
{
    $s = new DateTimeImmutable((string) $ev['starts_at']);
    $out = $s->format('D j M, g:i A');
    if (!empty($ev['ends_at'])) {
        $end = new DateTimeImmutable((string) $ev['ends_at']);
        $out .= ' – ' . ($end->format('Y-m-d') === $s->format('Y-m-d') ? $end->format('g:i A') : $end->format('D j M, g:i A'));
    }
    return $out;
}

function event_price_label(array $ev): string
{
    return $ev['price'] === null ? '' : ((float) $ev['price'] > 0 ? format_naira((float) $ev['price']) : 'Free');
}

/** Where an event's button goes: its own link, else the public events page. */
function event_link(array $ev): string
{
    return $ev['button_url'] ?: url('events') . '#event-' . (int) $ev['id'];
}

function event_is_past(array $ev): bool
{
    $end = $ev['ends_at'] ?: substr((string) $ev['starts_at'], 0, 10) . ' 23:59:59';
    return new DateTimeImmutable((string) $end) < new DateTimeImmutable();
}
