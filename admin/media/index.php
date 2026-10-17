<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/path.php';
$pdo = getDb();
$currentNav = 'media';
$pageTitle = 'Media Library';
$basePath = rtrim(BASE_PATH, '/');

// Optional: sync existing files from uploads into media (one-time or on demand)
$sync = isset($_GET['sync']) && $_GET['sync'] === '1';
if ($sync) {
    $uploadDir = dirname(__DIR__, 2) . '/' . (defined('UPLOAD_DIR') ? UPLOAD_DIR : 'uploads');
    $dirs = ['images' => 'image', 'videos' => 'video'];
    foreach ($dirs as $sub => $type) {
        $dir = $uploadDir . '/' . $sub;
        if (!is_dir($dir)) continue;
        $uploadDirName = defined('UPLOAD_DIR') ? UPLOAD_DIR : 'uploads';
        $iter = new DirectoryIterator($dir);
        foreach ($iter as $f) {
            if ($f->isDot() || $f->isDir()) continue;
            $name = $f->getFilename();
            $path = $uploadDirName . '/' . $sub . '/' . $name;
            $ext = strtolower($f->getExtension());
            $mime = $type === 'image' ? 'image/jpeg' : 'video/mp4';
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) $mime = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
            elseif (in_array($ext, ['mp4','webm','mov'], true)) $mime = 'video/' . ($ext === 'mov' ? 'quicktime' : $ext);
            try {
                $stmt = $pdo->prepare('INSERT IGNORE INTO media (file_path, file_name, type, mime_type, file_size) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$path, $name, $type, $mime, $f->getSize() ?: null]);
            } catch (Throwable $e) { /* skip */ }
        }
    }
    header('Location: index.php');
    exit;
}

$order = isset($_GET['order']) && $_GET['order'] === 'oldest' ? 'uploaded_at ASC' : 'uploaded_at DESC';
$media = $pdo->query("SELECT * FROM media ORDER BY $order")->fetchAll();

$imageCount = count(array_filter($media, fn($m) => $m['type'] === 'image'));
$videoCount = count(array_filter($media, fn($m) => $m['type'] === 'video'));
$totalSize = array_sum(array_column($media, 'file_size'));

include __DIR__ . '/../includes/header.php';
?>

<!-- Enhanced Page Header -->
 <div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Media Library</h1>
    <p class="page-header-enhanced__subtitle">Upload and manage images and videos. Use "Select from Media" on Pages, Products, and Sections to pick files from here.</p>
  </div>
</div>
</div>
<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo count($media); ?></div>
    <div class="page-stat-card__label">Total Files</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
      All media
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $imageCount; ?></div>
    <div class="page-stat-card__label">Images</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <?php echo $media ? round(($imageCount / count($media)) * 100) : 0; ?>%
    </div>
  </div>

  <div class="page-stat-card page-stat-card--orange">
    <div class="page-stat-card__value"><?php echo $videoCount; ?></div>
    <div class="page-stat-card__label">Videos</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><polygon points="12 15 17 21 7 21 7 9 12 15"/></svg>
      <?php echo $media ? round(($videoCount / count($media)) * 100) : 0; ?>%
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="media-search" placeholder="Search media..." onkeyup="filterMedia()">
  </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" id="type-filter" onchange="filterMedia()">
      <option value="">All Types</option>
      <option value="image">Images</option>
      <option value="video">Videos</option>
    </select>
    <select class="filter-toolbar__select" id="sort-filter" onchange="window.location.href='?order=' + this.value">
      <option value="newest" <?php echo (!isset($_GET['order']) || $_GET['order'] !== 'oldest') ? 'selected' : ''; ?>>Newest First</option>
      <option value="oldest" <?php echo isset($_GET['order']) && $_GET['order'] === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
    </select>
  </div>
  <button type="button" class="btn btn--primary" id="media-upload-btn">
    <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Upload New
  </button>
  <a href="?sync=1" class="btn btn--outline btn--sm" title="Add existing files from uploads folder">
    <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
    Sync
  </a>
</div>

<!-- Upload Form -->
<div id="media-upload-box" class="admin-card" style="display: none;">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Upload New File
    </h3>
  </div>
  <div class="admin-card__body">
    <form id="media-upload-form" method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($basePath); ?>/admin/upload.php" target="admin_upload_frame">
      <input type="hidden" name="from_media_gallery" value="1">
      <input type="hidden" name="type" id="media-upload-type" value="image">
      
      <div class="form-group">
        <label>File (image or video)</label>
        <input type="file" name="file" id="media-file-input" accept="image/*,video/mp4,video/webm,video/quicktime" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Title (optional)</label>
        <input type="text" name="title" class="form-control" placeholder="Media title">
      </div>
      <div class="form-group">
        <label>Alt text (optional)</label>
        <input type="text" name="alt_text" class="form-control" placeholder="Alternative text for accessibility">
      </div>
      <div class="form-group">
        <label>Caption (optional)</label>
        <textarea name="caption" class="form-control" rows="2" placeholder="Caption"></textarea>
      </div>
      
      <div id="media-upload-progress-wrap" class="media-upload-progress" style="display: none;">
        <div class="media-upload-progress__bar"><div class="media-upload-progress__fill media-upload-progress__fill--indeterminate" id="media-upload-progress-fill"></div></div>
        <span class="media-upload-progress__text" id="media-upload-progress-text">Uploading…</span>
      </div>
      
      <button type="submit" class="btn btn--primary" id="media-upload-submit">Upload</button>
      <button type="button" class="btn btn--outline btn--sm" id="media-upload-cancel">Cancel</button>
      <span id="media-upload-status" class="media-upload-status"></span>
    </form>
  </div>
</div>

<!-- Media Grid -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      All Media (<?php echo count($media); ?> items)
    </h3>
  </div>
  <div class="admin-card__body">
    <?php if (empty($media)): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" width="48" height="48" style="color: var(--text-muted);"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        <p>No media yet. Use <strong>Upload New</strong> to upload, or <strong>Sync uploads</strong> to add existing files from the uploads folder.</p>
      </div>
    <?php else: ?>
      <div class="media-grid" id="media-grid">
        <?php foreach ($media as $m): ?>
          <?php
          $url = $basePath . '/' . str_replace('\\', '/', $m['file_path']);
          $titleDisplay = !empty($m['title']) ? $m['title'] : $m['file_name'];
          ?>
          <div class="media-grid__item" data-title="<?php echo strtolower(htmlspecialchars($titleDisplay)); ?>" data-type="<?php echo $m['type']; ?>" data-name="<?php echo strtolower(htmlspecialchars($m['file_name'])); ?>">
            <a href="edit.php?id=<?php echo (int)$m['id']; ?>" class="media-grid__link">
              <?php if ($m['type'] === 'image'): ?>
                <img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($m['alt_text'] ?? $m['file_name']); ?>" class="media-grid__thumb" loading="lazy">
              <?php else: ?>
                <div class="media-grid__video-wrap">
                  <video src="<?php echo htmlspecialchars($url); ?>" class="media-grid__thumb" muted preload="metadata"></video>
                  <span class="media-grid__video-icon" aria-hidden="true">▶</span>
                </div>
              <?php endif; ?>
              <span class="media-grid__title"><?php echo htmlspecialchars($titleDisplay); ?></span>
              <span class="media-grid__meta"><?php echo htmlspecialchars($m['file_name']); ?> · <?php echo $m['type']; ?></span>
            </a>
            <div class="media-grid__actions">
              <a href="edit.php?id=<?php echo (int)$m['id']; ?>" class="btn btn--primary btn--sm">Edit</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function() {
  var uploadBtn = document.getElementById('media-upload-btn');
  var uploadBox = document.getElementById('media-upload-box');
  var uploadForm = document.getElementById('media-upload-form');
  var fileInput = document.getElementById('media-file-input');
  var typeInput = document.getElementById('media-upload-type');
  var cancelBtn = document.getElementById('media-upload-cancel');
  var statusEl = document.getElementById('media-upload-status');
  var progressWrap = document.getElementById('media-upload-progress-wrap');
  var progressFill = document.getElementById('media-upload-progress-fill');
  var submitBtn = document.getElementById('media-upload-submit');

  function showProgress(show) {
    if (progressWrap) progressWrap.style.display = show ? 'block' : 'none';
    if (progressFill) progressFill.classList.toggle('media-upload-progress__fill--indeterminate', show);
    if (submitBtn) submitBtn.disabled = !!show;
  }

  if (uploadBtn) uploadBtn.addEventListener('click', function() {
    uploadBox.style.display = uploadBox.style.display === 'none' ? 'block' : 'none';
    if (fileInput) fileInput.value = '';
    if (statusEl) statusEl.textContent = '';
    showProgress(false);
  });
  if (cancelBtn) cancelBtn.addEventListener('click', function() {
    uploadBox.style.display = 'none';
    showProgress(false);
  });

  var uploadTimeoutId = null;
  var UPLOAD_TIMEOUT_MS = 10 * 60 * 1000;

  function clearUploadTimeout() {
    if (uploadTimeoutId) {
      clearTimeout(uploadTimeoutId);
      uploadTimeoutId = null;
    }
  }

  // Form POST to iframe so the file is received by the server (XHR drops file on some setups)
  if (uploadForm && fileInput && typeInput) {
    uploadForm.addEventListener('submit', function(e) {
      if (!fileInput.files || !fileInput.files[0]) {
        e.preventDefault();
        if (statusEl) statusEl.textContent = 'Please select a file.';
        return;
      }
      typeInput.value = (fileInput.files[0].type || '').indexOf('video') !== -1 ? 'video' : 'image';
      if (statusEl) statusEl.textContent = '';
      showProgress(true);
      clearUploadTimeout();
      uploadTimeoutId = setTimeout(function() {
        uploadTimeoutId = null;
        showProgress(false);
        alert('Upload is taking too long. For videos:\n\n' +
          '• Max size here is 100 MB.\n' +
          '• In PHP (php.ini or WAMP PHP settings), set:\n  upload_max_filesize = 100M\n  post_max_size = 100M\n  max_execution_time = 600\n' +
          '• Restart Apache after changing php.ini.');
      }, UPLOAD_TIMEOUT_MS);
    });
  }

  window.onMediaUploadSuccess = function(path) {
    clearUploadTimeout();
    showProgress(false);
    if (statusEl) statusEl.textContent = 'Uploaded. Reloading…';
    window.location.reload();
  };
  window.onMediaUploadError = function(msg) {
    clearUploadTimeout();
    showProgress(false);
    if (statusEl) statusEl.textContent = '';
    alert(msg);
  };
})();

function filterMedia() {
  const searchValue = document.getElementById('media-search').value.toLowerCase();
  const typeFilter = document.getElementById('type-filter').value;
  const items = document.querySelectorAll('.media-grid__item');
  
  items.forEach(function(item) {
    const title = item.dataset.title;
    const name = item.dataset.name;
    const type = item.dataset.type;
    
    let matchesSearch = title.includes(searchValue) || name.includes(searchValue);
    let matchesType = !typeFilter || type === typeFilter;
    
    if (matchesSearch && matchesType) {
      item.style.display = '';
    } else {
      item.style.display = 'none';
    }
  });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
