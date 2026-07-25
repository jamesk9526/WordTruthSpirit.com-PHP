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
    if (preg_match('/^(approved|spam|trash):(\d+)$/', $quickAction, $match)) {
        $_POST['status'] = $match[1];
        $_POST['ids'] = [(int) $match[2]];
    }
    $status = in_array($_POST['status'] ?? '', ['pending','approved','spam','trash'], true) ? (string) $_POST['status'] : 'pending';
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
    if (!$ids && !empty($_POST['id'])) $ids = [(int) $_POST['id']];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("UPDATE wts_blog_comments SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id IN ({$placeholders})")->execute([$status, ...$ids]);
    }
    header('Location:' . url('admin/comments.php?status=' . urlencode((string) ($_POST['return_status'] ?? 'pending')) . '&updated=1'));
    exit;
}

$filter = in_array($_GET['status'] ?? '', ['all','pending','approved','spam'], true) ? (string) $_GET['status'] : 'pending';
$search = trim((string) ($_GET['q'] ?? ''));
$counts = commentModerationCounts();
$comments = [];
if ($db) {
    $where = ["c.status<>'trash'"];
    $params = [];
    if ($filter !== 'all') { $where[] = 'c.status=?'; $params[] = $filter; }
    if ($search !== '') {
        $where[] = '(c.name LIKE ? OR c.email LIKE ? OR c.post_slug LIKE ? OR c.body LIKE ?)';
        $needle = '%' . $search . '%';
        array_push($params, $needle, $needle, $needle, $needle);
    }
    $statement = $db->prepare("SELECT c.*,p.name AS parent_name,COUNT(r.id) AS likes_count
        FROM wts_blog_comments c
        LEFT JOIN wts_blog_comments p ON p.id=c.parent_id
        LEFT JOIN wts_comment_reactions r ON r.comment_id=c.id
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
<header class="admin-title"><div><p class="kicker">Reader discussion</p><h1>Comment moderation</h1><p>Review new responses, follow reply context, and manage the health of the journal conversation.</p></div></header>
<?php if(isset($_GET['updated'])):?><p class="notice success">Comment moderation updated.</p><?php endif;?>
<section class="comment-moderation-toolbar">
  <nav class="moderation-tabs" aria-label="Comment status"><?php foreach(['pending'=>'Needs review','approved'=>'Published','spam'=>'Spam','all'=>'All'] as $key=>$label):?><a class="<?=$filter===$key?'active':''?>" href="<?=url('admin/comments.php?status='.$key)?>"><span><?=$counts[$key]??0?></span><?=$label?></a><?php endforeach;?></nav>
  <form class="moderation-search" method="get"><input type="hidden" name="status" value="<?=e($filter)?>"><label>Search comments<input type="search" name="q" value="<?=e($search)?>" placeholder="Name, email, article, or text"></label><button class="button button-outline">Search</button></form>
</section>
<form method="post" class="admin-panel comment-admin-list" data-comment-moderation>
  <input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="return_status" value="<?=e($filter)?>">
  <?php if($comments):?><div class="bulk-comment-actions"><label><input type="checkbox" data-select-comments> Select all</label><span data-selected-comments>0 selected</span><button name="status" value="approved">Approve</button><button name="status" value="spam">Mark spam</button><button name="status" value="trash">Delete</button></div><?php endif;?>
  <?php foreach($comments as $comment):?><article class="comment-admin-card">
    <label class="comment-select"><input type="checkbox" name="ids[]" value="<?=(int)$comment['id']?>" data-comment-checkbox><span class="sr-only">Select comment by <?=e((string)$comment['name'])?></span></label>
    <div class="comment-admin-content"><header><div class="comment-author-mark"><?=e(mb_strtoupper(mb_substr((string)$comment['name'],0,1)))?></div><div><strong><?=e((string)$comment['name'])?></strong><span><?=e((string)$comment['email'])?></span></div><em class="status-pill status-<?=e((string)$comment['status'])?>"><?=e((string)$comment['status'])?></em></header>
      <p><?=nl2br(e((string)$comment['body']))?></p>
      <footer><span><strong>Article:</strong> <?=e((string)$comment['post_slug'])?></span><?php if($comment['parent_id']):?><span><strong>Reply to:</strong> <?=e((string)($comment['parent_name']??'comment'))?></span><?php endif;?><span>♥ <?=(int)$comment['likes_count']?> · <?=date('M j, Y g:i a',strtotime((string)$comment['created_at']))?></span><a href="<?=url('blog/post.php?slug='.urlencode((string)$comment['post_slug']).'#comment-'.(int)$comment['id'])?>" target="_blank" rel="noopener">Open discussion ↗</a></footer>
    </div>
    <div class="comment-quick-actions"><?php if($comment['status']!=='approved'):?><button name="quick_action" value="approved:<?=(int)$comment['id']?>">Approve</button><?php endif;?><button name="quick_action" value="spam:<?=(int)$comment['id']?>">Spam</button><button name="quick_action" value="trash:<?=(int)$comment['id']?>">Delete</button></div>
  </article><?php endforeach;?>
  <?php if(!$comments):?><div class="empty-state"><span>✓</span><h2>No comments here</h2><p><?= $filter==='pending'?'The moderation queue is clear.':'Try another status or search.'?></p></div><?php endif;?>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
