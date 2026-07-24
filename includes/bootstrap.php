<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('BASE_PATH', rtrim((string) (getenv('APP_BASE_PATH') ?: ''), '/'));

function url(string $path = ''): string
{
    return (BASE_PATH !== '' ? BASE_PATH : '') . '/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function excerpt(string $text, int $length = 170): string
{
    $clean = trim(strip_tags($text));
    return mb_strlen($clean) > $length ? mb_substr($clean, 0, $length - 1) . '…' : $clean;
}
