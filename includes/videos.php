<?php
declare(strict_types=1);

/** @return array<string, string> */
function allowed_video_mime_types(): array
{
    return [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
    ];
}

function video_count(): int
{
    try {
        return (int) db()->query('SELECT COUNT(*) FROM videos')->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

/** @return list<array{id:int,title:string,description:?string,source_type:string,source_value:string,mime_type:?string,file_size:?int,created_at:string}> */
function public_videos(?int $limit = null): array
{
    try {
        $sql = 'SELECT id, title, description, source_type, source_value, mime_type, file_size, created_at FROM videos ORDER BY created_at DESC, id DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, min($limit, 12));
        }
        return db()->query($sql)->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

/**
 * Accepts only known video platforms. If an iframe is supplied, only its src
 * is extracted and normalized; untrusted HTML is never stored or rendered.
 */
function normalize_external_video_source(string $input): ?string
{
    $value = trim($input);
    if (preg_match('/<iframe\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\']/i', $value, $matches) === 1) {
        $value = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        return null;
    }
    $parts = parse_url($value);
    $host = strtolower((string) ($parts['host'] ?? ''));
    $path = trim((string) ($parts['path'] ?? ''), '/');
    parse_str((string) ($parts['query'] ?? ''), $query);
    if (($parts['scheme'] ?? '') !== 'https') {
        return null;
    }

    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        $id = $path;
    } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com'], true)) {
        $id = str_starts_with($path, 'embed/') ? substr($path, 6) : (string) ($query['v'] ?? '');
    } else {
        $id = '';
    }
    if ($id !== '') {
        return preg_match('/\A[A-Za-z0-9_-]{6,20}\z/', $id) === 1 ? 'https://www.youtube-nocookie.com/embed/' . $id : null;
    }

    if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
        $segments = explode('/', $path);
        $id = end($segments);
        return is_string($id) && preg_match('/\A\d{6,15}\z/', $id) === 1 ? 'https://player.vimeo.com/video/' . $id : null;
    }
    return null;
}

/** @param array{id:int,source_type:string,source_value:string,mime_type:?string} $video */
function video_embed_code(array $video): string
{
    if ($video['source_type'] === 'local') {
        return '<video controls preload="metadata" src="video.php?id=' . (int) $video['id'] . '"></video>';
    }
    return '<iframe src="' . e($video['source_value']) . '" title="Video" loading="lazy" allowfullscreen></iframe>';
}

/** @param array{id:int,source_type:string,source_value:string,mime_type:?string,title:string} $video */
function render_video_preview(array $video, string $pathPrefix = ''): void
{
    if ($video['source_type'] === 'local') {
        echo '<video controls preload="metadata"><source src="' . e($pathPrefix . 'video.php?id=' . (int) $video['id']) . '" type="' . e($video['mime_type'] ?? 'video/mp4') . '">Tu navegador no puede reproducir este video.</video>';
        return;
    }
    echo '<iframe src="' . e($video['source_value']) . '" title="' . e($video['title']) . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>';
}
