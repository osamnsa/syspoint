<?php
declare(strict_types=1);

/** Top-level categories (Computers, Accessories, ...) for the shop landing page. */
function product_categories(): array
{
    return db()->query(
        "SELECT c.*, COUNT(p.id) AS product_count
         FROM product_categories c
         LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
         WHERE c.parent_id IS NULL
         GROUP BY c.id
         ORDER BY c.sort_order ASC, c.name ASC"
    )->fetchAll();
}

function product_category_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM product_categories WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    return $stmt->fetch() ?: null;
}

function product_category_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM product_categories WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function category_generate_slug(string $name, int $excludeId = 0): string
{
    $base = slugify($name) ?: 'category';
    if ($base === 'search') $base = 'search-items';   // /shop/search is the search page
    $slug = $base;
    $i = 2;

    $stmt = db()->prepare('SELECT id FROM product_categories WHERE slug = :slug AND id != :exclude LIMIT 1');
    while (true) {
        $stmt->execute(['slug' => $slug, 'exclude' => $excludeId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

/** Active products in a category, cheapest-stocked-first isn't assumed — just newest first. */
function products_in_category(int $categoryId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM products WHERE category_id = :category_id AND is_active = 1 ORDER BY created_at DESC'
    );
    $stmt->execute(['category_id' => $categoryId]);
    return $stmt->fetchAll();
}

function product_by_slug(string $slug): ?array
{
    $stmt = db()->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p JOIN product_categories c ON c.id = p.category_id
         WHERE p.slug = :slug AND p.is_active = 1 LIMIT 1"
    );
    $stmt->execute(['slug' => $slug]);
    return $stmt->fetch() ?: null;
}

function product_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

/** Newest active products across every category, for the shop landing page's "Trending" strip. */
function products_recent(int $limit = 6): array
{
    $limit = max(1, $limit);
    $stmt = db()->prepare(
        "SELECT p.*, c.slug AS category_slug
         FROM products p JOIN product_categories c ON c.id = p.category_id
         WHERE p.is_active = 1
         ORDER BY p.created_at DESC
         LIMIT {$limit}"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function product_gallery_images(int $productId): array
{
    $stmt = db()->prepare('SELECT image_path FROM product_images WHERE product_id = :id ORDER BY sort_order ASC');
    $stmt->execute(['id' => $productId]);
    return array_column($stmt->fetchAll(), 'image_path');
}

function product_generate_slug(string $name, int $excludeId = 0): string
{
    $base = slugify($name) ?: 'product';
    $slug = $base;
    $i = 2;

    $stmt = db()->prepare('SELECT id FROM products WHERE slug = :slug AND id != :exclude LIMIT 1');
    while (true) {
        $stmt->execute(['slug' => $slug, 'exclude' => $excludeId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

// --- Search & filters -------------------------------------------------------

const SHOP_SORTS = [
    'relevance' => 'Best match',
    'newest' => 'Newest',
    'price_asc' => 'Price: low to high',
    'price_desc' => 'Price: high to low',
    'name' => 'Name: A–Z',
];
const SHOP_PER_PAGE = 12;

/**
 * Read search/filter values from the query string, cleaned.
 * q, cat[] (category slugs), min, max (₦), stock (1 = in stock only), sort, page.
 */
function shop_filters_from_request(?array $lockCategory = null): array
{
    $num = fn($v) => ($v = preg_replace('/[^\d]/', '', (string) $v)) === '' ? null : (int) $v;
    $f = [
        'q' => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100),
        'cats' => array_values(array_filter(array_map('strval', (array) ($_GET['cat'] ?? [])), fn($s) => preg_match('/^[a-z0-9-]+$/', $s))),
        'min' => $num($_GET['min'] ?? ''),
        'max' => $num($_GET['max'] ?? ''),
        'stock' => !empty($_GET['stock']),
        'sort' => isset(SHOP_SORTS[$_GET['sort'] ?? '']) ? (string) $_GET['sort'] : '',
        'page' => max(1, (int) ($_GET['page'] ?? 1)),
    ];
    if ($f['min'] !== null && $f['max'] !== null && $f['min'] > $f['max']) [$f['min'], $f['max']] = [$f['max'], $f['min']];
    if ($lockCategory) $f['cats'] = [$lockCategory['slug']];
    if ($f['sort'] === '') $f['sort'] = $f['q'] !== '' ? 'relevance' : 'newest';
    if ($f['sort'] === 'relevance' && $f['q'] === '') $f['sort'] = 'newest';
    return $f;
}

/** WHERE clause + params shared by the result query and the facet counts. */
function shop_filter_sql(array $f, bool $withPrice = true, bool $withCats = true): array
{
    $where = ['p.is_active = 1'];
    $args = [];
    if ($f['q'] !== '') {
        // every word must appear somewhere in name, description, specs, SKU or category
        foreach (array_slice(preg_split('/\s+/', $f['q']), 0, 6) as $i => $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';
            $where[] = "(p.name LIKE :w{$i}a OR p.description LIKE :w{$i}b OR p.specs LIKE :w{$i}c OR p.sku LIKE :w{$i}d OR c.name LIKE :w{$i}e)";
            foreach (['a', 'b', 'c', 'd', 'e'] as $s) $args["w{$i}{$s}"] = $like;
        }
    }
    if ($withCats && $f['cats']) {
        $in = [];
        foreach ($f['cats'] as $i => $slug) { $in[] = ":c$i"; $args["c$i"] = $slug; }
        $where[] = 'c.slug IN (' . implode(',', $in) . ')';
    }
    if ($withPrice && $f['min'] !== null) { $where[] = 'p.price >= :pmin'; $args['pmin'] = $f['min']; }
    if ($withPrice && $f['max'] !== null) { $where[] = 'p.price <= :pmax'; $args['pmax'] = $f['max']; }
    if ($f['stock']) $where[] = 'p.stock_qty > 0';
    return [implode(' AND ', $where), $args];
}

/**
 * Run a search. Returns ['items', 'total', 'pages', 'page', 'price_min', 'price_max', 'facets' => [slug => count]].
 */
function shop_search(array $f): array
{
    [$where, $args] = shop_filter_sql($f);
    $from = 'FROM products p JOIN product_categories c ON c.id = p.category_id';
    $count = db()->prepare("SELECT COUNT(*) $from WHERE $where");
    $count->execute($args);
    $total = (int) $count->fetchColumn();

    $order = [
        'newest' => 'p.created_at DESC, p.id DESC',
        'price_asc' => 'p.price ASC, p.name ASC',
        'price_desc' => 'p.price DESC, p.name ASC',
        'name' => 'p.name ASC',
        'relevance' => 'p.created_at DESC',
    ][$f['sort']];
    $select = "SELECT p.*, c.slug AS category_slug, c.name AS category_name";
    if ($f['sort'] === 'relevance') {
        // name matches first, then whole-phrase name matches, then everything else
        $select .= ", (CASE WHEN p.name LIKE :rel1 THEN 2 WHEN p.name LIKE :rel2 THEN 1 ELSE 0 END) AS rel";
        $args['rel1'] = addcslashes($f['q'], '%_\\') . '%';
        $args['rel2'] = '%' . addcslashes($f['q'], '%_\\') . '%';
        $order = 'rel DESC, ' . $order;
    }
    $pages = max(1, (int) ceil($total / SHOP_PER_PAGE));
    $page = min($f['page'], $pages);
    $stmt = db()->prepare("$select $from WHERE $where ORDER BY $order LIMIT " . SHOP_PER_PAGE . ' OFFSET ' . (($page - 1) * SHOP_PER_PAGE));
    $stmt->execute($args);
    $items = $stmt->fetchAll();

    // price bounds for the current search ignoring the price filter (for the inputs' placeholders)
    [$w2, $a2] = shop_filter_sql($f, false);
    $b = db()->prepare("SELECT MIN(p.price), MAX(p.price) $from WHERE $w2");
    $b->execute($a2);
    [$pmin, $pmax] = $b->fetch(PDO::FETCH_NUM);

    // how many results each category would have (ignoring the category filter)
    [$w3, $a3] = shop_filter_sql($f, true, false);
    $fc = db()->prepare("SELECT c.slug, COUNT(*) $from WHERE $w3 GROUP BY c.slug");
    $fc->execute($a3);
    $facets = $fc->fetchAll(PDO::FETCH_KEY_PAIR);

    return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page,
            'price_min' => $pmin === null ? null : (float) $pmin, 'price_max' => $pmax === null ? null : (float) $pmax, 'facets' => $facets];
}

/** Query string for the current filters with some values changed (null removes). */
function shop_query(array $f, array $change = [], bool $keepCats = true): string
{
    $q = ['q' => $f['q'], 'cat' => $keepCats ? $f['cats'] : [], 'min' => $f['min'], 'max' => $f['max'],
          'stock' => $f['stock'] ? 1 : null, 'sort' => $f['sort'], 'page' => $f['page'] > 1 ? $f['page'] : null];
    $q = array_merge($q, $change);
    if (($q['sort'] ?? '') === ($q['q'] !== '' ? 'relevance' : 'newest')) $q['sort'] = null;
    $q = array_filter($q, fn($v) => $v !== null && $v !== '' && $v !== []);
    return $q ? '?' . http_build_query($q) : '';
}

/** Default hero copy for a category (used when its hero fields are empty). */
function category_hero(array $cat): array
{
    $defaults = [
        'computers' => ['Laptops · desktops · workstations', 'Power for every', 'workload', 'Genuine laptops and desktops for study, work and play — tested in our store, with warranty and after-sales support.'],
        'audio' => ['Headphones · earbuds · speakers', 'Hear every', 'detail', 'Wireless headphones and earbuds with deep bass, clear calls and all-day battery.'],
        'smartwatches' => ['Fitness · health · notifications', 'Smarter on your', 'wrist', 'Track workouts, heart rate and sleep, and keep up with calls and messages at a glance.'],
        'phones' => ['Smartphones · 5G · cameras', 'Stay connected in', 'style', 'Big, bright displays, pro cameras and all-day battery — genuine phones with warranty.'],
        'accessories' => ['Chargers · power banks · cables', 'The essentials,', 'sorted', 'Power banks, chargers, cables and the everyday extras that keep your gadgets going.'],
        'gaming' => ['Controllers · headsets · gear', 'Level up your', 'setup', 'Controllers and gaming gear for PC, console and mobile — test it in our lounge next door.'],
    ];
    $d = $defaults[$cat['slug']] ?? [$cat['name'], 'Shop', $cat['name'], 'Genuine ' . mb_strtolower($cat['name']) . ' in stock now, with secure checkout and fast local delivery.'];
    $title = trim((string) ($cat['hero_title'] ?? ''));
    return [
        'kicker' => trim((string) ($cat['hero_kicker'] ?? '')) ?: $d[0],
        'title' => $title !== '' ? $title : $d[1],
        'highlight' => $title !== '' ? '' : $d[2],
        'text' => trim((string) ($cat['hero_text'] ?? '')) ?: $d[3],
        'image' => ($cat['hero_image'] ?? '') ?: (($cat['image_path'] ?? '') ?: (['computers' => 'assets/img/shop/hero-laptop.png'][$cat['slug']] ?? '')),
    ];
}
