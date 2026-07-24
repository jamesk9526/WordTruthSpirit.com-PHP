<?php
declare(strict_types=1);
require __DIR__.'/auth.php';requireAdmin();require ROOT_PATH.'/includes/books.php';
$db=database();
$bookCount=databaseTableExists('wts_books')?(int)$db->query('SELECT COUNT(*) FROM wts_books')->fetchColumn():count(fallbackBooks());
$postCount=(int)$db->query(databaseUsesLegacySchema()?'SELECT COUNT(*) FROM posts':'SELECT COUNT(*) FROM wts_posts')->fetchColumn();
$messageCount=(int)$db->query(databaseUsesLegacySchema()?'SELECT COUNT(*) FROM contact_messages WHERE is_read=0':"SELECT COUNT(*) FROM wts_contact_messages WHERE status='new'")->fetchColumn();
$adminTitle='Dashboard';$currentAdminPage='dashboard';require __DIR__.'/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Welcome back</p><h1><?=e($_SESSION['wts_admin_name']??'Administrator')?></h1></div></header>
<section class="admin-stats"><a href="<?=url('admin/books.php')?>"><strong><?=$bookCount?></strong><span>Publications</span></a><a href="<?=url('admin/posts.php')?>"><strong><?=$postCount?></strong><span>Journal entries</span></a><a href="<?=url('admin/inbox.php')?>"><strong><?=$messageCount?></strong><span>New messages</span></a></section>
<section class="admin-panel dashboard-actions"><div><p class="kicker">Content management</p><h2>Keep the ministry moving.</h2><p>Publish a reflection, add a new book, or respond to readers from one secure workspace.</p></div><div class="button-row"><a class="button button-primary" href="<?=url('admin/post-edit.php')?>">Write journal entry</a><a class="button button-outline" href="<?=url('admin/book-edit.php')?>">Add publication</a></div></section>
<?php require __DIR__.'/_footer.php'; ?>
