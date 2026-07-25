<?php
declare(strict_types=1);
require __DIR__.'/auth.php';requireAdmin();require ROOT_PATH.'/includes/ads.php';

$ads=siteAds();$error='';$saved=isset($_GET['saved']);$editingId=(string)($_GET['edit']??'');
$blank=['id'=>'','name'=>'','placement'=>'home_sidebar','enabled'=>true,'eyebrow'=>'Sponsored resource','title'=>'','body'=>'','image'=>'','imageAlt'=>'','badge'=>'','sponsor'=>'','theme'=>'navy','actionLabel'=>'Learn more','actionUrl'=>'','newWindow'=>true,'startsAt'=>'','endsAt'=>'','pageEnabled'=>false,'pageSlug'=>'','pageContent'=>'','displayOrder'=>10];
$editing=$blank;
foreach($ads as $ad)if(($ad['id']??'')===$editingId){$editing=array_replace($blank,$ad);break;}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();$action=(string)($_POST['action']??'save');
    if($action==='delete'){
        $deleteId=(string)($_POST['id']??'');$ads=array_values(array_filter($ads,fn(array $ad):bool=>($ad['id']??'')!==$deleteId));
        if(!setSiteAds($ads))$error='Could not delete the ad. Confirm the database is connected.';else{header('Location:'.url('admin/ads.php?saved=1'));exit;}
    }elseif($action==='toggle'){
        $toggleId=(string)($_POST['id']??'');foreach($ads as &$ad)if(($ad['id']??'')===$toggleId){$ad['enabled']=empty($ad['enabled']);break;}unset($ad);
        if(!setSiteAds($ads))$error='Could not update the ad.';else{header('Location:'.url('admin/ads.php?saved=1'));exit;}
    }elseif($action==='duplicate'){
        $copy=null;$duplicateId=(string)($_POST['id']??'');foreach($ads as $ad)if(($ad['id']??'')===$duplicateId){$copy=$ad;$copy['id']='ad-'.bin2hex(random_bytes(5));$copy['name']=trim((string)($copy['name']??'Ad')).' copy';$copy['enabled']=false;$copy['pageEnabled']=false;$copy['pageSlug']='';$ads[]=$copy;break;}
        if(!$copy)$error='The ad to duplicate could not be found.';elseif(!setSiteAds($ads))$error='Could not duplicate the ad.';else{header('Location:'.url('admin/ads.php?edit='.rawurlencode((string)$copy['id']).'&saved=1'));exit;}
    }else{
        $id=preg_replace('/[^a-z0-9-]+/','-',strtolower(trim((string)($_POST['id']??''))))?:'ad-'.bin2hex(random_bytes(5));
        $image=trim((string)($_POST['image']??''));
        if(isset($_FILES['image_upload'])&&($_FILES['image_upload']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
            $upload=$_FILES['image_upload'];
            if($upload['error']!==UPLOAD_ERR_OK)$error='The image upload did not complete.';
            elseif((int)$upload['size']>5*1024*1024)$error='Ad images must be 5 MB or smaller.';
            else{
                $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$upload['tmp_name']);
                $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                if(!isset($extensions[$mime]))$error='Upload a JPG, PNG, WebP, or GIF image.';
                else{
                    $directory=ROOT_PATH.'/assets/uploads/ads';
                    if((is_dir($directory)||mkdir($directory,0755,true))){
                        $filename='ad-'.date('Ymd').'-'.bin2hex(random_bytes(5)).'.'.$extensions[$mime];
                        if(move_uploaded_file((string)$upload['tmp_name'],$directory.'/'.$filename))$image='assets/uploads/ads/'.$filename;
                        else $error='The uploaded image could not be saved.';
                    }else $error='The ad upload folder could not be created.';
                }
            }
        }
        $placement=in_array($_POST['placement']??'', ['home_top','home_sidebar','journal_top','article_end'],true)?(string)$_POST['placement']:'home_sidebar';
        $actionUrl=trim((string)($_POST['action_url']??''));
        if($actionUrl!==''&&!preg_match('#^(https?://|/)#i',$actionUrl))$error='Use a full HTTP/HTTPS URL or a site path beginning with /.';
        $pageEnabled=isset($_POST['page_enabled']);$pageSlug=trim((string)($_POST['page_slug']??''));
        $startsAt=trim((string)($_POST['starts_at']??''));$endsAt=trim((string)($_POST['ends_at']??''));
        $values=['id'=>$id,'name'=>trim((string)($_POST['name']??'')),'placement'=>$placement,'enabled'=>isset($_POST['enabled']),'eyebrow'=>trim((string)($_POST['eyebrow']??'')),'title'=>trim((string)($_POST['title']??'')),'body'=>trim((string)($_POST['body']??'')),'image'=>$image,'imageAlt'=>trim((string)($_POST['image_alt']??'')),'badge'=>trim((string)($_POST['badge']??'')),'sponsor'=>trim((string)($_POST['sponsor']??'')),'theme'=>in_array($_POST['theme']??'', ['navy','gold','light'],true)?(string)$_POST['theme']:'navy','actionLabel'=>trim((string)($_POST['action_label']??'')),'actionUrl'=>$actionUrl,'newWindow'=>isset($_POST['new_window']),'startsAt'=>$startsAt,'endsAt'=>$endsAt,'pageEnabled'=>$pageEnabled,'pageSlug'=>$pageSlug,'pageContent'=>trim((string)($_POST['page_content']??'')),'displayOrder'=>max(0,min(999,(int)($_POST['display_order']??10)))];
        if(!$error&&(!$values['name']||!$values['title']||!$values['actionLabel']||!$values['actionUrl']))$error='Name, title, button label, and destination URL are required.';
        if(!$error&&$pageEnabled&&!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$pageSlug))$error='The individual page URL must use lowercase letters, numbers, and single hyphens.';
        if(!$error&&$startsAt!==''&&$endsAt!==''&&strtotime($endsAt)<=strtotime($startsAt))$error='The end date must be later than the start date.';
        if(!$error&&$pageEnabled)foreach($ads as $ad)if(($ad['id']??'')!==$id&&!empty($ad['pageEnabled'])&&($ad['pageSlug']??'')===$pageSlug){$error='That individual ad-page URL is already in use.';break;}
        if(!$error){
            $replaced=false;foreach($ads as $index=>$ad)if(($ad['id']??'')===$id){$ads[$index]=$values;$replaced=true;break;}
            if(!$replaced)$ads[]=$values;
            if(!setSiteAds($ads))$error='Could not save the ad. Confirm the database is connected.';else{header('Location:'.url('admin/ads.php?saved=1'));exit;}
        }
        $editing=array_replace($blank,$values);
    }
}

$activeAds=count(array_filter($ads,fn(array $ad):bool=>adStatusLabel($ad)==='Active'));$scheduledAds=count(array_filter($ads,fn(array $ad):bool=>adStatusLabel($ad)==='Scheduled'));$adminTitle='Ads';$currentAdminPage='ads';require __DIR__.'/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Site revenue &amp; promotions</p><h1>Ads</h1><p>Create image-and-text ads for the homepage banner and right sidebar, with optional individual landing pages for expanded details.</p></div><a class="button button-primary" href="<?=url('admin/ads.php?edit=new')?>">New ad</a></header>
<?php if($saved):?><p class="notice success">Ad settings saved.</p><?php endif;?>
<section class="ad-workspace-stats"><div><strong><?=count($ads)?></strong><span>Total ads</span></div><div><strong><?=$activeAds?></strong><span>Active now</span></div><div><strong><?=$scheduledAds?></strong><span>Scheduled</span></div><div><strong><?=count(array_filter($ads,fn(array $ad):bool=>!empty($ad['pageEnabled'])))?></strong><span>Detail pages</span></div></section>
<div class="ads-admin-layout">
  <section class="admin-panel ads-list"><h2>Current ads</h2><?php foreach($ads as $ad):?><?php $placementLabels=['home_top'=>'Homepage top','home_sidebar'=>'Homepage sidebar','journal_top'=>'Journal banner','article_end'=>'Article ending'];?><article><div class="ads-list-image"><?php if(!empty($ad['image'])):?><img src="<?=e(adAssetUrl((string)$ad['image']))?>" alt=""><?php else:?><span>Ad</span><?php endif;?></div><div><small><?=e($placementLabels[$ad['placement']??'']??'Placement')?> · Order <?=(int)($ad['displayOrder']??10)?><?=!empty($ad['pageEnabled'])?' · Detail page':''?></small><h3><?=e($ad['name']??'Untitled ad')?></h3><p><?=e($ad['title']??'')?></p><span class="status-pill status-<?=strtolower(adStatusLabel($ad))?>"><?=e(adStatusLabel($ad))?></span></div><div class="ads-list-actions"><?php if(!empty($ad['pageEnabled'])):?><a href="<?=e(adPageUrl($ad))?>" target="_blank" rel="noopener">View</a><?php endif;?><a href="<?=url('admin/ads.php?edit='.rawurlencode((string)$ad['id']))?>">Edit</a><form method="post"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="id" value="<?=e((string)$ad['id'])?>"><button name="action" value="toggle"><?=!empty($ad['enabled'])?'Hide':'Enable'?></button><button name="action" value="duplicate">Duplicate</button><button class="danger-link" name="action" value="delete" onclick="return confirm('Delete this ad?')">Delete</button></form></div></article><?php endforeach;?><?php if(!$ads):?><p>No ads yet. Create the first ad to populate a placement.</p><?php endif;?></section>
  <section class="admin-panel ad-editor"><div class="ad-editor-heading"><div><p class="kicker">Creative workspace</p><h2><?=$editing['id']?'Edit ad':'Create an ad'?></h2></div><span>Changes preview instantly</span></div><?php if($error):?><p class="notice error"><?=e($error)?></p><?php endif;?>
    <aside class="ad-live-preview ad-theme-<?=e((string)$editing['theme'])?>" data-ad-preview><span class="ad-preview-label">Live preview</span><img data-preview-image src="<?=e($editing['image']?adAssetUrl((string)$editing['image']):'')?>" alt="" <?=$editing['image']?'':'hidden'?>> <div data-preview-badge><?=e((string)$editing['badge'])?></div><small data-preview-eyebrow><?=e((string)$editing['eyebrow'])?></small><h3 data-preview-title><?=e((string)($editing['title']?:'Your ad headline'))?></h3><p data-preview-body><?=e((string)($editing['body']?:'Your supporting message will appear here.'))?></p><button type="button" data-preview-action><?=e((string)$editing['actionLabel'])?> →</button></aside>
    <form method="post" enctype="multipart/form-data" data-ad-editor-form><input type="hidden" name="csrf" value="<?=csrfToken()?>"><input type="hidden" name="id" value="<?=e((string)$editing['id'])?>"><label class="toggle-label"><input type="checkbox" name="enabled" <?=$editing['enabled']?'checked':''?>> Display this ad publicly</label><div class="admin-form-grid"><label>Internal name<input name="name" value="<?=e((string)$editing['name'])?>" placeholder="Summer book offer" required></label><label>Placement<select name="placement"><option value="home_top" <?=$editing['placement']==='home_top'?'selected':''?>>Homepage top banner</option><option value="home_sidebar" <?=$editing['placement']==='home_sidebar'?'selected':''?>>Homepage right sidebar</option><option value="journal_top" <?=$editing['placement']==='journal_top'?'selected':''?>>Journal banner</option><option value="article_end" <?=$editing['placement']==='article_end'?'selected':''?>>End of journal articles</option></select></label></div><div class="admin-form-grid"><label>Eyebrow<input name="eyebrow" value="<?=e((string)$editing['eyebrow'])?>" placeholder="Sponsored resource"></label><label>Sponsor name<input name="sponsor" value="<?=e((string)$editing['sponsor'])?>" placeholder="Partner or ministry name"></label></div><div class="admin-form-grid"><label>Offer badge<input name="badge" value="<?=e((string)$editing['badge'])?>" placeholder="Save 20%"></label><label>Visual theme<select name="theme"><option value="navy" <?=$editing['theme']==='navy'?'selected':''?>>Navy</option><option value="gold" <?=$editing['theme']==='gold'?'selected':''?>>Gold</option><option value="light" <?=$editing['theme']==='light'?'selected':''?>>Light</option></select></label></div><label>Headline<input name="title" value="<?=e((string)$editing['title'])?>" required></label><label>Message<textarea name="body" rows="4"><?=e((string)$editing['body'])?></textarea></label><div class="admin-form-grid"><label>Image path or URL<input name="image" value="<?=e((string)$editing['image'])?>" placeholder="assets/images/ad.jpg"></label><label>Upload a new image<input type="file" name="image_upload" accept="image/jpeg,image/png,image/webp,image/gif"><small>JPG, PNG, WebP, or GIF; up to 5 MB.</small></label></div><label>Image alternative text<input name="image_alt" value="<?=e((string)$editing['imageAlt'])?>" placeholder="Describe the image for screen readers"></label><div class="admin-form-grid"><label>Button label<input name="action_label" value="<?=e((string)$editing['actionLabel'])?>" required></label><label>Display order<input type="number" min="0" max="999" name="display_order" value="<?=(int)$editing['displayOrder']?>"></label></div><label>Destination URL<input name="action_url" value="<?=e((string)$editing['actionUrl'])?>" placeholder="https://example.com/offer" required></label><label class="toggle-label"><input type="checkbox" name="new_window" <?=$editing['newWindow']?'checked':''?>> Open destination in a new tab</label><div class="settings-subsection"><h3>Schedule</h3><p>Leave both fields empty to display continuously.</p><div class="admin-form-grid"><label>Starts<input type="datetime-local" name="starts_at" value="<?=e($editing['startsAt']?date('Y-m-d\\TH:i',strtotime((string)$editing['startsAt'])):'')?>"></label><label>Ends<input type="datetime-local" name="ends_at" value="<?=e($editing['endsAt']?date('Y-m-d\\TH:i',strtotime((string)$editing['endsAt'])):'')?>"></label></div></div><div class="settings-subsection ad-page-settings"><h3>Individual ad page</h3><label class="toggle-label"><input type="checkbox" name="page_enabled" <?=$editing['pageEnabled']?'checked':''?>> Publish a dedicated detail page for this ad</label><label>Page URL slug<input name="page_slug" value="<?=e((string)$editing['pageSlug'])?>" placeholder="summer-book-offer"><small>Public address: /ads/your-slug/</small></label><label>Expanded page content<textarea name="page_content" rows="8" placeholder="Add fuller offer details, terms, background, or instructions. Basic HTML is supported."><?=e((string)$editing['pageContent'])?></textarea></label></div><div class="button-row"><button class="button button-primary" name="action" value="save">Save ad</button><a class="button button-outline" href="<?=url('admin/ads.php')?>">Cancel</a></div></form></section>
</div>
<?php require __DIR__.'/_footer.php';?>
