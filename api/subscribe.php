<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php'; require ROOT_PATH . '/config/database.php'; require ROOT_PATH . '/includes/mailer.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
$returnTo = trim((string) ($_POST['return_to'] ?? ''));
$returnParts = parse_url($returnTo);
if ($returnTo === '' || $returnParts === false || !str_starts_with((string) ($returnParts['path'] ?? ''), '/') || str_starts_with($returnTo, '//')) {
    $returnTo = url('blog/');
    $returnParts = parse_url($returnTo);
}
$returnQuery = [];
if (!empty($returnParts['query'])) parse_str((string) $returnParts['query'], $returnQuery);
$returnPath = (string) ($returnParts['path'] ?? url('blog/'));
$returnAnchor = preg_match('/^[a-z0-9_-]{1,60}$/i', (string) ($_POST['return_anchor'] ?? '')) ? (string) $_POST['return_anchor'] : 'email-signup';
$source = substr((string) preg_replace('/[^a-z0-9_-]+/i', '-', trim((string) ($_POST['source'] ?? 'website'))), 0, 80);
$redirect = static function (string $status) use ($returnPath, $returnQuery, $returnAnchor): void {
    $returnQuery['subscribe'] = $status;
    header('Location: ' . $returnPath . '?' . http_build_query($returnQuery) . '#' . rawurlencode($returnAnchor), true, 303);
    exit;
};
if (trim((string) ($_POST['website'] ?? '')) !== '') $redirect('pending');
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
if (!$email) $redirect('error');
$status = 'error'; $db = database();
if ($db) try {
    $confirmationToken = bin2hex(random_bytes(32)); $unsubscribeToken = bin2hex(random_bytes(32)); $legacy = databaseUsesLegacySchema();
    if ($legacy) {
        $existing=$db->prepare('SELECT id,status FROM email_subscribers WHERE email=?');$existing->execute([$email]);$subscriber=$existing->fetch();
        if($subscriber&&$subscriber['status']==='active'){$status='active';}
        else { $id=$subscriber['id']??uuidV4(); $statement=$db->prepare('INSERT INTO email_subscribers (id,email,status,confirmation_token_hash,unsubscribe_token_hash,source) VALUES (:id,:email,"pending",:confirm,:unsubscribe,:source) ON DUPLICATE KEY UPDATE status="pending",confirmation_token_hash=VALUES(confirmation_token_hash),unsubscribe_token_hash=VALUES(unsubscribe_token_hash),source=VALUES(source)');$statement->execute(['id'=>$id,'email'=>$email,'confirm'=>hash('sha256',$confirmationToken),'unsubscribe'=>hash('sha256',$unsubscribeToken),'source'=>$source]);$status='pending'; }
    } else {
        $existing=$db->prepare('SELECT id,status FROM wts_subscribers WHERE email=?');$existing->execute([$email]);$subscriber=$existing->fetch();
        if($subscriber&&$subscriber['status']==='active'){$id=(string)$subscriber['id'];$status='active';}
        else{
            $columns=$db->query('SHOW COLUMNS FROM wts_subscribers')->fetchAll(PDO::FETCH_COLUMN);
            if(in_array('source',$columns,true)){
                $statement=$db->prepare('INSERT INTO wts_subscribers (email,status,token,source) VALUES (:email,"pending",:token,:source) ON DUPLICATE KEY UPDATE status="pending",token=VALUES(token),source=VALUES(source),updated_at=CURRENT_TIMESTAMP');
                $statement->execute(['email'=>$email,'token'=>hash('sha256',$confirmationToken),'source'=>$source]);
            }else{
                $statement=$db->prepare('INSERT INTO wts_subscribers (email,status,token) VALUES (:email,"pending",:token) ON DUPLICATE KEY UPDATE status="pending",token=VALUES(token),updated_at=CURRENT_TIMESTAMP');
                $statement->execute(['email'=>$email,'token'=>hash('sha256',$confirmationToken)]);
            }
            $id=(string)$db->lastInsertId();if($id==='0'){ $lookup=$db->prepare('SELECT id FROM wts_subscribers WHERE email=?');$lookup->execute([$email]);$id=(string)$lookup->fetchColumn(); }$status='pending';
        }
    }
    if($status==='pending'){
        $link=applicationUrl('subscribe-confirm.php?id='.rawurlencode($id).'&token='.rawurlencode($confirmationToken));
        $html='<p>Thank you for subscribing to Word Truth Spirit.</p><p>Please confirm your email address to receive new reflections and ministry updates:</p><p><a href="'.e($link).'">Confirm my subscription</a></p><p>If you did not request this, you can ignore this email.</p>';
        $status=smtpSend($email,'Confirm your Word Truth Spirit subscription',$html)?'pending':'mail-error';
    }
} catch (Throwable $error) { error_log('Subscription failed: '.$error->getMessage()); }
$redirect($status);
