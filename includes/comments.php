<?php
declare(strict_types=1);

require_once ROOT_PATH . '/config/database.php';

function ensureCommentsTable(): bool
{
    $db = database();
    if (!$db) return false;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS wts_blog_comments (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $columns = $db->query('SHOW COLUMNS FROM wts_blog_comments')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('parent_id', $columns, true)) $db->exec('ALTER TABLE wts_blog_comments ADD COLUMN parent_id BIGINT UNSIGNED NULL AFTER post_slug, ADD INDEX idx_wts_comments_parent (parent_id)');
        if (!in_array('author_hash', $columns, true)) $db->exec('ALTER TABLE wts_blog_comments ADD COLUMN author_hash CHAR(64) NULL AFTER status');
        if (!in_array('updated_at', $columns, true)) $db->exec('ALTER TABLE wts_blog_comments ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at');
        $db->exec("CREATE TABLE IF NOT EXISTS wts_comment_reactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            comment_id BIGINT UNSIGNED NOT NULL,
            voter_hash CHAR(64) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_wts_comment_reaction (comment_id,voter_hash),
            INDEX idx_wts_comment_reactions_comment (comment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS wts_comment_reports (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS wts_blocked_commenters (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email_hash CHAR(64) NULL,
            author_hash CHAR(64) NULL,
            reason VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_wts_blocked_email (email_hash),
            UNIQUE KEY uq_wts_blocked_author (author_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return true;
    } catch (PDOException $exception) {
        error_log('Comment storage setup failed: ' . $exception->getMessage());
        return false;
    }
}

function commentVisitorHash(): string
{
    $address = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $agent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    $secret = (string) (getenv('APP_KEY') ?: getenv('DB_NAME') ?: 'word-truth-spirit');
    return hash_hmac('sha256', $address . '|' . $agent, $secret);
}

function ensureCommentSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) return;
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')]);
    session_start();
}

function commentInteractionToken(): string
{
    ensureCommentSession();
    if (empty($_SESSION['wts_comment_token'])) $_SESSION['wts_comment_token'] = bin2hex(random_bytes(24));
    return (string) $_SESSION['wts_comment_token'];
}

function verifyCommentInteractionToken(string $token): bool
{
    ensureCommentSession();
    return !empty($_SESSION['wts_comment_token']) && hash_equals((string) $_SESSION['wts_comment_token'], $token);
}

function approvedComments(string $slug): array
{
    $db = database();
    if (!$db || !ensureCommentsTable()) return [];
    try {
        $memberJoin=databaseTableExists('wts_members') ? ' LEFT JOIN wts_members m ON m.id=c.member_id AND m.email_confirmed_at IS NOT NULL ' : '';
        $memberSelect=databaseTableExists('wts_members') ? ',m.profile_slug' : ',NULL AS profile_slug';
        $memberGroup=databaseTableExists('wts_members') ? ',m.profile_slug' : '';
        $statement = $db->prepare("SELECT c.id,c.parent_id,c.name,c.body,c.created_at,c.updated_at{$memberSelect},COUNT(r.id) AS likes_count,
            COALESCE(MAX(r.voter_hash=?),0) AS viewer_liked
            FROM wts_blog_comments c
            {$memberJoin}
            LEFT JOIN wts_comment_reactions r ON r.comment_id=c.id
            WHERE c.post_slug=? AND c.status='approved'
            GROUP BY c.id,c.parent_id,c.name,c.body,c.created_at,c.updated_at{$memberGroup}
            ORDER BY c.created_at ASC");
        $statement->execute([commentVisitorHash(), $slug]);
        return $statement->fetchAll();
    } catch (PDOException $exception) {
        return [];
    }
}

function threadedComments(array $comments): array
{
    $byParent = [];
    foreach ($comments as $comment) $byParent[(int) ($comment['parent_id'] ?? 0)][] = $comment;
    $build = function (int $parentId, int $depth = 0) use (&$build, &$byParent): array {
        $threads = [];
        foreach ($byParent[$parentId] ?? [] as $comment) {
            $comment['depth'] = $depth;
            $comment['replies'] = $depth < 3 ? $build((int) $comment['id'], $depth + 1) : [];
            $threads[] = $comment;
        }
        return $threads;
    };
    return $build(0);
}

function commentParentIsValid(int $parentId, string $slug): bool
{
    if ($parentId < 1) return true;
    $db = database();
    if (!$db || !ensureCommentsTable()) return false;
    $statement = $db->prepare("SELECT COUNT(*) FROM wts_blog_comments WHERE id=? AND post_slug=? AND status='approved'");
    $statement->execute([$parentId, $slug]);
    return (bool) $statement->fetchColumn();
}

function commentRateLimited(string $authorHash): bool
{
    $db = database();
    if (!$db || !ensureCommentsTable()) return true;
    $statement = $db->prepare('SELECT COUNT(*) FROM wts_blog_comments WHERE author_hash=? AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $statement->execute([$authorHash]);
    return (int) $statement->fetchColumn() >= 5;
}

function commentEmailHash(string $email): string
{
    $secret = (string) (getenv('APP_KEY') ?: getenv('DB_NAME') ?: 'word-truth-spirit');
    return hash_hmac('sha256', mb_strtolower(trim($email)), $secret);
}

function commenterIsBlocked(string $email, string $authorHash): bool
{
    $db = database();
    if (!$db || !ensureCommentsTable()) return true;
    $statement = $db->prepare('SELECT COUNT(*) FROM wts_blocked_commenters WHERE email_hash=? OR author_hash=?');
    $statement->execute([commentEmailHash($email), $authorHash]);
    return (bool) $statement->fetchColumn();
}

function commentLooksLikeSpam(string $name, string $body): bool
{
    $combined = $name . ' ' . $body;
    $linkCount = preg_match_all('#https?://|www\.#i', $combined);
    $repeatRun = preg_match('/(.)\1{12,}/u', $body);
    return $linkCount > 3 || (bool) $repeatRun;
}

function commentsEnabledForPost(array $post): bool
{
    return !array_key_exists('comments_enabled', $post) || (bool) $post['comments_enabled'];
}

function commentReactionState(int $commentId): array
{
    $db = database();
    if (!$db || !ensureCommentsTable()) return ['count'=>0, 'liked'=>false];
    $count = $db->prepare('SELECT COUNT(*) FROM wts_comment_reactions WHERE comment_id=?');
    $count->execute([$commentId]);
    $liked = $db->prepare('SELECT COUNT(*) FROM wts_comment_reactions WHERE comment_id=? AND voter_hash=?');
    $liked->execute([$commentId, commentVisitorHash()]);
    return ['count'=>(int) $count->fetchColumn(), 'liked'=>(bool) $liked->fetchColumn()];
}

function commentModerationCounts(): array
{
    $counts = ['all'=>0, 'pending'=>0, 'approved'=>0, 'spam'=>0, 'reported'=>0];
    $db = database();
    if (!$db || !ensureCommentsTable()) return $counts;
    foreach ($db->query("SELECT status,COUNT(*) AS total FROM wts_blog_comments WHERE status<>'trash' GROUP BY status")->fetchAll() as $row) {
        $counts[$row['status']] = (int) $row['total'];
        $counts['all'] += (int) $row['total'];
    }
    $counts['reported'] = (int) $db->query("SELECT COUNT(DISTINCT comment_id) FROM wts_comment_reports WHERE status='open'")->fetchColumn();
    return $counts;
}
