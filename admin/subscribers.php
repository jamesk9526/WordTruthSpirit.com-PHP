<?php
declare(strict_types=1);
require __DIR__ . '/auth.php'; requireAdmin();
$db = database(); $legacy = databaseUsesLegacySchema();
$nativeSource = false;
if (!$legacy) {
    $columns = $db->query('SHOW COLUMNS FROM wts_subscribers')->fetchAll(PDO::FETCH_COLUMN);
    $nativeSource = in_array('source',$columns,true);
}
$rows = $legacy
    ? $db->query('SELECT email,status,source,confirmed_at,created_at FROM email_subscribers ORDER BY created_at DESC')->fetchAll()
    : $db->query('SELECT email,status,'.($nativeSource?'source':'NULL AS source').',NULL AS confirmed_at,created_at FROM wts_subscribers ORDER BY created_at DESC')->fetchAll();
$sourceLabels = ['journal-panel'=>'Journal panel','reflection-end'=>'Reflection ending','footer'=>'Website footer','bottom-banner'=>'Bottom banner','php-blog'=>'Journal','website'=>'Website'];
$counts = ['active'=>0,'pending'=>0,'unsubscribed'=>0]; foreach ($rows as $row) $counts[$row['status']] = ($counts[$row['status']] ?? 0) + 1;
$adminTitle = 'Subscribers'; $currentAdminPage = 'subscribers'; require __DIR__ . '/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Reader growth</p><h1>Subscribers</h1><p>People who asked to receive new journal reflections and ministry updates.</p></div></header>
<section class="admin-stats compact"><div><strong><?= $counts['active'] ?></strong><span>Active</span></div><div><strong><?= $counts['pending'] ?></strong><span>Pending</span></div><div><strong><?= $counts['unsubscribed'] ?></strong><span>Unsubscribed</span></div></section>
<section class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Email</th><th>Status</th><th>Source</th><th>Joined</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><strong><?= e($row['email']) ?></strong></td><td><span class="status-pill"><?= e($row['status']) ?></span></td><td><?= e($sourceLabels[(string)($row['source']??'')] ?? ($row['source'] ?: 'Website')) ?></td><td><?= date('M j, Y', strtotime($row['created_at'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php if (!$rows): ?><p>No subscribers yet.</p><?php endif; ?></section>
<?php require __DIR__ . '/_footer.php'; ?>
