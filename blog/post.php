<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/posts.php';
require ROOT_PATH . '/includes/seo.php';
require ROOT_PATH . '/includes/comments.php';
require ROOT_PATH . '/includes/subscription.php';
require ROOT_PATH . '/includes/ads.php';
require ROOT_PATH . '/includes/members.php';
$post = findPost((string) ($_GET['slug'] ?? ''));
if (!$post) { http_response_code(404); $pageTitle = 'Reflection not found'; } else { $pageTitle = (!empty($post['meta_title']) ? $post['meta_title'] : $post['title']) . ' | Word Truth Spirit'; $pageDescription = !empty($post['meta_description']) ? $post['meta_description'] : $post['excerpt']; $canonicalPath='blog/'.rawurlencode((string)$post['slug']).'/'; $openGraphType='article'; $socialImage=!empty($post['cover_image'])?(string)$post['cover_image']:url('assets/images/logo.png'); $structuredData=['@context'=>'https://schema.org','@type'=>'Article','headline'=>(string)$post['title'],'description'=>(string)$pageDescription,'datePublished'=>date(DATE_ATOM,strtotime((string)$post['published_at'])),'dateModified'=>date(DATE_ATOM,strtotime((string)$post['published_at'])),'mainEntityOfPage'=>seoAbsoluteUrl($canonicalPath),'author'=>['@type'=>'Person','name'=>(string)$post['author']],'publisher'=>['@type'=>'Organization','name'=>'Word Truth Spirit']]; if(!empty($post['cover_image']))$structuredData['image']=seoAbsoluteUrl((string)$post['cover_image']); }
$comments=$post?approvedComments($post['slug']):[];
$commentThreads=threadedComments($comments);
$commentToken=commentInteractionToken();
$allPosts=allPosts();$relatedPosts=$post?array_slice(array_values(array_filter($allPosts,fn(array $candidate):bool=>$candidate['slug']!==$post['slug']&&$candidate['category']===$post['category'])),0,3):[];
$postIndex=$post?array_search($post['slug'],array_column($allPosts,'slug'),true):false;
$newerPost=$postIndex!==false&&$postIndex>0?$allPosts[$postIndex-1]:null;$olderPost=$postIndex!==false&&isset($allPosts[$postIndex+1])?$allPosts[$postIndex+1]:null;
$articleAds=adsForPlacement('article_end');
$assetUrl = static function (string $path): string { return preg_match('#^https?://#i', $path) ? $path : url($path); };
function renderPublicComment(array $comment, string $token, string $slug): void
{
    $likes=(int)($comment['likes_count']??0);$liked=!empty($comment['viewer_liked']);$depth=(int)($comment['depth']??0);
    ?>
    <article class="public-comment comment-depth-<?=$depth?>" id="comment-<?=(int)$comment['id']?>" data-comment-card data-comment-id="<?=(int)$comment['id']?>" data-comment-date="<?=e((string)$comment['created_at'])?>" data-comment-likes="<?=$likes?>">
      <header><div class="comment-author-mark"><?=e(mb_strtoupper(mb_substr((string)$comment['name'],0,1)))?></div><div><?php if(!empty($comment['profile_slug'])):?><strong><a href="<?=url('members/profile.php?u='.urlencode((string)$comment['profile_slug']))?>"><?=e((string)$comment['name'])?></a></strong><?php else:?><strong><?=e((string)$comment['name'])?></strong><?php endif;?><small><?=date('F j, Y',strtotime((string)$comment['created_at']))?><?=!empty($comment['updated_at'])?' · edited':''?></small></div></header>
      <p><?=nl2br(e((string)$comment['body']))?></p>
      <footer class="comment-actions">
        <button type="button" class="comment-like<?=$liked?' liked':''?>" data-comment-like data-comment-id="<?=(int)$comment['id']?>" data-comment-token="<?=e($token)?>" aria-pressed="<?=$liked?'true':'false'?>"><span aria-hidden="true">♥</span> <span data-like-label><?=$liked?'Liked':'Like'?></span> <strong data-like-count><?=$likes?></strong></button>
        <button type="button" data-comment-reply data-comment-id="<?=(int)$comment['id']?>" data-comment-name="<?=e((string)$comment['name'])?>">Reply</button>
        <button type="button" data-comment-report data-comment-id="<?=(int)$comment['id']?>" data-comment-token="<?=e($token)?>">Report</button>
      </footer>
      <?php if(!empty($comment['replies'])):?><div class="comment-replies"><?php foreach($comment['replies'] as $reply)renderPublicComment($reply,$token,$slug);?></div><?php endif;?>
    </article>
    <?php
}
$activePage = 'blog';
require ROOT_PATH . '/includes/header.php';
?>
<main class="post-page">
<?php if (!$post): ?>
  <section class="page-hero"><h1>Reflection not found.</h1><p><a href="<?= url('blog/') ?>">Return to the blog</a></p></section>
<?php else: ?>
  <div class="reading-progress" aria-hidden="true"><span data-reading-progress></span></div>
  <article class="author-article" data-post-engagement data-post-slug="<?=e((string)$post['slug'])?>" data-engagement-url="<?=url('api/post-engagement.php')?>">
    <header class="author-post-header"><p class="kicker"><?= e($post['category']) ?> · Reflection</p><h1><?= e($post['title']) ?></h1><p class="post-deck"><?= e($post['excerpt']) ?></p><div class="author-byline"><span class="author-initial"><?= e(mb_strtoupper(mb_substr($post['author'], 0, 1))) ?></span><p><strong><?= e($post['author']) ?></strong><small><?= date('F j, Y', strtotime($post['published_at'])) ?> · <?= (int) $post['reading_minutes'] ?> min read</small></p></div></header>
    <?php if (!empty($post['cover_image'])): ?><figure class="post-cover"><img src="<?= e($assetUrl((string)$post['cover_image'])) ?>" alt=""><figcaption><?= e($post['title']) ?></figcaption></figure><?php endif; ?>
    <?php if (!empty($post['tags'])): ?><div class="tag-list"><?php foreach (array_filter(array_map('trim', explode(',', $post['tags']))) as $tag): ?><span>#<?= e($tag) ?></span><?php endforeach; ?></div><?php endif; ?>
    <?php if (!empty($post['audio_url'])): ?><section class="article-audio" aria-labelledby="listen-title"><div><p class="kicker">Listen</p><h2 id="listen-title">Audio version</h2><p>Prefer to listen? Play the narrated reflection below.</p></div><audio controls preload="metadata"><source src="<?=e($assetUrl((string)$post['audio_url']))?>">Your browser does not support audio playback. <a href="<?=e($assetUrl((string)$post['audio_url']))?>">Download the audio version.</a></audio></section><?php endif; ?>
    <div class="post-body"><?= articleHtml($post['body']) ?><blockquote>“Sanctify them through thy truth: thy word is truth.”<cite>— John 17:17</cite></blockquote></div>
    <footer class="article-footer"><div class="author-signoff"><span><?= e(mb_strtoupper(mb_substr($post['author'], 0, 1))) ?></span><p>Written for readers seeking to hold fast to both the Word and the Spirit.</p></div><div><button class="button button-outline" type="button" data-share-article data-share-title="<?=e((string)$post['title'])?>">Share</button> <button class="button button-outline" type="button" data-copy-link>Copy link</button> <a class="button button-outline" href="<?= url('blog/') ?>">← Blog</a></div></footer>
    <?php $subscription=subscriptionSettings(); if($subscription['enabled']&&$subscription['placements']['articleEnd']): ?>
    <section class="article-subscribe"><div><p class="kicker"><?=e($subscription['eyebrow'])?></p><h2><?=e($subscription['title'])?></h2><p><?=e($subscription['body'])?></p></div><?php renderSubscriptionForm('reflection-end','default','email-signup-article'); ?></section>
    <?php endif; ?>
    <?php if($articleAds):?><section class="article-ad-zone" aria-label="Sponsored resources"><header><span>Continue exploring</span><small>Sponsored</small></header><?php foreach($articleAds as $ad)renderAdCard($ad,'article');?></section><?php endif;?>
    <?php if($newerPost||$olderPost):?><nav class="post-navigation" aria-label="More reflections"><?php if($newerPost):?><a href="<?=url('blog/post.php?slug='.urlencode((string)$newerPost['slug']))?>"><small>Newer reflection</small><strong>← <?=e((string)$newerPost['title'])?></strong></a><?php else:?><span></span><?php endif;?><?php if($olderPost):?><a href="<?=url('blog/post.php?slug='.urlencode((string)$olderPost['slug']))?>"><small>Older reflection</small><strong><?=e((string)$olderPost['title'])?> →</strong></a><?php endif;?></nav><?php endif;?>
    <?php if($relatedPosts):?><section class="related-reflections"><header><p class="kicker">Keep reading</p><h2>Related reflections</h2></header><div><?php foreach($relatedPosts as $related):?><article><span><?=e((string)$related['category'])?></span><h3><a href="<?=url('blog/post.php?slug='.urlencode((string)$related['slug']))?>"><?=e((string)$related['title'])?></a></h3><p><?=e((string)$related['excerpt'])?></p><a href="<?=url('blog/post.php?slug='.urlencode((string)$related['slug']))?>">Read reflection →</a></article><?php endforeach;?></div></section><?php endif;?>
    <section class="comments-section" id="comments" data-comment-community data-reaction-url="<?=url('api/comment-reaction.php')?>" data-report-url="<?=url('api/comment-report.php')?>">
      <div class="comments-heading"><div><p class="kicker">Reader responses</p><h2>Join the conversation <span><?=count($comments)?></span></h2></div><?php if(count($commentThreads)>1):?><label>Sort discussion<select data-comment-sort><option value="oldest">Oldest first</option><option value="newest">Newest first</option><option value="popular">Most liked</option></select></label><?php endif;?></div>
      <?php if(($_GET['comment']??'')==='pending'):?><p class="notice success">Thank you. Your comment is awaiting moderation.</p><?php elseif(($_GET['comment']??'')==='reply-pending'):?><p class="notice success">Thank you. Your reply is awaiting moderation.</p><?php elseif(($_GET['comment']??'')==='rate'):?><p class="notice error">You have submitted several responses recently. Please wait a few minutes before posting again.</p><?php elseif(($_GET['comment']??'')==='duplicate'):?><p class="notice error">That response was already received.</p><?php elseif(($_GET['comment']??'')==='blocked'):?><p class="notice error">This response could not be accepted.</p><?php elseif(($_GET['comment']??'')==='closed'):?><p class="notice error">Comments are closed for this reflection.</p><?php elseif(($_GET['comment']??'')==='error'):?><p class="notice error">Please complete the comment form and try again.</p><?php endif;?>
      <div class="comment-thread" data-comment-thread><?php foreach($commentThreads as $comment)renderPublicComment($comment,$commentToken,$post['slug']);?></div>
      <?php if(!$comments):?><div class="comment-empty"><span>✦</span><h3>Begin the discussion</h3><p>Share a thoughtful response or question about this reflection.</p></div><?php endif;?>
      <?php if(commentsEnabledForPost($post)):?><form class="comment-form" action="<?=url('api/comment.php')?>" method="post" data-comment-form>
        <input type="hidden" name="slug" value="<?=e($post['slug'])?>"><input type="hidden" name="comment_token" value="<?=e($commentToken)?>"><input type="hidden" name="parent_id" value="0" data-comment-parent>
        <div class="comment-replying" data-comment-replying hidden><span>Replying to <strong data-comment-reply-name></strong></span><button type="button" data-comment-reply-cancel>Cancel reply</button></div>
        <?php $signedInMember=currentMember(); if($signedInMember):?><div class="comment-member-note">Commenting as <strong><?=e((string)$signedInMember['display_name'])?></strong> · <a href="<?=url('members/')">Edit profile</a></div><?php else:?><div class="comment-form-grid"><label>Name<input name="name" maxlength="120" autocomplete="name" required></label><label>Email <small>(not published)</small><input type="email" name="email" maxlength="190" autocomplete="email" required></label></div><p class="comment-account-prompt">Want a linked profile? <a href="<?=url('members/register.php')?>">Create a reader account</a>.</p><?php endif;?>
        <label>Your response<textarea name="body" rows="5" maxlength="4000" placeholder="Add to the conversation with clarity and grace." required></textarea></label><input class="honeypot" name="website" tabindex="-1" autocomplete="off">
        <div class="comment-form-footer"><small>Responses are reviewed before publication. Please address the ideas, not the person.</small><button class="button button-primary">Submit for review</button></div>
      </form><?php else:?><div class="comment-closed"><strong>Discussion closed</strong><p>Existing reader responses remain available, but this reflection is no longer accepting new comments.</p></div><?php endif;?>
    </section>
  </article>
<?php endif; ?>
</main>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
