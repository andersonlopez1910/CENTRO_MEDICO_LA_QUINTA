<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/post_images.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Imagen no encontrada.');
}

$query = db()->prepare('SELECT status, image_path, image_mime FROM posts WHERE id = :id');
$query->execute(['id' => $id]);
$post = $query->fetch();
if ($post === false || ($post['status'] !== 'published' && !is_admin()) || !is_string($post['image_path']) || !is_valid_post_image_name($post['image_path'])) {
    http_response_code(404);
    exit('Imagen no encontrada.');
}

$path = post_image_file_path($post['image_path']);
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('La imagen ya no está disponible.');
}

$mime = is_string($post['image_mime']) && in_array($post['image_mime'], allowed_post_image_mime_types(), true)
    ? $post['image_mime']
    : 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=3600');
readfile($path);
