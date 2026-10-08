<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = $argv[1] ?? '';
$password = getenv('ADMIN_PASSWORD') ?: '';
if (!preg_match('/\A[a-zA-Z0-9_.-]{3,80}\z/', $username)) {
    fwrite(STDERR, "Uso: php scripts/reset-admin-password.php <usuario>\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "Define ADMIN_PASSWORD con una contraseña de al menos 12 caracteres antes de ejecutar este comando.\n");
    exit(1);
}

$update = db()->prepare('UPDATE usuarios SET password_hash = :password_hash WHERE username = :username');
$update->execute([
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'username' => $username,
]);
if ($update->rowCount() !== 1) {
    fwrite(STDERR, "No se encontró la cuenta administradora indicada.\n");
    exit(1);
}

fwrite(STDOUT, "Contraseña actualizada para {$username}.\n");
