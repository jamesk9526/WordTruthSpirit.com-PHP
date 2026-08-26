<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/settings.php';

function defaultSiteAds(): array
{
    return [
        [
            'id'=>'spirit-of-truth-top',
            'name'=>'The Spirit of Truth eBook',
            'placement'=>'home_top',
            'enabled'=>true,
            'eyebrow'=>'Featured offer',
            'title'=>'Celebrate 250 with The Spirit of Truth.',
            'body'=>'For a limited time, get the eBook edition for only $2.50.',
            'image'=>'assets/images/book-cover.png',
            'imageAlt'=>'The Spirit of Truth book cover',
            'badge'=>'Limited-time offer',
            'sponsor'=>'Word Truth Spirit',
            'theme'=>'navy',
            'actionLabel'=>'Get the eBook',
            'actionUrl'=>'https://www.amazon.com/dp/B0GCVNK21K?dplnkId=6cf4e3b0-74cc-491f-aa07-04ab89e08491',
            'newWindow'=>true,
            'startsAt'=>'',
            'endsAt'=>'',
            'pageEnabled'=>true,
            'pageSlug'=>'spirit-of-truth-offer',
            'pageContent'=>'Discover a Scripture-grounded defense of traditional Pentecostal faith and practice. This dedicated offer page gives readers a clear place to learn about the promotion before continuing to Amazon.',
            'displayOrder'=>10,
        ],
        [
            'id'=>'bible-memory-sidebar',
            'name'=>'Bible Memory App',
            'placement'=>'home_sidebar',
            'enabled'=>true,
            'eyebrow'=>'Sponsored resource',
            'title'=>'Build a lasting habit of Scripture memory.',
            'body'=>'Try The Bible Memory App and save on Bible Memory Unlimited through Word Truth Spirit.',
            'image'=>'assets/images/bible-memory-app.png',
            'imageAlt'=>'The Bible Memory App',
            'badge'=>'Save 20%',
            'sponsor'=>'The Bible Memory App',
            'theme'=>'gold',
            'actionLabel'=>'Get the offer',
            'actionUrl'=>'https://biblememory.com/promo/wordandspirit20/',
            'newWindow'=>true,
            'startsAt'=>'',
            'endsAt'=>'',
            'pageEnabled'=>true,
            'pageSlug'=>'bible-memory-app',
            'pageContent'=>'The Bible Memory App helps individuals, families, and churches build a durable habit of Scripture memory. Follow the offer link to receive the Word Truth Spirit discount automatically.',
            'displayOrder'=>10,
        ],
    ];
}

function siteAds(): array
{
    $stored = appSetting('site.ads');
    if ($stored === null || $stored === '') return defaultSiteAds();
    $decoded = json_decode($stored,true);
    if (!is_array($decoded)) return defaultSiteAds();
    $defaults = ['sponsor'=>'','theme'=>'navy','newWindow'=>false,'startsAt'=>'','endsAt'=>'','pageEnabled'=>false,'displayOrder'=>10];
    $ads = array_map(function(array $ad)use($defaults):array{$normalized=array_replace($defaults,$ad);if(!in_array($normalized['theme'],['navy','gold','light'],true))$normalized['theme']='navy';return $normalized;},array_values(array_filter($decoded,'is_array')));
    usort($ads,fn(array $left,array $right): int => ((int)($left['displayOrder']??10)) <=> ((int)($right['displayOrder']??10)));
    return $ads;
}

function setSiteAds(array $ads): bool
{
    return setAppSetting('site.ads',(string)json_encode(array_values($ads),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}

function adsForPlacement(string $placement): array
{
    return array_values(array_filter(siteAds(),fn(array $ad): bool => adIsCurrentlyVisible($ad)&&($ad['placement']??'')===$placement));
}

function adIsCurrentlyVisible(array $ad): bool
{
    if (empty($ad['enabled'])) return false;
    $now = time();
    $starts = trim((string)($ad['startsAt']??''));$ends = trim((string)($ad['endsAt']??''));
    if ($starts !== '' && strtotime($starts) > $now) return false;
    if ($ends !== '' && strtotime($ends) < $now) return false;
    return true;
}

function adStatusLabel(array $ad): string
{
    if (empty($ad['enabled'])) return 'Hidden';
    $now=time();$starts=trim((string)($ad['startsAt']??''));$ends=trim((string)($ad['endsAt']??''));
    if($starts!==''&&strtotime($starts)>$now)return 'Scheduled';
    if($ends!==''&&strtotime($ends)<$now)return 'Ended';
    return 'Active';
}

function adAssetUrl(string $asset): string
{
    $asset = trim($asset);
    if (preg_match('#^https://#i', $asset)) return $asset;
    if (preg_match('#^assets/[a-z0-9_./-]+$#i', $asset)) return url($asset);
    return '';
}

function adDestinationUrl(string $destination): string
{
    $destination = trim($destination);
    if (preg_match('#^https?://#i', $destination)) return $destination;
    if (str_starts_with($destination, '/')) return url($destination);
    return url();
}

function adPageUrl(array $ad): string
{
    return url('ads/'.rawurlencode((string)($ad['pageSlug']??'')).'/');
}

function findPublicAdBySlug(string $slug): ?array
{
    foreach(siteAds() as $ad)if(adIsCurrentlyVisible($ad)&&!empty($ad['pageEnabled'])&&($ad['pageSlug']??'')===$slug)return $ad;
    return null;
}

function renderAdCard(array $ad,string $variant='journal'): void
{
    $target=!empty($ad['newWindow'])?' target="_blank"':'';
    $rel=preg_match('#^https?://#i',(string)($ad['actionUrl']??''))?' rel="sponsored noopener"':'';
    $artTarget=empty($ad['pageEnabled'])?$target:'';$artRel=empty($ad['pageEnabled'])?$rel:'';
    ?>
    <article class="ad-card ad-card-<?=e($variant)?> ad-theme-<?=e((string)($ad['theme']??'navy'))?>">
      <?php if($image=adAssetUrl((string)($ad['image']??''))):?><a class="ad-card-art" href="<?=e(!empty($ad['pageEnabled'])?adPageUrl($ad):adDestinationUrl((string)$ad['actionUrl']))?>"<?=$artTarget?><?=$artRel?>><img src="<?=e($image)?>" alt="<?=e((string)($ad['imageAlt']??''))?>"></a><?php endif;?>
      <div class="ad-card-copy"><div class="ad-card-meta"><span><?=e((string)($ad['eyebrow']??'Sponsored'))?></span><?php if(!empty($ad['sponsor'])):?><small>From <?=e((string)$ad['sponsor'])?></small><?php endif;?></div><?php if(!empty($ad['badge'])):?><strong class="ad-card-badge"><?=e((string)$ad['badge'])?></strong><?php endif;?><h2><?=e((string)$ad['title'])?></h2><?php if(!empty($ad['body'])):?><p><?=e((string)$ad['body'])?></p><?php endif;?></div>
      <div class="ad-card-actions"><?php if(!empty($ad['pageEnabled'])):?><a class="home-ad-details" href="<?=e(adPageUrl($ad))?>">View details</a><?php endif;?><a class="button button-primary" href="<?=e(adDestinationUrl((string)$ad['actionUrl']))?>"<?=$target?><?=$rel?>><?=e((string)$ad['actionLabel'])?> →</a></div>
    </article>
    <?php
}
