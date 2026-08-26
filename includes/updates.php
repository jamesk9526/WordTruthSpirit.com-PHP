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
    foreach (['tags'=>'TEXT NULL','cover_image'=>'VARCHAR(2048) NULL','audio_url'=>'VARCHAR(2048) NULL','meta_title'=>'VARCHAR(500) NULL','meta_description'=>'TEXT NULL','featured'=>'TINYINT(1) NOT NULL DEFAULT 0'] as $name=>$definition) if (!in_array($name,$columns,true)) $db->exec("ALTER TABLE wts_posts ADD COLUMN {$name} {$definition}");
}

function reflectionAudioColumn(PDO $db): void
{
    $table = databaseUsesLegacySchema() ? 'posts' : 'wts_posts';
    if (!databaseTableExists($table)) return;
    $columns = $db->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('audio_url', $columns, true)) $db->exec("ALTER TABLE {$table} ADD COLUMN audio_url VARCHAR(2048) NULL");
}

function subscriberSourceColumn(PDO $db): void
{
    if (databaseUsesLegacySchema() || !databaseTableExists('wts_subscribers')) return;
    $columns = $db->query('SHOW COLUMNS FROM wts_subscribers')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('source',$columns,true)) $db->exec("ALTER TABLE wts_subscribers ADD COLUMN source VARCHAR(80) NOT NULL DEFAULT 'website' AFTER token");
}

function threadedCommentUpgrade(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS wts_blog_comments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,post_slug VARCHAR(190) NOT NULL,parent_id BIGINT UNSIGNED NULL,name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL,body TEXT NOT NULL,status ENUM('pending','approved','spam','trash') NOT NULL DEFAULT 'pending',author_hash CHAR(64) NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP NULL DEFAULT NULL,INDEX idx_wts_comments_post (post_slug,status,created_at),INDEX idx_wts_comments_parent (parent_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $columns = $db->query('SHOW COLUMNS FROM wts_blog_comments')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('parent_id',$columns,true)) $db->exec('ALTER TABLE wts_blog_comments ADD COLUMN parent_id BIGINT UNSIGNED NULL AFTER post_slug, ADD INDEX idx_wts_comments_parent (parent_id)');
    if (!in_array('author_hash',$columns,true)) $db->exec('ALTER TABLE wts_blog_comments ADD COLUMN author_hash CHAR(64) NULL AFTER status');
    if (!in_array('updated_at',$columns,true)) $db->exec('ALTER TABLE wts_blog_comments ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at');
    $db->exec("CREATE TABLE IF NOT EXISTS wts_comment_reactions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,comment_id BIGINT UNSIGNED NOT NULL,voter_hash CHAR(64) NOT NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_wts_comment_reaction (comment_id,voter_hash),INDEX idx_wts_comment_reactions_comment (comment_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function databaseUpdates(): array
{
    return [
      '2026-07-site-settings'=>['Site settings storage','Creates the PHP-native settings table used by promotions, email signup controls, and custom ads.', fn(PDO $db)=>$db->exec("CREATE TABLE IF NOT EXISTS wts_settings (setting_key VARCHAR(190) PRIMARY KEY,setting_value LONGTEXT NULL,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")],
      '2026-07-writer-metadata'=>['Writer metadata','Adds tags, cover images, SEO fields, and featured status to the PHP-native posts table.', fn(PDO $db)=>writerMetadataColumns($db)],
      '2026-07-custom-tags'=>['Custom tags','Creates the reusable tag library used by the blog editor.', fn(PDO $db)=>$db->exec("CREATE TABLE IF NOT EXISTS wts_tags (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80) NOT NULL UNIQUE,slug VARCHAR(100) NOT NULL UNIQUE,description VARCHAR(255) NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")],
      '2026-07-seo-studio'=>['SEO studio','Creates storage for SEO titles, descriptions, and focus keywords for public pages.', fn(PDO $db)=>$db->exec("CREATE TABLE IF NOT EXISTS wts_seo_pages (page_key VARCHAR(80) PRIMARY KEY,meta_title VARCHAR(500) NULL,meta_description TEXT NULL,focus_keyword VARCHAR(160) NULL,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")],
      '2026-07-first-party-analytics'=>['First-party analytics','Creates the privacy-conscious page-view table. It stores no IP addresses.', fn(PDO $db)=>$db->exec("CREATE TABLE IF NOT EXISTS wts_page_views (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,viewed_on DATE NOT NULL,visitor_hash CHAR(64) NOT NULL,page_path VARCHAR(500) NOT NULL,referrer_host VARCHAR(190) NULL,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX idx_wts_views_day (viewed_on),INDEX idx_wts_views_path_day (page_path(190),viewed_on)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")],
      '2026-07-subscriber-sources'=>['Subscriber signup sources','Records which signup placement each subscriber used on the PHP-native subscriber table.', fn(PDO $db)=>subscriberSourceColumn($db)],
      '2026-07-threaded-comments'=>['Threaded comments and likes','Adds reader replies, anonymous likes, moderation metadata, and rate-limit support to blog comments.', fn(PDO $db)=>threadedCommentUpgrade($db)],
      '2026-08-reflection-audio'=>['Reflection audio','Adds an optional narrated audio URL to each reflection.', fn(PDO $db)=>reflectionAudioColumn($db)],
      '2026-08-reader-engagement'=>['Reader engagement analytics','Tracks aggregate scroll depth and completion for reflections without storing IP addresses.', fn(PDO $db)=>$db->exec("CREATE TABLE IF NOT EXISTS wts_post_engagement (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,viewed_on DATE NOT NULL,visitor_hash CHAR(64) NOT NULL,post_slug VARCHAR(190) NOT NULL,max_scroll TINYINT UNSIGNED NOT NULL DEFAULT 0,active_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 0,completed TINYINT(1) NOT NULL DEFAULT 0,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_wts_post_engagement_day (viewed_on,visitor_hash,post_slug),INDEX idx_wts_post_engagement_slug_day (post_slug,viewed_on)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")],
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
