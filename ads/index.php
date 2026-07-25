<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
require ROOT_PATH.'/includes/ads.php';
$ad=findPublicAdBySlug((string)($_GET['slug']??''));
if(!$ad){http_response_code(404);$pageTitle='Advertisement not found | Word Truth Spirit';}
else{$pageTitle=$ad['title'].' | Word Truth Spirit';$pageDescription=$ad['body']??'';}
require ROOT_PATH.'/includes/header.php';
?>
<main class="ad-detail-page">
<?php if(!$ad):?>
  <section class="page-hero"><p class="kicker">Advertisement</p><h1>This offer is not available.</h1><p>The ad may have ended or its individual page may no longer be published.</p><a class="button button-primary" href="<?=url()?>">Return home</a></section>
<?php else:?>
  <article class="ad-detail-card ad-theme-<?=e((string)($ad['theme']??'navy'))?>">
    <?php if(!empty($ad['image'])):?><figure><img src="<?=e(adAssetUrl((string)$ad['image']))?>" alt="<?=e((string)($ad['imageAlt']??''))?>"></figure><?php endif;?>
    <div><div class="ad-detail-meta"><p class="kicker"><?=e((string)($ad['eyebrow']??'Advertisement'))?></p><?php if(!empty($ad['sponsor'])):?><span>Presented by <?=e((string)$ad['sponsor'])?></span><?php endif;?></div><?php if(!empty($ad['badge'])):?><span class="ad-detail-badge"><?=e((string)$ad['badge'])?></span><?php endif;?><h1><?=e((string)$ad['title'])?></h1><?php if(!empty($ad['body'])):?><p class="ad-detail-lead"><?=e((string)$ad['body'])?></p><?php endif;?><?php if(!empty($ad['pageContent'])):?><div class="ad-detail-content"><?=articleHtml((string)$ad['pageContent'])?></div><?php endif;?><div class="ad-detail-cta"><a class="button button-primary" href="<?=e(adDestinationUrl((string)$ad['actionUrl']))?>" <?=!empty($ad['newWindow'])?'target="_blank"':''?> rel="sponsored noopener"><?=e((string)$ad['actionLabel'])?> →</a><small>Sponsored resource · You will continue to the advertiser’s destination.</small></div></div>
  </article>
<?php endif;?>
</main>
<?php require ROOT_PATH.'/includes/footer.php';?>
