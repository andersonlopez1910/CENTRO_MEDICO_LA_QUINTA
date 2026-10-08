<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo disponible desde la terminal.'); }
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$columns = $pdo->query('SHOW COLUMNS FROM pqrsf')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('notification_status', $columns, true)) {
    $pdo->exec("ALTER TABLE pqrsf ADD COLUMN notification_status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending' AFTER status");
}
if (!in_array('notification_attempted_at', $columns, true)) {
    $pdo->exec('ALTER TABLE pqrsf ADD COLUMN notification_attempted_at DATETIME NULL AFTER notification_status');
}
fwrite(STDOUT, "Migración de notificaciones PQRSF completada.\n");
