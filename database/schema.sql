-- Safe to run alongside other applications in the same MySQL database.
-- All Word Truth Spirit tables use the wts_ prefix.
CREATE TABLE IF NOT EXISTS wts_posts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'general',
  excerpt TEXT NULL,
  body LONGTEXT NOT NULL,
  author VARCHAR(120) NOT NULL DEFAULT 'Patrick E. Pennington',
  tags TEXT NULL,
  cover_image VARCHAR(2048) NULL,
  audio_url VARCHAR(2048) NULL,
  meta_title VARCHAR(500) NULL,
  meta_description TEXT NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  comments_enabled TINYINT(1) NOT NULL DEFAULT 1,
  reading_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 5,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_wts_posts_status_date (status, published_at),
  INDEX idx_wts_posts_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_seo_pages (
  page_key VARCHAR(80) PRIMARY KEY,
  meta_title VARCHAR(500) NULL,
  meta_description TEXT NULL,
  focus_keyword VARCHAR(160) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_page_views (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  viewed_on DATE NOT NULL,
  visitor_hash CHAR(64) NOT NULL,
  page_path VARCHAR(500) NOT NULL,
  referrer_host VARCHAR(190) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_wts_views_day (viewed_on),
  INDEX idx_wts_views_path_day (page_path(190), viewed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_post_engagement (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  viewed_on DATE NOT NULL,
  visitor_hash CHAR(64) NOT NULL,
  post_slug VARCHAR(190) NOT NULL,
  max_scroll TINYINT UNSIGNED NOT NULL DEFAULT 0,
  active_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  completed TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wts_post_engagement_day (viewed_on, visitor_hash, post_slug),
  INDEX idx_wts_post_engagement_slug_day (post_slug, viewed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_contact_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  subject VARCHAR(160) NOT NULL DEFAULT 'General Inquiry',
  message TEXT NOT NULL,
  ip_address VARCHAR(45) NULL,
  status ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_wts_contact_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_subscribers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  status ENUM('pending','active','unsubscribed') NOT NULL DEFAULT 'pending',
  token CHAR(64) NULL,
  source VARCHAR(80) NOT NULL DEFAULT 'website',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_settings (
  setting_key VARCHAR(190) PRIMARY KEY,
  setting_value LONGTEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_push_subscriptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  endpoint TEXT NOT NULL,
  endpoint_hash CHAR(64) NOT NULL UNIQUE,
  p256dh VARCHAR(255) NOT NULL,
  auth VARCHAR(255) NOT NULL,
  user_agent VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_wts_push_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_blog_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_slug VARCHAR(190) NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('pending','approved','spam','trash') NOT NULL DEFAULT 'pending',
  author_hash CHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_wts_comments_post (post_slug,status,created_at),
  INDEX idx_wts_comments_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_comment_reactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  comment_id BIGINT UNSIGNED NOT NULL,
  voter_hash CHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wts_comment_reaction (comment_id,voter_hash),
  INDEX idx_wts_comment_reactions_comment (comment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_comment_reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  comment_id BIGINT UNSIGNED NOT NULL,
  reporter_hash CHAR(64) NOT NULL,
  reason VARCHAR(40) NOT NULL DEFAULT 'other',
  details VARCHAR(500) NULL,
  status ENUM('open','reviewed','dismissed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wts_comment_reporter (comment_id,reporter_hash),
  INDEX idx_wts_comment_reports_status (status,created_at),
  INDEX idx_wts_comment_reports_comment (comment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_blocked_commenters (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email_hash CHAR(64) NULL,
  author_hash CHAR(64) NULL,
  reason VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wts_blocked_email (email_hash),
  UNIQUE KEY uq_wts_blocked_author (author_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_books (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  subtitle VARCHAR(255) NULL,
  description TEXT NULL,
  cover_image VARCHAR(500) NULL,
  purchase_url VARCHAR(1000) NULL,
  format_details VARCHAR(255) NULL,
  published_year SMALLINT UNSIGNED NULL,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_wts_books_status_order (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wts_admin_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO wts_books
  (slug,title,subtitle,description,cover_image,purchase_url,format_details,published_year,display_order,status)
VALUES
  ('the-spirit-of-truth','The Spirit of Truth','A Biblical Defense of Traditional Pentecostalism','A clear, Scripture-grounded case for the continuing work of the Holy Spirit without surrendering biblical order or discernment.','assets/images/book-cover.png','https://www.amazon.com/dp/B0GBVXPHVF','Paperback and eBook',2026,1,'published')
ON DUPLICATE KEY UPDATE title=VALUES(title);
