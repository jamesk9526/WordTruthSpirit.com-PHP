<?php
require_once ROOT_PATH . '/includes/settings.php';
require_once ROOT_PATH . '/includes/push.php';
require_once ROOT_PATH . '/includes/analytics.php';
require_once ROOT_PATH . '/includes/seo.php';
require_once ROOT_PATH . '/includes/members.php';
recordPublicPageView();
$experience = siteExperience();
$activePage = $activePage ?? '';
$pageTitle = $pageTitle ?? 'Word Truth Spirit';
$seoOverride = seoPage(seoPageKey());
if (!empty($seoOverride['meta_title'])) $pageTitle = $seoOverride['meta_title'];
if (!empty($seoOverride['meta_description'])) $pageDescription = $seoOverride['meta_description'];
$canonicalPath = $canonicalPath ?? ltrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
$canonicalUrl = seoAbsoluteUrl($canonicalPath);
$socialImage = $socialImage ?? url('assets/images/logo.png');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($pageDescription ?? 'Biblical teaching where Scripture and Spirit belong together.') ?>">
  <link rel="canonical" href="<?=e($canonicalUrl)?>">
  <meta property="og:type" content="<?=e($openGraphType ?? 'website')?>">
  <meta property="og:site_name" content="Word Truth Spirit">
  <meta property="og:title" content="<?= e($pageTitle) ?>"><meta property="og:description" content="<?= e($pageDescription ?? '') ?>"><meta property="og:url" content="<?= e($canonicalUrl) ?>"><meta property="og:image" content="<?= e(preg_match('#^https?://#i', $socialImage) ? $socialImage : seoAbsoluteUrl($socialImage)) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <?php if($gscVerification=appSetting('seo.googleVerification','')):?><meta name="google-site-verification" content="<?=e($gscVerification)?>"><?php endif;?>
  <title><?= e($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Source+Serif+4:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('assets/styles.css?v=20260806b') ?>">
  <link rel="stylesheet" href="<?= url('assets/commerce-admin.css?v=20260826a') ?>">
  <link rel="stylesheet" href="<?= url('assets/refresh.css?v=20260826c') ?>">
  <?php if(!empty($structuredData)): ?><script type="application/ld+json"><?=json_encode($structuredData, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?></script><?php endif; ?>
</head>
<body>
<a class="skip-link" href="#content">Skip to content</a>
<header class="site-header">
  <a class="brand" href="<?= url() ?>" aria-label="Word Truth Spirit home">
    <img src="<?= url('assets/images/logo.png') ?>" alt="Crest">
    <span><strong>Word Truth Spirit</strong><small>Patrick E. Pennington</small></span>
    <img class="header-brand-sprite" src="<?= url('assets/images/spirit-dove.png') ?>" alt="" aria-hidden="true">
  </a>
  <button class="menu-button" aria-expanded="false" aria-controls="primary-nav">Menu</button>
  <nav id="primary-nav" aria-label="Primary navigation">
    <?php foreach (['home'=>'Home','word'=>'Word','truth'=>'Truth','spirit'=>'Spirit','publications'=>'Publications','blog'=>'Blog','shop'=>'Shop & Give','commitments'=>'Commitments','contact'=>'Contact'] as $key => $label): ?>
      <?php $href = $key === 'home' ? url() : (in_array($key,['blog','shop'],true) ? url($key . '/') : url($key . '.php')); ?>
      <a class="<?= $activePage === $key ? 'active' : '' ?>" href="<?= $href ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
  <a class="site-account-link" href="<?=url(memberLoggedIn()?'members/':'members/login.php')?>"><?=memberLoggedIn()?'My account':'Sign in'?></a>
</header>
<?php if (!empty($experience['announcement']['enabled'])): ?><aside class="announcement" data-announcement>
  <p><?= e($experience['announcement']['message']) ?></p><a href="<?= e($experience['announcement']['actionUrl']) ?>" rel="noopener"><?= e($experience['announcement']['actionLabel']) ?></a><button type="button" aria-label="Dismiss announcement">×</button>
</aside><?php endif; ?>
<?php if (!empty($experience['popup']['enabled'])): ?><aside class="promotion-popup" data-promotion-popup data-delay="<?= (int) $experience['popup']['delaySeconds'] ?>" data-popup-id="<?= e($experience['popup']['id']) ?>" aria-hidden="true"><div class="promotion-popup-card"><button type="button" data-popup-close aria-label="Close offer">×</button><p class="kicker"><?= e($experience['popup']['eyebrow']) ?></p><h2><?= e($experience['popup']['title']) ?></h2><p><?= e($experience['popup']['body']) ?></p><a class="button button-primary" href="<?= e($experience['popup']['actionUrl']) ?>"><?= e($experience['popup']['actionLabel']) ?> →</a></div></aside><?php endif; ?>
<div id="content"></div>
