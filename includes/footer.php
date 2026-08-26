<?php require_once ROOT_PATH . '/includes/subscription.php'; $subscription=subscriptionSettings(); ?>
<?php if($subscription['enabled']&&$subscription['placements']['bottomBanner']): ?>
<aside class="subscriber-bottom-banner" data-subscriber-banner data-banner-id="<?=e($subscription['bottomBannerId'])?>" data-delay="<?=(int)$subscription['bottomBannerDelaySeconds']?>" aria-hidden="true">
  <button type="button" class="subscriber-banner-close" data-subscriber-banner-close aria-label="Dismiss email signup">×</button>
  <div><strong><?=e($subscription['bottomBannerTitle'])?></strong><span><?=e($subscription['bottomBannerBody'])?></span></div>
  <?php renderSubscriptionForm('bottom-banner','banner','email-signup-banner'); ?>
</aside>
<?php endif; ?>
<div class="site-brand-divider" aria-hidden="true"><img src="<?=url('assets/images/winged-lamp.png')?>" alt=""></div>
<footer class="site-footer">
  <div><h2>Word Truth Spirit</h2><p>Patrick E. Pennington · Author &amp; Teacher</p><p><em>“Verbum et Spiritus Veritatis”</em></p><p>Biblical Defense of Traditional Pentecostalism</p></div>
  <nav aria-label="Footer navigation"><h3>Navigation</h3><a href="<?= url() ?>">Home</a><a href="<?= url('word.php') ?>">Word</a><a href="<?= url('truth.php') ?>">Truth</a><a href="<?= url('spirit.php') ?>">Spirit</a><a href="<?= url('publications.php') ?>">Publications</a><a href="<?= url('blog/') ?>">Blog</a><a href="<?= url('shop/') ?>">Shop &amp; Give</a><a href="<?= url('contact.php') ?>">Contact</a></nav>
  <div><h3>Connect</h3><?php if($subscription['enabled']&&$subscription['placements']['footer']):?><p><?=e($subscription['body'])?></p><?php renderSubscriptionForm('footer','footer','email-signup-footer'); ?><?php else:?><p>Follow for updates on biblical teachings and resources.</p><?php endif;?><a class="footer-button" href="<?= url('contact.php') ?>">Contact Us</a><a class="footer-button" href="<?= url('donate/') ?>">Support the ministry</a></div>
  <small>© <?= date('Y') ?> Patrick E. Pennington. All rights reserved.</small>
</footer>
<script src="<?= url('assets/site.js?v=20260806a') ?>"></script>
<?php if (pushNotificationsConfigured()): ?><script src="<?= url('assets/push.js?v=20260815a') ?>" data-push-public-key="<?= e(pushPublicKey()) ?>" data-push-subscribe-url="<?= url('api/push-subscribe.php') ?>" data-push-service-worker="<?= url('push-service-worker.js') ?>"></script><?php endif; ?>
</body>
</html>
