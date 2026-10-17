<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getDb();
$currentNav = 'sections';
$pageTitle = 'Sections';

$message = '';
$messageType = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare('UPDATE page_sections SET title = ?, content = ?, content_extra = ?, image_path = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([
            trim($_POST['title'] ?? ''),
            trim($_POST['content'] ?? ''),
            trim($_POST['content_extra'] ?? ''),
            trim($_POST['image_path'] ?? '') ?: null,
            (int)($_POST['sort_order'] ?? 0),
            (int)$_POST['id']
        ]);
        $message = 'Section updated successfully.';
        $messageType = 'success';
    }
    
    if ($action === 'reorder' && !empty($_POST['orders'])) {
        $orders = json_decode($_POST['orders'], true);
        if (is_array($orders)) {
            $result = bulkUpdateSectionOrder($pdo, $orders);
            if ($result['success']) {
                $message = 'Section order updated successfully.';
                $messageType = 'success';
            } else {
                $message = $result['error'] ?? 'Failed to update order.';
                $messageType = 'error';
            }
        }
    }
}

$pageFilter = isset($_GET['page_id']) ? (int)$_GET['page_id'] : null;
$sql = 'SELECT s.*, p.title AS page_title, p.slug AS page_slug FROM page_sections s JOIN pages p ON p.id = s.page_id';
$params = [];
if ($pageFilter) {
    $sql .= ' WHERE s.page_id = ?';
    $params[] = $pageFilter;
}
$sql .= ' ORDER BY p.sort_order, s.sort_order, s.id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sections = $stmt->fetchAll();
$pages = $pdo->query('SELECT id, slug, title FROM pages ORDER BY sort_order')->fetchAll();
$sectionKeysWithItems = ['how_it_works', 'transform_farm', 'impact_numbers', 'solar_solutions'];

$sectionsWithImages = count(array_filter($sections, fn($s) => !empty($s['image_path'])));
$sectionsWithItems = count(array_filter($sections, fn($s) => in_array($s['section_key'], $sectionKeysWithItems, true)));

$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
 <div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Page Sections</h1>
    <p class="page-header-enhanced__subtitle">Edit content blocks (titles, body text, images) for each page on your website. Drag rows to reorder sections.</p>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="section-message">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
  </div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo count($sections); ?></div>
    <div class="page-stat-card__label">Total Sections</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      All content blocks
    </div>
  </div>

  <div class="page-stat-card page-stat-card--orange">
    <div class="page-stat-card__value"><?php echo $sectionsWithImages; ?></div>
    <div class="page-stat-card__label">With Images</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <?php echo $sections ? round(($sectionsWithImages / count($sections)) * 100) : 0; ?>% with images
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $sectionsWithItems; ?></div>
    <div class="page-stat-card__label">With Items</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      Multi-item sections
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="section-search" placeholder="Search sections..." onkeyup="filterSections()">
  </div>
  <div class="filter-toolbar__filters">
    <form method="get" id="page-filter-form" style="display: inline;">
      <select name="page_id" id="page_id" class="filter-toolbar__select" onchange="this.form.submit()">
        <option value="">All Pages</option>
        <?php foreach ($pages as $p): ?>
          <option value="<?php echo (int)$p['id']; ?>" <?php echo $pageFilter === (int)$p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['title']); ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <select class="filter-toolbar__select" id="type-filter" onchange="filterSections()">
      <option value="">All Types</option>
      <option value="with-image">With Image</option>
      <option value="with-items">With Items</option>
    </select>
  </div>
  <div class="filter-toolbar__actions">
    <button type="button" class="btn btn--primary btn--sm" onclick="saveAllOrders()" id="save-order-btn" style="display: none;">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Save Order
    </button>
  </div>
</div>

<!-- Sections Table -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Content Sections
    </h3>
    <span class="admin-card__hint">
      <svg viewBox="0 0 24 24" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      Drag rows to reorder sections within each page
    </span>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table enhanced-data-table sortable-table" id="sections-table">
      <thead>
        <tr>
          <th class="th-drag-handle"></th>
          <th class="th-order">#</th>
          <th>Page</th>
          <th>Section Key</th>
          <th>Title</th>
          <th>Has Image</th>
          <th>Has Items</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="sections-tbody">
        <?php 
        $currentPageId = null;
        $sectionIndex = 0;
        foreach ($sections as $s): 
            // Reset index when page changes
            if ($currentPageId !== $s['page_id']) {
                $currentPageId = $s['page_id'];
                $sectionIndex = 0;
            }
            $sectionIndex++;
        ?>
        <tr data-section-id="<?php echo (int)$s['id']; ?>" 
            data-page="<?php echo strtolower(htmlspecialchars($s['page_slug'])); ?>" 
            data-key="<?php echo strtolower(htmlspecialchars($s['section_key'])); ?>"
            data-has-image="<?php echo !empty($s['image_path']) ? 'yes' : 'no'; ?>"
            data-has-items="<?php echo in_array($s['section_key'], $sectionKeysWithItems, true) ? 'yes' : 'no'; ?>"
            data-sort-order="<?php echo (int)$s['sort_order']; ?>"
            data-page-id="<?php echo (int)$s['page_id']; ?>"
            class="sortable-row">
          <td class="td-drag-handle">
            <div class="drag-handle" title="Drag to reorder">
              <svg viewBox="0 0 24 24" width="16" height="16"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
            </div>
          </td>
          <td class="td-order">
            <span class="order-badge"><?php echo $sectionIndex; ?></span>
          </td>
          <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars($s['page_slug']); ?></code></td>
          <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars($s['section_key']); ?></code></td>
          <td>
            <div class="enhanced-data-table__cell-primary">
              <div class="enhanced-data-table__cell-title"><?php echo htmlspecialchars(mb_substr($s['title'] ?? '', 0, 50)); ?><?php echo mb_strlen($s['title'] ?? '') > 50 ? '…' : ''; ?></div>
            </div>
          </td>
          <td>
            <?php if (!empty($s['image_path'])): ?>
              <span class="badge badge--success">Yes</span>
            <?php else: ?>
              <span class="badge badge--muted">No</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (in_array($s['section_key'], $sectionKeysWithItems, true)): ?>
              <span class="badge badge--info">Yes</span>
            <?php else: ?>
              <span class="badge badge--muted">No</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display: flex; gap: var(--space-2); align-items: center;">
              <button type="button" class="btn btn--icon btn--sm" onclick="moveSectionUpRow(<?php echo (int)$s['id']; ?>)" title="Move up">
                <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="18 15 12 9 6 15"/></svg>
              </button>
              <button type="button" class="btn btn--icon btn--sm" onclick="moveSectionDownRow(<?php echo (int)$s['id']; ?>)" title="Move down">
                <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
              <button type="button" class="btn btn--primary btn--sm" data-toggle-row="<?php echo (int)$s['id']; ?>">Edit</button>
              <?php if (in_array($s['section_key'], $sectionKeysWithItems, true)): ?>
              <a href="section_items.php?section_id=<?php echo (int)$s['id']; ?>" class="btn btn--outline btn--sm" title="Edit items">
                <svg viewBox="0 0 24 24" width="16" height="16"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <tr id="row-form-<?php echo (int)$s['id']; ?>" class="expand-row" style="display: none;">
          <td colspan="8">
            <form method="post" style="max-width: 640px;">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
              
              <div class="form-section">
                <p class="form-section-title">Section Content</p>
                <div class="form-group">
                  <label>Title</label>
                  <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($s['title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                  <label>Content</label>
                  <textarea name="content" class="form-control form-control--full" rows="6"><?php echo htmlspecialchars($s['content'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                  <label>Content extra</label>
                  <textarea name="content_extra" class="form-control form-control--full" rows="3"><?php echo htmlspecialchars($s['content_extra'] ?? ''); ?></textarea>
                </div>
              </div>
              
              <div class="form-section">
                <p class="form-section-title">Image</p>
                <div class="form-group">
                  <label>Image (optional)</label>
                  <div class="upload-field">
                    <input type="text" id="section_image_<?php echo (int)$s['id']; ?>" name="image_path" class="form-control" value="<?php echo htmlspecialchars($s['image_path'] ?? ''); ?>" placeholder="Upload or enter path">
                    <button type="button" class="btn btn--secondary btn--sm" onclick="openMediaPicker('section_image_<?php echo (int)$s['id']; ?>', 'image')">Select from Media</button>
                  </div>
                  <?php if (!empty($s['image_path'])): ?>
                    <div class="upload-field-preview">
                      <img src="<?php echo htmlspecialchars($basePath . '/' . $s['image_path']); ?>" alt="" style="max-height: 120px; border-radius: var(--radius-md);">
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              
              <div class="form-section">
                <p class="form-section-title">Display Settings</p>
                <div class="form-group">
                  <label>Sort order</label>
                  <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$s['sort_order']; ?>" style="max-width: 100px;">
                </div>
              </div>
              
              <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn--primary">Save section</button>
                <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('row-form-<?php echo (int)$s['id']; ?>').style.display='none'">Cancel</button>
              </div>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- SortableJS for drag-and-drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
/* Drag and Drop Table Styles */
.sortable-table .th-drag-handle {
    width: 40px;
}

.sortable-table .th-order {
    width: 50px;
}

.sortable-table .td-drag-handle {
    padding: var(--space-2) !important;
}

.drag-handle {
    cursor: grab;
    padding: var(--space-2);
    border-radius: var(--radius-sm);
    color: var(--text-muted);
    transition: all 0.2s;
}

.drag-handle:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.drag-handle:active {
    cursor: grabbing;
}

.sortable-row {
    transition: all 0.2s ease;
}

.sortable-row--ghost {
    opacity: 0.4;
    background: var(--primary-light) !important;
}

.sortable-row--chosen {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.sortable-row--drag {
    background: white;
}

.order-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Toast Notification */
.sections-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-4);
    background: var(--gray-800);
    color: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    z-index: 9999;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
}

.sections-toast.show {
    transform: translateY(0);
    opacity: 1;
}

.sections-toast--success {
    background: var(--success);
}

.sections-toast--error {
    background: var(--danger);
}

/* Save Indicator */
.sections-save-indicator {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-4) var(--space-6);
    background: rgba(0, 0, 0, 0.85);
    color: white;
    border-radius: var(--radius-lg);
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
}

.sections-save-indicator.show {
    opacity: 1;
    visibility: visible;
}

.sections-save-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Hint text */
.admin-card__hint {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: normal;
}
</style>

<script>
// Toast notification
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.sections-toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = `sections-toast sections-toast--${type}`;
    toast.innerHTML = `
        <svg viewBox="0 0 24 24" width="18" height="18">
            ${type === 'success' 
                ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
                : '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'
            }
        </svg>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Save indicator
let saveIndicator = null;

function showSaveIndicator() {
    if (!saveIndicator) {
        saveIndicator = document.createElement('div');
        saveIndicator.className = 'sections-save-indicator';
        saveIndicator.innerHTML = '<div class="sections-save-spinner"></div><span>Saving order...</span>';
        document.body.appendChild(saveIndicator);
    }
    saveIndicator.classList.add('show');
}

function hideSaveIndicator() {
    if (saveIndicator) saveIndicator.classList.remove('show');
}

// Track order changes
let orderChanged = false;
let pendingOrders = {};

// Initialize SortableJS
let sortableInstance = null;

function initSectionsSortable() {
    const tbody = document.getElementById('sections-tbody');
    if (!tbody) return;
    
    sortableInstance = new Sortable(tbody, {
        animation: 200,
        ghostClass: 'sortable-row--ghost',
        chosenClass: 'sortable-row--chosen',
        dragClass: 'sortable-row--drag',
        handle: '.drag-handle',
        filter: '.expand-row',
        preventOnFilter: true,
        onEnd: function(evt) {
            if (evt.oldIndex !== evt.newIndex) {
                updateOrderBadges();
                collectPendingOrders();
                saveOrderViaAjax();
            }
        }
    });
}

function updateOrderBadges() {
    // Group rows by page and update order badges
    const rows = document.querySelectorAll('#sections-tbody .sortable-row');
    const pageGroups = {};
    
    rows.forEach(row => {
        const pageId = row.dataset.pageId;
        if (!pageGroups[pageId]) pageGroups[pageId] = [];
        pageGroups[pageId].push(row);
    });
    
    Object.values(pageGroups).forEach(group => {
        group.forEach((row, index) => {
            const badge = row.querySelector('.order-badge');
            if (badge) badge.textContent = index + 1;
        });
    });
}

function collectPendingOrders() {
    pendingOrders = {};
    const rows = document.querySelectorAll('#sections-tbody .sortable-row');
    
    // Group by page and collect orders
    const pageGroups = {};
    rows.forEach(row => {
        const pageId = row.dataset.pageId;
        if (!pageGroups[pageId]) pageGroups[pageId] = [];
        pageGroups[pageId].push(parseInt(row.dataset.sectionId));
    });
    
    // Flatten to section_id => sort_order
    Object.values(pageGroups).forEach(group => {
        group.forEach((sectionId, index) => {
            pendingOrders[sectionId] = index + 1;
        });
    });
}

function saveOrderViaAjax() {
    if (Object.keys(pendingOrders).length === 0) return;
    
    showSaveIndicator();
    
    fetch('api/sections.php?action=bulk-reorder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orders: pendingOrders })
    })
    .then(response => response.json())
    .then(data => {
        hideSaveIndicator();
        if (data.success) {
            showToast('Section order saved successfully', 'success');
            orderChanged = false;
        } else {
            showToast(data.error || 'Failed to save order', 'error');
        }
    })
    .catch(error => {
        hideSaveIndicator();
        showToast('Network error. Please try again.', 'error');
        console.error('Save order error:', error);
    });
}

// Move section up/down via API
function moveSectionUpRow(sectionId) {
    showSaveIndicator();
    fetch('api/sections.php?action=move-up', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ section_id: sectionId })
    })
    .then(response => response.json())
    .then(data => {
        hideSaveIndicator();
        if (data.success) {
            showToast('Section moved up', 'success');
            location.reload();
        } else {
            showToast(data.error || 'Could not move section', 'error');
        }
    })
    .catch(error => {
        hideSaveIndicator();
        showToast('Network error', 'error');
    });
}

function moveSectionDownRow(sectionId) {
    showSaveIndicator();
    fetch('api/sections.php?action=move-down', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ section_id: sectionId })
    })
    .then(response => response.json())
    .then(data => {
        hideSaveIndicator();
        if (data.success) {
            showToast('Section moved down', 'success');
            location.reload();
        } else {
            showToast(data.error || 'Could not move section', 'error');
        }
    })
    .catch(error => {
        hideSaveIndicator();
        showToast('Network error', 'error');
    });
}

// Filter sections
function filterSections() {
    const searchValue = document.getElementById('section-search').value.toLowerCase();
    const typeFilter = document.getElementById('type-filter').value;
    const rows = document.querySelectorAll('#sections-table tbody tr[data-page]');
    
    rows.forEach(function(row) {
        if (row.classList.contains('expand-row')) return;
        
        const page = row.dataset.page;
        const key = row.dataset.key;
        const hasImage = row.dataset.hasImage;
        const hasItems = row.dataset.hasItems;
        
        let matchesSearch = page.includes(searchValue) || key.includes(searchValue);
        let matchesType = true;
        
        if (typeFilter === 'with-image') {
            matchesType = hasImage === 'yes';
        } else if (typeFilter === 'with-items') {
            matchesType = hasItems === 'yes';
        }
        
        if (matchesSearch && matchesType) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
            // Hide associated edit form
            const formRow = document.getElementById('row-form-' + row.dataset.sectionId);
            if (formRow) formRow.style.display = 'none';
        }
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', initSectionsSortable);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
