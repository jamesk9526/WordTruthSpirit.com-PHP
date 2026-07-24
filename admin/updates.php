<?php
declare(strict_types=1);
require __DIR__ . '/auth.php'; requireAdmin(); require ROOT_PATH . '/includes/updates.php';
$error=''; $result=[];
if($_SERVER['REQUEST_METHOD']==='POST') { verifyCsrf(); if(!isset($_POST['confirm_updates'])) $error='Confirm that you have a current database backup before running updates.'; else try { $result=runPendingDatabaseUpdates((string)($_SESSION['wts_admin_email']??$_SESSION['wts_admin_name']??'administrator')); } catch(Throwable $exception) { $error='Update stopped safely: '.$exception->getMessage(); } }
$ledger=updateLedger(); $done=array_column($ledger,'update_key'); $updates=databaseUpdates();
$adminTitle='Database updates';$currentAdminPage='updates';require __DIR__.'/_header.php';
?>
<header class="admin-title"><div><p class="kicker">Maintenance</p><h1>Database updates</h1><p>Apply only the built-in, tested changes required by new Word Truth Spirit features.</p></div></header>
<?php if($error):?><p class="notice error"><?=e($error)?></p><?php elseif($_SERVER['REQUEST_METHOD']==='POST'):?><p class="notice success"><?=$result?'Applied: '.e(implode(', ',$result)).'.':'Your database is already current.'?></p><?php endif;?>
<section class="admin-panel updates-panel"><h2>Preflight checklist</h2><ul><li>These updates are additive only: they create new tables or add missing columns.</li><li>No existing posts, subscribers, messages, or publications are deleted or overwritten.</li><li>Each update is recorded in an internal ledger and will not run twice.</li><li>Make a current database backup before proceeding.</li></ul></section>
<section class="admin-panel updates-panel"><h2>Update status</h2><div class="update-list"><?php foreach($updates as $key=>[$name,$description]):$applied=in_array($key,$done,true);?><article><span class="<?=$applied?'update-complete':'update-pending'?>"><?=$applied?'Applied':'Pending'?></span><div><h3><?=e($name)?></h3><p><?=e($description)?></p><small><?=e($key)?></small></div></article><?php endforeach;?></div><form method="post" class="update-confirm"><input type="hidden" name="csrf" value="<?=csrfToken()?>"><label class="toggle-label"><input type="checkbox" name="confirm_updates" value="1"> I have a current database backup and want to apply all pending updates.</label><button class="button button-primary" type="submit">Run pending updates</button></form></section>
<?php if($ledger):?><section class="admin-panel updates-panel"><h2>Update history</h2><div class="update-history"><?php foreach($ledger as $item):?><p><strong><?=e($item['update_key'])?></strong><span><?=e($item['description'])?></span><small><?=e($item['applied_at'])?></small></p><?php endforeach;?></div></section><?php endif;?>
<?php require __DIR__.'/_footer.php'; ?>
