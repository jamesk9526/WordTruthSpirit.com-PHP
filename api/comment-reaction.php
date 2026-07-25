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
if (!$commentId || !verifyCommentInteractionToken($token) || !ensureCommentsTable()) {
    http_response_code(422);
    echo json_encode(['ok'=>false, 'message'=>'Unable to update this reaction.']);
    exit;
}

$db = database();
try {
    $comment = $db->prepare("SELECT id FROM wts_blog_comments WHERE id=? AND status='approved'");
    $comment->execute([$commentId]);
    if (!$comment->fetchColumn()) throw new RuntimeException('Comment not available.');
    $voterHash = commentVisitorHash();
    $existing = $db->prepare('SELECT id FROM wts_comment_reactions WHERE comment_id=? AND voter_hash=?');
    $existing->execute([$commentId, $voterHash]);
    $reactionId = (int) $existing->fetchColumn();
    if ($reactionId) {
        $db->prepare('DELETE FROM wts_comment_reactions WHERE id=?')->execute([$reactionId]);
        $liked = false;
    } else {
        $db->prepare('INSERT INTO wts_comment_reactions (comment_id,voter_hash) VALUES (?,?)')->execute([$commentId, $voterHash]);
        $liked = true;
    }
    $state = commentReactionState($commentId);
    echo json_encode(['ok'=>true, 'liked'=>$liked, 'count'=>$state['count']]);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode(['ok'=>false, 'message'=>'Unable to update this reaction.']);
}
