<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/content.php';
$homeContent=pageContent('home',['kicker'=>'✦ Word Truth Spirit','heading'=>'Where Scripture and Spirit belong together.','lead'=>'Clear biblical teaching for believers who value the authority of God’s Word and the active ministry of the Holy Spirit.']);$foundationContent=pageContent('foundation',['kicker'=>'✦ Explore the foundation','heading'=>'One faith. Three essential themes.','lead'=>'']);
$pageTitle = 'Word Truth Spirit | Patrick E. Pennington';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>
<main>
  <section class="home-hero">
    <div class="hero-copy">
      <p class="kicker"><?=e($homeContent['kicker'])?></p>
      <h1><?=e($homeContent['heading'])?></h1>
      <p class="hero-lead"><?=e($homeContent['lead'])?></p>
      <div class="button-row">
        <a class="button button-primary" href="<?= url('blog/') ?>">Read the journal <span>→</span></a>
        <a class="button-link" href="<?= url('commitments.php') ?>">Our commitments <span>→</span></a>
      </div>
      <ul class="principle-list">
        <li>Rooted in Scripture</li><li>Open to the Spirit</li><li>Centered on Christ</li>
      </ul>
    </div>
    <aside class="verse-card">
      <p>A word for the reader</p>
      <span class="ornament">❦</span>
      <blockquote>“God is a Spirit: and they that worship him must worship him in spirit and in truth.”</blockquote>
      <cite>John 4:24</cite>
    </aside>
  </section>

  <section class="theme-section">
    <div class="section-intro">
      <p class="kicker kicker-light"><?=e($foundationContent['kicker'])?></p>
      <h2><?=e($foundationContent['heading'])?></h2>
    </div>
    <div class="theme-grid">
      <a href="<?= url('word.php') ?>"><span>01</span><h3>Word</h3><p>Receiving Scripture as the preserved and trustworthy Word of God.</p><strong>Explore →</strong></a>
      <a href="<?= url('truth.php') ?>"><span>02</span><h3>Truth</h3><p>Holding biblical conviction with clarity, courage, and grace.</p><strong>Explore →</strong></a>
      <a href="<?= url('spirit.php') ?>"><span>03</span><h3>Spirit</h3><p>Welcoming the continuing work and gifts of the Holy Spirit.</p><strong>Explore →</strong></a>
    </div>
  </section>

  <section class="author-section">
    <div class="author-mark"><span>PEP</span><small>Author</small></div>
    <div class="author-copy">
      <p class="kicker">✦ &nbsp; From the author</p>
      <h2>Patrick E. Pennington</h2>
      <p>Patrick writes to help readers hold together the authority of Scripture and the continuing work of the Holy Spirit with conviction, order, and grace.</p>
      <p class="small-label">Author of <em>The Spirit of Truth</em></p>
      <div class="button-row">
        <a class="button button-outline" href="<?= url('commitments.php') ?>">Read the commitments</a>
        <a class="button-link" href="<?= url('contact.php') ?>">Contact the author →</a>
      </div>
    </div>
  </section>

  <section class="book-section">
    <div class="book-visual">
      <img src="<?= url('assets/images/book-cover.png') ?>" alt="The Spirit of Truth book cover">
      <span>Featured edition</span>
    </div>
    <div class="book-copy">
      <p class="kicker">✦ &nbsp; Featured publication</p>
      <h2>The Spirit of Truth</h2>
      <p class="book-subtitle">A Biblical Defense of Traditional Pentecostalism</p>
      <p>A clear, Scripture-grounded case for the continuing work of the Holy Spirit without surrendering biblical order or discernment.</p>
      <ul class="check-list">
        <li>Written for personal study, churches, and ministry leaders</li>
        <li>Engages Acts and 1 Corinthians 12–14</li>
        <li>Brings biblical authority and spiritual gifts into one conversation</li>
      </ul>
      <div class="button-row">
        <a class="button button-primary" href="<?= url('publications.php') ?>">View publication →</a>
        <a class="button-link" href="<?= url('contact.php') ?>">Bulk orders or questions</a>
      </div>
    </div>
  </section>

  <section class="journal-callout">
    <div><p class="kicker kicker-light">✦ &nbsp; From the journal</p><h2>Thoughtful teaching for an everyday faith.</h2></div>
    <div><p>Read reflections on Scripture, spiritual formation, truth, and life in the Spirit.</p><a class="button button-light" href="<?= url('blog/') ?>">Browse all reflections →</a></div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
