<?php
declare(strict_types=1);

// /sitemap.xml — every public page, shop category, product and upcoming event.
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$urls = [];
$add = function (string $path, ?string $lastmod = null, string $freq = 'weekly', string $priority = '0.6') use (&$urls) {
    $urls[] = ['loc' => public_url($path), 'lastmod' => $lastmod ? date('Y-m-d', strtotime($lastmod)) : null, 'freq' => $freq, 'priority' => $priority];
};

$add('', null, 'daily', '1.0');
foreach (['shop' => ['daily', '0.9'], 'software-clinic' => ['monthly', '0.8'], 'gaming' => ['weekly', '0.8'], 'training' => ['weekly', '0.8'],
          'about' => ['monthly', '0.7'], 'events' => ['daily', '0.7'], 'contact' => ['yearly', '0.6'],
          'privacy-policy' => ['yearly', '0.2'], 'returns-policy' => ['yearly', '0.3'], 'terms' => ['yearly', '0.2']] as $p => [$f, $pr]) {
    $add($p, null, $f, $pr);
}
foreach (product_categories() as $c) $add('shop/' . $c['slug'], null, 'daily', '0.8');
foreach (db()->query("SELECT p.slug, p.updated_at, c.slug AS cat FROM products p JOIN product_categories c ON c.id = p.category_id WHERE p.is_active = 1 ORDER BY p.id") as $p) {
    $add('shop/' . $p['cat'] . '/' . $p['slug'], $p['updated_at'], 'weekly', '0.7');
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n", '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as $u) {
    echo '  <url><loc>', htmlspecialchars($u['loc'], ENT_XML1), '</loc>',
        $u['lastmod'] ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '',
        '<changefreq>', $u['freq'], '</changefreq><priority>', $u['priority'], '</priority></url>', "\n";
}
echo '</urlset>', "\n";
