<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

db()->exec(
    "CREATE TABLE IF NOT EXISTS pqrsf (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        reference_code CHAR(27) NOT NULL,
        request_type ENUM('petition', 'complaint', 'claim', 'suggestion', 'compliment') NOT NULL,
        document_type ENUM('CC', 'CE', 'TI', 'PA', 'OT') NOT NULL,
        document_number VARCHAR(30) NOT NULL,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(254) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        subject VARCHAR(180) NOT NULL,
        message TEXT NOT NULL,
        data_accepted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM('received', 'in_progress', 'closed') NOT NULL DEFAULT 'received',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_pqrsf_reference_code (reference_code),
        KEY idx_pqrsf_status_created (status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

fwrite(STDOUT, "Migración de PQRSF completada.\n");
