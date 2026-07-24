<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/config/database.php';
require ROOT_PATH . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
if (!empty($_POST['website'] ?? '')) { header('Location: ' . url('contact.php?status=success'), true, 303); exit; }

$name = trim((string) ($_POST['name'] ?? ''));
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$subject = trim((string) ($_POST['subject'] ?? 'General Inquiry'));
$message = trim((string) ($_POST['message'] ?? ''));
if ($name === '' || !$email || $message === '' || mb_strlen($name) > 100 || mb_strlen($message) > 5000) {
    header('Location: ' . url('contact.php?status=error'), true, 303); exit;
}

$saved = false;
$db = database();
if ($db) {
    try {
        if (databaseUsesLegacySchema()) {
            $statement = $db->prepare('INSERT INTO contact_messages (id,name,email,subject,message,ip_address) VALUES (:id,:name,:email,:subject,:message,:ip)');
            $saved = $statement->execute(['id'=>uuidV4(),'name'=>$name,'email'=>$email,'subject'=>$subject ?: 'General Inquiry','message'=>$message,'ip'=>$_SERVER['REMOTE_ADDR'] ?? null]);
        } else {
            $statement = $db->prepare('INSERT INTO wts_contact_messages (name,email,subject,message,ip_address) VALUES (:name,:email,:subject,:message,:ip)');
            $saved = $statement->execute(['name'=>$name,'email'=>$email,'subject'=>$subject ?: 'General Inquiry','message'=>$message,'ip'=>$_SERVER['REMOTE_ADDR'] ?? null]);
        }
    } catch (PDOException $error) { error_log('Contact insert failed: ' . $error->getMessage()); }
}
if (!$saved) {
    $directory = ROOT_PATH . '/data';
    if (!is_dir($directory)) mkdir($directory, 0750, true);
    $saved = file_put_contents($directory . '/messages.jsonl', json_encode(['created_at'=>gmdate('c'),'name'=>$name,'email'=>$email,'subject'=>$subject,'message'=>$message], JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
}
if ($saved && smtpConfigured()) {
    $recipient = (string) (getenv('CONTACT_TO_EMAIL') ?: appSetting('email.contactToEmail', ''));
    if ($recipient !== '') {
        $subjectLine = 'Website inquiry: ' . ($subject ?: 'General Inquiry');
        $body = '<p><strong>From:</strong> ' . e($name) . ' &lt;' . e($email) . '&gt;</p><p><strong>Subject:</strong> ' . e($subject ?: 'General Inquiry') . '</p><p>' . nl2br(e($message)) . '</p>';
        smtpSend($recipient, $subjectLine, $body);
    }
}
header('Location: ' . url('contact.php?status=' . ($saved ? 'success' : 'error')), true, 303);
