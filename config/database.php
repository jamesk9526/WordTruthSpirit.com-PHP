<?php
declare(strict_types=1);

function database(): ?PDO
{
    static $connection = false;
    if ($connection instanceof PDO) {
        return $connection;
    }
    if ($connection === null) {
        return null;
    }

    $host = getenv('DB_HOST');
    $name = getenv('DB_NAME');
    if (!$host || !$name) {
        $connection = null;
        return null;
    }

    try {
        $connection = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                getenv('DB_PORT') ?: '3306',
                $name
            ),
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $connection;
    } catch (PDOException $error) {
        error_log('Word Truth Spirit database connection failed: ' . $error->getMessage());
        $connection = null;
        return null;
    }
}
