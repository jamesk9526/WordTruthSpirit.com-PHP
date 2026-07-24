<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle='Donation Complete | Word Truth Spirit';$pageDescription='Thank you for supporting Word Truth Spirit.';$activePage='';require ROOT_PATH . '/includes/header.php';
?>
<main class="thank-you-page"><section class="thank-you-card"><p class="kicker">✦ &nbsp; Donation complete</p><span class="thank-you-mark">✓</span><h1>Your donation is complete.</h1><p>Thank you for your generous support of Word Truth Spirit. Your gift helps sustain biblical teaching, resources, and encouragement for readers.</p><blockquote>“Every man according as he purposeth in his heart, so let him give.”<cite>— 2 Corinthians 9:7</cite></blockquote><div class="button-row"><a class="button button-primary" href="<?=url('blog/')?>">Read the journal →</a><a class="button button-outline" href="<?=url()?>">Return home</a></div></section></main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
