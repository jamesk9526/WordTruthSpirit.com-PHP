<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
requireAdmin();

$db = database();
$legacy = databaseUsesLegacySchema();
if (!$legacy) {
    $adminTitle = 'Conversations'; $currentAdminPage = 'conversations';
    require __DIR__ . '/_header.php';
    echo '<header class="admin-title"><div><p class="kicker">Reader chat</p><h1>Conversations</h1><p>Review and respond to reader conversations from one workspace.</p></div></header><section class="admin-panel empty-state"><span>◌</span><h2>Conversation storage is not available</h2><p>Import 127_0_0_1.sql to connect the original live-chat conversation tables.</p></section>';
    require __DIR__ . '/_footer.php'; exit;
}

$selected = trim((string) ($_GET['conversation'] ?? $_POST['conversation_id'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$filter = (string) ($_GET['status'] ?? 'all');
if (!in_array($filter, ['all', 'unread', 'open', 'closed'], true)) $filter = 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? 'reply');
    $exists = $db->prepare('SELECT COUNT(*) FROM chat_conversations WHERE id=?');
    $exists->execute([$selected]);
    if (!$selected || !(int) $exists->fetchColumn()) {
        header('Location:' . url('admin/conversations.php?' . http_build_query(['status' => $filter, 'q' => $search, 'notice' => 'missing']))); exit;
    }
    if ($action === 'reply') {
        $reply = trim((string) ($_POST['reply'] ?? ''));
        if ($reply === '') {
            header('Location:' . url('admin/conversations.php?' . http_build_query(['status' => $filter, 'q' => $search, 'conversation' => $selected, 'notice' => 'empty']))); exit;
        }
        $reply = substr($reply, 0, 8000);
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO chat_messages (id,conversation_id,sender,message) VALUES (?,?,"admin",?)')->execute([uuidV4(), $selected, $reply]);
            $db->prepare('UPDATE chat_conversations SET unread_by_admin=0,last_message_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$selected]);
            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack(); throw $exception;
        }
        $notice = 'sent';
    } elseif (in_array($action, ['open', 'closed'], true)) {
        $db->prepare('UPDATE chat_conversations SET status=?,unread_by_admin=0 WHERE id=?')->execute([$action, $selected]);
        $notice = $action;
    } else $notice = 'unchanged';
    header('Location:' . url('admin/conversations.php?' . http_build_query(['status' => $filter, 'q' => $search, 'conversation' => $selected, 'notice' => $notice]))); exit;
}

$where = []; $parameters = [];
if ($search !== '') {
    $where[] = '(c.name LIKE ? OR c.email LIKE ? OR c.page_path LIKE ?)';
    $term = '%' . $search . '%'; array_push($parameters, $term, $term, $term);
}
if ($filter === 'unread') $where[] = 'c.unread_by_admin=1';
if (in_array($filter, ['open', 'closed'], true)) { $where[] = 'c.status=?'; $parameters[] = $filter; }
$query = 'SELECT c.id,c.name,c.email,c.status,c.page_path,c.unread_by_admin,c.last_message_at,c.created_at,
    (SELECT m.message FROM chat_messages m WHERE m.conversation_id=c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
    (SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id=c.id) AS message_count
    FROM chat_conversations c' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY c.unread_by_admin DESC,c.last_message_at DESC';
$statement = $db->prepare($query); $statement->execute($parameters); $conversations = $statement->fetchAll();

$counts = ['all' => 0, 'unread' => 0, 'open' => 0, 'closed' => 0];
foreach ($db->query('SELECT status,unread_by_admin FROM chat_conversations')->fetchAll() as $row) {
    $counts['all']++;
    if ((int) $row['unread_by_admin']) $counts['unread']++;
    if (isset($counts[$row['status']])) $counts[$row['status']]++;
}

$selectionRequested = $selected !== '';
if (!$selected && $conversations) $selected = (string) $conversations[0]['id'];
$current = null;
foreach ($conversations as &$conversation) {
    if ((string) $conversation['id'] === $selected) { $current = $conversation; $conversation['unread_by_admin'] = 0; }
}
unset($conversation);
$messages = [];
if ($current) {
    if ((int) $current['unread_by_admin']) {
        $db->prepare('UPDATE chat_conversations SET unread_by_admin=0 WHERE id=?')->execute([$selected]);
        $counts['unread'] = max(0, $counts['unread'] - 1);
    }
    $statement = $db->prepare('SELECT sender,message,created_at FROM chat_messages WHERE conversation_id=? ORDER BY created_at,id');
    $statement->execute([$selected]); $messages = $statement->fetchAll();
}

$listUrl = static function (string $status, string $q = ''): string {
    $parameters = ['status' => $status]; if ($q !== '') $parameters['q'] = $q;
    return url('admin/conversations.php?' . http_build_query($parameters));
};
$readerInitial = static function (string $name): string {
    $name = $name !== '' ? $name : '?';
    $first = function_exists('mb_substr') ? mb_substr($name, 0, 1) : substr($name, 0, 1);
    return function_exists('mb_strtoupper') ? mb_strtoupper($first) : strtoupper($first);
};
$adminTitle = 'Conversations'; $currentAdminPage = 'conversations'; require __DIR__ . '/_header.php';
?>
<header class="admin-title conversation-title">
  <div><p class="kicker">Reader chat</p><h1>Conversations</h1><p>Review questions, follow context, and respond without losing your place.</p></div>
  <div class="conversation-summary" aria-label="Conversation summary"><strong><?= $counts['unread'] ?></strong><span>unread</span><strong><?= $counts['all'] ?></strong><span>total</span></div>
</header>
<?php if (($_GET['notice'] ?? '') === 'sent'): ?><p class="notice success conversation-notice" role="status">Reply sent successfully.</p><?php endif; ?>
<?php if (($_GET['notice'] ?? '') === 'empty'): ?><p class="notice error conversation-notice" role="alert">Write a reply before sending.</p><?php endif; ?>
<?php if (($_GET['notice'] ?? '') === 'missing'): ?><p class="notice error conversation-notice" role="alert">That conversation could not be found.</p><?php endif; ?>

<section class="conversation-toolbar" aria-label="Conversation filters">
  <nav class="conversation-tabs" aria-label="Filter conversations">
    <?php foreach (['all' => 'All', 'unread' => 'Unread', 'open' => 'Open', 'closed' => 'Closed'] as $key => $label): ?>
      <a class="<?= $filter === $key ? 'active' : '' ?>" href="<?= $listUrl($key, $search) ?>" <?= $filter === $key ? 'aria-current="page"' : '' ?>><?= $label ?><span><?= $counts[$key] ?></span></a>
    <?php endforeach; ?>
  </nav>
  <form method="get" class="conversation-search" role="search"><input type="hidden" name="status" value="<?= e($filter) ?>"><label for="conversation-search">Search conversations</label><div><input id="conversation-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Name, email, or page"><button class="button button-outline" type="submit">Search</button></div></form>
</section>

<section class="inbox-layout <?= $current ? 'has-selection' : '' ?> <?= $selectionRequested ? 'selection-requested' : '' ?>" data-conversation-workspace>
  <aside class="admin-panel inbox-sidebar" aria-label="Conversation list">
    <div class="inbox-list-heading"><strong><?= count($conversations) ?> conversation<?= count($conversations) === 1 ? '' : 's' ?></strong><?php if ($search !== ''): ?><a href="<?= $listUrl($filter) ?>">Clear search</a><?php endif; ?></div>
    <div class="inbox-list">
      <?php foreach ($conversations as $conversation): $conversationUrl = url('admin/conversations.php?' . http_build_query(['status' => $filter, 'q' => $search, 'conversation' => $conversation['id']])); ?>
        <a class="inbox-item <?= $selected === $conversation['id'] ? 'active' : '' ?> <?= $conversation['unread_by_admin'] ? 'unread' : '' ?>" href="<?= $conversationUrl ?>" <?= $selected === $conversation['id'] ? 'aria-current="true"' : '' ?>>
          <span class="inbox-avatar" aria-hidden="true"><?= e($readerInitial((string) $conversation['name'])) ?></span>
          <span class="inbox-item-copy"><span><strong><?= e($conversation['name'] ?: 'Unnamed reader') ?></strong><time datetime="<?= e((string) $conversation['last_message_at']) ?>"><?= date('M j', strtotime((string) $conversation['last_message_at'])) ?></time></span><small><?= e($conversation['email']) ?></small><em><?= e((string) ($conversation['last_message'] ?: 'No messages yet')) ?></em><span class="inbox-item-meta"><b class="conversation-status status-<?= e((string) $conversation['status']) ?>"><?= e((string) $conversation['status']) ?></b><small><?= (int) $conversation['message_count'] ?> message<?= (int) $conversation['message_count'] === 1 ? '' : 's' ?></small></span></span>
          <?php if ($conversation['unread_by_admin']): ?><span class="unread-dot"><span class="sr-only">Unread</span></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
      <?php if (!$conversations): ?><div class="inbox-empty"><span>⌕</span><strong>No conversations found</strong><p>Try a different filter or search.</p></div><?php endif; ?>
    </div>
  </aside>
  <article class="admin-panel message-detail">
    <?php if (!$current): ?><div class="empty-state conversation-empty"><span>◌</span><h2>Select a conversation</h2><p>Choose a reader from the list to review the full message history.</p></div>
    <?php else: ?>
      <a class="conversation-back" href="<?= $listUrl($filter, $search) ?>">← Back to conversations</a>
      <header class="conversation-reader-header"><span class="inbox-avatar large" aria-hidden="true"><?= e($readerInitial((string) $current['name'])) ?></span><div><p class="kicker">Reader conversation</p><h2><?= e($current['name'] ?: 'Unnamed reader') ?></h2><p><a href="mailto:<?= e($current['email']) ?>"><?= e($current['email']) ?></a><span aria-hidden="true">·</span><span><?= e($current['page_path'] ?: '/') ?></span></p></div><form method="post" class="conversation-status-form"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="conversation_id" value="<?= e($current['id']) ?>"><?php if ($current['status'] === 'closed'): ?><button class="button button-outline" name="action" value="open">Reopen</button><?php else: ?><button class="button button-outline" name="action" value="closed">Close conversation</button><?php endif; ?></form></header>
      <div class="chat-thread" data-chat-thread aria-label="Message history" aria-live="polite">
        <?php foreach ($messages as $message): ?><div class="chat-message <?= e($message['sender']) ?>"><span><?= $message['sender'] === 'admin' ? 'You' : e($current['name'] ?: 'Reader') ?></span><p><?= nl2br(e($message['message'])) ?></p><time datetime="<?= e((string) $message['created_at']) ?>"><?= date('M j, Y · g:i a', strtotime((string) $message['created_at'])) ?></time></div><?php endforeach; ?>
        <?php if (!$messages): ?><div class="chat-empty">There are no messages in this conversation yet.</div><?php endif; ?>
      </div>
      <form method="post" class="chat-reply" data-chat-reply><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="conversation_id" value="<?= e($current['id']) ?>"><div class="chat-reply-label"><label for="conversation-reply">Reply to <?= e($current['name'] ?: 'reader') ?></label><span><b data-reply-count>0</b>/8000</span></div><textarea id="conversation-reply" name="reply" rows="4" maxlength="8000" required placeholder="Write a thoughtful response…" data-reply-field></textarea><div class="chat-reply-actions"><small>Replies appear in this conversation immediately.</small><button class="button button-primary" name="action" value="reply">Send reply</button></div></form>
    <?php endif; ?>
  </article>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
