<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
requireAdmin();
require ROOT_PATH . '/includes/tags.php';
ensureWriterPostColumns();

$db = database();
$legacy = databaseUsesLegacySchema();
$id = (int) ($_GET['id'] ?? 0);
$post = null;
if ($id) {
    $statement = $db->prepare('SELECT * FROM ' . ($legacy ? 'posts' : 'wts_posts') . ' WHERE id=?');
    $statement->execute([$id]);
    $post = $statement->fetch();
    if ($post && $legacy) {
        $post['body'] = $post['content'];
        $post['reading_minutes'] = (int) $post['read_time'];
        $post['status'] = (int)$post['published'] === 2 ? 'update' : ($post['published'] ? 'published' : 'draft');
        $post['published_at'] = $post['date'];
        $post['tags'] = $post['tags'] ?? '';
        $post['cover_image'] = $post['cover_image'] ?? '';
        $post['meta_title'] = $post['meta_title'] ?? '';
        $post['meta_description'] = $post['meta_description'] ?? '';
        $post['featured'] = !empty($post['featured']);
    }
}
$post = $post ?: ['title'=>'','slug'=>'','category'=>'general','excerpt'=>'','body'=>'','author'=>'Patrick E. Pennington','reading_minutes'=>5,'status'=>'draft','published_at'=>date('Y-m-d\\TH:i'),'tags'=>'','cover_image'=>'','audio_url'=>'','meta_title'=>'','meta_description'=>'','featured'=>false,'comments_enabled'=>true];
$post['comments_enabled'] = !array_key_exists('comments_enabled', $post) || (bool) $post['comments_enabled'];
$availableTags = allTags();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $coverImage = trim((string) ($_POST['cover_image'] ?? ''));
    if (isset($_FILES['cover_upload']) && ($_FILES['cover_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['cover_upload'];
        if ($upload['error'] !== UPLOAD_ERR_OK) $error = 'The featured image upload did not complete.';
        elseif ((int) $upload['size'] > 8 * 1024 * 1024) $error = 'Featured images must be 8 MB or smaller.';
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']);
            $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            if (!isset($extensions[$mime])) $error = 'Upload a JPG, PNG, WebP, or GIF image.';
            else {
                $directory = ROOT_PATH . '/assets/uploads/posts';
                if ((is_dir($directory) || mkdir($directory, 0755, true))) {
                    $filename = 'post-' . date('Ymd') . '-' . bin2hex(random_bytes(5)) . '.' . $extensions[$mime];
                    if (move_uploaded_file((string) $upload['tmp_name'], $directory . '/' . $filename)) $coverImage = 'assets/uploads/posts/' . $filename;
                    else $error = 'The featured image could not be saved.';
                } else $error = 'The featured image folder could not be created.';
            }
        }
    }
    $audioUrl = trim((string) ($_POST['audio_url'] ?? ''));
    if ($audioUrl !== '' && !preg_match('#^https://#i', $audioUrl) && !preg_match('#^assets/[a-z0-9_./-]+$#i', $audioUrl)) $error = 'Use an HTTPS audio URL or a site path beginning with assets/.';
    if (!$error && isset($_FILES['audio_upload']) && ($_FILES['audio_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['audio_upload'];
        if ($upload['error'] !== UPLOAD_ERR_OK) $error = 'The audio upload did not complete.';
        elseif ((int) $upload['size'] > 100 * 1024 * 1024) $error = 'Audio files must be 100 MB or smaller.';
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']);
            $extensions = ['audio/mpeg'=>'mp3','audio/mp4'=>'m4a','audio/x-m4a'=>'m4a','audio/ogg'=>'ogg','audio/wav'=>'wav','audio/x-wav'=>'wav'];
            if (!isset($extensions[$mime])) $error = 'Upload an MP3, M4A, OGG, or WAV audio file.';
            else {
                $directory = ROOT_PATH . '/assets/uploads/audio';
                if ((is_dir($directory) || mkdir($directory, 0755, true))) {
                    $filename = 'reflection-' . date('Ymd') . '-' . bin2hex(random_bytes(5)) . '.' . $extensions[$mime];
                    if (move_uploaded_file((string) $upload['tmp_name'], $directory . '/' . $filename)) $audioUrl = 'assets/uploads/audio/' . $filename;
                    else $error = 'The audio file could not be saved.';
                } else $error = 'The audio folder could not be created.';
            }
        }
    }
    $publishingNow = isset($_POST['publish_now']);
    $values = [
        'title'=>trim((string) ($_POST['title'] ?? '')),
        'slug'=>trim((string) ($_POST['slug'] ?? '')),
        'category'=>trim((string) ($_POST['category'] ?? 'general')),
        'excerpt'=>trim((string) ($_POST['excerpt'] ?? '')),
        'body'=>trim((string) ($_POST['body'] ?? '')),
        'author'=>trim((string) ($_POST['author'] ?? 'Patrick E. Pennington')),
        'reading_minutes'=>max(1, (int) ($_POST['reading_minutes'] ?? 5)),
        'status'=>$publishingNow ? 'published' : (in_array($_POST['status'] ?? '', ['draft','published','update','archived'], true) ? $_POST['status'] : 'draft'),
        'published_at'=>($_POST['published_at'] ?? '') ? date('Y-m-d H:i:s', strtotime((string) $_POST['published_at'])) : ($publishingNow ? date('Y-m-d H:i:s') : null),
        'tags'=>trim((string) ($_POST['tags'] ?? '')),
        'cover_image'=>$coverImage,
        'audio_url'=>$audioUrl,
        'meta_title'=>trim((string) ($_POST['meta_title'] ?? '')),
        'meta_description'=>trim((string) ($_POST['meta_description'] ?? '')),
        'featured'=>isset($_POST['featured']),
        'comments_enabled'=>isset($_POST['comments_enabled']),
    ];
    if (!$error && (!$values['title'] || !preg_match('/^[a-z0-9-]+$/', $values['slug']) || !$values['body'])) {
        $error = 'Title, URL slug, and article content are required.';
    }
    if (!$error) {
        try {
            if ($legacy) {
                $legacyValues = ['title'=>$values['title'],'slug'=>$values['slug'],'category'=>$values['category'],'excerpt'=>$values['excerpt'],'content'=>$values['body'],'author'=>$values['author'],'read_time'=>$values['reading_minutes'].' min read','published'=>$values['status'] === 'published' ? 1 : ($values['status'] === 'update' ? 2 : 0),'date'=>$values['published_at'] ? date('Y-m-d', strtotime($values['published_at'])) : null,'tags'=>$values['tags'],'cover_image'=>$values['cover_image'],'audio_url'=>$values['audio_url'],'meta_title'=>$values['meta_title'],'meta_description'=>$values['meta_description'],'featured'=>$values['featured']?1:0,'comments_enabled'=>$values['comments_enabled']?1:0];
                if ($id) { $sql='UPDATE posts SET title=:title,slug=:slug,category=:category,excerpt=:excerpt,content=:content,author=:author,read_time=:read_time,published=:published,date=:date,tags=:tags,cover_image=:cover_image,audio_url=:audio_url,meta_title=:meta_title,meta_description=:meta_description,featured=:featured,comments_enabled=:comments_enabled WHERE id=:id'; $legacyValues['id']=$id; }
                else { $sql='INSERT INTO posts (title,slug,category,excerpt,content,author,read_time,published,date,tags,cover_image,audio_url,meta_title,meta_description,featured,comments_enabled) VALUES (:title,:slug,:category,:excerpt,:content,:author,:read_time,:published,:date,:tags,:cover_image,:audio_url,:meta_title,:meta_description,:featured,:comments_enabled)'; }
                $db->prepare($sql)->execute($legacyValues);
            } else {
                if ($id) { $sql='UPDATE wts_posts SET title=:title,slug=:slug,category=:category,excerpt=:excerpt,body=:body,author=:author,reading_minutes=:reading_minutes,status=:status,published_at=:published_at,tags=:tags,cover_image=:cover_image,audio_url=:audio_url,meta_title=:meta_title,meta_description=:meta_description,featured=:featured,comments_enabled=:comments_enabled WHERE id=:id'; $values['id']=$id; }
                else { $sql='INSERT INTO wts_posts (title,slug,category,excerpt,body,author,reading_minutes,status,published_at,tags,cover_image,audio_url,meta_title,meta_description,featured,comments_enabled) VALUES (:title,:slug,:category,:excerpt,:body,:author,:reading_minutes,:status,:published_at,:tags,:cover_image,:audio_url,:meta_title,:meta_description,:featured,:comments_enabled)'; }
                $db->prepare($sql)->execute($values);
            }
            header('Location:' . url('admin/posts.php?saved=1')); exit;
        } catch (PDOException $exception) {
            $error = 'Unable to save. Make sure the URL slug is unique.';
        }
    }
    $post = array_merge($post, $values);
}

$adminTitle = $id ? 'Edit reflection' : 'New reflection';
$currentAdminPage = 'posts';
$dedicatedAdminEditor = true;
$coverPreviewUrl = $post['cover_image'] ? (preg_match('#^https?://#i',(string)$post['cover_image']) ? (string)$post['cover_image'] : url((string)$post['cover_image'])) : '';
require __DIR__ . '/_header.php';
?>
<form method="post" enctype="multipart/form-data" class="dedicated-post-editor" data-post-editor data-base-url="<?=e(url())?>">
  <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
  <aside class="editor-app-rail" aria-label="Editor navigation">
    <a class="editor-rail-brand" href="<?=url('admin/')?>"><img src="<?=url('assets/images/logo.png')?>" alt="Word Truth Spirit"></a>
    <nav><a href="<?=url('admin/')?>" title="Dashboard"><span>▦</span><strong>Dashboard</strong></a><a class="active" href="<?=url('admin/posts.php')?>" title="Blog"><span>▤</span><strong>Blog</strong></a><a href="<?=url('admin/comments.php')?>" title="Comments"><span>◌</span><strong>Comments</strong></a><a href="<?=url('admin/content.php')?>" title="Page content"><span>¶</span><strong>Pages</strong></a><a href="<?=url('admin/products.php')?>" title="Products and giving"><span>◇</span><strong>Products</strong></a><a href="<?=url('admin/settings.php')?>" title="Settings"><span>⚙</span><strong>Settings</strong></a></nav>
    <a class="editor-rail-exit" href="<?=url('admin/posts.php')?>"><span>←</span><strong>Exit editor</strong></a>
  </aside>
  <header class="editor-app-topbar">
    <a class="editor-mobile-back" href="<?=url('admin/posts.php')?>" aria-label="Back to blog">←</a>
    <div class="editor-status"><span class="editor-saved-mark">✓</span><strong data-editor-status>Ready</strong></div>
    <div class="editor-top-actions"><button class="editor-settings-toggle" type="button" data-editor-settings-toggle aria-expanded="true">Settings</button><a class="button button-outline" target="_blank" href="<?= $id ? url('blog/post.php?slug=' . urlencode($post['slug'])) : url('blog/') ?>">Preview</a><button class="button button-outline" type="submit">Save</button><button class="button button-primary" type="submit" name="publish_now" value="1"><?= $post['status']==='published'?'Update':'Publish' ?></button></div>
  </header>
  <section class="editor-document">
    <div class="editor-toolbar editor-toolbar-dedicated" aria-label="Formatting tools">
      <select data-format-block aria-label="Text style"><option value="p">Paragraph</option><option value="h2">Heading 2</option><option value="h3">Heading 3</option><option value="blockquote">Quote</option></select><span></span>
      <button type="button" data-command="bold" title="Bold"><strong>B</strong></button><button type="button" data-command="italic" title="Italic"><em>I</em></button><button type="button" data-command="underline" title="Underline"><u>U</u></button><span></span>
      <button type="button" data-command="insertUnorderedList" title="Bulleted list">• List</button><button type="button" data-command="insertOrderedList" title="Numbered list">1. List</button><button type="button" data-block="blockquote" title="Quote">“ ”</button><span></span>
      <button type="button" data-command="justifyLeft" title="Align left">≡</button><button type="button" data-command="justifyCenter" title="Align center">≡</button><button type="button" data-command="justifyRight" title="Align right">≡</button><span></span>
      <button type="button" data-link title="Add link">Link</button><button type="button" data-command="removeFormat" title="Clear formatting">Clear</button><button type="button" data-focus-mode title="Distraction-free writing">Focus</button>
      <div class="editor-mode-switch"><button type="button" class="editor-mode-button active" data-editor-mode="visual">Blocks</button><button type="button" class="editor-mode-button" data-editor-mode="source">HTML</button></div>
    </div>
    <div class="editor-document-scroll">
      <div class="editor-document-inner">
        <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
        <div class="editor-category-pill"><span>✦</span><strong data-category-pill><?=e(ucfirst((string)$post['category']))?></strong></div>
        <label class="sr-only" for="editor-post-title">Reflection title</label><textarea id="editor-post-title" class="editor-headline" name="title" rows="2" placeholder="Add a compelling title" required data-slug-source><?=e((string)$post['title'])?></textarea>
        <label class="sr-only" for="editor-post-excerpt">Excerpt</label><textarea id="editor-post-excerpt" class="editor-deck-field" name="excerpt" rows="3" placeholder="Write a short introduction that invites readers into the reflection."><?=e((string)$post['excerpt'])?></textarea>
        <figure class="editor-featured-preview" data-editor-cover-preview <?=$post['cover_image']?'':'hidden'?>><img src="<?=e($coverPreviewUrl)?>" alt=""><figcaption>Featured image preview</figcaption></figure>
        <div class="rich-editor rich-editor-dedicated" data-rich-editor>
          <div class="block-inserter" aria-label="Add a writing block"><span>Add block</span><button type="button" data-add-block="p">Paragraph</button><button type="button" data-add-block="h2">Heading</button><button type="button" data-add-block="blockquote">Scripture / quote</button><button type="button" data-add-block="ul">Bullets</button><button type="button" data-add-block="ol">Numbered list</button><button type="button" data-add-block="image">Image</button><button type="button" data-add-block="hr">Divider</button></div>
          <div class="editor-surface block-canvas" role="textbox" aria-label="Article blocks" aria-multiline="true" data-editor-surface><?=articleHtml((string)$post['body'])?></div>
          <textarea class="editor-source" data-editor-source hidden aria-label="HTML source"></textarea><textarea name="body" hidden data-editor-output><?=e((string)$post['body'])?></textarea>
        </div>
      </div>
    </div>
    <footer class="editor-document-footer"><span><strong data-word-count>0</strong> words</span><span>•</span><span><strong data-read-time>1</strong> min read</span><span class="editor-help">Blocks can be dragged and reordered · Ctrl/⌘ + S saves</span></footer>
  </section>
  <aside class="editor-settings-panel" data-editor-settings>
    <header><div><span>Reflection controls</span><h2>Post settings</h2></div><button type="button" data-editor-settings-close aria-label="Close settings">×</button></header>
    <div class="editor-settings-scroll">
      <details open><summary>Publishing</summary><div class="editor-settings-group"><label>Status<select name="status"><?php foreach(['draft','published','update','archived'] as $status):?><option value="<?=$status?>" <?=$post['status']===$status?'selected':''?>><?=$status==='update'?'Subscriber update':ucfirst($status)?></option><?php endforeach;?></select></label><label>Author<input name="author" value="<?=e((string)$post['author'])?>"></label><label>Publish date<input type="datetime-local" name="published_at" value="<?=e($post['published_at']?date('Y-m-d\\TH:i',strtotime((string)$post['published_at'])):'')?>"></label><label>Reading minutes<input type="number" min="1" name="reading_minutes" value="<?=(int)$post['reading_minutes']?>"></label><label class="check-label"><input type="checkbox" name="featured" value="1" <?=!empty($post['featured'])?'checked':''?>> Feature this reflection</label><label class="check-label"><input type="checkbox" name="comments_enabled" value="1" <?=!empty($post['comments_enabled'])?'checked':''?>> Allow reader comments</label></div></details>
      <details open><summary>Story details</summary><div class="editor-settings-group"><label>Category<select name="category" data-editor-category><?php foreach(['word','truth','spirit','general','christmas'] as $category):?><option value="<?=$category?>" <?=$post['category']===$category?'selected':''?>><?=ucfirst($category)?></option><?php endforeach;?></select></label><label>URL slug<input name="slug" value="<?=e((string)$post['slug'])?>" placeholder="reflection-title" required data-slug-field></label><label>Topics / tags<input name="tags" list="tag-suggestions" value="<?=e((string)$post['tags'])?>" placeholder="Scripture, discipleship, prayer"></label><datalist id="tag-suggestions"><?php foreach($availableTags as $tag):?><option value="<?=e($tag)?>"><?php endforeach;?></datalist><a class="editor-tags-link" href="<?=url('admin/tags.php')?>" target="_blank">Manage tags ↗</a></div></details>
      <details open><summary>Featured image</summary><div class="editor-settings-group"><div class="editor-cover-thumb" data-editor-cover-thumb><img src="<?=e($coverPreviewUrl)?>" alt="" <?=$post['cover_image']?'':'hidden'?>><span <?=$post['cover_image']?'hidden':''?>>No image selected</span></div><label>Image path or URL<input name="cover_image" value="<?=e((string)$post['cover_image'])?>" placeholder="assets/images/my-cover.jpg" data-cover-path></label><label>Upload new image<input type="file" name="cover_upload" accept="image/jpeg,image/png,image/webp,image/gif" data-cover-upload><small>JPG, PNG, WebP, or GIF up to 8 MB.</small></label><button type="button" class="editor-remove-image" data-remove-cover>Remove image</button></div></details>
      <details><summary>Audio version</summary><div class="editor-settings-group"><p>Optionally give readers a narrated version of this reflection.</p><label>Audio URL or site path<input name="audio_url" value="<?=e((string)($post['audio_url'] ?? ''))?>" placeholder="assets/uploads/audio/reflection.mp3"></label><label>Upload audio<input type="file" name="audio_upload" accept="audio/mpeg,audio/mp4,audio/ogg,audio/wav"><small>MP3, M4A, OGG, or WAV up to 100 MB.</small></label></div></details>
      <details><summary>Search &amp; sharing</summary><div class="editor-settings-group"><label>SEO title<input name="meta_title" maxlength="500" value="<?=e((string)$post['meta_title'])?>" placeholder="Defaults to reflection title"></label><label>Meta description<textarea name="meta_description" rows="4" maxlength="1000" placeholder="Defaults to the excerpt."><?=e((string)$post['meta_description'])?></textarea></label><p>These fields control how the reflection appears in search results and shared links.</p></div></details>
    </div>
  </aside>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
