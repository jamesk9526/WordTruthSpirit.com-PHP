<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
requireAdmin();
require ROOT_PATH . '/includes/tags.php';

$db = database(); $error = ''; ensureTagTable();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'delete') $db->prepare('DELETE FROM wts_tags WHERE id=?')->execute([(int) ($_POST['id'] ?? 0)]);
        else {
            $name = trim((string) ($_POST['name'] ?? ''));
            $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? ''; $slug = trim($slug, '-');
            if ($name === '' || $slug === '') throw new RuntimeException('Give the tag a name and a URL-safe key.');
            if ($action === 'update') $db->prepare('UPDATE wts_tags SET name=?,slug=?,description=? WHERE id=?')->execute([$name,$slug,trim((string)($_POST['description']??'')),(int)$_POST['id']]);
            else $db->prepare('INSERT INTO wts_tags (name,slug,description) VALUES (?,?,?)')->execute([$name,$slug,trim((string)($_POST['description']??''))]);
        }
        header('Location:' . url('admin/tags.php?saved=1')); exit;
    } catch (Throwable $exception) { $error = $exception->getMessage() === 'Give the tag a name and a URL-safe key.' ? $exception->getMessage() : 'Could not save this tag. Names and keys must be unique.'; }
}
$tags = $db ? $db->query('SELECT * FROM wts_tags ORDER BY name')->fetchAll() : [];
$adminTitle = 'Journal tags'; $currentAdminPage = 'tags'; require __DIR__ . '/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Journal organization</p><h1>Custom tags</h1><p>Create reusable topics for reflections. Add more than one to a post with commas, then use them to connect related writing.</p></div><a class="button button-outline" href="<?= url('admin/posts.php') ?>">Back to journal</a></header>
<?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php elseif (isset($_GET['saved'])): ?><p class="notice success">Tag saved.</p><?php endif; ?>
<div class="tag-admin-layout"><section class="admin-panel admin-form"><h2>New tag</h2><form method="post"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><label>Name<input name="name" placeholder="Truth" required></label><label>Key / slug<input name="slug" placeholder="truth" required></label><label>Description <small>(optional)</small><input name="description" placeholder="Used for reflections on biblical truth"></label><button class="button button-primary" name="action" value="create">Create tag</button></form></section><section class="admin-panel tag-table"><h2>Available tags</h2><?php foreach ($tags as $tag): ?><form method="post" class="tag-row"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= (int)$tag['id'] ?>"><input name="name" value="<?= e($tag['name']) ?>"><input name="slug" value="<?= e($tag['slug']) ?>"><input name="description" value="<?= e($tag['description']) ?>" placeholder="Description"><button type="submit" name="action" value="update">Save</button><button type="submit" name="action" value="delete" onclick="return confirm('Delete this tag from the library? It will not remove the tag text already used in reflections.')">Delete</button></form><?php endforeach; ?><?php if (!$tags): ?><p>No custom tags yet. Create one to make it available throughout the editor.</p><?php endif; ?></section></div>
<?php require __DIR__ . '/_footer.php'; ?>
