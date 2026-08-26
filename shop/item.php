<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/products.php';
$slug = trim((string) ($_GET['slug'] ?? ''));
$product = productBySlug($slug);
if (!$product) { http_response_code(404); $pageTitle='Item not found | Word Truth Spirit'; $pageDescription='The requested item is not available.'; }
else { $pageTitle=$product['name'].' | Word Truth Spirit'; $pageDescription=$product['short_description'] ?: excerpt((string)$product['description']); }
$activePage='shop'; require ROOT_PATH . '/includes/header.php';
?>
<main class="product-detail-page">
<?php if(!$product):?><section class="shop-empty"><span>◇</span><h1>This item is not available.</h1><p>It may have moved or is not currently published.</p><a class="button button-primary" href="<?=url('shop/')?>">Browse shop &amp; giving</a></section>
<?php else:$amounts=productSuggestedAmounts($product);?>
  <nav class="product-breadcrumb" aria-label="Breadcrumb"><a href="<?=url('shop/')?>">Shop &amp; give</a><span>→</span><span><?=e($product['name'])?></span></nav>
  <div class="product-detail-grid">
    <section class="product-detail-visual"><?php if($image=productImageUrl($product)):?><img src="<?=e($image)?>" alt=""><?php else:?><div class="product-card-placeholder"><span>Word Truth Spirit</span></div><?php endif;?></section>
    <section class="product-detail-content"><?php if($product['badge']):?><span class="product-badge"><?=e($product['badge'])?></span><?php endif;?><p class="kicker"><?=$product['pricing_mode']==='contribution'?'Giving opportunity':'Ministry resource'?></p><h1><?=e($product['name'])?></h1><p class="product-lead"><?=e($product['short_description'])?></p><div class="product-description"><?=articleHtml((string)$product['description'])?></div>
      <form class="product-checkout-card" method="post" action="<?=url('shop/checkout.php')?>" data-product-checkout><input type="hidden" name="slug" value="<?=e($product['slug'])?>">
      <?php if($product['pricing_mode']==='fixed'):?><strong class="product-price"><?=e(formatMoney((float)$product['price']))?></strong>
      <?php else:?><fieldset><legend>Choose an amount</legend><div class="suggested-amounts"><?php foreach($amounts as $index=>$amount):?><label><input type="radio" name="amount_choice" value="<?=e(number_format($amount,2,'.',''))?>" <?=$index===0?'checked':''?>><span><?=e(formatMoney($amount))?></span></label><?php endforeach;?><?php if(!empty($product['allow_custom_amount'])):?><label><input type="radio" name="amount_choice" value="custom" <?=$amounts?'':'checked'?>><span>Other</span></label><?php endif;?></div><?php if(!empty($product['allow_custom_amount'])):?><label class="custom-amount-field">Custom amount<span><b><?=paypalSettings()['currency']==='USD'?'$':e(paypalSettings()['currency'])?></b><input type="number" name="custom_amount" min="<?=e((string)$product['minimum_amount'])?>" <?=$product['maximum_amount']!==null?'max="'.e((string)$product['maximum_amount']).'"':''?> step="0.01" placeholder="<?=e((string)$product['minimum_amount'])?>"></span></label><?php endif;?></fieldset><?php endif;?>
      <?php if($product['fulfillment_note']):?><p class="fulfillment-note"><?=e($product['fulfillment_note'])?></p><?php endif;?><button class="button button-primary button-paypal" type="submit"><?=e($product['button_label'])?> →</button><small>Securely completed on PayPal. You will have a chance to review before paying.</small></form>
    </section>
  </div>
<?php endif;?>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>

