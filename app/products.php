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
