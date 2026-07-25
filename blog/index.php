<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/posts.php';
require ROOT_PATH . '/includes/content.php';
require ROOT_PATH . '/includes/subscription.php';
require ROOT_PATH . '/includes/ads.php';
$content=pageContent('blog',['kicker'=>'✦ Word Truth Spirit Journal','heading'=>'Letters for a faith that lives beyond Sunday.','lead'=>'Scripture-rooted teaching, honest questions, and practical encouragement from the desk of Patrick E. Pennington.']);
$posts = allPosts();
$featuredPosts = array_values(array_filter($posts, fn(array $post): bool => !empty($post['featured'])));
$featured = $featuredPosts[0] ?? findPost('jesus-was-both-word-and-spirit') ?? ($posts[0] ?? null);
$categories=array_values(array_unique(array_map(fn(array $post):string=>(string)$post['category'],$posts)));
sort($categories);
$journalAds=adsForPlacement('journal_top');
$pageTitle = 'Journal | Word Truth Spirit';
$pageDescription = 'Scripture-rooted reflections for everyday discipleship.';
$activePage = 'blog';
require ROOT_PATH . '/includes/header.php';
?>
<main class="blog-page">
  <section class="blog-hero">
    <div><p class="kicker"><?=e($content['kicker'])?></p><h1><?=e($content['heading'])?></h1><p><?=e($content['lead'])?></p><a class="button button-primary" href="#latest-reflections">Explore reflections →</a></div>
    <aside><span>Word · Truth · Spirit</span><strong>Essays &amp;<br>Reflections</strong><small>By Patrick E. Pennington</small></aside>
  </section>

  <?php if($journalAds):?><section class="journal-ad-zone" aria-label="Featured partners"><header><span>Featured resources</span><small>Sponsored</small></header><?php foreach($journalAds as $ad)renderAdCard($ad,'journal');?></section><?php endif;?>

  <?php $subscription=subscriptionSettings(); if($subscription['enabled']&&$subscription['placements']['blogPanel']): ?>
  <section class="subscribe-panel">
    <div><p class="kicker kicker-light"><?=e($subscription['eyebrow'])?></p><h2><?=e($subscription['title'])?></h2><p><?=e($subscription['body'])?></p></div>
    <?php renderSubscriptionForm('journal-panel','light','email-signup-journal'); ?>
  </section>
  <?php endif; ?>

  <?php if($featured):?><article class="featured-post">
    <?php if (!empty($featured['cover_image'])): ?><div class="featured-cover"><img src="<?= e($featured['cover_image']) ?>" alt=""></div><?php else: ?><div class="post-monogram">WTS</div><?php endif; ?>
    <div><p class="kicker">Featured reflection · <?= e($featured['category']) ?></p><p class="post-meta"><?= date('F j, Y', strtotime($featured['published_at'])) ?> · <?= (int) $featured['reading_minutes'] ?> min read</p><h2><a href="<?= url('blog/post.php?slug=' . urlencode($featured['slug'])) ?>"><?= e($featured['title']) ?></a></h2><p><?= e($featured['excerpt']) ?></p><a class="button-link" href="<?= url('blog/post.php?slug=' . urlencode($featured['slug'])) ?>">Read the reflection →</a></div>
  </article><?php endif;?>

  <section class="journal-index" id="latest-reflections">
    <header><div><p class="kicker">✦ &nbsp; Browse the journal</p><h2>Latest reflections</h2><p aria-live="polite"><span data-post-count><?= count($posts) ?></span> reflections found</p></div><label class="search-label">Search reflections<input type="search" placeholder="Search by title or topic" data-blog-search></label></header>
    <div class="filters" aria-label="Filter by category">
      <?php foreach (['all',...$categories] as $category): ?><button class="<?= $category === 'all' ? 'active' : '' ?>" type="button" data-category="<?= e($category) ?>" aria-pressed="<?= $category === 'all' ? 'true' : 'false' ?>"><?= ucfirst(e($category)) ?></button><?php endforeach; ?>
    </div>
    <div class="journal-no-results" data-blog-empty hidden><span>⌕</span><h3>No reflections match those filters.</h3><p>Try another category or a broader search.</p></div>
    <div class="post-grid" data-post-grid>
      <?php foreach ($posts as $post): ?>
        <article data-post data-category="<?= e($post['category']) ?>" data-search="<?= e(mb_strtolower($post['title'] . ' ' . $post['excerpt'])) ?>">
          <?php if (!empty($post['cover_image'])): ?><img class="post-card-cover" src="<?= e($post['cover_image']) ?>" alt=""><?php endif; ?>
          <div class="category-mark"><?= e(strtoupper(mb_substr($post['category'], 0, 1))) ?><span><?= e($post['category']) ?></span></div>
          <p class="post-meta"><?= date('F j, Y', strtotime($post['published_at'])) ?> · <?= (int) $post['reading_minutes'] ?> min read</p>
          <h3><a href="<?= url('blog/post.php?slug=' . urlencode($post['slug'])) ?>"><?= e($post['title']) ?></a></h3>
          <p><?= e($post['excerpt']) ?></p>
          <footer><span>By <?= e($post['author']) ?></span><a href="<?= url('blog/post.php?slug=' . urlencode($post['slug'])) ?>">Read →</a></footer>
        </article>
      <?php endforeach; ?>
      <?php if(!$posts):?><div class="journal-empty"><span>✦</span><h3>The next reflection is being prepared.</h3><p>Subscribe above to receive it when it is published.</p></div><?php endif;?>
    </div>
  </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
