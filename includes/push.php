<?php
declare(strict_types=1);

require_once ROOT_PATH . '/config/database.php';

function pushConfiguration(): array
{
    return [
        'subject' => trim((string) (getenv('VAPID_SUBJECT') ?: 'mailto:patrick@wordtruthspirit.com')),
        'publicKey' => trim((string) getenv('VAPID_PUBLIC_KEY')),
        'privateKey' => trim((string) getenv('VAPID_PRIVATE_KEY')),
    ];
}

function pushNotificationsConfigured(): bool
{
    $configuration = pushConfiguration();
    return (bool) preg_match('/^[A-Za-z0-9_-]{40,}$/', $configuration['publicKey'])
        && (bool) preg_match('/^[A-Za-z0-9_-]{40,}$/', $configuration['privateKey'])
        && (bool) filter_var((string) preg_replace('/^mailto:/i', '', $configuration['subject']), FILTER_VALIDATE_EMAIL);
}

function pushPublicKey(): string
{
    return (string) pushConfiguration()['publicKey'];
}

function ensurePushSubscriptionsTable(): bool
{
    $db = database();
    if (!$db) return false;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS wts_push_subscriptions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            endpoint TEXT NOT NULL,
            endpoint_hash CHAR(64) NOT NULL UNIQUE,
            p256dh VARCHAR(255) NOT NULL,
            auth VARCHAR(255) NOT NULL,
            user_agent VARCHAR(500) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_wts_push_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return true;
    } catch (PDOException $exception) {
        error_log('Push subscription setup failed: ' . $exception->getMessage());
        return false;
    }
}

function savePushSubscription(string $endpoint, string $p256dh, string $auth, string $userAgent = ''): bool
{
    if (!preg_match('#^https://#i', $endpoint) || strlen($endpoint) > 2048) return false;
    if (!preg_match('/^[A-Za-z0-9_-]{16,}$/', $p256dh) || !preg_match('/^[A-Za-z0-9_-]{8,}$/', $auth)) return false;
    $db = database();
    if (!$db || !ensurePushSubscriptionsTable()) return false;
    try {
        $statement = $db->prepare('INSERT INTO wts_push_subscriptions (endpoint,endpoint_hash,p256dh,auth,user_agent) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE endpoint=VALUES(endpoint),p256dh=VALUES(p256dh),auth=VALUES(auth),user_agent=VALUES(user_agent),updated_at=CURRENT_TIMESTAMP');
        return $statement->execute([$endpoint, hash('sha256', $endpoint), $p256dh, $auth, substr($userAgent, 0, 500)]);
    } catch (PDOException $exception) {
        error_log('Push subscription save failed: ' . $exception->getMessage());
        return false;
    }
}

function pushSubscriptionCount(): int
{
    $db = database();
    if (!$db || !ensurePushSubscriptionsTable()) return 0;
    try {
        return (int) $db->query('SELECT COUNT(*) FROM wts_push_subscriptions')->fetchColumn();
    } catch (PDOException $exception) {
        return 0;
    }
}
