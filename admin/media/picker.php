<?php
/**
 * Media picker popup: select from media library or upload new. Used by Pages, Products, Sections.
 * GET: field_id (required), type (optional: image|video to filter)
 * On select: calls opener.setUploadPath(field_id, path) and closes.
 */
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/path.php';
$pdo = getDb();
$basePath = rtrim(BASE_PATH, '/');
$adminBase = $basePath . '/admin';

$fieldId = isset($_GET['field_id']) ? trim((string) $_GET['field_id']) : '';
$typeFilter = isset($_GET['type']) ? strtolower(trim((string) $_GET['type'])) : '';
if ($fieldId === '') {
    header('Location: index.php');
    exit;
}
if ($typeFilter !== '' && $typeFilter !== 'image' && $typeFilter !== 'video') {
    $typeFilter = '';
}

$order = isset($_GET['order']) && $_GET['order'] === 'oldest' ? 'uploaded_at ASC' : 'uploaded_at DESC';
if ($typeFilter !== '') {
    $stmt = $pdo->prepare("SELECT * FROM media WHERE type = ? ORDER BY $order");
    $stmt->execute([$typeFilter]);
    $media = $stmt->fetchAll();
} else {
    $media = $pdo->query("SELECT * FROM media ORDER BY $order")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Select from Media</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($adminBase); ?>/assets/css/admin.css">
  <style>
    body { margin: 0; padding: 1rem; background: var(--bg, #f0f2f5); }
    .picker-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
    .picker-header h1 { margin: 0; font-size: 1.25rem; }
    .media-picker-actions { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
    .media-grid__item .btn { margin-top: 0.25rem; }
  </style>
</head>
<body>
  <div class="picker-header">
    <h1>Select from Media</h1>
    <div class="media-picker-actions">
      <?php if ($typeFilter !== ''): ?>
        <a href="picker.php?field_id=<?php echo htmlspecialchars(urlencode($fieldId)); ?>" class="btn btn--outline btn--sm">All</a>
      <?php endif; ?>
      <a href="picker.php?field_id=<?php echo htmlspecialchars(urlencode($fieldId)); ?>&type=image" class="btn btn--outline btn--sm">Images only</a>
      <a href="picker.php?field_id=<?php echo htmlspecialchars(urlencode($fieldId)); ?>&type=video" class="btn btn--outline btn--sm">Videos only</a>
      <button type="button" class="btn btn--outline btn--sm" onclick="window.close()">Cancel</button>
    </div>
  </div>

  <div id="picker-upload-box" class="admin-card" style="display: none; margin-bottom: 1rem;">
    <div class="admin-card__header">Upload new file</div>
    <div class="admin-card__body">
      <form id="picker-upload-form" method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($adminBase); ?>/upload.php" target="picker_upload_frame">
        <input type="hidden" name="from_media_gallery" value="1">
        <input type="hidden" name="type" id="picker-upload-type" value="<?php echo $typeFilter ?: 'image'; ?>">
        <div class="form-group">
          <label>File</label>
          <input type="file" name="file" id="picker-file-input" accept="<?php echo $typeFilter === 'video' ? 'video/mp4,video/webm,video/quicktime' : 'image/*'; ?>" class="form-control" required>
        </div>
        <button type="submit" class="btn btn--primary">Upload &amp; use</button>
        <button type="button" class="btn btn--outline btn--sm" id="picker-upload-cancel">Cancel</button>
      </form>
    </div>
  </div>
  <iframe name="picker_upload_frame" id="picker_upload_frame" style="position:absolute;width:0;height:0;border:0;visibility:hidden;" title="Upload"></iframe>

  <div class="admin-card">
    <div class="admin-card__body">
      <p style="margin: 0 0 0.5rem;">
        <button type="button" class="btn btn--primary btn--sm" id="picker-add-new">Add new (upload)</button>
      </p>
      <?php if (empty($media)): ?>
        <p class="text-muted">No media yet. Click &quot;Add new (upload)&quot; to upload a file, then it will be selected for you.</p>
      <?php else: ?>
        <div class="media-grid">
          <?php foreach ($media as $m): ?>
            <?php
            $url = $basePath . '/' . str_replace('\\', '/', $m['file_path']);
            $titleDisplay = !empty($m['title']) ? $m['title'] : $m['file_name'];
            ?>
            <div class="media-grid__item">
              <div class="media-grid__link" style="pointer-events: none;">
                <?php if ($m['type'] === 'image'): ?>
                  <img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($m['alt_text'] ?? $m['file_name']); ?>" class="media-grid__thumb" loading="lazy">
                <?php else: ?>
                  <div class="media-grid__video-wrap">
                    <video src="<?php echo htmlspecialchars($url); ?>" class="media-grid__thumb" muted preload="metadata"></video>
                    <span class="media-grid__video-icon" aria-hidden="true">▶</span>
                  </div>
                <?php endif; ?>
                <span class="media-grid__title"><?php echo htmlspecialchars($titleDisplay); ?></span>
              </div>
              <div class="media-grid__actions">
                <button type="button" class="btn btn--primary btn--sm" data-path="<?php echo htmlspecialchars($m['file_path'], ENT_QUOTES, 'UTF-8'); ?>">Select</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  (function() {
    var fieldId = <?php echo json_encode($fieldId); ?>;
    function selectPath(path) {
      if (window.opener && typeof window.opener.setUploadPath === 'function') {
        window.opener.setUploadPath(fieldId, path);
      }
      window.close();
    }

    document.querySelectorAll('.media-grid__actions button[data-path]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        selectPath(this.getAttribute('data-path'));
      });
    });

    var addBtn = document.getElementById('picker-add-new');
    var uploadBox = document.getElementById('picker-upload-box');
    var cancelBtn = document.getElementById('picker-upload-cancel');
    var fileInput = document.getElementById('picker-file-input');
    var typeInput = document.getElementById('picker-upload-type');
    if (addBtn) addBtn.addEventListener('click', function() {
      uploadBox.style.display = uploadBox.style.display === 'none' ? 'block' : 'none';
      if (fileInput) fileInput.value = '';
    });
    if (cancelBtn) cancelBtn.addEventListener('click', function() { uploadBox.style.display = 'none'; });
    if (fileInput) fileInput.addEventListener('change', function() {
      if (fileInput.files && fileInput.files[0])
        typeInput.value = (fileInput.files[0].type || '').indexOf('video') !== -1 ? 'video' : 'image';
    });

    window.onMediaUploadSuccess = function(path) {
      selectPath(path);
    };
    window.onMediaUploadError = function(msg) {
      alert(msg);
    };
  })();
  </script>
</body>
</html>
