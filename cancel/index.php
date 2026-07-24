<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle='Donation Cancelled | Word Truth Spirit';$pageDescription='Your donation was not completed.';$activePage='';require ROOT_PATH . '/includes/header.php';
?>
<main class="thank-you-page"><section class="thank-you-card"><p class="kicker">✦ &nbsp; Donation not completed</p><span class="thank-you-mark">—</span><h1>Your donation was cancelled.</h1><p>No donation was processed. Thank you for considering support for Word Truth Spirit; you are welcome to return whenever you are ready.</p><div class="button-row"><a class="button button-primary" href="<?=url()?>">Return home</a><a class="button button-outline" href="<?=url('contact.php')?>">Contact us</a></div></section></main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
