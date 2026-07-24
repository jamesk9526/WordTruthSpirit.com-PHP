<?php
declare(strict_types=1);
require_once ROOT_PATH . '/config/database.php';

function fallbackBooks(): array
{
    return [[
        'id' => 0,
        'slug' => 'the-spirit-of-truth',
        'title' => 'The Spirit of Truth',
        'subtitle' => 'A Biblical Defense of Traditional Pentecostalism',
        'description' => 'A clear, Scripture-grounded case for the continuing work of the Holy Spirit without surrendering biblical order or discernment.',
        'cover_image' => 'assets/images/book-cover.png',
        'purchase_url' => 'https://www.amazon.com/dp/B0GBVXPHVF',
        'format_details' => 'Paperback and eBook',
        'published_year' => 2026,
        'display_order' => 1,
        'status' => 'published',
    ]];
}

function allBooks(bool $includeDrafts = false): array
{
    $db = database();
    if ($db) {
        try {
            $sql = 'SELECT * FROM wts_books' . ($includeDrafts ? '' : " WHERE status='published'") . ' ORDER BY display_order, published_year DESC, title';
            return $db->query($sql)->fetchAll();
        } catch (PDOException $error) {
            error_log('Publication query failed: ' . $error->getMessage());
        }
    }
    return fallbackBooks();
}

function findBookById(int $id): ?array
{
    foreach (allBooks(true) as $book) {
        if ((int) $book['id'] === $id) return $book;
    }
    return null;
}
