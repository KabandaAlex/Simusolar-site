<?php
/**
 * Tulima Solar - Product Categories Management
 * Full CRUD for product categories with drag-and-drop reordering
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDb();
$currentNav = 'product-categories';
$pageTitle = 'Product Categories';

$message = '';
$messageType = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Create new category
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $slug = trim($_POST['slug'] ?? '') ?: generateCategorySlug($pdo, $name);
            $description = trim($_POST['description'] ?? '') ?: null;
            $image = trim($_POST['image'] ?? '') ?: null;
            $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            try {
                $stmt = $pdo->prepare('INSERT INTO product_categories (name, slug, description, image, parent_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $slug, $description, $image, $parentId, $sortOrder, $isActive]);
                $message = 'Category created successfully.';
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
            $slug = trim($_POST['slug'] ?? '') ?: generateCategorySlug($pdo, $name, $id);
            $description = trim($_POST['description'] ?? '') ?: null;
            $image = trim($_POST['image'] ?? '') ?: null;
            $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            
            // Prevent category from being its own parent
            if ($parentId == $id) {
                $parentId = null;
            }
            
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            try {
                $stmt = $pdo->prepare('UPDATE product_categories SET name = ?, slug = ?, description = ?, image = ?, parent_id = ?, sort_order = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$name, $slug, $description, $image, $parentId, $sortOrder, $isActive, $id]);
                $message = 'Category updated successfully.';
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
            // First, set category_id to NULL for products in this category
            $stmt = $pdo->prepare('UPDATE products SET category_id = NULL WHERE category_id = ?');
            $stmt->execute([$id]);
            
            // Then delete the category
            $stmt = $pdo->prepare('DELETE FROM product_categories WHERE id = ?');
            $stmt->execute([$id]);
            $message = 'Category deleted successfully. Products moved to uncategorized.';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Error deleting category: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // Toggle active status
    if ($action === 'toggle' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('UPDATE product_categories SET is_active = NOT is_active WHERE id = ?');
        $stmt->execute([$id]);
        $message = 'Category status updated.';
        $messageType = 'success';
    }
    
    // Reorder categories
    if ($action === 'reorder' && !empty($_POST['orders'])) {
        $orders = json_decode($_POST['orders'], true);
        if (is_array($orders)) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('UPDATE product_categories SET sort_order = ? WHERE id = ?');
                foreach ($orders as $order => $id) {
                    $stmt->execute([(int)$order, (int)$id]);
                }
                $pdo->commit();
                $message = 'Category order updated successfully.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = 'Error updating order: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get all categories with product counts
$categories = $pdo->query('
    SELECT c.*, 
           p.name AS parent_name,
           (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count,
           (SELECT COUNT(*) FROM product_categories WHERE parent_id = c.id) AS child_count
    FROM product_categories c
    LEFT JOIN product_categories p ON c.parent_id = p.id
    ORDER BY c.sort_order, c.id
')->fetchAll();

// Get parent categories (for dropdown)
$parentCategories = array_filter($categories, fn($c) => $c['parent_id'] === null);

// Stats
$totalCategories = count($categories);
$activeCategories = count(array_filter($categories, fn($c) => $c['is_active']));
$parentCategoryCount = count($parentCategories);
$categoriesWithProducts = count(array_filter($categories, fn($c) => $c['product_count'] > 0));

$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
<div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Product Categories</h1>
    <p class="page-header-enhanced__subtitle">Organize your products into categories. Drag and drop to reorder. Create subcategories for better organization.</p>
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
    <div class="page-stat-card__value"><?php echo $categoriesWithProducts; ?></div>
    <div class="page-stat-card__label">With Products</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
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
      <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
      New Category
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post" id="category-create-form">
      <input type="hidden" name="action" value="create">
      
      <div class="form-section">
        <p class="form-section-title">Basic Information</p>
        <div class="form-row">
          <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <input type="text" name="name" id="create-name" class="form-control" required placeholder="e.g., Solar Water Pumps">
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
        <p class="form-section-title">Hierarchy & Display</p>
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
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="0" style="max-width: 100px;">
          </div>
        </div>
        <div class="form-group">
          <label>Category Image</label>
          <div class="upload-field">
            <input type="text" id="create_image" name="image" class="form-control" placeholder="Upload or enter path">
            <button type="button" class="btn btn--secondary btn--sm" onclick="openMediaPicker('create_image', 'image')">Select from Media</button>
          </div>
        </div>
        <div class="form-group">
          <label><input type="checkbox" name="is_active" checked> Active (visible on site)</label>
        </div>
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
      <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
      All Categories
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
          <th style="width: 30px;"></th>
          <th>Image</th>
          <th>Name</th>
          <th>Slug</th>
          <th>Products</th>
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
          <td class="table-cell--drag">
            <svg class="drag-handle" viewBox="0 0 24 24" width="16" height="16">
              <circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/>
              <circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/>
            </svg>
          </td>
          <td class="table-cell--thumb">
            <?php if (!empty($cat['image'])): ?>
              <img src="<?php echo htmlspecialchars($basePath . '/' . $cat['image']); ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-md);">
            <?php else: ?>
              <div class="category-placeholder-icon" style="width: 48px; height: 48px; background: var(--bg-secondary); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="var(--text-muted)"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <div class="enhanced-data-table__cell-primary">
              <div class="enhanced-data-table__cell-title">
                <?php if ($cat['parent_id']): ?>
                  <span style="color: var(--text-muted); margin-right: 4px;">↳</span>
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
            <span class="badge badge--<?php echo $cat['product_count'] > 0 ? 'info' : 'muted'; ?>">
              <?php echo (int)$cat['product_count']; ?> product<?php echo $cat['product_count'] != 1 ? 's' : ''; ?>
            </span>
          </td>
          <td><span class="badge <?php echo $cat['is_active'] ? 'badge--success' : 'badge--muted'; ?>"><?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
          <td>
            <div style="display: flex; gap: var(--space-2);">
              <button type="button" class="btn btn--primary btn--sm" data-toggle-row="<?php echo (int)$cat['id']; ?>">Edit</button>
              <form method="post" style="display: inline;" onsubmit="return confirm('Delete this category? Products will be moved to uncategorized.');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <tr id="row-form-<?php echo (int)$cat['id']; ?>" class="expand-row" style="display: none;">
          <td colspan="7">
            <form method="post" style="max-width: 640px;">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
              
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
                <p class="form-section-title">Hierarchy & Display</p>
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
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$cat['sort_order']; ?>" style="max-width: 100px;">
                  </div>
                </div>
                <div class="form-group">
                  <label>Category Image</label>
                  <div class="upload-field">
                    <input type="text" id="image_<?php echo (int)$cat['id']; ?>" name="image" class="form-control" value="<?php echo htmlspecialchars($cat['image'] ?? ''); ?>" placeholder="Upload or enter path">
                    <button type="button" class="btn btn--secondary btn--sm" onclick="openMediaPicker('image_<?php echo (int)$cat['id']; ?>', 'image')">Select from Media</button>
                  </div>
                  <?php if (!empty($cat['image'])): ?>
                    <div class="upload-field-preview">
                      <img src="<?php echo htmlspecialchars($basePath . '/' . $cat['image']); ?>" alt="" style="max-height: 120px; border-radius: var(--radius-md);">
                    </div>
                  <?php endif; ?>
                </div>
                <div class="form-group">
                  <label><input type="checkbox" name="is_active" <?php echo $cat['is_active'] ? 'checked' : ''; ?>> Active (visible on site)</label>
                </div>
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
                <span class="category-tree-name"><?php echo htmlspecialchars($parent['name']); ?></span>
                <span class="badge badge--<?php echo $parent['is_active'] ? 'success' : 'muted'; ?> badge--sm"><?php echo $parent['is_active'] ? 'Active' : 'Inactive'; ?></span>
                <span class="category-tree-count"><?php echo (int)$parent['product_count']; ?> products</span>
              </div>
              <?php if (!empty($parent['children'])): ?>
                <ul class="category-tree-children">
                  <?php foreach ($parent['children'] as $child): ?>
                    <li class="category-tree-item category-tree-item--child">
                      <div class="category-tree-node">
                        <span class="category-tree-name"><?php echo htmlspecialchars($child['name']); ?></span>
                        <span class="badge badge--<?php echo $child['is_active'] ? 'success' : 'muted'; ?> badge--sm"><?php echo $child['is_active'] ? 'Active' : 'Inactive'; ?></span>
                        <span class="category-tree-count"><?php echo (int)$child['product_count']; ?> products</span>
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
  background: var(--bg-tertiary, var(--bg-primary));
  border-left: 3px solid var(--color-primary);
}

/* Form Row */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-4);
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

/* Category Row Styles */
.category-row--child {
  background: var(--bg-secondary);
}

/* Drag Handle */
.table-cell--drag {
  cursor: grab;
  color: var(--text-muted);
}

.table-cell--drag:active {
  cursor: grabbing;
}

.drag-handle {
  opacity: 0.5;
  transition: opacity 0.2s;
}

tr:hover .drag-handle {
  opacity: 1;
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

// Filter categories
function filterCategories() {
  var searchValue = document.getElementById('category-search').value.toLowerCase();
  var statusFilter = document.getElementById('status-filter').value;
  var parentFilter = document.getElementById('parent-filter').value;
  var rows = document.querySelectorAll('#categories-tbody tr[data-id]');
  
  rows.forEach(function(row) {
    var name = row.dataset.name;
    var slug = row.dataset.slug;
    var status = row.dataset.status;
    var parent = row.dataset.parent;
    
    var matchesSearch = name.includes(searchValue) || slug.includes(searchValue);
    var matchesStatus = !statusFilter || status === statusFilter;
    var matchesParent = !parentFilter || parent === parentFilter;
    
    if (matchesSearch && matchesStatus && matchesParent) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// Toggle expand rows for inline edit forms
document.querySelectorAll('[data-toggle-row]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id = this.getAttribute('data-toggle-row');
    var row = document.getElementById('row-form-' + id);
    if (!row) return;
    var current = window.getComputedStyle(row).display;
    row.style.display = (current === 'none' ? 'table-row' : 'none');
  });
});

// Drag and drop reordering with SortableJS
var tbody = document.getElementById('categories-tbody');
var orderChanged = false;
var originalOrder = [];

if (tbody && typeof Sortable !== 'undefined') {
  // Store original order
  originalOrder = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(row) {
    return row.dataset.id;
  });
  
  new Sortable(tbody, {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    dataIdAttr: 'data-id',
    onEnd: function(evt) {
      var newOrder = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(row) {
        return row.dataset.id;
      });
      
      // Check if order changed
      if (JSON.stringify(newOrder) !== JSON.stringify(originalOrder)) {
        orderChanged = true;
        document.getElementById('save-order-btn').style.display = 'inline-flex';
      } else {
        orderChanged = false;
        document.getElementById('save-order-btn').style.display = 'none';
      }
    }
  });
}

function saveAllOrders() {
  var rows = document.querySelectorAll('#categories-tbody tr[data-id]');
  var orders = [];
  rows.forEach(function(row, index) {
    orders.push(row.dataset.id);
  });
  
  var form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="action" value="reorder">' +
                   '<input type="hidden" name="orders" value=\'' + JSON.stringify(orders) + '\'>';
  document.body.appendChild(form);
  form.submit();
}

// Media picker integration
function openMediaPicker(targetId, type) {
  var url = '<?php echo $basePath; ?>/admin/media/picker.php?type=' + encodeURIComponent(type);
  var win = window.open(url, 'mediaPicker', 'width=900,height=600,scrollbars=yes');
  win.targetInput = document.getElementById(targetId);
}
</script>

<!-- SortableJS for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
