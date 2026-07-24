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

function databaseTableExists(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) return $cache[$table];
    $db = database();
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) return $cache[$table] = false;
    try {
        $statement = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $statement->execute([$table]);
        return $cache[$table] = (bool) $statement->fetchColumn();
    } catch (PDOException $error) {
        return $cache[$table] = false;
    }
}

function databaseUsesLegacySchema(): bool
{
    return databaseTableExists('posts') && databaseTableExists('users');
}

function uuidV4(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}
