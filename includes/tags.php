<?php
declare(strict_types=1);
require_once ROOT_PATH . '/config/database.php';

function postTags(string $tags): array
{
    $values = array_filter(array_map(fn(string $tag): string => trim($tag), explode(',', $tags)));
    $unique = [];
    foreach ($values as $tag) $unique[mb_strtolower($tag)] = $tag;
    return array_values($unique);
}

function ensureTagTable(): bool
{
    $db = database();
    if (!$db) return false;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS wts_tags (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL UNIQUE, slug VARCHAR(100) NOT NULL UNIQUE, description VARCHAR(255) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return true;
    } catch (PDOException $exception) { error_log('Tag table creation failed: ' . $exception->getMessage()); return false; }
}

function allTags(): array
{
    $tags = [];
    $db = database();
    if ($db) {
        try {
            if (ensureTagTable()) foreach ($db->query('SELECT name FROM wts_tags ORDER BY name')->fetchAll(PDO::FETCH_COLUMN) as $tag) $tags[mb_strtolower($tag)] = $tag;
            $table = databaseUsesLegacySchema() ? 'posts' : 'wts_posts';
            $column = databaseUsesLegacySchema() ? 'tags' : null;
            if ($column) foreach ($db->query("SELECT tags FROM {$table} WHERE tags IS NOT NULL AND tags<>''")->fetchAll(PDO::FETCH_COLUMN) as $raw) foreach (postTags((string)$raw) as $tag) $tags[mb_strtolower($tag)] = $tag;
        } catch (PDOException $exception) { error_log('Tag lookup failed: ' . $exception->getMessage()); }
    }
    ksort($tags, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($tags);
}
