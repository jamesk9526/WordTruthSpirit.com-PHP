<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/books.php';
$books = allBooks();
$pageTitle = 'Publications | Word Truth Spirit';
$pageDescription = 'Books by Patrick E. Pennington on Scripture, truth, and the continuing work of the Holy Spirit.';
$activePage = 'publications';
require ROOT_PATH . '/includes/header.php';
?>
<main class="publications-page">
  <header class="page-hero publications-hero">
    <div><p class="kicker">✦ &nbsp; Books by Patrick E. Pennington</p><h1>Publications</h1><p>Scripture-grounded books written to bring biblical authority and the continuing work of the Holy Spirit into one faithful conversation.</p></div>
    <aside><span>Word</span><span>Truth</span><span>Spirit</span></aside>
  </header>
  <section class="book-catalog">
    <?php if (!$books): ?><p>No publications are currently available.</p><?php endif; ?>
    <?php foreach ($books as $index => $book): ?>
      <article class="publication-card">
        <div class="publication-cover"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><img src="<?= url($book['cover_image'] ?: 'assets/images/book-cover.png') ?>" alt="<?= e($book['title']) ?> book cover"></div>
        <div>
          <p class="kicker">Featured publication<?= !empty($book['published_year']) ? ' · ' . (int) $book['published_year'] : '' ?></p>
          <h2><?= e($book['title']) ?></h2>
          <?php if ($book['subtitle']): ?><p class="book-subtitle"><?= e($book['subtitle']) ?></p><?php endif; ?>
          <p><?= e($book['description']) ?></p>
          <?php if ($book['format_details']): ?><p class="format-details"><?= e($book['format_details']) ?></p><?php endif; ?>
          <div class="button-row">
            <?php if ($book['purchase_url']): ?><a class="button button-primary" href="<?= e($book['purchase_url']) ?>" rel="noopener">View this book →</a><?php endif; ?>
            <a class="button-link" href="<?= url('contact.php') ?>">Bulk orders or questions</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
