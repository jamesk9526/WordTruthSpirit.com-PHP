<?php
$activePage = $activePage ?? '';
$pageTitle = $pageTitle ?? 'Word Truth Spirit';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($pageDescription ?? 'Biblical teaching where Scripture and Spirit belong together.') ?>">
  <title><?= e($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Source+Serif+4:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('assets/styles.css') ?>">
</head>
<body>
<a class="skip-link" href="#content">Skip to content</a>
<header class="site-header">
  <a class="brand" href="<?= url() ?>" aria-label="Word Truth Spirit home">
    <img src="<?= url('assets/images/logo.png') ?>" alt="Crest">
    <span><strong>Word Truth Spirit</strong><small>Patrick E. Pennington</small></span>
  </a>
  <button class="menu-button" aria-expanded="false" aria-controls="primary-nav">Menu</button>
  <nav id="primary-nav" aria-label="Primary navigation">
    <?php foreach (['home'=>'Home','word'=>'Word','truth'=>'Truth','spirit'=>'Spirit','blog'=>'Blog','commitments'=>'Commitments','contact'=>'Contact'] as $key => $label): ?>
      <?php $href = $key === 'home' ? url() : ($key === 'blog' ? url('blog/') : url($key . '.php')); ?>
      <a class="<?= $activePage === $key ? 'active' : '' ?>" href="<?= $href ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
</header>
<aside class="announcement" data-announcement>
  <p>Celebrate 250! For a limited time get the ebook, <em>The Spirit of Truth</em> for only $2.50!</p>
  <a href="https://www.amazon.com/dp/B0GCVNK21K" rel="noopener">Get the eBook.</a>
  <button type="button" aria-label="Dismiss announcement">×</button>
</aside>
<div id="content"></div>
