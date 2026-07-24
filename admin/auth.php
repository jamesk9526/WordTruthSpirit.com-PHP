<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once ROOT_PATH . '/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
    session_start();
}

function adminCount(): int
{
    $db = database();
    if (!$db) return 0;
    try {
        $table = databaseUsesLegacySchema() ? 'users' : 'wts_admin_users';
        return (int) $db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }
    catch (PDOException $error) { return 0; }
}
function adminLoggedIn(): bool { return !empty($_SESSION['wts_admin_id']); }
function requireAdmin(): void
{
    if (!adminLoggedIn()) { header('Location: ' . url('admin/login.php')); exit; }
}
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function verifyCsrf(): void
{
    if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419); exit('Your session expired. Please go back and try again.');
    }
}
