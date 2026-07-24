<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

if (!empty($_POST['website'] ?? '')) {
    header('Location: ../index.php?status=success#contact');
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === false || $message === '' || mb_strlen($name) > 100 || mb_strlen($message) > 3000) {
    header('Location: ../index.php?status=error#contact');
    exit;
}

$record = [
    'id' => bin2hex(random_bytes(8)),
    'created_at' => gmdate('c'),
    'name' => $name,
    'email' => $email,
    'message' => $message,
];

$dataDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
$dataFile = $dataDirectory . DIRECTORY_SEPARATOR . 'messages.jsonl';

if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0750, true) && !is_dir($dataDirectory)) {
    http_response_code(500);
    exit('Unable to save message');
}

$written = file_put_contents(
    $dataFile,
    json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

if ($written === false) {
    http_response_code(500);
    exit('Unable to save message');
}

header('Location: ../index.php?status=success#contact', true, 303);
