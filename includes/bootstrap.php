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

function articleHtml(string $content): string
{
    if (!str_contains($content, '<')) {
        return implode('', array_map(
            fn(string $paragraph): string => '<p>' . e($paragraph) . '</p>',
            preg_split('/\R\R+/', trim($content)) ?: []
        ));
    }
    $clean = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $content) ?? '';
    $clean = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/is', '', $clean) ?? '';
    $clean = preg_replace('/\s+(href|src)\s*=\s*(["\'])\s*javascript:.*?\2/is', '', $clean) ?? '';
    return strip_tags($clean, '<p><br><h2><h3><h4><blockquote><strong><b><em><i><u><ul><ol><li><a><hr>');
}
