<?php
declare(strict_types=1);
require __DIR__.'/auth.php';requireAdmin();require ROOT_PATH.'/includes/books.php';
$db=database();
if($_SERVER['REQUEST_METHOD']==='POST'){verifyCsrf();$id=(int)($_POST['id']??0);$statement=$db->prepare('DELETE FROM wts_books WHERE id=?');$statement->execute([$id]);header('Location:'.url('admin/books.php?deleted=1'));exit;}
$books=allBooks(true);$adminTitle='Publications';require __DIR__.'/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Catalog</p><h1>Publications</h1></div><a class="button button-primary" href="<?=url('admin/book-edit.php')?>">Add publication</a></header>
<section class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Book</th><th>Status</th><th>Year</th><th>Order</th><th>Actions</th></tr></thead><tbody><?php foreach($books as $book):?><tr><td><strong><?=e($book['title'])?></strong><small><?=e($book['subtitle'])?></small></td><td><span class="status-pill"><?=e($book['status'])?></span></td><td><?=e((string)$book['published_year'])?></td><td><?=e((string)$book['display_order'])?></td><td><a href="<?=url('admin/book-edit.php?id='.(int)$book['id'])?>">Edit</a><?php if((int)$book['id']>0):?><form method="post" onsubmit="return confirm('Delete this publication?')"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="id" value="<?=(int)$book['id']?>"><button type="submit">Delete</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php require __DIR__.'/_footer.php'; ?>
