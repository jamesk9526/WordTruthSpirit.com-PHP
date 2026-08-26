<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/products.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location:' . url('shop/')); exit; }
$product = productBySlug(trim((string)($_POST['slug']??'')));
$error = '';
$checkout = null;
$amount = 0.0;
try {
    if (!$product) throw new InvalidArgumentException('That item is not currently available.');
    $submitted = ($product['pricing_mode']??'fixed')==='contribution'
        ? (($_POST['amount_choice']??'')==='custom' ? ($_POST['custom_amount']??'') : ($_POST['amount_choice']??''))
        : null;
    $amount = validateProductAmount($product, $submitted);
    $checkout = paypalCheckoutFields($product, $amount);
} catch (Throwable $exception) { $error=$exception->getMessage(); }
$pageTitle=$error?'Checkout needs attention | Word Truth Spirit':'Continue to PayPal | Word Truth Spirit';
$pageDescription='Secure PayPal checkout handoff.'; $activePage='shop'; require ROOT_PATH.'/includes/header.php';
?>
<main class="checkout-handoff-page"><section class="checkout-handoff-card">
<?php if($error):?><span class="checkout-mark">!</span><p class="kicker">Checkout needs attention</p><h1>We could not start checkout.</h1><p><?=e($error)?></p><a class="button button-primary" href="<?=$product?url('shop/item.php?slug='.urlencode($product['slug'])):url('shop/')?>">Return to item</a>
<?php else:?><span class="checkout-mark">↗</span><p class="kicker">Secure handoff</p><h1>Continue to PayPal</h1><p>You are continuing with <strong><?=e($product['name'])?></strong> for <strong><?=e(formatMoney($amount))?></strong>.</p><form method="post" action="<?=e($checkout['action'])?>"><?php foreach($checkout['fields'] as $name=>$value):?><input type="hidden" name="<?=e($name)?>" value="<?=e((string)$value)?>"><?php endforeach;?><button class="button button-primary button-paypal" type="submit">Continue securely →</button></form><p class="checkout-fine-print">Word Truth Spirit does not receive your card or PayPal sign-in details.</p><?php endif;?>
</section></main>
<?php require ROOT_PATH.'/includes/footer.php'; ?>
