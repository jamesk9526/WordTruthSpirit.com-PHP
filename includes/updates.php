<?php
declare(strict_types=1);
require_once ROOT_PATH . '/config/database.php';

function ensureUpdateLedger(): bool
{
    $db = database(); if (!$db) return false;
    try { $db->exec("CREATE TABLE IF NOT EXISTS wts_database_updates (update_key VARCHAR(120) PRIMARY KEY, description VARCHAR(255) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, applied_by VARCHAR(190) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); return true; }
    catch (PDOException $exception) { error_log('Update ledger setup failed: ' . $exception->getMessage()); return false; }
}

function writerMetadataColumns(PDO $db): void
{
    if (databaseUsesLegacySchema() || !databaseTableExists('wts_posts')) return;
    $columns = $db->query('SHOW COLUMNS FROM wts_posts')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['tags'=>'TEXT NULL','cover_image'=>'VARCHAR(2048) NULL','meta_title'=>'VARCHAR(500) NULL','meta_description'=>'TEXT NULL','featured'=>'TINYINT(1) NOT NULL DEFAULT 0'] as $name=>$definition) if (!in_array($name,$columns,true)) $db->exec("ALTER TABLE wts_posts ADD COLUMN {$name} {$definition}");
}

function databaseUpdates(): array
{
    return [
      '2026-07-writer-metadata'=>['Writer metadata','Adds tags, cover images, SEO fields, and featured status to the PHP-native posts table.', fn(PDO $db)=>writerMetadataColumns($db)],
      '2026-07-custom-tags'=>['Custom tags','Creates the reusable tag library used by the journal editor.', fn(PDO $db)=>$db->exec("CREATE TABLE IF NOT EXISTS wts_tags (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80) NOT NULL UNIQUE,slug VARCHAR(100) NOT NULL UNIQUE,description VARCHAR(255) NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")],
      '2026-07-seo-studio'=>['SEO studio','Creates storage for SEO titles, descriptions, and focus keywords for public pages.', fn(PDO $db)=>$db->exec("CREATE TABLE IF NOT EXISTS wts_seo_pages (page_key VARCHAR(80) PRIMARY KEY,meta_title VARCHAR(500) NULL,meta_description TEXT NULL,focus_keyword VARCHAR(160) NULL,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")],
      '2026-07-first-party-analytics'=>['First-party analytics','Creates the privacy-conscious page-view table. It stores no IP addresses.', fn(PDO $db)=>$db->exec("CREATE TABLE IF NOT EXISTS wts_page_views (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,viewed_on DATE NOT NULL,visitor_hash CHAR(64) NOT NULL,page_path VARCHAR(500) NOT NULL,referrer_host VARCHAR(190) NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX idx_wts_views_day (viewed_on),INDEX idx_wts_views_path_day (page_path(190),viewed_on)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")],
    ];
}

function updateLedger(): array
{
    $db=database(); if (!$db || !ensureUpdateLedger()) return [];
    return $db->query('SELECT update_key,description,applied_at,applied_by FROM wts_database_updates ORDER BY applied_at DESC')->fetchAll();
}

function runPendingDatabaseUpdates(string $adminEmail): array
{
    $db=database(); if (!$db || !ensureUpdateLedger()) throw new RuntimeException('Database is not connected or the update ledger could not be prepared.');
    $done=array_column(updateLedger(),'update_key'); $applied=[];
    foreach(databaseUpdates() as $key=>[$name,$description,$run]) {
        if(in_array($key,$done,true)) continue;
        $run($db);
        $db->prepare('INSERT INTO wts_database_updates (update_key,description,applied_by) VALUES (?,?,?)')->execute([$key,$description,$adminEmail]);
        $applied[]=$name;
    }
    return $applied;
}
