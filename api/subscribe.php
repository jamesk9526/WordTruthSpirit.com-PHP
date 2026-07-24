<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
if (!$email) { header('Location: ' . url('blog/?subscribe=error'), true, 303); exit; }
$db = database();
if ($db) {
    try {
        $statement = $db->prepare('INSERT INTO wts_subscribers (email,status) VALUES (:email,"pending") ON DUPLICATE KEY UPDATE updated_at=CURRENT_TIMESTAMP');
        $statement->execute(['email'=>$email]);
    } catch (PDOException $error) { error_log('Subscription insert failed: ' . $error->getMessage()); }
}
header('Location: ' . url('blog/?subscribe=success'), true, 303);
