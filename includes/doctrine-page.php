<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require ROOT_PATH . '/includes/posts.php';
$pages = require __DIR__ . '/page-data.php';
$page = $pages[$pageKey];
$pageTitle = $page['title'] . ' | Word Truth Spirit';
$activePage = $pageKey;
$relatedPosts = postsForTag($pageKey);
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
  <?php if ($relatedPosts): ?>
  <section class="doctrine-journal" aria-label="Related journal reflections">
    <header><div><p class="kicker">From the journal</p><h2>Reflections on <?= e(ucfirst($pageKey)) ?></h2><p>More Scripture-rooted writing connected to this theme.</p></div><div class="carousel-controls"><button type="button" data-carousel-prev aria-label="Previous reflections">←</button><button type="button" data-carousel-next aria-label="Next reflections">→</button></div></header>
    <div class="doctrine-carousel" data-doctrine-carousel>
      <?php foreach ($relatedPosts as $post): ?><article class="doctrine-post-card"><p class="post-meta"><?= date('M j, Y', strtotime($post['published_at'])) ?> · <?= (int)$post['reading_minutes'] ?> min read</p><h3><?= e($post['title']) ?></h3><p><?= e($post['excerpt']) ?></p><a href="<?= url('blog/post.php?slug=' . urlencode($post['slug'])) ?>">Read reflection →</a></article><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/footer.php'; ?>
