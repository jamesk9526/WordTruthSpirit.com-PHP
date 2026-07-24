<?php
declare(strict_types=1);
require __DIR__.'/auth.php';requireAdmin();
$db=database();$legacy=databaseUsesLegacySchema();if($_SERVER['REQUEST_METHOD']==='POST'){verifyCsrf();$db->prepare('DELETE FROM '.($legacy?'posts':'wts_posts').' WHERE id=?')->execute([(int)$_POST['id']]);header('Location:'.url('admin/posts.php?deleted=1'));exit;}
$posts=$legacy
 ?$db->query("SELECT id,title,slug,category,IF(published=1,'published','draft') AS status,date AS published_at FROM posts ORDER BY date DESC,created_at DESC")->fetchAll()
 :$db->query('SELECT id,title,slug,category,status,published_at FROM wts_posts ORDER BY published_at DESC,created_at DESC')->fetchAll();$adminTitle='Journal';require __DIR__.'/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Content</p><h1>Journal</h1></div><a class="button button-primary" href="<?=url('admin/post-edit.php')?>">New reflection</a></header>
<section class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Reflection</th><th>Category</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead><tbody><?php foreach($posts as $post):?><tr><td><strong><?=e($post['title'])?></strong><small>/<?=e($post['slug'])?></small></td><td><?=e($post['category'])?></td><td><span class="status-pill"><?=e($post['status'])?></span></td><td><?=e($post['published_at']?date('M j, Y',strtotime($post['published_at'])):'—')?></td><td><a href="<?=url('admin/post-edit.php?id='.(int)$post['id'])?>">Edit</a><form method="post" onsubmit="return confirm('Delete this reflection?')"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="id" value="<?=(int)$post['id']?>"><button>Delete</button></form></td></tr><?php endforeach;?></tbody></table></div><?php if(!$posts):?><p>No database journal entries yet. The public journal is currently showing bundled fallback entries.</p><?php endif;?></section>
<?php require __DIR__.'/_footer.php'; ?>
