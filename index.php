<?php
declare(strict_types=1);

$year = (int) date('Y');
$status = isset($_GET['status']) ? (string) $_GET['status'] : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Clear biblical teaching where Scripture and Spirit belong together.">
  <title>Word Truth Spirit | Patrick E. Pennington</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Libre+Caslon+Text:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="#home" aria-label="Word Truth Spirit home">
      <span class="crest" aria-hidden="true">WTS</span>
      <span><strong>Word Truth Spirit</strong><small>Patrick E. Pennington</small></span>
    </a>
    <button class="menu-button" aria-expanded="false" aria-controls="main-nav">Menu</button>
    <nav id="main-nav" aria-label="Primary navigation">
      <a href="#home">Home</a>
      <a href="#foundation">Word</a>
      <a href="#foundation">Truth</a>
      <a href="#foundation">Spirit</a>
      <a href="#publication">Publication</a>
      <a href="#contact">Contact</a>
    </nav>
  </header>

  <main>
    <section class="hero" id="home">
      <div class="hero-copy">
        <p class="eyebrow">✦ Word Truth Spirit</p>
        <h1>Where Scripture and Spirit belong together.</h1>
        <p class="lead">Clear biblical teaching for believers who value the authority of God’s Word and the active ministry of the Holy Spirit.</p>
        <div class="actions">
          <a class="button" href="#foundation">Explore the foundation <span>→</span></a>
          <a class="text-link" href="#commitments">Our commitments <span>→</span></a>
        </div>
        <ul class="principles" aria-label="Ministry principles">
          <li>Rooted in Scripture</li>
          <li>Open to the Spirit</li>
          <li>Centered on Christ</li>
        </ul>
      </div>
      <aside class="scripture-card">
        <p>A word for the reader</p>
        <span class="quill" aria-hidden="true">❦</span>
        <blockquote>“God is a Spirit: and they that worship him must worship him in spirit and in truth.”</blockquote>
        <cite>John 4:24</cite>
      </aside>
    </section>

    <section class="foundation section" id="foundation" aria-labelledby="foundation-title">
      <div class="section-heading">
        <p class="eyebrow">✦ Explore the foundation</p>
        <h2 id="foundation-title">One faith. Three essential themes.</h2>
      </div>
      <div class="theme-grid">
        <article>
          <span>01</span>
          <h3>Word</h3>
          <p>Receiving Scripture as the preserved and trustworthy Word of God.</p>
        </article>
        <article>
          <span>02</span>
          <h3>Truth</h3>
          <p>Holding biblical conviction with clarity, courage, and grace.</p>
        </article>
        <article>
          <span>03</span>
          <h3>Spirit</h3>
          <p>Welcoming the continuing work and gifts of the Holy Spirit.</p>
        </article>
      </div>
    </section>

    <section class="author section" id="commitments">
      <div class="monogram" aria-hidden="true">PEP</div>
      <div>
        <p class="eyebrow">✦ From the author</p>
        <h2>Patrick E. Pennington</h2>
        <p>Patrick writes to help readers hold together the authority of Scripture and the continuing work of the Holy Spirit with conviction, order, and grace.</p>
        <p class="byline">Author of <em>The Spirit of Truth</em></p>
      </div>
    </section>

    <section class="publication section" id="publication">
      <div class="book" aria-label="The Spirit of Truth book">
        <span>Patrick E. Pennington</span>
        <strong>The Spirit<br>of Truth</strong>
        <small>A Biblical Defense of<br>Traditional Pentecostalism</small>
      </div>
      <div class="publication-copy">
        <p class="eyebrow">✦ Featured publication</p>
        <h2>The Spirit of Truth</h2>
        <p class="subtitle">A Biblical Defense of Traditional Pentecostalism</p>
        <p>A clear, Scripture-grounded case for the continuing work of the Holy Spirit without surrendering biblical order or discernment.</p>
        <ul>
          <li>Written for personal study, churches, and ministry leaders</li>
          <li>Engages Acts and 1 Corinthians 12–14</li>
          <li>Brings biblical authority and spiritual gifts into one conversation</li>
        </ul>
        <a class="button" href="https://www.amazon.com/dp/B0GBVXPHVF" rel="noopener noreferrer">View on Amazon <span>→</span></a>
      </div>
    </section>

    <section class="contact section" id="contact">
      <div>
        <p class="eyebrow">✦ Connect</p>
        <h2>Questions or bulk orders?</h2>
        <p>Send a note to Patrick about the publication, biblical teaching, or ministry resources.</p>
      </div>
      <form action="api/contact.php" method="post">
        <?php if ($status === 'success'): ?>
          <p class="notice success" role="status">Thank you. Your message has been received.</p>
        <?php elseif ($status === 'error'): ?>
          <p class="notice error" role="alert">Please complete every field with a valid email address.</p>
        <?php endif; ?>
        <div class="form-row">
          <label>Name<input type="text" name="name" autocomplete="name" required maxlength="100"></label>
          <label>Email<input type="email" name="email" autocomplete="email" required maxlength="150"></label>
        </div>
        <label>Message<textarea name="message" rows="5" required maxlength="3000"></textarea></label>
        <input class="honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
        <button class="button" type="submit">Send message <span>→</span></button>
      </form>
    </section>
  </main>

  <footer>
    <div>
      <h2>Word Truth Spirit ❦</h2>
      <p>Patrick E. Pennington · Author &amp; Teacher</p>
      <p><em>“Verbum et Spiritus Veritatis”</em></p>
    </div>
    <p>© <?= $year ?> Patrick E. Pennington. All rights reserved.</p>
  </footer>
  <script src="assets/site.js"></script>
</body>
</html>
