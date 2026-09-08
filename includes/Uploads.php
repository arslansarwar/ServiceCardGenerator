<?php

/**
 * Handles an uploaded image file (JPG/PNG), saving it under uploads/{subdir}/.
 * Returns the stored relative path (e.g. "uploads/logos/abc123.png"), or null if no file was uploaded.
 * Throws a RuntimeException with a user-friendly message on failure.
 */
function handleImageUpload(array $file, string $subdir, int $maxSizeMb = 2): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Image must be a JPG or PNG file.');
    }

    if ($file['size'] > $maxSizeMb * 1024 * 1024) {
        throw new RuntimeException("Image must be smaller than {$maxSizeMb}MB.");
    }

    $ext = $allowed[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir = __DIR__ . '/../uploads/' . $subdir . '/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $destPath = $destDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Could not save uploaded image.');
    }

    return 'uploads/' . $subdir . '/' . $filename;
}

/**
 * Deletes a previously uploaded image, if it exists. Safe to call with null/empty paths.
 */
function deleteUploadedImage(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }
    $fullPath = __DIR__ . '/../' . $relativePath;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}