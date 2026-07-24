<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
requireAdmin();

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
        $post['status'] = $post['published'] ? 'published' : 'draft';
        $post['published_at'] = $post['date'];
    }
}
$post = $post ?: ['title'=>'','slug'=>'','category'=>'general','excerpt'=>'','body'=>'','author'=>'Patrick E. Pennington','reading_minutes'=>5,'status'=>'draft','published_at'=>date('Y-m-d\\TH:i')];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $values = [
        'title'=>trim((string) ($_POST['title'] ?? '')),
        'slug'=>trim((string) ($_POST['slug'] ?? '')),
        'category'=>trim((string) ($_POST['category'] ?? 'general')),
        'excerpt'=>trim((string) ($_POST['excerpt'] ?? '')),
        'body'=>trim((string) ($_POST['body'] ?? '')),
        'author'=>trim((string) ($_POST['author'] ?? 'Patrick E. Pennington')),
        'reading_minutes'=>max(1, (int) ($_POST['reading_minutes'] ?? 5)),
        'status'=>in_array($_POST['status'] ?? '', ['draft','published','archived'], true) ? $_POST['status'] : 'draft',
        'published_at'=>($_POST['published_at'] ?? '') ? date('Y-m-d H:i:s', strtotime((string) $_POST['published_at'])) : null,
    ];
    if (!$values['title'] || !preg_match('/^[a-z0-9-]+$/', $values['slug']) || !$values['body']) {
        $error = 'Title, URL slug, and article content are required.';
    } else {
        try {
            if ($legacy) {
                $legacyValues = ['title'=>$values['title'],'slug'=>$values['slug'],'category'=>$values['category'],'excerpt'=>$values['excerpt'],'content'=>$values['body'],'author'=>$values['author'],'read_time'=>$values['reading_minutes'].' min read','published'=>$values['status'] === 'published' ? 1 : 0,'date'=>$values['published_at'] ? date('Y-m-d', strtotime($values['published_at'])) : null];
                if ($id) { $sql='UPDATE posts SET title=:title,slug=:slug,category=:category,excerpt=:excerpt,content=:content,author=:author,read_time=:read_time,published=:published,date=:date WHERE id=:id'; $legacyValues['id']=$id; }
                else { $sql='INSERT INTO posts (title,slug,category,excerpt,content,author,read_time,published,date) VALUES (:title,:slug,:category,:excerpt,:content,:author,:read_time,:published,:date)'; }
                $db->prepare($sql)->execute($legacyValues);
            } else {
                if ($id) { $sql='UPDATE wts_posts SET title=:title,slug=:slug,category=:category,excerpt=:excerpt,body=:body,author=:author,reading_minutes=:reading_minutes,status=:status,published_at=:published_at WHERE id=:id'; $values['id']=$id; }
                else { $sql='INSERT INTO wts_posts (title,slug,category,excerpt,body,author,reading_minutes,status,published_at) VALUES (:title,:slug,:category,:excerpt,:body,:author,:reading_minutes,:status,:published_at)'; }
                $db->prepare($sql)->execute($values);
            }
            header('Location:' . url('admin/posts.php?saved=1')); exit;
        } catch (PDOException $exception) {
            $error = 'Unable to save. Make sure the URL slug is unique.';
        }
        $post = array_merge($post, $values);
    }
}

$adminTitle = $id ? 'Edit reflection' : 'New reflection';
$currentAdminPage = 'posts';
require __DIR__ . '/_header.php';
?>
<header class="admin-title editor-title"><div><p class="kicker">Journal editor</p><h1><?= e($adminTitle) ?></h1><p>Compose visually, switch to source when needed, and preview before publishing.</p></div><div class="editor-status"><span data-editor-status>All changes saved</span><a class="button button-outline" target="_blank" href="<?= $id ? url('blog/post.php?slug=' . urlencode($post['slug'])) : url('blog/') ?>">Preview site</a></div></header>
<form method="post" class="editor-layout" data-post-editor>
  <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
  <section class="admin-panel editor-main">
    <?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
    <label class="editor-title-field">Title<input name="title" value="<?= e($post['title']) ?>" placeholder="Reflection title" required data-slug-source></label>
    <div class="editor-meta-row"><label>URL slug<input name="slug" value="<?= e($post['slug']) ?>" placeholder="reflection-title" required data-slug-field></label><label>Category<select name="category"><?php foreach(['word','truth','spirit','general','christmas'] as $category): ?><option value="<?= $category ?>" <?= $post['category'] === $category ? 'selected' : '' ?>><?= ucfirst($category) ?></option><?php endforeach; ?></select></label></div>
    <label>Excerpt<textarea name="excerpt" rows="3" placeholder="A short description used in the journal list."><?= e($post['excerpt']) ?></textarea></label>
    <div class="editor-label-row"><label>Article content</label><div><button type="button" class="editor-mode-button active" data-editor-mode="visual">Visual</button><button type="button" class="editor-mode-button" data-editor-mode="source">HTML source</button></div></div>
    <div class="rich-editor" data-rich-editor>
      <div class="editor-toolbar" aria-label="Formatting tools">
        <button type="button" data-command="bold" title="Bold"><strong>B</strong></button><button type="button" data-command="italic" title="Italic"><em>I</em></button><button type="button" data-command="underline" title="Underline"><u>U</u></button><span></span>
        <button type="button" data-block="h2">Heading</button><button type="button" data-block="p">Paragraph</button><button type="button" data-command="insertUnorderedList" title="Bulleted list">• List</button><button type="button" data-command="insertOrderedList" title="Numbered list">1. List</button><button type="button" data-block="blockquote">Quote</button><span></span>
        <button type="button" data-link title="Add link">Link</button><button type="button" data-command="removeFormat" title="Clear formatting">Clear</button>
      </div>
      <div class="editor-surface" contenteditable="true" role="textbox" aria-multiline="true" data-editor-surface><?= articleHtml($post['body']) ?></div>
      <textarea class="editor-source" data-editor-source hidden aria-label="HTML source"></textarea>
      <textarea name="body" hidden data-editor-output><?= e($post['body']) ?></textarea>
    </div>
    <p class="editor-help">Use <kbd>Ctrl</kbd> + <kbd>S</kbd> to save. Pasting is cleaned automatically before saving.</p>
  </section>
  <aside class="editor-sidebar">
    <section class="admin-panel"><h2>Publishing</h2><label>Status<select name="status"><?php foreach(['draft','published','archived'] as $status): ?><option value="<?= $status ?>" <?= $post['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label><label>Publish date<input type="datetime-local" name="published_at" value="<?= e($post['published_at'] ? date('Y-m-d\\TH:i', strtotime($post['published_at'])) : '') ?>"></label><label>Reading minutes<input type="number" min="1" name="reading_minutes" value="<?= (int) $post['reading_minutes'] ?>"></label><label>Author<input name="author" value="<?= e($post['author']) ?>"></label><button class="button button-primary editor-save" type="submit">Save reflection</button><a class="editor-cancel" href="<?= url('admin/posts.php') ?>">Cancel</a></section>
    <section class="admin-panel editor-insights"><h2>Writing insights</h2><p><strong data-word-count>0</strong> words</p><p><strong data-read-time>1</strong> min estimated read</p><p>Posts are saved as clean HTML and displayed with the same formatting on the public journal.</p></section>
  </aside>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
