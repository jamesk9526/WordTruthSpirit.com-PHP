<?php
declare(strict_types=1);
require __DIR__.'/auth.php';requireAdmin();
$db=database();$bookCount=(int)$db->query('SELECT COUNT(*) FROM wts_books')->fetchColumn();$postCount=(int)$db->query('SELECT COUNT(*) FROM wts_posts')->fetchColumn();$messageCount=(int)$db->query("SELECT COUNT(*) FROM wts_contact_messages WHERE status='new'")->fetchColumn();
$adminTitle='Dashboard';require __DIR__.'/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Welcome back</p><h1><?=e($_SESSION['wts_admin_name']??'Administrator')?></h1></div></header>
<section class="admin-stats"><a href="<?=url('admin/books.php')?>"><strong><?=$bookCount?></strong><span>Publications</span></a><a href="<?=url('admin/posts.php')?>"><strong><?=$postCount?></strong><span>Journal entries</span></a><div><strong><?=$messageCount?></strong><span>New messages</span></div></section>
<section class="admin-panel"><h2>Quick actions</h2><div class="button-row"><a class="button button-primary" href="<?=url('admin/book-edit.php')?>">Add publication</a><a class="button button-outline" href="<?=url('admin/post-edit.php')?>">Write journal entry</a></div></section>
<?php require __DIR__.'/_footer.php'; ?>
