<?php
declare(strict_types=1);
require_once ROOT_PATH . '/config/database.php';

function ensureAnalyticsTable(): bool
{
    $db = database(); if (!$db) return false;
    try { $db->exec("CREATE TABLE IF NOT EXISTS wts_page_views (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, viewed_on DATE NOT NULL, visitor_hash CHAR(64) NOT NULL, page_path VARCHAR(500) NOT NULL, referrer_host VARCHAR(190) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_wts_views_day (viewed_on), INDEX idx_wts_views_path_day (page_path(190),viewed_on)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); $db->exec("CREATE TABLE IF NOT EXISTS wts_post_engagement (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,viewed_on DATE NOT NULL,visitor_hash CHAR(64) NOT NULL,post_slug VARCHAR(190) NOT NULL,max_scroll TINYINT UNSIGNED NOT NULL DEFAULT 0,active_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 0,completed TINYINT(1) NOT NULL DEFAULT 0,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_wts_post_engagement_day (viewed_on,visitor_hash,post_slug),INDEX idx_wts_post_engagement_slug_day (post_slug,viewed_on)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); return true; }
    catch (PDOException $exception) { error_log('Analytics setup failed: ' . $exception->getMessage()); return false; }
}

function recordPublicPageView(): void
{
    if (PHP_SAPI === 'cli' || str_starts_with($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') || str_starts_with($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) return;
    $db = database(); if (!$db || !ensureAnalyticsTable()) return;
    try {
        $visitor = analyticsVisitorId();
        $path = substr((string) ($_SERVER['REQUEST_URI'] ?? '/'), 0, 500);
        $referrer = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST) ?: null;
        $db->prepare('INSERT INTO wts_page_views (viewed_on,visitor_hash,page_path,referrer_host) VALUES (CURDATE(),?,?,?)')->execute([hash('sha256', $visitor . date('Y-m-d')), $path, $referrer]);
    } catch (Throwable $exception) { error_log('Analytics event failed: ' . $exception->getMessage()); }
}

function analyticsVisitorId(): string
{
    $visitor = $_COOKIE['wts_visitor'] ?? bin2hex(random_bytes(16));
    if (!isset($_COOKIE['wts_visitor'])) setcookie('wts_visitor', $visitor, ['expires'=>time()+31536000,'path'=>'/','secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),'httponly'=>true,'samesite'=>'Lax']);
    return $visitor;
}

function recordPostEngagement(string $slug, int $scroll, int $activeSeconds, bool $completed): bool
{
    $db = database();
    if (!$db || !ensureAnalyticsTable() || !preg_match('/^[a-z0-9-]{1,190}$/', $slug)) return false;
    try {
        $visitorHash = hash('sha256', analyticsVisitorId() . date('Y-m-d'));
        $db->prepare('INSERT INTO wts_post_engagement (viewed_on,visitor_hash,post_slug,max_scroll,active_seconds,completed) VALUES (CURDATE(),?,?,?,?,?) ON DUPLICATE KEY UPDATE max_scroll=GREATEST(max_scroll,VALUES(max_scroll)),active_seconds=GREATEST(active_seconds,VALUES(active_seconds)),completed=GREATEST(completed,VALUES(completed))')->execute([$visitorHash, $slug, max(0,min(100,$scroll)), max(0,min(14400,$activeSeconds)), $completed ? 1 : 0]);
        return true;
    } catch (PDOException $exception) { error_log('Post engagement event failed: ' . $exception->getMessage()); return false; }
}

function analyticsSummary(int $days = 30): array
{
    $db = database(); $empty = ['views'=>0,'visitors'=>0,'topPages'=>[],'referrers'=>[],'daily'=>[],'engagedReaders'=>0,'averageScroll'=>0,'completionRate'=>0,'topPosts'=>[]];
    if (!$db || !ensureAnalyticsTable()) return $empty;
    try {
        $start = date('Y-m-d', strtotime('-' . max(1, $days - 1) . ' days'));
        $summary = $db->prepare('SELECT COUNT(*) views,COUNT(DISTINCT visitor_hash) visitors FROM wts_page_views WHERE viewed_on>=?'); $summary->execute([$start]); $stats = $summary->fetch() ?: [];
        $pages = $db->prepare('SELECT page_path,COUNT(*) total FROM wts_page_views WHERE viewed_on>=? GROUP BY page_path ORDER BY total DESC LIMIT 8'); $pages->execute([$start]);
        $refs = $db->prepare("SELECT referrer_host,COUNT(*) total FROM wts_page_views WHERE viewed_on>=? AND referrer_host IS NOT NULL AND referrer_host<>'' GROUP BY referrer_host ORDER BY total DESC LIMIT 6"); $refs->execute([$start]);
        $daily = $db->prepare('SELECT viewed_on,COUNT(*) total FROM wts_page_views WHERE viewed_on>=? GROUP BY viewed_on ORDER BY viewed_on'); $daily->execute([$start]);
        $engagement = $db->prepare('SELECT COUNT(*) readers,COALESCE(ROUND(AVG(max_scroll)),0) average_scroll,COALESCE(ROUND(100 * AVG(completed)),0) completion_rate FROM wts_post_engagement WHERE viewed_on>=?'); $engagement->execute([$start]); $engagementStats = $engagement->fetch() ?: [];
        $posts = $db->prepare('SELECT post_slug,COUNT(*) readers,ROUND(AVG(max_scroll)) average_scroll,ROUND(100*AVG(completed)) completion_rate,ROUND(AVG(active_seconds)) average_seconds FROM wts_post_engagement WHERE viewed_on>=? GROUP BY post_slug ORDER BY readers DESC,average_scroll DESC LIMIT 8'); $posts->execute([$start]);
        return ['views'=>(int)($stats['views']??0),'visitors'=>(int)($stats['visitors']??0),'topPages'=>$pages->fetchAll(),'referrers'=>$refs->fetchAll(),'daily'=>$daily->fetchAll(),'engagedReaders'=>(int)($engagementStats['readers']??0),'averageScroll'=>(int)($engagementStats['average_scroll']??0),'completionRate'=>(int)($engagementStats['completion_rate']??0),'topPosts'=>$posts->fetchAll()];
    } catch (PDOException $exception) { return $empty; }
}
