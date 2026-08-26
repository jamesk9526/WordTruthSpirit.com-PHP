-- Run this after importing 127_0_0_1.sql.
-- The original Node-site tables remain unchanged. This adds only the new
-- Publications catalog required by the PHP site.
USE `wts`;

CREATE TABLE IF NOT EXISTS `wts_books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_url` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `format_details` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_year` smallint unsigned DEFAULT NULL,
  `display_order` smallint unsigned NOT NULL DEFAULT '10',
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_wts_books_status_order` (`status`,`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wts_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wts_tags_name` (`name`),
  UNIQUE KEY `wts_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wts_seo_pages` (
  `page_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `focus_keyword` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`page_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wts_page_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `viewed_on` date NOT NULL,
  `visitor_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referrer_host` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wts_views_day` (`viewed_on`),
  KEY `idx_wts_views_path_day` (`page_path`(190),`viewed_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wts_post_engagement` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `viewed_on` date NOT NULL,
  `visitor_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_slug` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_scroll` tinyint unsigned NOT NULL DEFAULT '0',
  `active_seconds` smallint unsigned NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wts_post_engagement_day` (`viewed_on`,`visitor_hash`,`post_slug`),
  KEY `idx_wts_post_engagement_slug_day` (`post_slug`,`viewed_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wts_books`
  (`slug`,`title`,`subtitle`,`description`,`cover_image`,`purchase_url`,`format_details`,`published_year`,`display_order`,`status`)
VALUES
  ('the-spirit-of-truth','The Spirit of Truth','A Biblical Defense of Traditional Pentecostalism','A clear, Scripture-grounded case for the continuing work of the Holy Spirit without surrendering biblical order or discernment.','assets/images/book-cover.png','https://www.amazon.com/dp/B0GBVXPHVF','Paperback and eBook',2026,1,'published')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);
