<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/posts.php';
$posts = allPosts();
$featured = findPost('jesus-was-both-word-and-spirit') ?? $posts[0];
$pageTitle = 'Journal | Word Truth Spirit';
$pageDescription = 'Scripture-rooted reflections for everyday discipleship.';
$activePage = 'blog';
$subscribeStatus = (string) ($_GET['subscribe'] ?? '');
require ROOT_PATH . '/includes/header.php';
?>
<main class="blog-page">
  <section class="blog-hero">
    <div><p class="kicker">✦ &nbsp; Word Truth Spirit Journal</p><h1>Thoughtful reflections for a faith that lives beyond Sunday.</h1><p>Scripture-rooted teaching, honest questions, and practical encouragement for everyday discipleship.</p><a class="button button-primary" href="#latest-reflections">Explore reflections →</a></div>
    <aside><span>Word · Truth · Spirit</span><strong>Essays &amp;<br>Reflections</strong><small>Written by Patrick E. Pennington</small></aside>
  </section>

  <section class="subscribe-panel">
    <div><p class="kicker kicker-light">Stay connected</p><h2>Never miss a new reflection.</h2><p>We’ll only reach out when a new post is published.</p></div>
    <form action="<?= url('api/subscribe.php') ?>" method="post"><label for="subscriber-email">Email updates</label><div><input id="subscriber-email" type="email" name="email" placeholder="you@example.com" required><button class="button button-light" type="submit">Subscribe</button></div><small>No noise. Unsubscribe anytime.</small><?php if ($subscribeStatus === 'pending'): ?><p class="subscribe-notice">Check your inbox to confirm your subscription.</p><?php elseif ($subscribeStatus === 'active'): ?><p class="subscribe-notice">That email is already subscribed.</p><?php elseif ($subscribeStatus === 'mail-error'): ?><p class="subscribe-notice">We saved your request, but could not send a confirmation email. Please try again later.</p><?php elseif ($subscribeStatus === 'error'): ?><p class="subscribe-notice">Please enter a valid email address.</p><?php endif; ?></form>
  </section>

  <article class="featured-post">
    <div class="post-monogram">WTS</div>
    <div><p class="kicker">Featured reflection · <?= e($featured['category']) ?></p><p class="post-meta"><?= date('F j, Y', strtotime($featured['published_at'])) ?> · <?= (int) $featured['reading_minutes'] ?> min read</p><h2><a href="<?= url('blog/post.php?slug=' . urlencode($featured['slug'])) ?>"><?= e($featured['title']) ?></a></h2><p><?= e($featured['excerpt']) ?></p><a class="button-link" href="<?= url('blog/post.php?slug=' . urlencode($featured['slug'])) ?>">Read the reflection →</a></div>
  </article>

  <section class="journal-index" id="latest-reflections">
    <header><div><p class="kicker">✦ &nbsp; Browse the journal</p><h2>Latest reflections</h2><p><span data-post-count><?= count($posts) ?></span> reflections found</p></div><label class="search-label">Search reflections<input type="search" placeholder="Search by title or topic" data-blog-search></label></header>
    <div class="filters" aria-label="Filter by category">
      <?php foreach (['all','word','truth','spirit','general','christmas'] as $category): ?><button class="<?= $category === 'all' ? 'active' : '' ?>" type="button" data-category="<?= $category ?>"><?= ucfirst($category) ?></button><?php endforeach; ?>
    </div>
    <div class="post-grid" data-post-grid>
      <?php foreach ($posts as $post): ?>
        <article data-post data-category="<?= e($post['category']) ?>" data-search="<?= e(mb_strtolower($post['title'] . ' ' . $post['excerpt'])) ?>">
          <div class="category-mark"><?= e(strtoupper(mb_substr($post['category'], 0, 1))) ?><span><?= e($post['category']) ?></span></div>
          <p class="post-meta"><?= date('F j, Y', strtotime($post['published_at'])) ?> · <?= (int) $post['reading_minutes'] ?> min read</p>
          <h3><a href="<?= url('blog/post.php?slug=' . urlencode($post['slug'])) ?>"><?= e($post['title']) ?></a></h3>
          <p><?= e($post['excerpt']) ?></p>
          <footer><span>By <?= e($post['author']) ?></span><a href="<?= url('blog/post.php?slug=' . urlencode($post['slug'])) ?>">Read →</a></footer>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
