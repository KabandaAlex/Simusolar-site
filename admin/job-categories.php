<?php
/**
 * Tulima Solar - Job Categories Management
 * Full CRUD for job categories with hierarchical support and drag-and-drop reordering
 * 
 * RBAC: Requires admin role
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'job-categories';
$pageTitle = 'Job Categories';

$message = '';
$messageType = 'success';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to generate unique slug
function generateJobCategorySlug($pdo, $name, $excludeId = null) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    $baseSlug = $slug;
    $counter = 1;
    
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare('SELECT id FROM job_categories WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM job_categories WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        
        if (!$stmt->fetch()) {
            return $slug;
        }
        
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid security token. Please try again.';
        $messageType = 'error';
    } else {
        // Create new category
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if ($name) {
                $slug = trim($_POST['slug'] ?? '') ?: generateJobCategorySlug($pdo, $name);
                $description = trim($_POST['description'] ?? '') ?: null;
                $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                $color = trim($_POST['color'] ?? '') ?: '#3498db';
                $icon = trim($_POST['icon'] ?? '') ?: null;
                $metaTitle = trim($_POST['meta_title'] ?? '') ?: null;
                $metaDescription = trim($_POST['meta_description'] ?? '') ?: null;
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare('INSERT INTO job_categories (name, slug, description, parent_id, color, icon, meta_title, meta_description, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $slug, $description, $parentId, $color, $icon, $metaTitle, $metaDescription, $displayOrder, $isActive]);
                    $newId = $pdo->lastInsertId();
                    
                    logActivity('created', 'job_category', $newId, $name);
                    $message = 'Job category created successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error creating category: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Update category
        if ($action === 'update' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name'] ?? '');
            if ($name) {
                $slug = trim($_POST['slug'] ?? '') ?: generateJobCategorySlug($pdo, $name, $id);
                $description = trim($_POST['description'] ?? '') ?: null;
                $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                
                // Prevent category from being its own parent
                if ($parentId == $id) {
                    $parentId = null;
                }
                
                $color = trim($_POST['color'] ?? '') ?: '#3498db';
                $icon = trim($_POST['icon'] ?? '') ?: null;
                $metaTitle = trim($_POST['meta_title'] ?? '') ?: null;
                $metaDescription = trim($_POST['meta_description'] ?? '') ?: null;
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare('UPDATE job_categories SET name = ?, slug = ?, description = ?, parent_id = ?, color = ?, icon = ?, meta_title = ?, meta_description = ?, display_order = ?, is_active = ? WHERE id = ?');
                    $stmt->execute([$name, $slug, $description, $parentId, $color, $icon, $metaTitle, $metaDescription, $displayOrder, $isActive, $id]);
                    
                    logActivity('updated', 'job_category', $id, $name);
                    $message = 'Job category updated successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error updating category: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Delete category
        if ($action === 'delete' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            try {
                // Check if category has jobs
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE category_id = ?');
                $stmt->execute([$id]);
                $jobCount = (int)$stmt->fetchColumn();
                
                // Check if category has children
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM job_categories WHERE parent_id = ?');
                $stmt->execute([$id]);
                $childCount = (int)$stmt->fetchColumn();
                
                if ($jobCount > 0 || $childCount > 0) {
                    $reasons = [];
                    if ($jobCount > 0) {
                        $reasons[] = "$jobCount job(s) assigned";
                    }
                    if ($childCount > 0) {
                        $reasons[] = "$childCount subcategor(y/ies)";
                    }
                    $message = 'Cannot delete category: ' . implode(' and ', $reasons) . '. Please reassign or delete them first.';
                    $messageType = 'error';
                } else {
                    // Get category name before deletion
                    $stmt = $pdo->prepare('SELECT name FROM job_categories WHERE id = ?');
                    $stmt->execute([$id]);
                    $catName = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare('DELETE FROM job_categories WHERE id = ?');
                    $stmt->execute([$id]);
                    
                    logActivity('deleted', 'job_category', $id, $catName);
                    $message = 'Job category deleted successfully.';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error deleting category: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Toggle active status
        if ($action === 'toggle' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare('UPDATE job_categories SET is_active = NOT is_active WHERE id = ?');
            $stmt->execute([$id]);
            
            $stmt = $pdo->prepare('SELECT name FROM job_categories WHERE id = ?');
            $stmt->execute([$id]);
            $catName = $stmt->fetchColumn();
            
            logActivity('toggled', 'job_category', $id, $catName);
            $message = 'Category status updated.';
            $messageType = 'success';
        }
        
        // Reorder categories
        if ($action === 'reorder' && !empty($_POST['orders'])) {
            $orders = json_decode($_POST['orders'], true);
            if (is_array($orders)) {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('UPDATE job_categories SET display_order = ? WHERE id = ?');
                    foreach ($orders as $order => $id) {
                        $stmt->execute([(int)$order, (int)$id]);
                    }
                    $pdo->commit();
                    
                    logActivity('reordered', 'job_categories', null, 'All job categories');
                    $message = 'Category order updated successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $message = 'Error updating order: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Bulk delete
        if ($action === 'bulk_delete' && !empty($_POST['selected_ids'])) {
            $ids = json_decode($_POST['selected_ids'], true);
            if (is_array($ids) && count($ids) > 0) {
                try {
                    $deletedCount = 0;
                    $skippedCount = 0;
                    
                    foreach ($ids as $id) {
                        $id = (int)$id;
                        
                        // Check if category can be deleted
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE category_id = ?');
                        $stmt->execute([$id]);
                        $jobCount = (int)$stmt->fetchColumn();
                        
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM job_categories WHERE parent_id = ?');
                        $stmt->execute([$id]);
                        $childCount = (int)$stmt->fetchColumn();
                        
                        if ($jobCount === 0 && $childCount === 0) {
                            $stmt = $pdo->prepare('SELECT name FROM job_categories WHERE id = ?');
                            $stmt->execute([$id]);
                            $catName = $stmt->fetchColumn();
                            
                            $stmt = $pdo->prepare('DELETE FROM job_categories WHERE id = ?');
                            $stmt->execute([$id]);
                            
                            logActivity('deleted', 'job_category', $id, $catName);
                            $deletedCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                    
                    if ($deletedCount > 0 && $skippedCount > 0) {
                        $message = "Deleted $deletedCount categor(y/ies). Skipped $skippedCount with jobs or subcategories.";
                    } elseif ($deletedCount > 0) {
                        $message = "Successfully deleted $deletedCount categor(y/ies).";
                    } else {
                        $message = "No categories deleted. All selected categories have jobs or subcategories.";
                        $messageType = 'error';
                    }
                } catch (PDOException $e) {
                    $message = 'Error deleting categories: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

// Get all categories with job counts
$categories = $pdo->query('
    SELECT c.*, 
           p.name AS parent_name,
           (SELECT COUNT(*) FROM jobs WHERE category_id = c.id) AS job_count,
           (SELECT COUNT(*) FROM job_categories WHERE parent_id = c.id) AS child_count
    FROM job_categories c
    LEFT JOIN job_categories p ON c.parent_id = p.id
    ORDER BY c.display_order, c.id
')->fetchAll();

// Get parent categories (for dropdown)
$parentCategories = array_filter($categories, fn($c) => $c['parent_id'] === null);

// Stats
$totalCategories = count($categories);
$activeCategories = count(array_filter($categories, fn($c) => $c['is_active']));
$parentCategoryCount = count($parentCategories);
$categoriesWithJobs = count(array_filter($categories, fn($c) => $c['job_count'] > 0));

$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
<div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Job Categories</h1>
    <p class="page-header-enhanced__subtitle">Organize job listings into categories. Create subcategories for better organization. Drag and drop to reorder.</p>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="category-message">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
  </div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo $totalCategories; ?></div>
    <div class="page-stat-card__label">Total Categories</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      All categories
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $activeCategories; ?></div>
    <div class="page-stat-card__label">Active</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      <?php echo $totalCategories ? round(($activeCategories / $totalCategories) * 100) : 0; ?>% visible
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $parentCategoryCount; ?></div>
    <div class="page-stat-card__label">Parent Categories</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Top-level categories
    </div>
  </div>

  <div class="page-stat-card page-stat-card--orange">
    <div class="page-stat-card__value"><?php echo $categoriesWithJobs; ?></div>
    <div class="page-stat-card__label">With Jobs</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      Categories in use
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="category-search" placeholder="Search categories..." onkeyup="filterCategories()">
  </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" id="status-filter" onchange="filterCategories()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
    <select class="filter-toolbar__select" id="parent-filter" onchange="filterCategories()">
      <option value="">All Categories</option>
      <option value="parent">Parent Only</option>
      <option value="child">Subcategories</option>
    </select>
  </div>
  <button type="button" class="btn btn--primary" onclick="var f=document.getElementById('create-form'); f.style.display=f.style.display==='none'?'block':'none'">
    <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Category
  </button>
</div>

<!-- Create Form -->
<div id="create-form" style="display: none;" class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      New Job Category
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post" id="category-create-form">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      
      <div class="form-section">
        <p class="form-section-title">Basic Information</p>
        <div class="form-row">
          <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <input type="text" name="name" id="create-name" class="form-control" required placeholder="e.g., Engineering">
          </div>
          <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" id="create-slug" class="form-control" placeholder="Auto-generated from name">
          </div>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this category"></textarea>
        </div>
      </div>
      
      <div class="form-section">
        <p class="form-section-title">Appearance & Hierarchy</p>
        <div class="form-row">
          <div class="form-group">
            <label>Color</label>
            <div style="display: flex; align-items: center; gap: var(--space-2);">
              <input type="color" name="color" id="create-color" class="form-control" value="#3498db" style="width: 60px; height: 38px; padding: 2px;">
              <input type="text" id="create-color-text" class="form-control" value="#3498db" style="max-width: 120px;" oninput="document.getElementById('create-color').value = this.value">
            </div>
          </div>
          <div class="form-group">
            <label>Icon (FontAwesome class)</label>
            <input type="text" name="icon" class="form-control" placeholder="e.g., fa-cogs">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Parent Category</label>
            <select name="parent_id" class="form-control">
              <option value="">None (Top-level)</option>
              <?php foreach ($parentCategories as $cat): ?>
                <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Display Order</label>
            <input type="number" name="display_order" class="form-control" value="0" style="max-width: 100px;">
          </div>
        </div>
      </div>
      
      <div class="form-section">
        <p class="form-section-title">SEO Settings</p>
        <div class="form-group">
          <label>Meta Title</label>
          <input type="text" name="meta_title" class="form-control" placeholder="SEO title for category page">
        </div>
        <div class="form-group">
          <label>Meta Description</label>
          <textarea name="meta_description" class="form-control" rows="2" placeholder="SEO description for category page"></textarea>
        </div>
      </div>
      
      <div class="form-group">
        <label><input type="checkbox" name="is_active" checked> Active (visible on site)</label>
      </div>
      
      <button type="submit" class="btn btn--primary">Create Category</button>
      <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<!-- Categories Table -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      All Job Categories
    </h3>
    <div class="admin-card__actions">
      <span class="admin-card__hint">Drag to reorder</span>
      <button type="button" class="btn btn--primary btn--sm" onclick="saveAllOrders()" id="save-order-btn" style="display: none;">
        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Order
      </button>
    </div>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table enhanced-data-table" id="categories-table">
      <thead>
        <tr>
          <th style="width: 30px;">
            <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
          </th>
          <th style="width: 30px;"></th>
          <th>Name</th>
          <th>Slug</th>
          <th>Color</th>
          <th>Jobs</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="categories-tbody">
        <?php foreach ($categories as $cat): ?>
        <tr data-id="<?php echo (int)$cat['id']; ?>" 
            data-name="<?php echo strtolower(htmlspecialchars($cat['name'])); ?>" 
            data-slug="<?php echo strtolower(htmlspecialchars($cat['slug'])); ?>" 
            data-status="<?php echo $cat['is_active'] ? 'active' : 'inactive'; ?>"
            data-parent="<?php echo $cat['parent_id'] ? 'child' : 'parent'; ?>"
            class="category-row <?php echo $cat['parent_id'] ? 'category-row--child' : ''; ?>">
          <td>
            <input type="checkbox" class="category-checkbox" value="<?php echo (int)$cat['id']; ?>">
          </td>
          <td class="table-cell--drag">
            <svg class="drag-handle" viewBox="0 0 24 24" width="16" height="16">
              <circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/>
              <circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/>
            </svg>
          </td>
          <td>
            <div class="enhanced-data-table__cell-primary">
              <div class="enhanced-data-table__cell-title">
                <?php if ($cat['parent_id']): ?>
                  <span style="color: var(--text-muted); margin-right: 4px;">↳</span>
                <?php endif; ?>
                <?php if ($cat['icon']): ?>
                  <i class="<?php echo htmlspecialchars($cat['icon']); ?>" style="margin-right: 4px;"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($cat['name']); ?>
              </div>
              <?php if ($cat['parent_name']): ?>
                <div class="enhanced-data-table__cell-subtitle">Parent: <?php echo htmlspecialchars($cat['parent_name']); ?></div>
              <?php endif; ?>
              <?php if ($cat['child_count'] > 0): ?>
                <div class="enhanced-data-table__cell-subtitle"><?php echo (int)$cat['child_count']; ?> subcategories</div>
              <?php endif; ?>
            </div>
          </td>
          <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars($cat['slug']); ?></code></td>
          <td>
            <span class="color-badge" style="display: inline-flex; align-items: center; gap: 4px;">
              <span style="width: 16px; height: 16px; background: <?php echo htmlspecialchars($cat['color']); ?>; border-radius: 4px; display: inline-block;"></span>
              <?php echo htmlspecialchars($cat['color']); ?>
            </span>
          </td>
          <td>
            <span class="badge badge--<?php echo $cat['job_count'] > 0 ? 'info' : 'muted'; ?>">
              <?php echo (int)$cat['job_count']; ?> job<?php echo $cat['job_count'] != 1 ? 's' : ''; ?>
            </span>
          </td>
          <td><span class="badge <?php echo $cat['is_active'] ? 'badge--success' : 'badge--muted'; ?>"><?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
          <td>
            <div style="display: flex; gap: var(--space-2);">
              <button type="button" class="btn btn--primary btn--sm" data-toggle-row="<?php echo (int)$cat['id']; ?>">Edit</button>
              <form method="post" style="display: inline;" onsubmit="return confirmDelete(<?php echo (int)$cat['job_count']; ?>, <?php echo (int)$cat['child_count']; ?>);">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <tr id="row-form-<?php echo (int)$cat['id']; ?>" class="expand-row" style="display: none;">
          <td colspan="8">
            <form method="post" style="max-width: 640px;">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              
              <div class="form-section">
                <p class="form-section-title">Basic Information</p>
                <div class="form-row">
                  <div class="form-group">
                    <label>Name <span class="required">*</span></label>
                    <input type="text" name="name" id="edit-name-<?php echo (int)$cat['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($cat['name']); ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" id="edit-slug-<?php echo (int)$cat['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($cat['slug']); ?>">
                  </div>
                </div>
                <div class="form-group">
                  <label>Description</label>
                  <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></textarea>
                </div>
              </div>
              
              <div class="form-section">
                <p class="form-section-title">Appearance & Hierarchy</p>
                <div class="form-row">
                  <div class="form-group">
                    <label>Color</label>
                    <div style="display: flex; align-items: center; gap: var(--space-2);">
                      <input type="color" name="color" id="edit-color-<?php echo (int)$cat['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($cat['color']); ?>" style="width: 60px; height: 38px; padding: 2px;">
                      <input type="text" id="edit-color-text-<?php echo (int)$cat['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($cat['color']); ?>" style="max-width: 120px;" oninput="document.getElementById('edit-color-<?php echo (int)$cat['id']; ?>').value = this.value">
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Icon (FontAwesome class)</label>
                    <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars($cat['icon'] ?? ''); ?>" placeholder="e.g., fa-cogs">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Parent Category</label>
                    <select name="parent_id" class="form-control">
                      <option value="">None (Top-level)</option>
                      <?php foreach ($parentCategories as $parent): ?>
                        <?php if ($parent['id'] != $cat['id']): ?>
                          <option value="<?php echo (int)$parent['id']; ?>" <?php echo $cat['parent_id'] == $parent['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($parent['name']); ?></option>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?php echo (int)$cat['display_order']; ?>" style="max-width: 100px;">
                  </div>
                </div>
              </div>
              
              <div class="form-section">
                <p class="form-section-title">SEO Settings</p>
                <div class="form-group">
                  <label>Meta Title</label>
                  <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($cat['meta_title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                  <label>Meta Description</label>
                  <textarea name="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($cat['meta_description'] ?? ''); ?></textarea>
                </div>
              </div>
              
              <div class="form-group">
                <label><input type="checkbox" name="is_active" <?php echo $cat['is_active'] ? 'checked' : ''; ?>> Active (visible on site)</label>
              </div>
              
              <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn--primary">Save Category</button>
                <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('row-form-<?php echo (int)$cat['id']; ?>').style.display='none'">Cancel</button>
              </div>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($categories) > 0): ?>
  <div class="admin-card__footer">
    <button type="button" class="btn btn--danger btn--sm" onclick="bulkDelete()">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      Delete Selected
    </button>
  </div>
  <?php endif; ?>
</div>

<!-- Category Hierarchy Tree View -->
<div class="admin-card" style="margin-top: 1.5rem;">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Category Hierarchy
    </h3>
  </div>
  <div class="admin-card__body">
    <div class="category-tree">
      <?php
      // Build tree structure
      $tree = [];
      foreach ($categories as $cat) {
          if ($cat['parent_id'] === null) {
              $tree[$cat['id']] = $cat;
              $tree[$cat['id']]['children'] = [];
          }
      }
      foreach ($categories as $cat) {
          if ($cat['parent_id'] !== null && isset($tree[$cat['parent_id']])) {
              $tree[$cat['parent_id']]['children'][] = $cat;
          }
      }
      
      if (empty($tree)): ?>
        <p class="text-muted">No categories created yet. Click "Add Category" to create your first category.</p>
      <?php else: ?>
        <ul class="category-tree-list">
          <?php foreach ($tree as $parent): ?>
            <li class="category-tree-item">
              <div class="category-tree-node">
                <span class="category-color-dot" style="background: <?php echo htmlspecialchars($parent['color']); ?>;"></span>
                <span class="category-tree-name"><?php echo htmlspecialchars($parent['name']); ?></span>
                <span class="badge badge--<?php echo $parent['is_active'] ? 'success' : 'muted'; ?> badge--sm"><?php echo $parent['is_active'] ? 'Active' : 'Inactive'; ?></span>
                <span class="category-tree-count"><?php echo (int)$parent['job_count']; ?> jobs</span>
              </div>
              <?php if (!empty($parent['children'])): ?>
                <ul class="category-tree-children">
                  <?php foreach ($parent['children'] as $child): ?>
                    <li class="category-tree-item category-tree-item--child">
                      <div class="category-tree-node">
                        <span class="category-color-dot" style="background: <?php echo htmlspecialchars($child['color']); ?>;"></span>
                        <span class="category-tree-name"><?php echo htmlspecialchars($child['name']); ?></span>
                        <span class="badge badge--<?php echo $child['is_active'] ? 'success' : 'muted'; ?> badge--sm"><?php echo $child['is_active'] ? 'Active' : 'Inactive'; ?></span>
                        <span class="category-tree-count"><?php echo (int)$child['job_count']; ?> jobs</span>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
/* Category Tree Styles */
.category-tree {
  padding: var(--space-4);
}

.category-tree-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.category-tree-item {
  margin-bottom: var(--space-2);
}

.category-tree-node {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-2) var(--space-3);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
}

.category-color-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.category-tree-name {
  font-weight: 500;
}

.category-tree-count {
  color: var(--text-muted);
  font-size: 0.85rem;
  margin-left: auto;
}

.category-tree-children {
  list-style: none;
  padding-left: var(--space-6);
  margin-top: var(--space-2);
}

.category-tree-item--child .category-tree-node {
  background: var(--bg-tertiary, #f8f9fa);
}

/* Drag handle styles */
.drag-handle {
  cursor: grab;
  opacity: 0.4;
  transition: opacity 0.2s;
}

.drag-handle:hover {
  opacity: 1;
}

.category-row--child {
  background: var(--bg-tertiary, #fafbfc);
}

.category-row--child td:first-child {
  padding-left: var(--space-4);
}

/* Color picker sync */
input[type="color"] {
  cursor: pointer;
  border: 1px solid var(--border-color, #ddd);
  border-radius: var(--radius-sm);
}
</style>

<script>
// Auto-generate slug from name
document.getElementById('create-name').addEventListener('input', function() {
    var name = this.value;
    var slug = name.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('create-slug').value = slug;
});

// Sync color picker with text input
document.getElementById('create-color').addEventListener('input', function() {
    document.getElementById('create-color-text').value = this.value;
});

// Toggle edit forms
document.querySelectorAll('[data-toggle-row]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-toggle-row');
        var row = document.getElementById('row-form-' + id);
        if (row) {
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
    });
});

// Filter categories
function filterCategories() {
    var search = document.getElementById('category-search').value.toLowerCase();
    var statusFilter = document.getElementById('status-filter').value;
    var parentFilter = document.getElementById('parent-filter').value;
    
    document.querySelectorAll('#categories-tbody .category-row').forEach(function(row) {
        var name = row.getAttribute('data-name');
        var slug = row.getAttribute('data-slug');
        var status = row.getAttribute('data-status');
        var parent = row.getAttribute('data-parent');
        
        var matchesSearch = name.includes(search) || slug.includes(search);
        var matchesStatus = !statusFilter || status === statusFilter;
        var matchesParent = !parentFilter || parent === parentFilter;
        
        row.style.display = matchesSearch && matchesStatus && matchesParent ? '' : 'none';
        
        // Also hide/show the corresponding edit form row
        var formRow = document.getElementById('row-form-' + row.getAttribute('data-id'));
        if (formRow) {
            formRow.style.display = 'none';
        }
    });
}

// Select all checkbox
function toggleSelectAll() {
    var selectAll = document.getElementById('select-all').checked;
    document.querySelectorAll('.category-checkbox').forEach(function(cb) {
        cb.checked = selectAll;
    });
}

// Confirm delete
function confirmDelete(jobCount, childCount) {
    if (jobCount > 0 || childCount > 0) {
        var reasons = [];
        if (jobCount > 0) reasons.push(jobCount + ' job(s)');
        if (childCount > 0) reasons.push(childCount + ' subcategor(y/ies)');
        alert('Cannot delete category: ' + reasons.join(' and ') + ' assigned. Please reassign or delete them first.');
        return false;
    }
    return confirm('Are you sure you want to delete this category?');
}

// Bulk delete
function bulkDelete() {
    var selected = [];
    document.querySelectorAll('.category-checkbox:checked').forEach(function(cb) {
        selected.push(cb.value);
    });
    
    if (selected.length === 0) {
        alert('Please select at least one category to delete.');
        return;
    }
    
    if (!confirm('Are you sure you want to delete ' + selected.length + ' selected categor(y/ies)? Categories with jobs or subcategories will be skipped.')) {
        return;
    }
    
    var form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = '<input type="hidden" name="action" value="bulk_delete">' +
                     '<input type="hidden" name="selected_ids" value=\'' + JSON.stringify(selected) + '\'>' +
                     '<input type="hidden" name="csrf_token" value="' + document.querySelector('[name="csrf_token"]').value + '">';
    document.body.appendChild(form);
    form.submit();
}

// Drag and drop reordering
var draggedRow = null;
var orderChanged = false;

document.querySelectorAll('#categories-tbody .category-row').forEach(function(row) {
    var dragHandle = row.querySelector('.drag-handle');
    
    dragHandle.addEventListener('mousedown', function() {
        row.setAttribute('draggable', 'true');
    });
    
    row.addEventListener('dragstart', function(e) {
        draggedRow = this;
        this.style.opacity = '0.5';
    });
    
    row.addEventListener('dragend', function() {
        this.style.opacity = '';
        this.setAttribute('draggable', 'false');
        draggedRow = null;
        
        if (orderChanged) {
            document.getElementById('save-order-btn').style.display = '';
        }
    });
    
    row.addEventListener('dragover', function(e) {
        e.preventDefault();
        var tbody = document.getElementById('categories-tbody');
        var afterElement = getDragAfterElement(tbody, e.clientY);
        if (afterElement == null) {
            tbody.appendChild(draggedRow);
        } else {
            tbody.insertBefore(draggedRow, afterElement);
        }
        orderChanged = true;
    });
});

function getDragAfterElement(container, y) {
    var draggableElements = [...container.querySelectorAll('.category-row:not([style*="opacity: 0.5"])')];
    
    return draggableElements.reduce(function(closest, child) {
        var box = child.getBoundingClientRect();
        var offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function saveAllOrders() {
    var orders = [];
    document.querySelectorAll('#categories-tbody .category-row').forEach(function(row, index) {
        orders.push(row.getAttribute('data-id'));
    });
    
    var form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = '<input type="hidden" name="action" value="reorder">' +
                     '<input type="hidden" name="orders" value=\'' + JSON.stringify(orders) + '\'>' +
                     '<input type="hidden" name="csrf_token" value="' + document.querySelector('[name="csrf_token"]').value + '">';
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
