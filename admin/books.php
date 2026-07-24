<?php
declare(strict_types=1);
require __DIR__.'/auth.php';requireAdmin();require ROOT_PATH.'/includes/books.php';
$db=database();
if($_SERVER['REQUEST_METHOD']==='POST'){verifyCsrf();$id=(int)($_POST['id']??0);$statement=$db->prepare('DELETE FROM wts_books WHERE id=?');$statement->execute([$id]);header('Location:'.url('admin/books.php?deleted=1'));exit;}
$books=allBooks(true);$adminTitle='Publications';$currentAdminPage='books';require __DIR__.'/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Catalog</p><h1>Publications</h1></div><?php if(databaseTableExists('wts_books')):?><a class="button button-primary" href="<?=url('admin/book-edit.php')?>">Add publication</a><?php endif;?></header>
<section class="admin-panel"><?php if(!databaseTableExists('wts_books')):?><p class="notice error">The supplied database is connected, but the Publications extension is not installed yet. Run <code>database/127_0_0_1_compat.sql</code>.</p><?php endif;?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Book</th><th>Status</th><th>Year</th><th>Order</th><th>Actions</th></tr></thead><tbody><?php foreach($books as $book):?><tr><td><strong><?=e($book['title'])?></strong><small><?=e($book['subtitle'])?></small></td><td><span class="status-pill"><?=e($book['status'])?></span></td><td><?=e((string)$book['published_year'])?></td><td><?=e((string)$book['display_order'])?></td><td><?php if(databaseTableExists('wts_books')):?><a href="<?=url('admin/book-edit.php?id='.(int)$book['id'])?>">Edit</a><?php if((int)$book['id']>0):?><form method="post" onsubmit="return confirm('Delete this publication?')"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="id" value="<?=(int)$book['id']?>"><button type="submit">Delete</button></form><?php endif;?><?php else:?>Install extension<?php endif;?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php require __DIR__.'/_footer.php'; ?>
