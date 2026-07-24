<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/posts.php';
$post = findPost((string) ($_GET['slug'] ?? ''));
if (!$post) { http_response_code(404); $pageTitle = 'Reflection not found'; } else { $pageTitle = $post['title'] . ' | Word Truth Spirit'; }
$activePage = 'blog';
require ROOT_PATH . '/includes/header.php';
?>
<main class="post-page">
<?php if (!$post): ?>
  <section class="page-hero"><h1>Reflection not found.</h1><p><a href="<?= url('blog/') ?>">Return to the journal</a></p></section>
<?php else: ?>
  <article>
    <header><p class="kicker"><?= e($post['category']) ?> · Reflection</p><h1><?= e($post['title']) ?></h1><p class="post-meta"><?= date('F j, Y', strtotime($post['published_at'])) ?> · <?= (int) $post['reading_minutes'] ?> min read · By <?= e($post['author']) ?></p><p class="post-deck"><?= e($post['excerpt']) ?></p></header>
    <div class="post-body"><?php foreach (preg_split('/\R\R+/', $post['body']) as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?><blockquote>“Sanctify them through thy truth: thy word is truth.”<cite>— John 17:17</cite></blockquote></div>
    <footer><a class="button button-outline" href="<?= url('blog/') ?>">← Back to the journal</a></footer>
  </article>
<?php endif; ?>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
