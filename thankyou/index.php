<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = 'Thank You | Word Truth Spirit';
$pageDescription = 'Thank you for supporting the Word Truth Spirit ministry.';
$activePage = '';
require ROOT_PATH . '/includes/header.php';
?>
<main class="thank-you-page">
  <section class="thank-you-card">
    <p class="kicker">✦ &nbsp; Thank you</p>
    <span class="thank-you-mark">♥</span>
    <h1>Thank you for supporting the ministry.</h1>
    <p>Your generosity helps Word Truth Spirit continue providing Scripture-rooted teaching, reflections, and resources for readers seeking to grow in the Word and the Spirit.</p>
    <blockquote>“God loveth a cheerful giver.”<cite>— 2 Corinthians 9:7</cite></blockquote>
    <div class="button-row"><a class="button button-primary" href="<?= url('blog/') ?>">Read the blog →</a><a class="button button-outline" href="<?= url() ?>">Return home</a></div>
  </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
