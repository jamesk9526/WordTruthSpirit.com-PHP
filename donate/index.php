<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/products.php';
$givingProducts = array_values(array_filter(allProducts(true), fn(array $product): bool => $product['pricing_mode'] === 'contribution'));
$paypal = paypalSettings();
$pageTitle = 'Support the Ministry | Word Truth Spirit';
$pageDescription = 'Support Word Truth Spirit through a secure PayPal donation.';
$activePage = 'shop';
require ROOT_PATH . '/includes/header.php';
?>
<main class="donate-page"><section class="donate-card"><p class="kicker">✦ &nbsp; Ministry giving</p><h1>Help support the ministry.</h1><p>Your gift helps Word Truth Spirit continue sharing Scripture-rooted teaching, thoughtful reflections, and biblical resources with readers.</p><blockquote>“Freely ye have received, freely give.”<cite>— Matthew 10:8</cite></blockquote>
<?php if($givingProducts):?><div class="donation-opportunities"><p>Choose where you would like your gift to make an impact.</p><?php foreach($givingProducts as $product):?><a class="button button-primary" href="<?=url('shop/item.php?slug='.urlencode($product['slug']))?>"><?=e($product['name'])?> →</a><?php endforeach;?></div>
<?php elseif($paypal['donationButtonId']!==''):?><form class="paypal-donate paypal-donate-large" action="https://www.paypal.com/donate" method="post" target="_top"><input type="hidden" name="hosted_button_id" value="<?=e($paypal['donationButtonId'])?>"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit" title="Donate securely with PayPal" alt="Donate with PayPal button"></form>
<?php else:?><p class="notice error">Online giving is being configured. Please contact us if you would like to help.</p><a class="button button-primary" href="<?=url('contact.php')?>">Contact us</a><?php endif;?>
<p class="donate-note">Secure giving is processed by PayPal.</p></section></main>
<?php require ROOT_PATH.'/includes/footer.php'; ?>
