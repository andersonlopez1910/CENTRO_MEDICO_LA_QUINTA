<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/videos.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Video no encontrado.');
}
$find = db()->prepare("SELECT source_value, mime_type FROM videos WHERE id = :id AND source_type = 'local'");
$find->execute(['id' => $id]);
$video = $find->fetch();
if ($video === false || !preg_match('/\A[a-f0-9]{32}\.(mp4|webm|ogg)\z/', $video['source_value'])) {
    http_response_code(404);
    exit('Video no encontrado.');
}
$path = VIDEO_STORAGE . DIRECTORY_SEPARATOR . $video['source_value'];
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('El video ya no está disponible.');
}

$size = filesize($path);
if ($size === false) {
    http_response_code(500);
    exit('No fue posible leer el video.');
}
$start = 0;
$end = $size - 1;
$status = 200;
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $range) === 1) {
    $start = $range[1] === '' ? max(0, $size - (int) $range[2]) : (int) $range[1];
    $end = $range[2] === '' ? $end : min((int) $range[2], $end);
    if ($start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        http_response_code(416);
        exit;
    }
    $status = 206;
}
$length = $end - $start + 1;
http_response_code($status);
header('Content-Type: ' . ($video['mime_type'] ?? 'video/mp4'));
header('Accept-Ranges: bytes');
header('Content-Length: ' . $length);
header('X-Content-Type-Options: nosniff');
if ($status === 206) {
    header("Content-Range: bytes {$start}-{$end}/{$size}");
}
$handle = fopen($path, 'rb');
if ($handle === false) {
    http_response_code(500);
    exit;
}
fseek($handle, $start);
$remaining = $length;
while ($remaining > 0 && !feof($handle)) {
    $chunk = fread($handle, min(8192, $remaining));
    if ($chunk === false) {
        break;
    }
    echo $chunk;
    $remaining -= strlen($chunk);
    flush();
}
fclose($handle);
