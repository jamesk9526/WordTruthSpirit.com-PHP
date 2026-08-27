<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/products.php';
require ROOT_PATH . '/includes/members.php';

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
    if (memberLoggedIn() && ($member=currentMember())) $purchaseReference=recordPurchaseIntent((int)$member['id'],$product,$amount);
    memberSession();
    if (paypalApiIsConfigured()) {
        $checkout = paypalCreateOrder($product, $amount, $purchaseReference ?? null);
        $_SESSION['wts_paypal_checkout'] = ['orderId'=>$checkout['id'], 'purchaseReference'=>$purchaseReference ?? '', 'productName'=>(string)$product['name'], 'amount'=>$amount];
    } else {
        $checkout = paypalCheckoutFields($product, $amount);
        if (!empty($purchaseReference)) $checkout['fields']['custom'] = $purchaseReference;
    }
} catch (Throwable $exception) { $error=$exception->getMessage(); }
$pageTitle=$error?'Checkout needs attention | Word Truth Spirit':'Continue to PayPal | Word Truth Spirit';
$pageDescription='Secure PayPal checkout handoff.'; $activePage='shop'; require ROOT_PATH.'/includes/header.php';
?>
<main class="checkout-handoff-page"><section class="checkout-handoff-card">
<?php if($error):?><span class="checkout-mark">!</span><p class="kicker">Checkout needs attention</p><h1>We could not start checkout.</h1><p><?=e($error)?></p><a class="button button-primary" href="<?=$product?url('shop/item.php?slug='.urlencode($product['slug'])):url('shop/')?>">Return to item</a>
<?php else:?><span class="checkout-mark">↗</span><p class="kicker">Secure handoff</p><h1>Continue to PayPal</h1><p>You are continuing with <strong><?=e($product['name'])?></strong> for <strong><?=e(formatMoney($amount))?></strong>.</p><?php if(!empty($checkout['approvalUrl'])):?><a class="button button-primary button-paypal" href="<?=e($checkout['approvalUrl'])?>">Continue securely →</a><?php else:?><form method="post" action="<?=e($checkout['action'])?>"><?php foreach($checkout['fields'] as $name=>$value):?><input type="hidden" name="<?=e($name)?>" value="<?=e((string)$value)?>"><?php endforeach;?><button class="button button-primary button-paypal" type="submit">Continue securely →</button></form><?php endif;?><p class="checkout-fine-print">Word Truth Spirit does not receive your card or PayPal sign-in details.</p><?php endif;?>
</section></main>
<?php require ROOT_PATH.'/includes/footer.php'; ?>
