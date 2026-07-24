<?php
$adminTitle = $adminTitle ?? 'Dashboard';
$currentAdminPage = $currentAdminPage ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($adminTitle) ?> | WTS Admin</title>
  <link rel="stylesheet" href="<?= url('assets/styles.css') ?>">
</head>
<body class="admin-body">
<?php if (adminLoggedIn()): ?>
  <aside class="admin-sidebar" id="admin-sidebar">
    <a class="admin-brand" href="<?= url('admin/') ?>"><img src="<?= url('assets/images/logo.png') ?>" alt=""><span>Word Truth Spirit<small>Secure workspace</small></span></a>
    <nav aria-label="Administration">
      <p>Workspace</p>
      <a class="<?= $currentAdminPage === 'dashboard' ? 'active' : '' ?>" href="<?= url('admin/') ?>"><span>▦</span>Dashboard</a>
      <a class="<?= $currentAdminPage === 'posts' ? 'active' : '' ?>" href="<?= url('admin/posts.php') ?>"><span>✦</span>Journal</a>
      <a class="<?= $currentAdminPage === 'books' ? 'active' : '' ?>" href="<?= url('admin/books.php') ?>"><span>▤</span>Publications</a>
      <p>Readers</p>
      <a class="<?= $currentAdminPage === 'inbox' ? 'active' : '' ?>" href="<?= url('admin/inbox.php') ?>"><span>✉</span>Inbox</a>
      <a class="<?= $currentAdminPage === 'conversations' ? 'active' : '' ?>" href="<?= url('admin/conversations.php') ?>"><span>◌</span>Conversations</a>
      <a class="<?= $currentAdminPage === 'subscribers' ? 'active' : '' ?>" href="<?= url('admin/subscribers.php') ?>"><span>◎</span>Subscribers</a>
    </nav>
    <div class="admin-sidebar-bottom"><a href="<?= url() ?>">↗ View public site</a><a href="<?= url('admin/logout.php') ?>">Sign out</a></div>
  </aside>
<?php endif; ?>
<div class="admin-shell">
  <?php if (adminLoggedIn()): ?><header class="admin-topbar"><button type="button" class="admin-menu-button" aria-controls="admin-sidebar" aria-expanded="false">Menu</button><span><?= e($adminTitle) ?></span><strong><?= e($_SESSION['wts_admin_name'] ?? 'Administrator') ?></strong></header><?php endif; ?>
  <main class="admin-main">
