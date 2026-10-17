<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/path.php';
$pdo = getDb();
$currentNav = 'media';
$basePath = rtrim(BASE_PATH, '/');
$uploadDirName = defined('UPLOAD_DIR') ? UPLOAD_DIR : 'uploads';
$rootPath = dirname(__DIR__, 2);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}
$stmt = $pdo->prepare('SELECT * FROM media WHERE id = ?');
$stmt->execute([$id]);
$media = $stmt->fetch();
if (!$media) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $filePath = $rootPath . '/' . str_replace('\\', '/', $media['file_path']);
    if (file_exists($filePath) && is_file($filePath)) {
        @unlink($filePath);
    }
    $pdo->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
    header('Location: index.php?deleted=1');
    exit;
}

// Update attributes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $title = trim($_POST['title'] ?? '') ?: null;
    $altText = trim($_POST['alt_text'] ?? '') ?: null;
    $caption = trim($_POST['caption'] ?? '') ?: null;
    $stmt = $pdo->prepare('UPDATE media SET title = ?, alt_text = ?, caption = ? WHERE id = ?');
    $stmt->execute([$title, $altText, $caption, $id]);
    $message = 'Attributes updated.';
    $media['title'] = $title;
    $media['alt_text'] = $altText;
    $media['caption'] = $caption;
}

$pageTitle = 'Edit: ' . ($media['title'] ?: $media['file_name']);
$mediaUrl = $basePath . '/' . str_replace('\\', '/', $media['file_path']);
$fileSize = $media['file_size'] ? number_format($media['file_size'] / 1024, 1) . ' KB' : '—';

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-page-intro">
  <div class="admin-page-intro__content">
    <h1>Edit media</h1>
    <p class="admin-page-intro__subtitle">Update title, alt text, and caption for this file.</p>
  </div>
  <div class="admin-page-intro__actions">
    <a href="index.php" class="btn btn--outline btn--sm">← Back to library</a>
  </div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="admin-alert admin-alert--error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card__header">
    <span class="card-header-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>
    Preview &amp; details
  </div>
  <div class="admin-card__body">
    <div class="media-detail-preview">
      <?php if ($media['type'] === 'image'): ?>
        <img src="<?php echo htmlspecialchars($mediaUrl); ?>" alt="<?php echo htmlspecialchars($media['alt_text'] ?? $media['file_name']); ?>">
      <?php else: ?>
        <video src="<?php echo htmlspecialchars($mediaUrl); ?>" controls></video>
      <?php endif; ?>
    </div>
    <div class="media-detail-meta">
      <strong>File:</strong> <?php echo htmlspecialchars($media['file_name']); ?><br>
      <strong>Path:</strong> <code><?php echo htmlspecialchars($media['file_path']); ?></code><br>
      <strong>Type:</strong> <?php echo htmlspecialchars($media['type']); ?> · <?php echo htmlspecialchars($media['mime_type']); ?><br>
      <strong>Size:</strong> <?php echo htmlspecialchars($fileSize); ?><br>
      <strong>Uploaded:</strong> <?php echo htmlspecialchars($media['uploaded_at']); ?>
    </div>

    <form method="post">
      <input type="hidden" name="action" value="update">
      <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" class="form-control form-control--full" value="<?php echo htmlspecialchars($media['title'] ?? ''); ?>" placeholder="Media title">
      </div>
      <div class="form-group">
        <label>Alt text</label>
        <input type="text" name="alt_text" class="form-control form-control--full" value="<?php echo htmlspecialchars($media['alt_text'] ?? ''); ?>" placeholder="Alternative text for accessibility">
      </div>
      <div class="form-group">
        <label>Caption</label>
        <textarea name="caption" class="form-control form-control--full" rows="3" placeholder="Caption"><?php echo htmlspecialchars($media['caption'] ?? ''); ?></textarea>
      </div>
      <div class="media-detail-actions">
        <button type="submit" class="btn btn--primary">Update attributes</button>
      </div>
    </form>

    <hr style="margin: 2rem 0 1rem; border: 0; border-top: 1px solid var(--border);">
    <div class="media-detail-actions">
      <form method="post" onsubmit="return confirm('Delete this file from the server and remove it from the library?');">
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="btn btn--danger">Delete permanently</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
