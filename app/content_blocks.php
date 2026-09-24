<?php
declare(strict_types=1);

/**
 * Lightweight in-house CMS: lets an admin edit copy on otherwise-hardcoded
 * pages without a code deploy. A view calls content_block('some.key',
 * $originalHardcodedText) in place of that literal string; nothing changes
 * until an admin actually edits it, so a fresh install renders identically
 * to before this feature existed.
 */

function content_blocks_all(): array
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT block_key, content FROM content_blocks') as $row) {
            $cache[$row['block_key']] = $row['content'];
        }
    }

    return $cache;
}

function content_block(string $key, string $default): string
{
    $blocks = content_blocks_all();
    return isset($blocks[$key]) && $blocks[$key] !== '' ? $blocks[$key] : $default;
}

function content_block_save(string $key, string $value): void
{
    db()->prepare(
        'INSERT INTO content_blocks (block_key, content) VALUES (:key, :content)
         ON DUPLICATE KEY UPDATE content = VALUES(content)'
    )->execute(['key' => $key, 'content' => $value]);
}
