<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php'; require ROOT_PATH . '/config/database.php'; require ROOT_PATH . '/includes/mailer.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
if (!$email) { header('Location: ' . url('blog/?subscribe=error'), true, 303); exit; }
$status = 'error'; $db = database();
if ($db) try {
    $confirmationToken = bin2hex(random_bytes(32)); $unsubscribeToken = bin2hex(random_bytes(32)); $legacy = databaseUsesLegacySchema();
    if ($legacy) {
        $existing=$db->prepare('SELECT id,status FROM email_subscribers WHERE email=?');$existing->execute([$email]);$subscriber=$existing->fetch();
        if($subscriber&&$subscriber['status']==='active'){$status='active';}
        else { $id=$subscriber['id']??uuidV4(); $statement=$db->prepare('INSERT INTO email_subscribers (id,email,status,confirmation_token_hash,unsubscribe_token_hash,source) VALUES (:id,:email,"pending",:confirm,:unsubscribe,"php-blog") ON DUPLICATE KEY UPDATE status="pending",confirmation_token_hash=VALUES(confirmation_token_hash),unsubscribe_token_hash=VALUES(unsubscribe_token_hash),source="php-blog"');$statement->execute(['id'=>$id,'email'=>$email,'confirm'=>hash('sha256',$confirmationToken),'unsubscribe'=>hash('sha256',$unsubscribeToken)]);$status='pending'; }
    } else { $statement=$db->prepare('INSERT INTO wts_subscribers (id,email,status,token) VALUES (:id,:email,"pending",:token) ON DUPLICATE KEY UPDATE status="pending",token=VALUES(token),updated_at=CURRENT_TIMESTAMP');$id=uuidV4();$statement->execute(['id'=>$id,'email'=>$email,'token'=>hash('sha256',$confirmationToken)]);$status='pending'; }
    if($status==='pending'){
        $link=applicationUrl('subscribe-confirm.php?id='.rawurlencode($id).'&token='.rawurlencode($confirmationToken));
        $html='<p>Thank you for subscribing to Word Truth Spirit.</p><p>Please confirm your email address to receive new reflections and ministry updates:</p><p><a href="'.e($link).'">Confirm my subscription</a></p><p>If you did not request this, you can ignore this email.</p>';
        $status=smtpSend($email,'Confirm your Word Truth Spirit subscription',$html)?'pending':'mail-error';
    }
} catch (Throwable $error) { error_log('Subscription failed: '.$error->getMessage()); }
header('Location: '.url('blog/?subscribe='.$status),true,303);
