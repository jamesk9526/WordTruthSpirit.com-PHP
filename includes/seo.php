<?php
declare(strict_types=1);
require_once ROOT_PATH . '/config/database.php';

function ensureSeoTable(): bool
{
    $db = database(); if (!$db) return false;
    try { $db->exec("CREATE TABLE IF NOT EXISTS wts_seo_pages (page_key VARCHAR(80) PRIMARY KEY, meta_title VARCHAR(500) NULL, meta_description TEXT NULL, focus_keyword VARCHAR(160) NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); return true; }
    catch (PDOException $exception) { error_log('SEO table setup failed: ' . $exception->getMessage()); return false; }
}
function seoPageKey(): string
{
    $file = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    return match ($file) { 'index.php' => str_contains((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/blog/') ? 'blog' : 'home', 'word.php'=>'word', 'truth.php'=>'truth', 'spirit.php'=>'spirit', 'publications.php'=>'publications', 'commitments.php'=>'commitments', 'contact.php'=>'contact', default=>'' };
}
function seoPage(string $key): array
{
    $db = database(); if (!$db || !ensureSeoTable()) return [];
    try { $statement=$db->prepare('SELECT * FROM wts_seo_pages WHERE page_key=?'); $statement->execute([$key]); return $statement->fetch() ?: []; } catch (PDOException $exception) { return []; }
}
