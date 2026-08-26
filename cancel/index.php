<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle='Checkout Cancelled | Word Truth Spirit';$pageDescription='Your PayPal checkout was not completed.';$activePage='';require ROOT_PATH . '/includes/header.php';
?>
<main class="thank-you-page"><section class="thank-you-card"><p class="kicker">✦ &nbsp; Checkout not completed</p><span class="thank-you-mark">—</span><h1>Your checkout was cancelled.</h1><p>No PayPal payment was completed. You are welcome to return whenever you are ready.</p><div class="button-row"><a class="button button-primary" href="<?=url('shop/')?>">Return to shop &amp; giving</a><a class="button button-outline" href="<?=url('contact.php')?>">Contact us</a></div></section></main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
