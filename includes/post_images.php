<?php
declare(strict_types=1);

/** @return array<string, string> */
function allowed_post_image_mime_types(): array
{
    return [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];
}

function is_valid_post_image_name(string $name): bool
{
    return preg_match('/\A[a-f0-9]{32}\.(jpg|jpeg|png|webp|gif|svg)\z/', $name) === 1;
}

function post_image_file_path(string $name): string
{
    return POST_IMAGE_STORAGE . DIRECTORY_SEPARATOR . $name;
}

function remove_post_image(?string $name): void
{
    if ($name === null || !is_valid_post_image_name($name)) {
        return;
    }
    $path = post_image_file_path($name);
    if (is_file($path) && !unlink($path)) {
        error_log('No fue posible eliminar la imagen de publicación: ' . $path);
    }
}

function sanitize_svg_upload(string $source, string $destination): bool
{
    $svg = file_get_contents($source);
    if ($svg === false || strlen($svg) > POST_IMAGE_MAX_SIZE || preg_match('/<!DOCTYPE|<!ENTITY/i', $svg) === 1) {
        return false;
    }

    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded || $document->documentElement === null || strtolower($document->documentElement->localName) !== 'svg') {
        return false;
    }

    $blockedElements = ['script', 'foreignobject', 'iframe', 'object', 'embed', 'audio', 'video', 'animate', 'animatemotion', 'animatetransform', 'set', 'style'];
    $elements = [];
    foreach ($document->getElementsByTagName('*') as $element) {
        $elements[] = $element;
    }
    foreach ($elements as $element) {
        if (in_array(strtolower($element->localName), $blockedElements, true)) {
            $element->parentNode?->removeChild($element);
            continue;
        }
        $attributes = [];
        foreach ($element->attributes ?? [] as $attribute) {
            $attributes[] = $attribute->name;
        }
        foreach ($attributes as $name) {
            $lowerName = strtolower($name);
            if (str_starts_with($lowerName, 'on') || in_array($lowerName, ['style', 'href', 'xlink:href', 'src'], true)) {
                $element->removeAttribute($name);
            }
        }
    }

    return file_put_contents($destination, $document->saveXML(), LOCK_EX) !== false;
}

/** @param array{name:mixed,type:mixed,tmp_name:mixed,error:mixed,size:mixed} $file
 *  @return array{path:string,mime:string}
 */
function store_post_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $message = in_array($file['error'] ?? null, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'La imagen supera el límite permitido de 10 MB.'
            : 'No fue posible recibir la imagen seleccionada.';
        throw new InvalidArgumentException($message);
    }
    if (!is_string($file['name'] ?? null) || !is_string($file['tmp_name'] ?? null) || !is_int($file['size'] ?? null)
        || !is_uploaded_file($file['tmp_name']) || $file['size'] < 1 || $file['size'] > POST_IMAGE_MAX_SIZE) {
        throw new InvalidArgumentException('La imagen debe ser válida y pesar como máximo 10 MB.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = allowed_post_image_mime_types();
    if (!isset($allowed[$extension])) {
        throw new InvalidArgumentException('Solo se permiten imágenes JPG, PNG, WEBP, GIF o SVG.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($mime !== $allowed[$extension]) {
        throw new InvalidArgumentException('El contenido de la imagen no coincide con el formato indicado.');
    }
    if ($extension !== 'svg' && @getimagesize($file['tmp_name']) === false) {
        throw new InvalidArgumentException('El archivo no contiene una imagen válida.');
    }
    if (!is_dir(POST_IMAGE_STORAGE) && !mkdir(POST_IMAGE_STORAGE, 0750, true) && !is_dir(POST_IMAGE_STORAGE)) {
        throw new RuntimeException('No fue posible preparar el almacenamiento de imágenes.');
    }

    $name = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = post_image_file_path($name);
    $stored = $extension === 'svg'
        ? sanitize_svg_upload($file['tmp_name'], $destination)
        : move_uploaded_file($file['tmp_name'], $destination);
    if (!$stored) {
        @unlink($destination);
        throw new RuntimeException('No fue posible guardar la imagen seleccionada.');
    }
    return ['path' => $name, 'mime' => $allowed[$extension]];
}
