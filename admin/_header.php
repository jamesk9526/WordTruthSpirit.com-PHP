<?php
$adminTitle = $adminTitle ?? 'Dashboard';
$currentAdminPage = $currentAdminPage ?? '';
$dedicatedAdminEditor = $dedicatedAdminEditor ?? false;
if (!$dedicatedAdminEditor && adminLoggedIn()) {
  require_once ROOT_PATH . '/includes/updates.php';
  $adminUpdates = databaseUpdates(); $adminUpdateLedger = updateLedger();
  $adminPendingUpdates = array_values(array_diff(array_keys($adminUpdates), array_column($adminUpdateLedger, 'update_key')));
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($adminTitle) ?> | WTS Admin</title>
  <link rel="stylesheet" href="<?= url('assets/styles.css?v=20260826a') ?>">
  <link rel="stylesheet" href="<?= url('assets/commerce-admin.css?v=20260826a') ?>">
  <link rel="stylesheet" href="<?= url('assets/refresh.css?v=20260826c') ?>">
</head>
<body class="admin-body<?= $dedicatedAdminEditor ? ' editor-workspace-body' : '' ?>">
<?php if (adminLoggedIn() && !$dedicatedAdminEditor): ?>
  <aside class="admin-sidebar" id="admin-sidebar">
    <a class="admin-brand" href="<?= url('admin/') ?>"><img src="<?= url('assets/images/spirit-dove.png') ?>" alt=""><span>Word Truth Spirit<small>Secure workspace</small></span></a>
    <img class="admin-winged-sprite" src="<?=url('assets/images/winged-lamp.png')?>" alt="" aria-hidden="true">
    <nav aria-label="Administration">
      <p>Workspace</p>
      <a class="<?= $currentAdminPage === 'dashboard' ? 'active' : '' ?>" href="<?= url('admin/') ?>" <?= $currentAdminPage === 'dashboard' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">▦</span>Dashboard</a>
      <a class="<?= $currentAdminPage === 'posts' ? 'active' : '' ?>" href="<?= url('admin/posts.php') ?>" <?= $currentAdminPage === 'posts' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">✦</span>Blog</a>
      <a class="<?= $currentAdminPage === 'comments' ? 'active' : '' ?>" href="<?= url('admin/comments.php') ?>" <?= $currentAdminPage === 'comments' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">☷</span>Comments</a>
      <a class="<?= $currentAdminPage === 'tags' ? 'active' : '' ?>" href="<?= url('admin/tags.php') ?>" <?= $currentAdminPage === 'tags' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">#</span>Blog tags</a>
      <a class="<?= $currentAdminPage === 'books' ? 'active' : '' ?>" href="<?= url('admin/books.php') ?>" <?= $currentAdminPage === 'books' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">▤</span>Publications</a>
      <a class="<?= $currentAdminPage === 'products' ? 'active' : '' ?>" href="<?= url('admin/products.php') ?>" <?= $currentAdminPage === 'products' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">◇</span>Products &amp; giving</a>
      <p>Readers</p>
      <a class="<?= $currentAdminPage === 'inbox' ? 'active' : '' ?>" href="<?= url('admin/inbox.php') ?>" <?= $currentAdminPage === 'inbox' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">✉</span>Inbox</a>
      <a class="<?= $currentAdminPage === 'conversations' ? 'active' : '' ?>" href="<?= url('admin/conversations.php') ?>" <?= $currentAdminPage === 'conversations' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">◌</span>Conversations</a>
      <a class="<?= $currentAdminPage === 'subscribers' ? 'active' : '' ?>" href="<?= url('admin/subscribers.php') ?>" <?= $currentAdminPage === 'subscribers' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">◎</span>Subscribers</a>
      <a class="<?= $currentAdminPage === 'campaigns' ? 'active' : '' ?>" href="<?= url('admin/campaigns.php') ?>" <?= $currentAdminPage === 'campaigns' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">↗</span>Campaigns</a>
      <p>Site controls</p>
      <a class="<?= $currentAdminPage === 'content' ? 'active' : '' ?>" href="<?= url('admin/content.php') ?>" <?= $currentAdminPage === 'content' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">¶</span>Page content</a>
      <a class="<?= $currentAdminPage === 'ads' ? 'active' : '' ?>" href="<?= url('admin/ads.php') ?>" <?= $currentAdminPage === 'ads' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">▣</span>Ads</a>
      <a class="<?= $currentAdminPage === 'seo' ? 'active' : '' ?>" href="<?= url('admin/seo.php') ?>" <?= $currentAdminPage === 'seo' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">⌕</span>SEO studio</a>
      <a class="<?= $currentAdminPage === 'settings' ? 'active' : '' ?>" href="<?= url('admin/settings.php') ?>" <?= $currentAdminPage === 'settings' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">⚙</span>Promotions &amp; SMTP</a>
      <a class="<?= $currentAdminPage === 'accounts' ? 'active' : '' ?>" href="<?= url('admin/accounts.php') ?>" <?= $currentAdminPage === 'accounts' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">♙</span>Accounts &amp; security</a>
      <a class="<?= $currentAdminPage === 'updates' ? 'active' : '' ?>" href="<?= url('admin/updates.php') ?>" <?= $currentAdminPage === 'updates' ? 'aria-current="page"' : '' ?>><span aria-hidden="true">↻</span>Database updates<?php if(!empty($adminPendingUpdates)):?><b class="admin-nav-badge"><?=count($adminPendingUpdates)?></b><?php endif;?></a>
    </nav>
    <div class="admin-sidebar-bottom"><a href="<?= url() ?>">↗ View public site</a><a href="<?= url('admin/logout.php') ?>">Sign out</a></div>
  </aside>
  <button type="button" class="admin-sidebar-backdrop" aria-label="Close administration navigation"></button>
<?php endif; ?>
<div class="admin-shell<?= $dedicatedAdminEditor ? ' editor-workspace-shell' : '' ?>">
  <?php if (adminLoggedIn() && !$dedicatedAdminEditor): ?><header class="admin-topbar"><button type="button" class="admin-menu-button" aria-controls="admin-sidebar" aria-expanded="false">Menu</button><div class="admin-topbar-context"><img class="admin-topbar-sprite" src="<?=url('assets/images/spirit-dove.png')?>" alt="" aria-hidden="true"><span><?= e($adminTitle) ?></span></div><a class="admin-profile-chip" href="<?=url('admin/accounts.php')?>"><span><?=e(strtoupper(mb_substr((string)($_SESSION['wts_admin_name']??'A'),0,1)))?></span><strong><?= e($_SESSION['wts_admin_name'] ?? 'Administrator') ?></strong></a></header><?php endif; ?>
  <main class="admin-main<?= $dedicatedAdminEditor ? ' editor-workspace-main' : '' ?>">
  <?php if (!empty($adminPendingUpdates)): ?><aside class="admin-update-banner" role="status"><div><p class="kicker">There are updates</p><strong><?=count($adminPendingUpdates)?> new <?=count($adminPendingUpdates) === 1 ? 'improvement is' : 'improvements are'?> ready</strong><p><?php foreach(array_slice($adminPendingUpdates, 0, 3) as $updateKey): ?><span><?=e($adminUpdates[$updateKey][0])?></span><?php endforeach; ?></p></div><a class="button button-primary" href="<?=url('admin/updates.php')?>">See what’s new</a></aside><?php endif; ?>
