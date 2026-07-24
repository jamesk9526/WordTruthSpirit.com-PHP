<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/content.php';
$content=pageContent('contact',['kicker'=>'✦ Connect','heading'=>'Contact','lead'=>'Get in touch with questions, comments, or ministry inquiries.']);
$pageTitle = 'Contact | Word Truth Spirit';
$activePage = 'contact';
$status = (string) ($_GET['status'] ?? '');
require __DIR__ . '/includes/header.php';
?>
<main class="inner-page">
  <header class="page-hero compact"><p class="kicker"><?=e($content['kicker'])?></p><h1><?=e($content['heading'])?></h1><p><?=e($content['lead'])?></p></header>
  <section class="contact-layout">
    <aside>
      <h2>About the Author</h2><p>Patrick E. Pennington is the author of <em>The Spirit of Truth</em>—a Biblical defense of Traditional Pentecostalism.</p>
      <h3>Ministry Inquiries</h3><p>For speaking engagements, ministry partnerships, or bulk book orders, indicate the nature of your inquiry.</p>
      <h3>Response Time</h3><p>Messages are typically answered within 3–5 business days.</p>
      <blockquote>“Let your speech be alway with grace, seasoned with salt.”<cite>— Colossians 4:6</cite></blockquote>
    </aside>
    <div class="contact-form"><h2>Send a Message</h2><p>Messages are sent directly to Patrick at <a href="mailto:Patrick@wordtruthspirit.com">Patrick@wordtruthspirit.com</a>.</p>
      <?php if ($status === 'success'): ?><p class="notice success" role="status">Thank you. Your message has been received.</p><?php elseif ($status === 'error'): ?><p class="notice error" role="alert">Please review the form and try again.</p><?php endif; ?>
      <form action="<?= url('api/contact.php') ?>" method="post">
        <label>Full Name *<input type="text" name="name" autocomplete="name" placeholder="Your full name" maxlength="100" required></label>
        <label>Email Address *<input type="email" name="email" autocomplete="email" placeholder="your@email.com" maxlength="150" required></label>
        <label>Subject<select name="subject"><option value="">Select a subject…</option><option>General Inquiry</option><option>Book / Publication</option><option>Ministry / Speaking</option><option>Doctrine / Scripture</option><option>Prayer Request</option><option>Other</option></select></label>
        <label>Message *<textarea name="message" rows="7" placeholder="Write your message here…" maxlength="5000" required></textarea></label>
        <input class="honeypot" type="text" name="website" tabindex="-1" autocomplete="off">
        <button class="button button-primary" type="submit">Send Message →</button>
      </form>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
