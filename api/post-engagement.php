<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require ROOT_PATH . '/includes/analytics.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false]); exit; }
$slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
$scroll = filter_var($_POST['scroll'] ?? null, FILTER_VALIDATE_INT);
$seconds = filter_var($_POST['active_seconds'] ?? null, FILTER_VALIDATE_INT);
if ($scroll === false || $seconds === false || !preg_match('/^[a-z0-9-]{1,190}$/', $slug)) { http_response_code(422); echo json_encode(['ok'=>false]); exit; }
echo json_encode(['ok'=>recordPostEngagement($slug, $scroll, $seconds, !empty($_POST['completed']))]);
