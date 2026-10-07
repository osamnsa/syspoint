<?php
declare(strict_types=1);

/**
 * Homepage social proof: testimonials and the three headline stats
 * (clients / years in business / employees). Testimonials are an
 * admin-managed table; the stats are content_blocks, since they're three
 * numbers rather than a list. Client logos are deployed_businesses rows
 * (Admin -> Businesses), shared with the Software Clinic portfolio.
 */

/** Stat keys in display order => label. */
const HOME_STATS = [
    'home.stat_clients' => 'Clients',
    'home.stat_years' => 'Years in Business',
    'home.stat_employees' => 'Employees',
];

/** Shorter stat labels for phones, keyed by the full label above. */
const HOME_STATS_SHORT = [
    'Years in Business' => 'Experience',
];

function testimonials_active(): array
{
    return db()->query(
        'SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll();
}

function testimonial_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM testimonials WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Only the stats an admin has actually filled in, as [label => value] —
 * the homepage hides the whole band until at least one is set, rather
 * than showing invented numbers.
 *
 * @return array<string, string>
 */
function home_stats(): array
{
    $stats = [];
    foreach (HOME_STATS as $key => $label) {
        $value = trim(content_block($key, ''));
        if ($value !== '') {
            $stats[$label] = $value;
        }
    }
    return $stats;
}

/**
 * Image src for a stored media path: an absolute http(s) URL passes through
 * as-is (seeded rows still pointing at the old site's media library);
 * anything else is a path under public/ and goes through asset(). Always
 * returns an HTML-escaped string, ready to drop into an attribute.
 */
function media_url(?string $path): string
{
    $path = (string) $path;
    if (preg_match('#^https?://#i', $path)) {
        return e($path);
    }
    return e(asset($path));
}

/** Initials for a testimonial with no photo, e.g. "Precious E.C" => "PE". */
function initials(string $name): string
{
    $letters = '';
    foreach (preg_split('/[\s.]+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
        $letters .= mb_strtoupper(mb_substr($word, 0, 1));
        if (mb_strlen($letters) === 2) {
            break;
        }
    }
    return $letters;
}
