<?php
declare(strict_types=1);
require __DIR__ . '/auth.php'; requireAdmin();
$db = database(); $legacy = databaseUsesLegacySchema();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf(); $id = (string) ($_POST['id'] ?? '');
    if ($id !== '') {
        $sql = $legacy ? 'UPDATE contact_messages SET is_read=1 WHERE id=?' : "UPDATE wts_contact_messages SET status='read' WHERE id=?";
        $db->prepare($sql)->execute([$id]);
    }
    header('Location: ' . url('admin/inbox.php?message=' . urlencode($id))); exit;
}
$selected = (string) ($_GET['message'] ?? '');
$rows = $legacy
    ? $db->query('SELECT id,name,email,subject,message,is_read,created_at,replied_at FROM contact_messages ORDER BY created_at DESC')->fetchAll()
    : $db->query('SELECT id,name,email,subject,message,status,created_at,NULL AS replied_at FROM wts_contact_messages ORDER BY created_at DESC')->fetchAll();
$current = null; foreach ($rows as $row) if ((string) $row['id'] === $selected) $current = $row;
$adminTitle = 'Inbox'; $currentAdminPage = 'inbox'; require __DIR__ . '/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Reader messages</p><h1>Inbox</h1><p>Questions, book requests, and ministry inquiries sent from the public website.</p></div></header>
<section class="inbox-layout">
  <div class="admin-panel inbox-list">
    <?php if (!$rows): ?><p>No contact messages yet.</p><?php endif; ?>
    <?php foreach ($rows as $row): $unread = $legacy ? !(bool) $row['is_read'] : $row['status'] === 'new'; ?>
      <a class="inbox-item <?= $selected === (string) $row['id'] ? 'active' : '' ?> <?= $unread ? 'unread' : '' ?>" href="<?= url('admin/inbox.php?message=' . urlencode((string) $row['id'])) ?>"><strong><?= e($row['name']) ?></strong><span><?= e($row['subject'] ?: 'General inquiry') ?></span><small><?= date('M j, Y', strtotime($row['created_at'])) ?></small></a>
    <?php endforeach; ?>
  </div>
  <article class="admin-panel message-detail">
    <?php if (!$current): ?><div class="empty-state"><span>✉</span><h2>Select a message</h2><p>Choose a message to read its full details.</p></div>
    <?php else: ?>
      <p class="kicker"><?= e($current['subject'] ?: 'General inquiry') ?></p><h2><?= e($current['name']) ?></h2><p><a href="mailto:<?= e($current['email']) ?>"><?= e($current['email']) ?></a> · <?= date('F j, Y g:i a', strtotime($current['created_at'])) ?></p><div class="message-copy"><?= nl2br(e($current['message'])) ?></div>
      <div class="button-row"><a class="button button-primary" href="mailto:<?= e($current['email']) ?>?subject=<?= rawurlencode('Re: ' . ($current['subject'] ?: 'Your message to Word Truth Spirit')) ?>">Reply by email</a><?php if (($legacy && !(bool) $current['is_read']) || (!$legacy && $current['status'] === 'new')): ?><form method="post"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= e((string) $current['id']) ?>"><button class="button button-outline">Mark as read</button></form><?php endif; ?></div>
    <?php endif; ?>
  </article>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
