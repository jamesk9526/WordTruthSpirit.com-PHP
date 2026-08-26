<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/push.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST is required.']);
    exit;
}

if (!pushNotificationsConfigured()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Browser notifications are not configured.']);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$subscription = is_array($payload['subscription'] ?? null) ? $payload['subscription'] : [];
$keys = is_array($subscription['keys'] ?? null) ? $subscription['keys'] : [];
$endpoint = trim((string) ($subscription['endpoint'] ?? ''));
$p256dh = trim((string) ($keys['p256dh'] ?? ''));
$auth = trim((string) ($keys['auth'] ?? ''));

if (!savePushSubscription($endpoint, $p256dh, $auth, (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'That browser subscription could not be saved.']);
    exit;
}

echo json_encode(['ok' => true]);
