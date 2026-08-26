<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
requireAdmin();
require ROOT_PATH . '/includes/ads.php';

function validAdDateTime(string $value): bool
{
    if ($value === '') return true;
    $date = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    return $date instanceof DateTime && $date->format('Y-m-d\TH:i') === $value;
}

function adEditorValue(array $ad, string $key, string $fallback = ''): string
{
    $value = $ad[$key] ?? $fallback;
    return is_scalar($value) ? (string) $value : $fallback;
}

function adEditorDateTimeValue(array $ad, string $key): string
{
    $value = trim(adEditorValue($ad, $key));
    $timestamp = $value === '' ? false : strtotime($value);
    return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
}

$ads = siteAds();
$error = '';
$saved = isset($_GET['saved']);
$editingId = (string) ($_GET['edit'] ?? '');
$editingRequested = $editingId !== '' && $editingId !== 'new';
$editingFound = !$editingRequested;
$blank = [
    'id'=>'','name'=>'','placement'=>'home_sidebar','enabled'=>true,
    'eyebrow'=>'Sponsored resource','title'=>'','body'=>'','image'=>'','imageAlt'=>'',
    'badge'=>'','sponsor'=>'','theme'=>'navy','actionLabel'=>'Learn more','actionUrl'=>'',
    'newWindow'=>true,'startsAt'=>'','endsAt'=>'','pageEnabled'=>false,'archived'=>false,
    'pageSlug'=>'','pageContent'=>'','displayOrder'=>10,
];
$editing = $blank;
foreach ($ads as $ad) {
    if (($ad['id'] ?? '') === $editingId) { $editing = array_replace($blank, $ad); $editingFound = true; break; }
}
if ($editingRequested && !$editingFound) $error = 'That ad could not be found. Choose one from the list or create a new ad.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'delete') {
        $deleteId = (string) ($_POST['id'] ?? '');
        $before = count($ads);
        $ads = array_values(array_filter($ads, fn(array $ad): bool => ($ad['id'] ?? '') !== $deleteId));
        if ($deleteId === '' || count($ads) === $before) $error = 'The ad to delete could not be found.';
        elseif (!setSiteAds($ads)) $error = 'Could not delete the ad. Confirm the database is connected.';
        else { header('Location:' . url('admin/ads.php?saved=1')); exit; }
    } elseif ($action === 'toggle') {
        $toggleId = (string) ($_POST['id'] ?? '');
        $toggled = false;
        foreach ($ads as &$ad) {
            if (($ad['id'] ?? '') === $toggleId) { $ad['enabled'] = empty($ad['enabled']); $toggled = true; break; }
        }
        unset($ad);
        if (!$toggled) $error = 'The ad to update could not be found.';
        elseif (!setSiteAds($ads)) $error = 'Could not update the ad.';
        else { header('Location:' . url('admin/ads.php?saved=1')); exit; }
    } elseif ($action === 'archive') {
        $archiveId = (string) ($_POST['id'] ?? ''); $changed = false;
        foreach ($ads as &$ad) {
            if (($ad['id'] ?? '') === $archiveId) { $ad['archived'] = empty($ad['archived']); if (!empty($ad['archived'])) $ad['enabled'] = false; $changed = true; break; }
        }
        unset($ad);
        if (!$changed) $error = 'The ad to archive could not be found.';
        elseif (!setSiteAds($ads)) $error = 'Could not archive the ad.';
        else { header('Location:' . url('admin/ads.php?saved=1')); exit; }
    } elseif ($action === 'duplicate') {
        $copy = null;
        $duplicateId = (string) ($_POST['id'] ?? '');
        foreach ($ads as $ad) {
            if (($ad['id'] ?? '') === $duplicateId) {
                $copy = $ad;
                $copy['id'] = 'ad-' . bin2hex(random_bytes(5));
                $copy['name'] = trim((string) ($copy['name'] ?? 'Ad')) . ' copy';
                $copy['enabled'] = false;
                $copy['archived'] = false;
                $copy['pageEnabled'] = false;
                $copy['pageSlug'] = '';
                $ads[] = $copy;
                break;
            }
        }
        if (!$copy) $error = 'The ad to duplicate could not be found.';
        elseif (!setSiteAds($ads)) $error = 'Could not duplicate the ad.';
        else { header('Location:' . url('admin/ads.php?edit=' . rawurlencode((string) $copy['id']) . '&saved=1#ad-editor')); exit; }
    } else {
        $id = preg_replace('/[^a-z0-9-]+/', '-', strtolower(trim((string) ($_POST['id'] ?? '')))) ?: 'ad-' . bin2hex(random_bytes(5));
        $image = trim((string) ($_POST['image'] ?? ''));
        if ($image !== '' && !preg_match('#^https://#i', $image) && !preg_match('#^assets/[a-z0-9_./-]+$#i', $image)) {
            $error = 'Use an HTTPS image URL or a site path beginning with assets/.';
        }
        if (!$error && isset($_FILES['image_upload']) && ($_FILES['image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['image_upload'];
            if ($upload['error'] !== UPLOAD_ERR_OK) $error = 'The image upload did not complete.';
            elseif ((int) $upload['size'] > 5 * 1024 * 1024) $error = 'Ad images must be 5 MB or smaller.';
            else {
                $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
                $mime = $finfo ? finfo_file($finfo, (string) $upload['tmp_name']) : (function_exists('mime_content_type') ? mime_content_type((string) $upload['tmp_name']) : false);
                if ($finfo) finfo_close($finfo);
                $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                if (!isset($extensions[$mime])) $error = 'Upload a JPG, PNG, WebP, or GIF image.';
                else {
                    $directory = ROOT_PATH . '/assets/uploads/ads';
                    if (is_dir($directory) || mkdir($directory, 0755, true)) {
                        $filename = 'ad-' . date('Ymd') . '-' . bin2hex(random_bytes(5)) . '.' . $extensions[$mime];
                        if (move_uploaded_file((string) $upload['tmp_name'], $directory . '/' . $filename)) $image = 'assets/uploads/ads/' . $filename;
                        else $error = 'The uploaded image could not be saved.';
                    } else $error = 'The ad upload folder could not be created.';
                }
            }
        }
        $placement = in_array($_POST['placement'] ?? '', ['home_top','home_sidebar','journal_top','article_end'], true) ? (string) $_POST['placement'] : 'home_sidebar';
        $actionUrl = trim((string) ($_POST['action_url'] ?? ''));
        $pageEnabled = isset($_POST['page_enabled']);
        $pageSlug = trim((string) ($_POST['page_slug'] ?? ''));
        $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAt = trim((string) ($_POST['ends_at'] ?? ''));
        $values = [
            'id'=>$id,'name'=>trim((string) ($_POST['name'] ?? '')),'placement'=>$placement,
            'enabled'=>isset($_POST['enabled']),'eyebrow'=>trim((string) ($_POST['eyebrow'] ?? '')),
            'title'=>trim((string) ($_POST['title'] ?? '')),'body'=>trim((string) ($_POST['body'] ?? '')),
            'image'=>$image,'imageAlt'=>trim((string) ($_POST['image_alt'] ?? '')),
            'badge'=>trim((string) ($_POST['badge'] ?? '')),'sponsor'=>trim((string) ($_POST['sponsor'] ?? '')),
            'theme'=>in_array($_POST['theme'] ?? '', ['navy','gold','light'], true) ? (string) $_POST['theme'] : 'navy',
            'actionLabel'=>trim((string) ($_POST['action_label'] ?? '')),'actionUrl'=>$actionUrl,
            'newWindow'=>isset($_POST['new_window']),'startsAt'=>$startsAt,'endsAt'=>$endsAt,'archived'=>isset($_POST['archived']),
            'pageEnabled'=>$pageEnabled,'pageSlug'=>$pageSlug,
            'pageContent'=>trim((string) ($_POST['page_content'] ?? '')),
            'displayOrder'=>max(0, min(999, (int) ($_POST['display_order'] ?? 10))),
        ];
        if (!$error && (!$values['name'] || !$values['title'] || !$values['actionLabel'] || !$values['actionUrl'])) $error = 'Name, headline, button label, and destination URL are required.';
        elseif (!$error && !preg_match('#^(https?://|/)#i', $actionUrl)) $error = 'Use a full HTTP/HTTPS destination URL or a site path beginning with /.';
        elseif (!$error && !validAdDateTime($startsAt)) $error = 'Enter a valid start date and time.';
        elseif (!$error && !validAdDateTime($endsAt)) $error = 'Enter a valid end date and time.';
        elseif (!$error && $startsAt !== '' && $endsAt !== '' && strtotime($endsAt) <= strtotime($startsAt)) $error = 'The end date must be later than the start date.';
        elseif (!$error && $pageEnabled && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $pageSlug)) $error = 'The individual page URL must use lowercase letters, numbers, and single hyphens.';
        if (!$error && $pageEnabled) {
            foreach ($ads as $ad) if (($ad['id'] ?? '') !== $id && !empty($ad['pageEnabled']) && ($ad['pageSlug'] ?? '') === $pageSlug) { $error = 'That individual ad-page URL is already in use.'; break; }
        }
        if (!$error) {
            $replaced = false;
            foreach ($ads as $index => $ad) if (($ad['id'] ?? '') === $id) { $ads[$index] = $values; $replaced = true; break; }
            if (!$replaced) $ads[] = $values;
            if (!setSiteAds($ads)) $error = 'Could not save the ad. Confirm the database is connected.';
            else { header('Location:' . url('admin/ads.php?saved=1')); exit; }
        }
        $editing = array_replace($blank, $values);
    }
}

$activeAds = count(array_filter($ads, fn(array $ad): bool => adStatusLabel($ad) === 'Active'));
$scheduledAds = count(array_filter($ads, fn(array $ad): bool => adStatusLabel($ad) === 'Scheduled'));
$placementLabels = ['home_top'=>'Homepage top','home_sidebar'=>'Homepage sidebar','journal_top'=>'Blog banner','article_end'=>'End of blog articles'];
$adminTitle = 'Ads';
$currentAdminPage = 'ads';
require __DIR__ . '/_header.php';
?>
<header class="admin-title">
  <div><p class="kicker">Site revenue &amp; promotions</p><h1>Ad manager</h1><p>Plan, preview, schedule, and publish partner offers without leaving the administration workspace.</p></div>
  <a class="button button-primary" href="<?=url('admin/ads.php?edit=new#ad-editor')?>">Create ad</a>
</header>
<?php if ($saved): ?><p class="notice success">Ad settings saved.</p><?php endif; ?>
<?php if ($error && !$editing['id']): ?><p class="notice error"><?=e($error)?></p><?php endif; ?>
<section class="ad-workspace-stats"><div><strong><?=count($ads)?></strong><span>Total ads</span></div><div><strong><?=$activeAds?></strong><span>Live now</span></div><div><strong><?=$scheduledAds?></strong><span>Scheduled</span></div><div><strong><?=count(array_filter($ads,fn(array $ad): bool => !empty($ad['pageEnabled'])))?></strong><span>Detail pages</span></div></section>
<div class="ads-admin-layout">
  <section class="admin-panel ads-list">
    <header class="ads-list-heading"><div><p class="kicker">Manage placements</p><h2>Your ads</h2></div><span><?=count($ads)?> <?=count($ads) === 1 ? 'ad' : 'ads'?></span></header>
    <div class="ads-filter-bar" data-ad-filters><input type="search" placeholder="Search ads" aria-label="Search ads" data-ad-search><select aria-label="Filter ads by placement" data-ad-placement><option value="">All placements</option><?php foreach($placementLabels as $key=>$label): ?><option value="<?=$key?>"><?=e($label)?></option><?php endforeach; ?></select><select aria-label="Filter ads by status" data-ad-status><option value="">Any status</option><option value="active">Live</option><option value="scheduled">Scheduled</option><option value="hidden">Hidden</option><option value="archived">Archived</option><option value="ended">Ended</option></select></div>
    <p class="ad-manager-tip">Tip: duplicate a finished campaign to preserve its creative and schedule the new version separately.</p>
    <div data-ad-list>
    <?php foreach ($ads as $ad): $adId=(string)($ad['id']??''); $status=strtolower(adStatusLabel($ad)); $isSelected=$editingId!==''&&$editingId!=='new'&&$editingId===$adId; ?>
      <article class="<?=$isSelected?'selected':''?>" data-ad-row data-ad-placement="<?=e(adEditorValue($ad,'placement'))?>" data-ad-status="<?=e($status)?>" data-ad-search="<?=e(mb_strtolower(adEditorValue($ad,'name').' '.adEditorValue($ad,'title').' '.adEditorValue($ad,'sponsor')))?>">
        <div class="ads-list-image"><?php if($image=adAssetUrl((string)($ad['image']??''))): ?><img src="<?=e($image)?>" alt=""><?php else: ?><span>Ad</span><?php endif; ?></div>
        <div class="ads-list-copy"><small><?=e($placementLabels[$ad['placement']??'']??'Placement')?> · Order <?=(int)($ad['displayOrder']??10)?><?=!empty($ad['pageEnabled'])?' · Detail page':''?></small><h3><?=e($ad['name']??'Untitled ad')?></h3><p><?=e($ad['title']??'')?></p><span class="status-pill status-<?=e($status)?>"><?=e(adStatusLabel($ad))?></span></div>
        <div class="ads-list-actions"><div class="ads-list-primary-actions"><?php if(!empty($ad['pageEnabled'])&&!empty($ad['enabled'])&&empty($ad['archived'])): ?><a class="button-link" href="<?=e(adPageUrl($ad))?>" target="_blank" rel="noopener">View</a><?php endif; ?><a class="button-link" href="<?=url('admin/ads.php?edit='.rawurlencode($adId).'#ad-editor')?>">Edit</a></div><div class="ads-list-secondary-actions"><form method="post" class="ad-row-form"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="id" value="<?=e($adId)?>"><button name="action" value="toggle" <?=!empty($ad['archived'])?'disabled title="Restore before enabling"':''?>><?=!empty($ad['enabled'])?'Hide':'Enable'?></button><button name="action" value="archive"><?=!empty($ad['archived'])?'Restore':'Archive'?></button><button name="action" value="duplicate">Duplicate</button></form><form method="post" class="ad-delete-form" onsubmit="return confirm('Delete this ad? This cannot be undone.')"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="id" value="<?=e($adId)?>"><button class="danger-link" type="submit" name="action" value="delete">Delete</button></form></div></div>
      </article>
    <?php endforeach; ?>
    </div>
    <?php if(!$ads): ?><div class="ads-empty-state"><span>✦</span><h3>No ads yet</h3><p>Create your first partner or ministry offer.</p><a class="button button-outline" href="<?=url('admin/ads.php?edit=new#ad-editor')?>">Create an ad</a></div><?php endif; ?>
    <p class="ads-filter-empty" data-ad-filter-empty hidden>No ads match those filters.</p>
  </section>
  <section class="admin-panel ad-editor" id="ad-editor">
    <div class="ad-editor-heading"><div><p class="kicker">Creative workspace</p><h2><?=$editing['id']?'Edit ad':'Create an ad'?></h2></div><span data-ad-draft-status>Preview updates as you type</span></div>
    <?php if($error && $editing['id']): ?><p class="notice error"><?=e($error)?></p><?php endif; ?>
    <aside class="ad-live-preview ad-theme-<?=e((string)$editing['theme'])?>" data-ad-preview><span class="ad-preview-label">Live preview</span><?php if($image=adAssetUrl((string)$editing['image']): ?><img data-preview-image src="<?=e($image)?>" alt="" ><?php else: ?><img data-preview-image src="" alt="" hidden><?php endif; ?><div data-preview-badge><?=e((string)$editing['badge'])?></div><small data-preview-eyebrow><?=e((string)$editing['eyebrow'])?></small><h3 data-preview-title><?=e((string)($editing['title']?:'Your ad headline'))?></h3><p data-preview-body><?=e((string)($editing['body']?:'Your supporting message will appear here.'))?></p><button type="button" data-preview-action><?=e((string)$editing['actionLabel'])?> →</button></aside>
    <form method="post" enctype="multipart/form-data" data-ad-editor-form data-ad-draft-key="<?=e($editing['id']?:'new')?>" data-base-url="<?=e(url())?>"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="id" value="<?=e((string)$editing['id'])?>">
      <div class="ad-form-sections">
        <section class="ad-form-section"><h3>Placement &amp; visibility</h3><p>Choose where this offer appears and when readers can see it.</p><div class="ad-state-controls"><label class="toggle-label"><input type="checkbox" name="enabled" <?=$editing['enabled']?'checked':''?> <?=!empty($editing['archived'])?'disabled':''?>> Display this ad publicly</label><label class="toggle-label"><input type="checkbox" name="archived" <?=!empty($editing['archived'])?'checked':''?>> Archive this ad and remove it from public placements</label></div><div class="admin-form-grid"><label>Internal name<input name="name" value="<?=e((string)$editing['name'])?>" placeholder="Summer book offer" required data-ad-name></label><label>Placement<select name="placement"><option value="home_top" <?=$editing['placement']==='home_top'?'selected':''?>>Homepage top banner</option><option value="home_sidebar" <?=$editing['placement']==='home_sidebar'?'selected':''?>>Homepage sidebar</option><option value="journal_top" <?=$editing['placement']==='journal_top'?'selected':''?>>Blog banner</option><option value="article_end" <?=$editing['placement']==='article_end'?'selected':''?>>End of blog articles</option></select></label></div><div class="admin-form-grid"><label>Starts<input type="datetime-local" name="starts_at" value="<?=e(adEditorDateTimeValue($editing,'startsAt'))?>"></label><label>Ends<input type="datetime-local" name="ends_at" value="<?=e(adEditorDateTimeValue($editing,'endsAt'))?>"></label></div></section>
        <section class="ad-form-section"><h3>Creative</h3><p>Keep the headline direct and the visual accessible.</p><div class="admin-form-grid"><label>Eyebrow<input name="eyebrow" value="<?=e((string)$editing['eyebrow'])?>" placeholder="Sponsored resource"></label><label>Sponsor name<input name="sponsor" value="<?=e((string)$editing['sponsor'])?>" placeholder="Partner or ministry name"></label></div><div class="admin-form-grid"><label>Offer badge<input name="badge" value="<?=e((string)$editing['badge'])?>" placeholder="Save 20%"></label><label>Visual theme<select name="theme"><option value="navy" <?=$editing['theme']==='navy'?'selected':''?>>Navy</option><option value="gold" <?=$editing['theme']==='gold'?'selected':''?>>Gold</option><option value="light" <?=$editing['theme']==='light'?'selected':''?>>Light</option></select></label></div><label>Headline<input name="title" value="<?=e((string)$editing['title'])?>" required></label><label>Message<textarea name="body" rows="4"><?=e((string)$editing['body'])?></textarea></label><div class="admin-form-grid"><label>Image URL or site path<input name="image" value="<?=e((string)$editing['image'])?>" placeholder="assets/images/ad.jpg"></label><label>Upload image<input type="file" name="image_upload" accept="image/jpeg,image/png,image/webp,image/gif"><small>JPG, PNG, WebP, or GIF; up to 5 MB.</small></label></div><label>Image alternative text<input name="image_alt" value="<?=e((string)$editing['imageAlt'])?>" placeholder="Describe the image for screen readers"></label></section>
        <section class="ad-form-section"><h3>Destination</h3><p>Send readers to an offer, detail page, or a focused call to action.</p><div class="admin-form-grid"><label>Button label<input name="action_label" value="<?=e((string)$editing['actionLabel'])?>" required></label><label>Display order<input type="number" min="0" max="999" name="display_order" value="<?=(int)$editing['displayOrder']?>"></label></div><label>Destination URL<input name="action_url" value="<?=e((string)$editing['actionUrl'])?>" placeholder="https://example.com/offer" required></label><label class="toggle-label"><input type="checkbox" name="new_window" <?=$editing['newWindow']?'checked':''?>> Open an external destination in a new tab</label><label class="toggle-label"><input type="checkbox" name="page_enabled" <?=$editing['pageEnabled']?'checked':''?>> Publish a dedicated detail page for this ad</label><label>Page URL slug<input name="page_slug" value="<?=e((string)$editing['pageSlug'])?>" placeholder="summer-book-offer" data-ad-page-slug><small>Public address: /ads/your-slug/</small></label><label>Expanded page content<textarea name="page_content" rows="7" placeholder="Add fuller offer details, terms, background, or instructions. Basic HTML is supported."><?=e((string)$editing['pageContent'])?></textarea></label></section>
      </div>
      <div class="button-row"><button class="button button-primary" name="action" value="save">Save ad</button><a class="button button-outline" href="<?=url('admin/ads.php')?>">Cancel</a></div>
    </form>
  </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
