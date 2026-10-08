<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo disponible desde la terminal.'); }
require_once __DIR__ . '/../config/database.php';
$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS google_oauth_tokens (
 id TINYINT UNSIGNED NOT NULL, client_id VARCHAR(255) NOT NULL, client_secret_encrypted TEXT NOT NULL,
 access_token_encrypted TEXT NULL, refresh_token_encrypted TEXT NULL, token_expires_at DATETIME NULL,
 sender_email VARCHAR(254) NOT NULL DEFAULT 'direccionadm.cmq@gmail.com', recipient_email VARCHAR(254) NOT NULL DEFAULT 'direccionadm.cmq@gmail.com',
 authorized_email VARCHAR(254) NULL, connection_status VARCHAR(32) NOT NULL DEFAULT 'pending', last_error VARCHAR(255) NULL,
 last_checked_at DATETIME NULL, last_verified_at DATETIME NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$columns = $pdo->query('SHOW COLUMNS FROM google_oauth_tokens')->fetchAll(PDO::FETCH_COLUMN);
$changes = [
 'authorized_email' => 'ADD COLUMN authorized_email VARCHAR(254) NULL AFTER recipient_email',
 'connection_status' => "ADD COLUMN connection_status VARCHAR(32) NOT NULL DEFAULT 'pending' AFTER authorized_email",
 'last_error' => 'ADD COLUMN last_error VARCHAR(255) NULL AFTER connection_status',
 'last_checked_at' => 'ADD COLUMN last_checked_at DATETIME NULL AFTER last_error',
 'last_verified_at' => 'ADD COLUMN last_verified_at DATETIME NULL AFTER last_checked_at',
];
foreach ($changes as $column => $sql) if (!in_array($column, $columns, true)) $pdo->exec('ALTER TABLE google_oauth_tokens ' . $sql);
fwrite(STDOUT, "Migración Google OAuth completada.\n");