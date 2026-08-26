<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle='Payment Complete | Word Truth Spirit';$pageDescription='Thank you for supporting Word Truth Spirit.';$activePage='';require ROOT_PATH . '/includes/header.php';
?>
<main class="thank-you-page"><section class="thank-you-card"><p class="kicker">✦ &nbsp; PayPal complete</p><span class="thank-you-mark">✓</span><h1>Thank you.</h1><p>PayPal has completed your checkout. Your purchase or gift helps sustain biblical teaching, resources, and encouragement for readers.</p><blockquote>“Every man according as he purposeth in his heart, so let him give.”<cite>— 2 Corinthians 9:7</cite></blockquote><div class="button-row"><a class="button button-primary" href="<?=url('shop/')?>">Return to shop &amp; giving →</a><a class="button button-outline" href="<?=url()?>">Return home</a></div></section></main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
