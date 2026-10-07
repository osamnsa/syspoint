<?php
/**
 * Server-rendered SVG charts for the admin dashboards — no JS library, no CDN.
 * Every mark carries data-tip="…" so public/assets/js/admin.js can show a
 * hover tooltip; values are also in <title> for screen readers / no-JS.
 *
 * Colours: one series uses brand gold; several series use CHART_SERIES in
 * fixed order (validated for colour-blind separation and contrast on the dark
 * glass surface). Text never wears a series colour.
 */

declare(strict_types=1);

/** Fixed categorical order: Shop, Gaming, Software/CRM, Training. */
const CHART_SERIES = ['#B48C00', '#7A7AE6', '#1E9E86', '#D86A50'];
const CHART_GOLD = '#F7CB1E';

/** ₦ amount, shortened for axes and tiles: ₦950, ₦12.5K, ₦1.2M. */
function naira_short(float $amount): string
{
    $abs = abs($amount);
    if ($abs >= 1_000_000) return '₦' . rtrim(rtrim(number_format($amount / 1_000_000, 1), '0'), '.') . 'M';
    if ($abs >= 1_000) return '₦' . rtrim(rtrim(number_format($amount / 1_000, 1), '0'), '.') . 'K';
    return '₦' . number_format($amount);
}

/** A "nice" axis maximum (1, 2, 2.5, 5 × 10^n) at or above $max. */
function chart_nice_max(float $max): float
{
    if ($max <= 0) return 1;
    $exp = 10 ** floor(log10($max));
    foreach ([1, 2, 2.5, 5, 10] as $m) {
        if ($m * $exp >= $max) return $m * $exp;
    }
    return 10 * $exp;
}

/** Gridline count that keeps every tick a round number (5 for 2.5× / 5× tops, else 4). */
function chart_divisions(float $top): int
{
    $lead = $top / (10 ** floor(log10($top)));
    return in_array(round($lead, 2), [2.5, 5.0], true) ? 5 : 4;
}

/** Smooth SVG path through points (Catmull-Rom → cubic Bézier). */
/** Control points are clamped to [$yTop, $yBottom] so the curve never overshoots the axis (no fake negatives). */
function chart_smooth_path(array $pts, float $yTop = -INF, float $yBottom = INF): string
{
    $n = count($pts);
    if ($n === 0) return '';
    $d = sprintf('M%.1f %.1f', $pts[0][0], $pts[0][1]);
    for ($i = 0; $i < $n - 1; $i++) {
        $p0 = $pts[max(0, $i - 1)];
        $p1 = $pts[$i];
        $p2 = $pts[$i + 1];
        $p3 = $pts[min($n - 1, $i + 2)];
        $c1 = [$p1[0] + ($p2[0] - $p0[0]) / 6, min($yBottom, max($yTop, $p1[1] + ($p2[1] - $p0[1]) / 6))];
        $c2 = [$p2[0] - ($p3[0] - $p1[0]) / 6, min($yBottom, max($yTop, $p2[1] - ($p3[1] - $p1[1]) / 6))];
        // Flat between two zero points: keep the segment on the baseline.
        if ($p1[1] >= $yBottom && $p2[1] >= $yBottom) { $c1[1] = $c2[1] = $yBottom; }
        $d .= sprintf(' C%.1f %.1f %.1f %.1f %.1f %.1f', $c1[0], $c1[1], $c2[0], $c2[1], $p2[0], $p2[1]);
    }
    return $d;
}

/**
 * Line/area chart over time.
 * $series: [['name' => 'Shop', 'values' => [...], 'color' => '#…'], ...] — values align with $labels.
 * Options: format (callable for tooltips/axis), height, area (bool, single series only).
 */
function chart_line(array $labels, array $series, array $opt = []): string
{
    $w = $opt['width'] ?? 720; $h = $opt['height'] ?? 260;
    $pl = $w < 500 ? 50 : 56; $pr = 16; $pt = 16; $pb = 30;
    $fmt = $opt['format'] ?? fn($v) => number_format((float) $v);
    $n = max(1, count($labels));
    $max = 0;
    foreach ($series as $s) foreach ($s['values'] as $v) $max = max($max, (float) $v);
    $top = chart_nice_max($max);
    $x = fn($i) => $pl + ($n === 1 ? ($w - $pl - $pr) / 2 : $i * ($w - $pl - $pr) / ($n - 1));
    $y = fn($v) => $pt + ($h - $pt - $pb) * (1 - ((float) $v) / $top);
    $id = 'g' . substr(md5(json_encode([$labels, $series])), 0, 8);

    $svg = '<svg class="chart chart-line" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . e($opt['label'] ?? 'Chart') . '">';
    // Recessive grid + y labels (4 steps)
    $div = chart_divisions($top);
    for ($g = 0; $g <= $div; $g++) {
        $val = $top * $g / $div; $gy = $y($val);
        $svg .= sprintf('<line class="chart-grid" x1="%d" x2="%d" y1="%.1f" y2="%.1f"/>', $pl, $w - $pr, $gy, $gy);
        $svg .= sprintf('<text class="chart-axis" x="%d" y="%.1f" text-anchor="end">%s</text>', $pl - 8, $gy + 4, e($fmt($val)));
    }
    // x labels: at most ~8 so they never collide
    $step = max(1, (int) ceil($n / max(3, (int) ($w / 90))));
    foreach ($labels as $i => $lab) {
        $isLast = $i === $n - 1;
        if (!$isLast && ($i % $step !== 0 || $n - 1 - $i < max(2, (int) ceil($step * 0.6)))) continue;
        $svg .= sprintf('<text class="chart-axis" x="%.1f" y="%d" text-anchor="%s">%s</text>', $x($i), $h - 8, $isLast && $n > 1 ? 'end' : 'middle', e($lab));
    }

    $single = count($series) === 1;
    foreach ($series as $si => $s) {
        $color = $s['color'] ?? ($single ? CHART_GOLD : CHART_SERIES[$si % 4]);
        $pts = [];
        foreach ($labels as $i => $_) $pts[] = [$x($i), $y($s['values'][$i] ?? 0)];
        $path = chart_smooth_path($pts, $pt, $h - $pb);
        if ($single && ($opt['area'] ?? true)) {
            $svg .= '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . $color . '" stop-opacity="0.35"/><stop offset="1" stop-color="' . $color . '" stop-opacity="0"/></linearGradient></defs>';
            $svg .= sprintf('<path d="%s L%.1f %d L%.1f %d Z" fill="url(#%s)"/>', $path, end($pts)[0], $h - $pb, $pts[0][0], $h - $pb, $id);
        }
        $svg .= '<path class="chart-stroke" d="' . $path . '" stroke="' . $color . '"/>';
        // Last point marker with a surface ring
        $lp = end($pts);
        $svg .= sprintf('<circle cx="%.1f" cy="%.1f" r="5" fill="%s" class="chart-dot"/>', $lp[0], $lp[1], $color);
    }
    // Hover columns: one hit target per x, tooltip lists every series
    $colW = ($w - $pl - $pr) / max(1, $n - 1);
    foreach ($labels as $i => $lab) {
        $tip = $lab;
        foreach ($series as $s) $tip .= ' · ' . $s['name'] . ': ' . $fmt($s['values'][$i] ?? 0);
        $svg .= sprintf('<rect class="chart-hit" x="%.1f" y="%d" width="%.1f" height="%d" data-tip="%s" data-x="%.1f"><title>%s</title></rect>',
            max($pl, $x($i) - $colW / 2), $pt, $n === 1 ? $w - $pl - $pr : $colW, $h - $pt - $pb, e($tip), $x($i), e($tip));
    }
    $svg .= '<line class="chart-crosshair" x1="0" x2="0" y1="' . $pt . '" y2="' . ($h - $pb) . '"/>';
    return $svg . '</svg>';
}

/**
 * Vertical bars, each on a faint full-height track (like a target).
 * $values aligned with $labels. Options: format, height, color.
 */
function chart_bars(array $labels, array $values, array $opt = []): string
{
    $w = $opt['width'] ?? 720; $h = $opt['height'] ?? 240;
    $pl = 44; $pr = 8; $pt = 12; $pb = 30;
    $fmt = $opt['format'] ?? fn($v) => number_format((float) $v);
    $color = $opt['color'] ?? CHART_GOLD;
    $n = max(1, count($labels));
    // Counts: never let ticks fall between whole numbers.
    $top = chart_nice_max(max(($opt['integer'] ?? true) ? 4.0 : 0.0, (float) max([0, ...array_map('floatval', $values)])));
    $slot = ($w - $pl - $pr) / $n;
    $bw = min(44, $slot * 0.62);
    $ih = $h - $pt - $pb;

    $svg = '<svg class="chart chart-bars" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . e($opt['label'] ?? 'Chart') . '">';
    $div = chart_divisions($top);
    for ($g = 0; $g <= $div; $g++) {
        $val = $top * $g / $div; $gy = $pt + $ih * (1 - $g / $div);
        $svg .= sprintf('<line class="chart-grid" x1="%d" x2="%d" y1="%.1f" y2="%.1f"/>', $pl, $w - $pr, $gy, $gy);
        $svg .= sprintf('<text class="chart-axis" x="%d" y="%.1f" text-anchor="end">%s</text>', $pl - 8, $gy + 4, e($fmt($val)));
    }
    $step = max(1, (int) ceil($n / max(4, (int) ($w / 70))));
    foreach ($labels as $i => $lab) {
        $cx = $pl + $slot * ($i + 0.5);
        $v = (float) ($values[$i] ?? 0);
        $bh = $ih * $v / $top;
        $svg .= sprintf('<rect class="chart-track" x="%.1f" y="%d" width="%.1f" height="%d" rx="4"/>', $cx - $bw / 2, $pt, $bw, $ih);
        if ($bh > 0) {
            $svg .= sprintf('<path d="M%.1f %.1f v%.1f a4 4 0 0 1 4 -4 h%.1f a4 4 0 0 1 4 4 v%.1f Z" fill="%s"/>',
                $cx - $bw / 2, $pt + $ih, -max(0, $bh - 4), $bw - 8, max(0, $bh - 4), $color);
        }
        $tip = $lab . ': ' . $fmt($v);
        $svg .= sprintf('<rect class="chart-hit" x="%.1f" y="%d" width="%.1f" height="%d" data-tip="%s"><title>%s</title></rect>', $cx - $slot / 2, $pt, $slot, $ih, e($tip), e($tip));
        if ($i % $step === 0 && ($n - 1 - $i >= $step || $i === $n - 1) || ($i === $n - 1)) {
            $svg .= sprintf('<text class="chart-axis" x="%.1f" y="%d" text-anchor="middle">%s</text>', $cx, $h - 8, e($lab));
        }
    }
    return $svg . '</svg>';
}

/**
 * Donut showing parts of a whole. $parts: [['name'=>…, 'value'=>…], …] in
 * CHART_SERIES order. Centre shows $centre (big) and $caption (small).
 */
function chart_donut(array $parts, string $centre, string $caption, array $opt = []): string
{
    $fmt = $opt['format'] ?? fn($v) => number_format((float) $v);
    $total = array_sum(array_map(fn($p) => (float) $p['value'], $parts));
    $r = 70; $c = 2 * M_PI * $r; $gap = 3;
    $svg = '<svg class="chart chart-donut" viewBox="0 0 200 200" role="img" aria-label="' . e($opt['label'] ?? $caption) . '">';
    $svg .= '<circle cx="100" cy="100" r="' . $r . '" class="chart-donut-track"/>';
    $offset = 0;
    if ($total > 0) {
        foreach ($parts as $i => $p) {
            $len = $c * ((float) $p['value']) / $total;
            if ($len <= 0) continue;
            $tip = $p['name'] . ': ' . $fmt($p['value']) . ' (' . round(100 * $p['value'] / $total) . '%)';
            $svg .= sprintf('<circle cx="100" cy="100" r="%d" fill="none" stroke="%s" stroke-width="18" stroke-dasharray="%.2f %.2f" stroke-dashoffset="%.2f" transform="rotate(-90 100 100)" data-tip="%s" class="chart-seg"><title>%s</title></circle>',
                $r, $p['color'] ?? CHART_SERIES[$i % 4], max(0.01, $len - ($len > $gap * 2 ? $gap : 0)), $c, -$offset, e($tip), e($tip));
            $offset += $len;
        }
    }
    $svg .= '<text x="100" y="100" text-anchor="middle" class="chart-donut-value">' . e($centre) . '</text>';
    $svg .= '<text x="100" y="124" text-anchor="middle" class="chart-donut-caption">' . e($caption) . '</text>';
    return $svg . '</svg>';
}

/** Legend for multi-series charts: swatch + name (+ optional value). */
function chart_legend(array $items): string
{
    $html = '<ul class="chart-legend">';
    foreach ($items as $i => $it) {
        $html .= '<li><span class="chart-swatch" style="background:' . e($it['color'] ?? CHART_SERIES[$i % 4]) . '"></span>' . e($it['name'])
            . (isset($it['value']) ? ' <strong>' . e((string) $it['value']) . '</strong>' : '') . '</li>';
    }
    return $html . '</ul>';
}
