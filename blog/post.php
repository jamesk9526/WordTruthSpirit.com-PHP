<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/posts.php';
$post = findPost((string) ($_GET['slug'] ?? ''));
if (!$post) { http_response_code(404); $pageTitle = 'Reflection not found'; } else { $pageTitle = (!empty($post['meta_title']) ? $post['meta_title'] : $post['title']) . ' | Word Truth Spirit'; $pageDescription = !empty($post['meta_description']) ? $post['meta_description'] : $post['excerpt']; }
$activePage = 'blog';
require ROOT_PATH . '/includes/header.php';
?>
<main class="post-page">
<?php if (!$post): ?>
  <section class="page-hero"><h1>Reflection not found.</h1><p><a href="<?= url('blog/') ?>">Return to the journal</a></p></section>
<?php else: ?>
  <div class="reading-progress" aria-hidden="true"><span data-reading-progress></span></div>
  <article class="author-article">
    <header class="author-post-header"><p class="kicker"><?= e($post['category']) ?> · Reflection</p><h1><?= e($post['title']) ?></h1><p class="post-deck"><?= e($post['excerpt']) ?></p><div class="author-byline"><span class="author-initial"><?= e(mb_strtoupper(mb_substr($post['author'], 0, 1))) ?></span><p><strong><?= e($post['author']) ?></strong><small><?= date('F j, Y', strtotime($post['published_at'])) ?> · <?= (int) $post['reading_minutes'] ?> min read</small></p></div></header>
    <?php if (!empty($post['cover_image'])): ?><figure class="post-cover"><img src="<?= e($post['cover_image']) ?>" alt=""><figcaption><?= e($post['title']) ?></figcaption></figure><?php endif; ?>
    <?php if (!empty($post['tags'])): ?><div class="tag-list"><?php foreach (array_filter(array_map('trim', explode(',', $post['tags']))) as $tag): ?><span>#<?= e($tag) ?></span><?php endforeach; ?></div><?php endif; ?>
    <div class="post-body"><?= articleHtml($post['body']) ?><blockquote>“Sanctify them through thy truth: thy word is truth.”<cite>— John 17:17</cite></blockquote></div>
    <footer class="article-footer"><div class="author-signoff"><span><?= e(mb_strtoupper(mb_substr($post['author'], 0, 1))) ?></span><p>Written for readers seeking to hold fast to both the Word and the Spirit.</p></div><div><button class="button button-outline" type="button" data-copy-link>Copy article link</button> <a class="button button-outline" href="<?= url('blog/') ?>">← Journal</a></div></footer>
  </article>
<?php endif; ?>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
