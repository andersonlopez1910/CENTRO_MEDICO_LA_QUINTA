<?php
declare(strict_types=1);

function db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $localConfig = __DIR__ . '/database.local.php';
    $local = is_file($localConfig) ? require $localConfig : [];
    if (!is_array($local)) {
        throw new RuntimeException('La configuración local de base de datos no es válida.');
    }
    $host = $local['host'] ?? getenv('DB_HOST') ?: '127.0.0.1';
    $port = $local['port'] ?? getenv('DB_PORT') ?: '3306';
    $name = $local['name'] ?? getenv('DB_NAME') ?: 'centro_medico';
    $user = $local['user'] ?? getenv('DB_USER') ?: 'root';
    $password = $local['password'] ?? getenv('DB_PASSWORD') ?: '';
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $connection = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}
