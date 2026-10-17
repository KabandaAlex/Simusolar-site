<?php
/**
 * Process uploaded file and return stored path (relative to project root).
 * Returns null on failure.
 * $type: 'video' | 'image'
 * $inputName: $_FILES key name
 */
function handleUpload(string $type, string $inputName = 'file'): ?string {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$inputName];
    $allowedVideo = ['video/mp4', 'video/webm'];
    $allowedImage = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtVideo = ['mp4', 'webm'];
    $allowedExtImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxVideo = 100 * 1024 * 1024; // 100 MB
    $maxImage = 10 * 1024 * 1024;  // 10 MB

    if ($type === 'video') {
        $allowed = $allowedVideo;
        $exts = $allowedExtVideo;
        $max = $maxVideo;
        $subdir = 'videos';
    } else {
        $allowed = $allowedImage;
        $exts = $allowedExtImage;
        $max = $maxImage;
        $subdir = 'images';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        return null;
    }
    if ($file['size'] > $max) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $exts, true)) {
        return null;
    }

    $baseDir = dirname(__DIR__, 2) . '/uploads/' . $subdir;
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);
    }
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
    $name = $name ?: 'file';
    $destName = $name . '_' . time() . '.' . $ext;
    $destPath = $baseDir . '/' . $destName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return null;
    }
    return 'uploads/' . $subdir . '/' . $destName;
}
