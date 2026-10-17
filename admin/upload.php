<?php
/**
 * Admin file upload. Accepts POST (form or FormData).
 * If path_input_id is in POST, returns HTML for iframe that calls parent.setUploadPath/setUploadError.
 * Otherwise returns JSON: { "ok": true, "path": "..." } or { "ok": false, "error": "..." }
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/path.php';

// Allow long-running request for large video uploads (10 min)
@set_time_limit(600);

$pathInputId = isset($_POST['path_input_id']) ? trim((string)$_POST['path_input_id']) : '';
$fromMediaGallery = !empty($_POST['from_media_gallery']);
$returnHtml = $pathInputId !== '' || $fromMediaGallery;
if (!$returnHtml) {
    header('Content-Type: application/json');
}
$sendError = function ($msg) use ($returnHtml, $fromMediaGallery) {
    if ($returnHtml) {
        header('Content-Type: text/html; charset=utf-8');
        if ($fromMediaGallery) {
            echo "<!DOCTYPE html><html><body><script>if(window.parent.onMediaUploadError){window.parent.onMediaUploadError(" . json_encode($msg) . ");}</script></body></html>";
        } else {
            echo "<!DOCTYPE html><html><body><script>if(window.parent.setUploadError){window.parent.setUploadError(" . json_encode($msg) . ");}</script></body></html>";
        }
    } else {
        echo json_encode(['ok' => false, 'error' => $msg]);
    }
    exit;
};

$allowed = [
    'video' => ['ext' => ['mp4', 'webm', 'mov'], 'mime' => ['video/mp4', 'video/webm', 'video/quicktime'], 'dir' => 'videos', 'max' => 100 * 1024 * 1024],
    'image' => ['ext' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'mime' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 'dir' => 'images', 'max' => 5 * 1024 * 1024],
];

$type = '';
$fileKey = null;
if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    foreach ($allowed as $t => $cfg) {
        if (in_array($ext, $cfg['ext'], true)) {
            $type = $t;
            break;
        }
    }
    if ($type === '' && class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['file']['tmp_name']);
        foreach ($allowed as $t => $cfg) {
            if (in_array($mime, $cfg['mime'], true)) {
                $type = $t;
                break;
            }
        }
    }
}
if ($type === '') {
    $raw = isset($_POST['type']) ? $_POST['type'] : (isset($_REQUEST['type']) ? $_REQUEST['type'] : '');
    $type = is_string($raw) ? strtolower(trim($raw)) : '';
}
if (!isset($allowed[$type])) {
    $debug = [];
    if (empty($_FILES['file'])) {
        $debug[] = 'no file in request';
    } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $debug[] = 'file error ' . $_FILES['file']['error'];
    } elseif (empty($_FILES['file']['name'])) {
        $debug[] = 'no filename';
    }
    $msg = 'Invalid type. Upload an image (jpg, png, gif, webp) or video (mp4, webm, mov).';
    if (!empty($debug)) {
        $msg .= ' [' . implode('; ', $debug) . ']';
    }
    $contentLen = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    if ($contentLen > 10 * 1024 * 1024) {
        $msg .= ' Large file? Set upload_max_filesize and post_max_size in PHP (php.ini) to at least the file size, e.g. 100M.';
    }
    $sendError($msg);
}

$config = $allowed[$type];
if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['file']['error'] ?? 'No file';
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        $err = 'File too large. In PHP (php.ini), set upload_max_filesize and post_max_size to at least the file size (e.g. 100M for videos). Restart Apache after changing.';
    } elseif ($err === UPLOAD_ERR_NO_FILE) {
        $err = 'No file selected';
    } else {
        $err = 'Upload failed: ' . $err;
    }
    $sendError($err);
}

$tmp = $_FILES['file']['tmp_name'];
$name = $_FILES['file']['name'];
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmp);

if (!in_array($ext, $config['ext'], true)) {
    $sendError('Invalid file type. Allowed: ' . implode(', ', $config['ext']));
}
if (!in_array($mime, $config['mime'], true)) {
    $sendError('Invalid file content.');
}
if ($_FILES['file']['size'] > $config['max']) {
    $sendError('File too large. Max ' . round($config['max'] / 1024 / 1024) . ' MB.');
}

$uploadDirName = defined('UPLOAD_DIR') ? UPLOAD_DIR : 'uploads';
$baseDir = dirname(__DIR__) . '/' . $uploadDirName . '/' . $config['dir'];
if (!is_dir($baseDir)) {
    if (!@mkdir($baseDir, 0755, true)) {
        $sendError('Could not create upload folder.');
    }
}

$safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($name, PATHINFO_FILENAME));
$fileName = $safeName . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8) . '.' . $ext;
$dest = $baseDir . '/' . $fileName;
if (!move_uploaded_file($tmp, $dest)) {
    $sendError('Could not save file.');
}

$path = $uploadDirName . '/' . $config['dir'] . '/' . $fileName;
$fileSize = (int) @filesize($dest);

// Register in media library so it appears in Media gallery
try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDb();
    $title = isset($_POST['title']) ? trim((string) $_POST['title']) : null;
    $altText = isset($_POST['alt_text']) ? trim((string) $_POST['alt_text']) : null;
    $caption = isset($_POST['caption']) ? trim((string) $_POST['caption']) : null;
    if ($title === '') $title = null;
    if ($altText === '') $altText = null;
    if ($caption === '') $caption = null;
    $stmt = $pdo->prepare('INSERT INTO media (file_path, file_name, type, mime_type, file_size, title, alt_text, caption) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE file_name = VALUES(file_name), mime_type = VALUES(mime_type), file_size = VALUES(file_size), updated_at = CURRENT_TIMESTAMP');
    $stmt->execute([$path, $name, $type, $mime, $fileSize ?: null, $title, $altText, $caption]);
} catch (Throwable $e) {
    // Continue even if media table missing or insert fails; path still returned
}

if ($returnHtml) {
    header('Content-Type: text/html; charset=utf-8');
    if ($fromMediaGallery) {
        echo "<!DOCTYPE html><html><body><script>if(window.parent.onMediaUploadSuccess){window.parent.onMediaUploadSuccess(" . json_encode($path) . ");}</script></body></html>";
    } else {
        echo "<!DOCTYPE html><html><body><script>if(window.parent.setUploadPath){window.parent.setUploadPath(" . json_encode($pathInputId) . "," . json_encode($path) . ");}</script></body></html>";
    }
} else {
    echo json_encode(['ok' => true, 'path' => $path]);
}
