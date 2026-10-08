<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Documento no encontrado.');
}

$query = db()->prepare('SELECT original_name, stored_name, file_size FROM documentos_pdf WHERE id = :id');
$query->execute(['id' => $id]);
$document = $query->fetch();
if ($document === false || !preg_match('/\A[a-f0-9]{32}\.pdf\z/', $document['stored_name'])) {
    http_response_code(404);
    exit('Documento no encontrado.');
}

$path = PDF_STORAGE . DIRECTORY_SEPARATOR . $document['stored_name'];
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('El archivo ya no está disponible.');
}

$safeName = str_replace(["\r", "\n", '"', '\\'], '', basename($document['original_name']));
$inline = ($_GET['view'] ?? '') === '1';
header('Content-Type: application/pdf');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="documento.pdf"; filename*=UTF-8\'\'' . rawurlencode($safeName));
readfile($path);
