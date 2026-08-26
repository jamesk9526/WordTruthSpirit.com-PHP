<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
requireAdmin();
require ROOT_PATH . '/includes/products.php';

$db = database();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (!productsAvailable()) { http_response_code(409); exit('Install the product catalog database update first.'); }
    $db->prepare('DELETE FROM wts_products WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
    header('Location:' . url('admin/products.php?deleted=1')); exit;
}
$products = allProducts();
$adminTitle = 'Products & giving';
$currentAdminPage = 'products';
require __DIR__ . '/_header.php';
?>
<header class="admin-title admin-title-actions"><div><p class="kicker">Commerce</p><h1>Products &amp; giving</h1><p>Create fixed-price resources or contribution items with suggested and custom amounts.</p></div><?php if(productsAvailable()):?><a class="button button-primary" href="<?=url('admin/product-edit.php')?>">Add product</a><?php endif;?></header>
<?php if(isset($_GET['saved'])):?><p class="notice success">Product saved.</p><?php elseif(isset($_GET['deleted'])):?><p class="notice success">Product removed.</p><?php endif;?>
<?php if(!productsAvailable()):?><section class="admin-panel empty-state"><span>↻</span><h2>Catalog update required</h2><p>Run the pending database updates to create the product catalog safely.</p><a class="button button-primary" href="<?=url('admin/updates.php')?>">Open database updates</a></section><?php else:?>
<section class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Product</th><th>Pricing</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead><tbody>
<?php foreach($products as $product):?><tr><td><strong><?=e($product['name'])?></strong><small>/shop/item.php?slug=<?=e($product['slug'])?></small></td><td><?php if($product['pricing_mode']==='fixed'):?><?=e(formatMoney((float)$product['price']))?><?php else:?>Suggested giving<small><?=e(implode(', ',array_map(fn(float $amount):string=>formatMoney($amount),productSuggestedAmounts($product))))?></small><?php endif;?></td><td><span class="status-pill status-<?=e($product['status'])?>"><?=e($product['status'])?></span></td><td><?=(int)$product['display_order']?></td><td><?php if($product['status']==='published'):?><a href="<?=url('shop/item.php?slug='.urlencode($product['slug']))?>" target="_blank" rel="noopener">View</a><?php endif;?><a href="<?=url('admin/product-edit.php?id='.(int)$product['id'])?>">Edit</a><form method="post" onsubmit="return confirm('Delete this product permanently?')"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="id" value="<?=(int)$product['id']?>"><button type="submit">Delete</button></form></td></tr><?php endforeach;?>
</tbody></table></div><?php if(!$products):?><div class="empty-state"><span>◇</span><h2>Your catalog is ready</h2><p>Add a resource, event, sponsorship, or giving opportunity.</p><a class="button button-primary" href="<?=url('admin/product-edit.php')?>">Create the first product</a></div><?php endif;?></section>
<?php endif; require __DIR__ . '/_footer.php'; ?>

