<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/comments.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false, 'message'=>'Method not allowed']);
    exit;
}

$commentId = max(0, (int) ($_POST['comment_id'] ?? 0));
$token = (string) ($_POST['comment_token'] ?? '');
$reason = in_array($_POST['reason'] ?? '', ['spam','abuse','misinformation','other'], true) ? (string) $_POST['reason'] : 'other';
$details = mb_substr(trim((string) ($_POST['details'] ?? '')), 0, 500);
if (!$commentId || !verifyCommentInteractionToken($token) || !ensureCommentsTable()) {
    http_response_code(422);
    echo json_encode(['ok'=>false, 'message'=>'Unable to submit this report.']);
    exit;
}

try {
    $db = database();
    $comment = $db->prepare("SELECT id FROM wts_blog_comments WHERE id=? AND status='approved'");
    $comment->execute([$commentId]);
    if (!$comment->fetchColumn()) throw new RuntimeException('Comment not available.');
    $statement = $db->prepare("INSERT INTO wts_comment_reports (comment_id,reporter_hash,reason,details) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE reason=VALUES(reason),details=VALUES(details),status='open',created_at=CURRENT_TIMESTAMP");
    $statement->execute([$commentId, commentVisitorHash(), $reason, $details ?: null]);
    echo json_encode(['ok'=>true, 'message'=>'Report received. Thank you.']);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode(['ok'=>false, 'message'=>'Unable to submit this report.']);
}
