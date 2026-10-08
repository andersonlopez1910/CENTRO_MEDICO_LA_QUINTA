<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

db()->exec(
    "CREATE TABLE IF NOT EXISTS videos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        title VARCHAR(180) NOT NULL,
        description TEXT NULL,
        source_type ENUM('local', 'external') NOT NULL,
        source_value VARCHAR(255) NOT NULL,
        mime_type VARCHAR(80) NULL,
        file_size BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_videos_source_value (source_value),
        KEY idx_videos_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$columns = db()->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'videos'")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('description', $columns, true)) {
    db()->exec('ALTER TABLE videos ADD COLUMN description TEXT NULL AFTER title');
}

fwrite(STDOUT, "Migración de videos completada.\n");
