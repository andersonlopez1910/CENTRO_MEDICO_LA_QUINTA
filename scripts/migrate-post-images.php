<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo disponible desde la terminal.');
}

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$columns = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts'")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('image_path', $columns, true)) {
    $pdo->exec('ALTER TABLE posts ADD COLUMN image_path VARCHAR(255) NULL AFTER content');
    echo "Se agregó posts.image_path.\n";
}
if (!in_array('image_mime', $columns, true)) {
    $pdo->exec('ALTER TABLE posts ADD COLUMN image_mime VARCHAR(80) NULL AFTER image_path');
    echo "Se agregó posts.image_mime.\n";
}
echo "Migración de imágenes de publicaciones completada.\n";
