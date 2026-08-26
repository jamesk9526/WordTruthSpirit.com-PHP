<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
requireAdmin();
require ROOT_PATH . '/includes/comments.php';
ensureCommentsTable();
$db = database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    verifyCsrf();
    $quickAction = (string) ($_POST['quick_action'] ?? '');
    if (preg_match('/^(approved|spam|trash|block|reply|dismiss):(\d+)$/', $quickAction, $match)) {
        $operation = $match[1];
        $targetId = (int) $match[2];
        if ($operation === 'reply') {
            $replyBody = trim((string) ($_POST['reply_body'] ?? ''));
            $parent = $db->prepare('SELECT post_slug FROM wts_blog_comments WHERE id=?');
            $parent->execute([$targetId]);
            $postSlug = (string) $parent->fetchColumn();
            if ($postSlug && $replyBody !== '' && mb_strlen($replyBody) <= 4000) {
                $db->prepare("INSERT INTO wts_blog_comments (post_slug,parent_id,name,email,body,status,author_hash) VALUES (?,?,?,?,?,'approved',?)")
                    ->execute([$postSlug, $targetId, (string) ($_SESSION['wts_admin_name'] ?? 'Word Truth Spirit'), 'admin@wordtruthspirit.local', $replyBody, hash('sha256', 'wts-admin')]);
                $db->prepare("UPDATE wts_blog_comments SET status='approved',updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$targetId]);
            }
        } elseif ($operation === 'block') {
            $comment = $db->prepare('SELECT email,author_hash FROM wts_blog_comments WHERE id=?');
            $comment->execute([$targetId]);
            if ($row = $comment->fetch()) {
                $db->prepare('INSERT IGNORE INTO wts_blocked_commenters (email_hash,author_hash,reason) VALUES (?,?,?)')
                    ->execute([commentEmailHash((string) $row['email']), $row['author_hash'] ?: null, 'Blocked during comment moderation']);
                $db->prepare("UPDATE wts_blog_comments SET status='spam',updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$targetId]);
            }
        } elseif ($operation === 'dismiss') {
            $db->prepare("UPDATE wts_comment_reports SET status='dismissed' WHERE comment_id=?")->execute([$targetId]);
        } else {
            $_POST['status'] = $operation;
            $_POST['ids'] = [$targetId];
        }
    }
    $status = in_array($_POST['status'] ?? '', ['pending','approved','spam','trash'], true) ? (string) $_POST['status'] : 'pending';
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
    if (!$ids && !empty($_POST['id'])) $ids = [(int) $_POST['id']];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("UPDATE wts_blog_comments SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id IN ({$placeholders})")->execute([$status, ...$ids]);
        $db->prepare("UPDATE wts_comment_reports SET status='reviewed' WHERE comment_id IN ({$placeholders})")->execute($ids);
    }
    header('Location:' . url('admin/comments.php?status=' . urlencode((string) ($_POST['return_status'] ?? 'pending')) . '&updated=1'));
    exit;
}

$filter = in_array($_GET['status'] ?? '', ['all','pending','approved','spam','reported'], true) ? (string) $_GET['status'] : 'pending';
$search = trim((string) ($_GET['q'] ?? ''));
$counts = commentModerationCounts();
$comments = [];
if ($db) {
    $where = ["c.status<>'trash'"];
    $params = [];
    if ($filter === 'reported') $where[] = "EXISTS (SELECT 1 FROM wts_comment_reports rx WHERE rx.comment_id=c.id AND rx.status='open')";
    elseif ($filter !== 'all') { $where[] = 'c.status=?'; $params[] = $filter; }
    if ($search !== '') {
        $where[] = '(c.name LIKE ? OR c.email LIKE ? OR c.post_slug LIKE ? OR c.body LIKE ?)';
        $needle = '%' . $search . '%';
        array_push($params, $needle, $needle, $needle, $needle);
    }
    $statement = $db->prepare("SELECT c.*,p.name AS parent_name,COUNT(DISTINCT r.id) AS likes_count,
        COUNT(DISTINCT CASE WHEN reports.status='open' THEN reports.id END) AS report_count,
        GROUP_CONCAT(DISTINCT CASE WHEN reports.status='open' THEN reports.reason END ORDER BY reports.reason SEPARATOR ', ') AS report_reasons
        FROM wts_blog_comments c
        LEFT JOIN wts_blog_comments p ON p.id=c.parent_id
        LEFT JOIN wts_comment_reactions r ON r.comment_id=c.id
        LEFT JOIN wts_comment_reports reports ON reports.comment_id=c.id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY c.id,p.name
        ORDER BY c.status='pending' DESC,c.created_at DESC
        LIMIT 250");
    $statement->execute($params);
    $comments = $statement->fetchAll();
}

$adminTitle = 'Comment moderation';
$currentAdminPage = 'comments';
require __DIR__ . '/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Reader discussion</p><h1>Comment moderation</h1><p>Review new responses, follow reply context, and manage the health of the blog conversation.</p></div></header>
<?php if(isset($_GET['updated'])):?><p class="notice success">Comment moderation updated.</p><?php endif;?>
<section class="comment-moderation-toolbar">
  <nav class="moderation-tabs" aria-label="Comment status"><?php foreach(['pending'=>'Needs review','reported'=>'Reported','approved'=>'Published','spam'=>'Spam','all'=>'All'] as $key=>$label):?><a class="<?=$filter===$key?'active':''?>" href="<?=url('admin/comments.php?status='.$key)?>"><span><?=$counts[$key]??0?></span><?=$label?></a><?php endforeach;?></nav>
  <form class="moderation-search" method="get"><input type="hidden" name="status" value="<?=e($filter)?>"><label>Search comments<input type="search" name="q" value="<?=e($search)?>" placeholder="Name, email, article, or text"></label><button class="button button-outline">Search</button></form>
</section>
<form method="post" class="admin-panel comment-admin-list" data-comment-moderation>
  <input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="return_status" value="<?=e($filter)?>">
  <?php if($comments):?><div class="bulk-comment-actions"><label><input type="checkbox" data-select-comments> Select all</label><span data-selected-comments>0 selected</span><button name="status" value="approved">Approve</button><button name="status" value="spam">Mark spam</button><button name="status" value="trash">Delete</button></div><?php endif;?>
  <?php foreach($comments as $comment):?><article class="comment-admin-card">
    <label class="comment-select"><input type="checkbox" name="ids[]" value="<?=(int)$comment['id']?>" data-comment-checkbox><span class="sr-only">Select comment by <?=e((string)$comment['name'])?></span></label>
    <div class="comment-admin-content"><header><div class="comment-author-mark"><?=e(mb_strtoupper(mb_substr((string)$comment['name'],0,1)))?></div><div><strong><?=e((string)$comment['name'])?></strong><span><?=e((string)$comment['email'])?></span></div><em class="status-pill status-<?=e((string)$comment['status'])?>"><?=e((string)$comment['status'])?></em></header>
      <p><?=nl2br(e((string)$comment['body']))?></p>
      <?php if((int)$comment['report_count']>0):?><aside class="comment-report-alert"><strong><?=(int)$comment['report_count']?> reader report<?=(int)$comment['report_count']===1?'':'s'?></strong><span><?=e((string)$comment['report_reasons'])?></span></aside><?php endif;?>
      <footer><span><strong>Article:</strong> <?=e((string)$comment['post_slug'])?></span><?php if($comment['parent_id']):?><span><strong>Reply to:</strong> <?=e((string)($comment['parent_name']??'comment'))?></span><?php endif;?><span>♥ <?=(int)$comment['likes_count']?> · <?=date('M j, Y g:i a',strtotime((string)$comment['created_at']))?></span><a href="<?=url('blog/post.php?slug='.urlencode((string)$comment['post_slug']).'#comment-'.(int)$comment['id'])?>" target="_blank" rel="noopener">Open discussion ↗</a></footer>
      <details class="comment-admin-reply"><summary>Write a public reply</summary><div><textarea name="reply_body" rows="3" maxlength="4000" placeholder="Reply as Word Truth Spirit"></textarea><button name="quick_action" value="reply:<?=(int)$comment['id']?>">Publish reply</button></div></details>
    </div>
    <div class="comment-quick-actions"><?php if($comment['status']!=='approved'):?><button name="quick_action" value="approved:<?=(int)$comment['id']?>">Approve</button><?php endif;?><?php if((int)$comment['report_count']>0):?><button name="quick_action" value="dismiss:<?=(int)$comment['id']?>">Dismiss report</button><?php endif;?><button name="quick_action" value="spam:<?=(int)$comment['id']?>">Spam</button><button name="quick_action" value="block:<?=(int)$comment['id']?>" onclick="return confirm('Block this commenter and mark the comment as spam?')">Block</button><button name="quick_action" value="trash:<?=(int)$comment['id']?>">Delete</button></div>
  </article><?php endforeach;?>
  <?php if(!$comments):?><div class="empty-state"><span>✓</span><h2>No comments here</h2><p><?= $filter==='pending'?'The moderation queue is clear.':'Try another status or search.'?></p></div><?php endif;?>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
