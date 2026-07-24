<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$pages = require __DIR__ . '/page-data.php';
$page = $pages[$pageKey];
$pageTitle = $page['title'] . ' | Word Truth Spirit';
$activePage = $pageKey;
require __DIR__ . '/header.php';
?>
<main class="inner-page">
  <header class="page-hero">
    <p class="kicker">✦ &nbsp; Word Truth Spirit</p>
    <h1><?= e($page['title']) ?></h1>
    <p><?= e($page['intro']) ?></p>
  </header>
  <article class="doctrine-layout">
    <aside class="article-aside"><span><?= strtoupper(e($page['title'])) ?></span><p>Scripture · Doctrine · Practice</p></aside>
    <div class="article-body">
      <h2><?= e($page['headline']) ?></h2>
      <blockquote><?= e($page['verse']) ?><cite>— <?= e($page['reference']) ?></cite></blockquote>
      <?php foreach ($page['sections'] as [$heading, $copy]): ?>
        <section><h3><?= e($heading) ?></h3><p><?= e($copy) ?></p></section>
      <?php endforeach; ?>
      <div class="article-next"><span>Continue exploring</span><a href="<?= url('commitments.php') ?>">Read our commitments →</a></div>
    </div>
  </article>
</main>
<?php require __DIR__ . '/footer.php'; ?>
