<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/products.php';
$products = allProducts(true);
$pageTitle = 'Shop & Give | Word Truth Spirit';
$pageDescription = 'Discover ministry resources and giving opportunities from Word Truth Spirit.';
$activePage = 'shop';
require ROOT_PATH . '/includes/header.php';
?>
<main class="shop-page">
  <header class="shop-hero"><p class="kicker">Resources with purpose</p><h1>Shop &amp; give</h1><p>Choose a resource or support an area of ministry. Secure checkout is completed on PayPal.</p></header>
  <?php if($products):?><section class="product-grid" aria-label="Available products and giving opportunities">
    <?php foreach($products as $product):?><article class="product-card">
      <?php if($image=productImageUrl($product)):?><a class="product-card-image" href="<?=url('shop/item.php?slug='.urlencode($product['slug']))?>"><img src="<?=e($image)?>" alt=""></a><?php else:?><div class="product-card-image product-card-placeholder"><span>WTS</span></div><?php endif;?>
      <div class="product-card-content"><?php if($product['badge']):?><span class="product-badge"><?=e($product['badge'])?></span><?php endif;?><h2><a href="<?=url('shop/item.php?slug='.urlencode($product['slug']))?>"><?=e($product['name'])?></a></h2><p><?=e($product['short_description'])?></p><div class="product-card-footer"><strong><?=$product['pricing_mode']==='fixed'?e(formatMoney((float)$product['price'])):'Choose your amount'?></strong><a href="<?=url('shop/item.php?slug='.urlencode($product['slug']))?>">View details →</a></div></div>
    </article><?php endforeach;?>
  </section><?php else:?><section class="shop-empty"><span>✦</span><h2>New resources are on the way.</h2><p>In the meantime, you can support the ministry through our secure PayPal giving page.</p><a class="button button-primary" href="<?=url('donate/')?>">Support the ministry</a></section><?php endif;?>
  <aside class="paypal-trust-strip"><strong>Secure PayPal checkout</strong><span>Payment details are entered on PayPal, never on this website.</span></aside>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>

