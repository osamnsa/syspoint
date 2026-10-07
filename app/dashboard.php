<?php
/**
 * Numbers behind the admin dashboards. Every figure is computed from the live
 * tables: shop revenue = paid online orders; gaming revenue = confirmed or
 * completed room bookings (hours × room rate). New revenue streams (walk-in
 * POS, CRM invoices) add themselves to dashboard_revenue_streams().
 */

declare(strict_types=1);

const DASH_RANGES = [
    '7d' => 'Last 7 days',
    '30d' => 'Last 30 days',
    '90d' => 'Last 90 days',
    '12m' => 'Last 12 months',
];

/**
 * Period for a range key: start/end (inclusive dates), the previous period of
 * the same length, and the buckets the charts group by.
 */
function dashboard_period(string $range): array
{
    $range = isset(DASH_RANGES[$range]) ? $range : '30d';
    $today = new DateTimeImmutable('today');

    if ($range === '12m') {
        $start = $today->modify('first day of this month')->modify('-11 months');
        $buckets = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $start->modify("+$i months");
            $buckets[] = ['key' => $m->format('Y-m'), 'label' => $m->format('M'), 'from' => $m, 'to' => $m->modify('last day of this month')];
        }
        $prevStart = $start->modify('-12 months');
        return ['range' => $range, 'unit' => 'month', 'start' => $start, 'end' => $today,
            'prev_start' => $prevStart, 'prev_end' => $start->modify('-1 day'), 'buckets' => $buckets];
    }

    $days = ['7d' => 7, '30d' => 30, '90d' => 90][$range];
    $start = $today->modify('-' . ($days - 1) . ' days');
    $buckets = [];
    if ($days <= 30) {
        for ($i = 0; $i < $days; $i++) {
            $d = $start->modify("+$i days");
            $buckets[] = ['key' => $d->format('Y-m-d'), 'label' => $days === 7 ? $d->format('D') : $d->format('j M'), 'from' => $d, 'to' => $d];
        }
        $unit = 'day';
    } else {
        for ($d = $start; $d <= $today; $d = $d->modify('+7 days')) {
            $to = min($d->modify('+6 days'), $today);
            $buckets[] = ['key' => $d->format('Y-m-d'), 'label' => $d->format('j M'), 'from' => $d, 'to' => $to];
        }
        $unit = 'week';
    }
    return ['range' => $range, 'unit' => $unit, 'start' => $start, 'end' => $today,
        'prev_start' => $start->modify("-$days days"), 'prev_end' => $start->modify('-1 day'), 'buckets' => $buckets];
}

/** Rows of [day (Y-m-d), amount] for one stream between two dates. */
function dashboard_stream_rows(string $stream, DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $params = ['from' => $from->format('Y-m-d 00:00:00'), 'to' => $to->format('Y-m-d 23:59:59')];
    $sql = match ($stream) {
        'shop' => "SELECT DATE(created_at) AS day, SUM(subtotal) AS amount FROM orders
                   WHERE payment_status = 'paid' AND created_at BETWEEN :from AND :to GROUP BY DATE(created_at)",
        'gaming' => "SELECT b.booking_date AS day,
                       SUM(GREATEST(TIME_TO_SEC(TIMEDIFF(b.end_time, b.start_time)), 0) / 3600 * r.hourly_rate) AS amount
                     FROM room_bookings b JOIN gaming_rooms r ON r.id = b.room_id
                     WHERE b.status IN ('confirmed', 'completed') AND b.booking_date BETWEEN DATE(:from) AND DATE(:to)
                     GROUP BY b.booking_date",
        default => null,
    };
    if ($sql === null) return [];
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Revenue streams shown on the Overview, in CHART_SERIES order. */
function dashboard_revenue_streams(): array
{
    return [
        'shop' => 'Shop',
        'gaming' => 'Gaming',
    ];
}

/** Per-stream values per bucket, plus totals for this and the previous period. */
function dashboard_revenue(array $period): array
{
    $out = [];
    foreach (dashboard_revenue_streams() as $key => $name) {
        $byDay = [];
        foreach (dashboard_stream_rows($key, $period['start'], $period['end']) as $row) {
            $byDay[$row['day']] = (float) $row['amount'];
        }
        $values = [];
        foreach ($period['buckets'] as $b) {
            $sum = 0.0;
            foreach ($byDay as $day => $amt) {
                if ($day >= $b['from']->format('Y-m-d') && $day <= $b['to']->format('Y-m-d')) $sum += $amt;
            }
            $values[] = $sum;
        }
        $prev = array_sum(array_map(fn($r) => (float) $r['amount'], dashboard_stream_rows($key, $period['prev_start'], $period['prev_end'])));
        $out[$key] = ['name' => $name, 'values' => $values, 'total' => array_sum($values), 'prev' => $prev];
    }
    return $out;
}

/** Count rows of $table in a date window (by $col). */
function dashboard_count(string $sql, DateTimeImmutable $from, DateTimeImmutable $to): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute(['from' => $from->format('Y-m-d 00:00:00'), 'to' => $to->format('Y-m-d 23:59:59')]);
    return (int) $stmt->fetchColumn();
}

/** % change from $prev to $now, or null when there's nothing to compare against. */
function dashboard_change(float $now, float $prev): ?float
{
    if ($prev <= 0) return null;
    return ($now - $prev) / $prev * 100;
}

/** Bookings per bucket (any status except cancelled). */
function dashboard_bookings_series(array $period): array
{
    $stmt = db()->prepare("SELECT booking_date AS day, COUNT(*) AS n FROM room_bookings
        WHERE status <> 'cancelled' AND booking_date BETWEEN :from AND :to GROUP BY booking_date");
    $stmt->execute(['from' => $period['start']->format('Y-m-d'), 'to' => $period['end']->format('Y-m-d')]);
    $byDay = [];
    foreach ($stmt->fetchAll() as $r) $byDay[$r['day']] = (int) $r['n'];
    $values = [];
    foreach ($period['buckets'] as $b) {
        $sum = 0;
        foreach ($byDay as $day => $n) {
            if ($day >= $b['from']->format('Y-m-d') && $day <= $b['to']->format('Y-m-d')) $sum += $n;
        }
        $values[] = $sum;
    }
    return $values;
}

/** Software Clinic requests by stage — the sales funnel until CRM deals replace it. */
function dashboard_request_funnel(): array
{
    $counts = ['new' => 0, 'in_review' => 0, 'quoted' => 0, 'closed' => 0];
    foreach (db()->query('SELECT status, COUNT(*) AS n FROM software_requests GROUP BY status') as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
    return [
        ['label' => 'New', 'value' => $counts['new']],
        ['label' => 'In review', 'value' => $counts['in_review']],
        ['label' => 'Quoted', 'value' => $counts['quoted']],
        ['label' => 'Closed', 'value' => $counts['closed']],
    ];
}

/** Monthly summary for the last $months months, newest first. */
function dashboard_monthly_summary(int $months = 6): array
{
    $rows = [];
    $first = (new DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');
    for ($i = $months - 1; $i >= 0; $i--) {
        $m = $first->modify("+$i months");
        $end = $m->modify('last day of this month');
        $shop = array_sum(array_map(fn($r) => (float) $r['amount'], dashboard_stream_rows('shop', $m, $end)));
        $gaming = array_sum(array_map(fn($r) => (float) $r['amount'], dashboard_stream_rows('gaming', $m, $end)));
        $orders = dashboard_count("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid' AND created_at BETWEEN :from AND :to", $m, $end);
        $stmt = db()->prepare("SELECT COUNT(*) FROM room_bookings WHERE status <> 'cancelled' AND booking_date BETWEEN :from AND :to");
        $stmt->execute(['from' => $m->format('Y-m-d'), 'to' => $end->format('Y-m-d')]);
        $rows[] = ['month' => $m->format('F Y'), 'shop' => $shop, 'orders' => $orders, 'gaming' => $gaming,
            'bookings' => (int) $stmt->fetchColumn(), 'total' => $shop + $gaming];
    }
    return $rows;
}
