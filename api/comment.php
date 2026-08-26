<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/comments.php';
require ROOT_PATH . '/includes/posts.php';

$slug = trim((string) ($_POST['slug'] ?? ''));
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$body = trim((string) ($_POST['body'] ?? ''));
$parentId = max(0, (int) ($_POST['parent_id'] ?? 0));
$token = (string) ($_POST['comment_token'] ?? '');
$return = static function (string $status) use ($slug): never {
    header('Location:' . url('blog/post.php?slug=' . urlencode($slug) . '&comment=' . urlencode($status) . '#comments'));
    exit;
};

if (
    ($_POST['website'] ?? '') !== ''
    || !verifyCommentInteractionToken($token)
    || !$slug
    || !findPost($slug)
    || !$name
    || mb_strlen($name) > 120
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || mb_strlen($body) < 3
    || mb_strlen($body) > 4000
    || !commentParentIsValid($parentId, $slug)
) $return('error');

$authorHash = commentVisitorHash();
if (commentRateLimited($authorHash)) $return('rate');
if (commenterIsBlocked($email, $authorHash)) $return('blocked');

if (ensureCommentsTable()) {
    try {
        $post = findPost($slug);
        if (!$post || !commentsEnabledForPost($post)) $return('closed');
        $duplicate = database()->prepare('SELECT COUNT(*) FROM wts_blog_comments WHERE post_slug=? AND author_hash=? AND body=? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
        $duplicate->execute([$slug, $authorHash, $body]);
        if ($duplicate->fetchColumn()) $return('duplicate');
        $status = commentLooksLikeSpam($name, $body) ? 'spam' : 'pending';
        database()->prepare('INSERT INTO wts_blog_comments (post_slug,parent_id,name,email,body,status,author_hash) VALUES (?,?,?,?,?,?,?)')
            ->execute([$slug, $parentId ?: null, $name, $email, $body, $status, $authorHash]);
        $return($status === 'spam' ? 'pending' : ($parentId ? 'reply-pending' : 'pending'));
    } catch (PDOException $exception) {
        error_log('Comment submission failed: ' . $exception->getMessage());
    }
}
$return('error');
