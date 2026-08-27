<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/products.php';
require ROOT_PATH . '/includes/members.php';
memberSession();
$captureError=''; $completed=false;
$orderId=trim((string)($_GET['token']??'')); $checkout=$_SESSION['wts_paypal_checkout']??null;
if (is_array($checkout) && !empty($checkout['completed']) && hash_equals((string)($checkout['orderId']??''),$orderId)) $completed=true;
elseif ($orderId !== '' && is_array($checkout) && hash_equals((string)($checkout['orderId']??''),$orderId) && paypalApiIsConfigured()) {
  try { $order=paypalCaptureOrder($orderId); if(($order['status']??'')!=='COMPLETED') throw new RuntimeException('PayPal did not confirm this payment.'); $transactionId=paypalCaptureTransactionId($order); completePurchaseIntent((string)($checkout['purchaseReference']??''),$transactionId); $_SESSION['wts_paypal_checkout']=['orderId'=>$orderId,'completed'=>true]; $completed=true; } catch (Throwable $exception) { $captureError=$exception->getMessage(); }
} elseif ($orderId !== '') $captureError='We could not verify this checkout session. Please return to the shop and begin again.';
$pageTitle=$captureError?'Checkout needs attention | Word Truth Spirit':'Payment Complete | Word Truth Spirit';$pageDescription='Thank you for supporting Word Truth Spirit.';$activePage='';require ROOT_PATH . '/includes/header.php';
?>
<main class="thank-you-page"><section class="thank-you-card"><?php if($captureError):?><p class="kicker">Checkout needs attention</p><span class="thank-you-mark">!</span><h1>We could not confirm payment.</h1><p><?=e($captureError)?></p><div class="button-row"><a class="button button-primary" href="<?=url('shop/')?>">Return to shop &amp; giving</a><a class="button button-outline" href="<?=url('contact.php')?>">Contact us</a></div><?php else:?><p class="kicker">✦ &nbsp; PayPal complete</p><span class="thank-you-mark">✓</span><h1>Thank you.</h1><p><?= $completed ? 'PayPal has completed your checkout. Your purchase or gift helps sustain biblical teaching, resources, and encouragement for readers.' : 'Your checkout was completed through PayPal.' ?></p><blockquote>“Every man according as he purposeth in his heart, so let him give.”<cite>— 2 Corinthians 9:7</cite></blockquote><div class="button-row"><a class="button button-primary" href="<?=url('shop/')?>">Return to shop &amp; giving →</a><a class="button button-outline" href="<?=url()?>">Return home</a></div><?php endif;?></section></main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
