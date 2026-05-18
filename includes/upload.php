<?php

declare(strict_types=1);

function saveUploadedEventImage(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Image upload failed.');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Image must be 2 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        throw new InvalidArgumentException('Only JPG, PNG, WEBP, or GIF images are allowed.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = 'event_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $destination = UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded image.');
    }

    return UPLOAD_URL . '/' . $filename;
}
