<?php
/**
 * Tulima Solar - Products Management
 * Manage product catalog with category support
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDb();
$currentNav = 'products';
$pageTitle = 'Products';

$message = '';
$messageType = 'success';

// Helper function to generate unique product slug
function generateProductSlug(PDO $pdo, string $title, ?int $excludeId = null): string {
    $baseSlug = generateSlug($title);
    $slug = $baseSlug;
    $counter = 1;
    
    while (true) {
        $sql = 'SELECT id FROM products WHERE slug = ?';
        $params = [$slug];
        
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if (!$stmt->fetch()) {
            return $slug;
        }
        
        $counter++;
        $slug = $baseSlug . '-' . $counter;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Create new product
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        if ($title) {
            $slug = trim($_POST['slug'] ?? '') ?: generateProductSlug($pdo, $title);
            $shortDescription = trim($_POST['short_description'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $imagePath = trim($_POST['image_path'] ?? '') ?: null;
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $featured = isset($_POST['featured']) ? 1 : 0;
            
            try {
                $stmt = $pdo->prepare('INSERT INTO products (title, slug, short_description, description, image_path, category_id, sort_order, is_active, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$title, $slug, $shortDescription, $description, $imagePath, $categoryId, $sortOrder, $isActive, $featured]);
                $message = 'Product created successfully.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Error creating product: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
    
    // Update product
    if ($action === 'update' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title'] ?? '');
        if ($title) {
            $slug = trim($_POST['slug'] ?? '') ?: generateProductSlug($pdo, $title, $id);
            $shortDescription = trim($_POST['short_description'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $imagePath = trim($_POST['image_path'] ?? '') ?: null;
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $featured = isset($_POST['featured']) ? 1 : 0;
            
            try {
                $stmt = $pdo->prepare('UPDATE products SET title = ?, slug = ?, short_description = ?, description = ?, image_path = ?, category_id = ?, sort_order = ?, is_active = ?, featured = ? WHERE id = ?');
                $stmt->execute([$title, $slug, $shortDescription, $description, $imagePath, $categoryId, $sortOrder, $isActive, $featured, $id]);
                $message = 'Product updated successfully.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Error updating product: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
    
    // Delete product
    if ($action === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $message = 'Product deleted successfully.';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Error deleting product: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // Toggle featured status
    if ($action === 'toggle_featured' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('UPDATE products SET featured = NOT featured WHERE id = ?');
        $stmt->execute([$id]);
        $message = 'Product featured status updated.';
        $messageType = 'success';
    }
    
    // Bulk category assignment
    if ($action === 'bulk_category' && !empty($_POST['product_ids']) && isset($_POST['bulk_category_id'])) {
        $category_id = !empty($_POST['bulk_category_id']) ? (int)$_POST['bulk_category_id'] : null;
        $product_ids = json_decode($_POST['product_ids'], true);
        if (is_array($product_ids) && !empty($product_ids)) {
            try {
                $stmt = $pdo->prepare('UPDATE products SET category_id = ? WHERE id = ?');
                foreach ($product_ids as $pid) {
                    $stmt->execute([$category_id, (int)$pid]);
                }
                $message = count($product_ids) . ' products updated to new category.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Error updating products: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
    
    // Bulk featured toggle
    if ($action === 'bulk_featured' && !empty($_POST['product_ids'])) {
        $product_ids = json_decode($_POST['product_ids'], true);
        $featured_value = isset($_POST['featured_value']) ? 1 : 0;
        if (is_array($product_ids) && !empty($product_ids)) {
            try {
                $stmt = $pdo->prepare('UPDATE products SET featured = ? WHERE id = ?');
                foreach ($product_ids as $pid) {
                    $stmt->execute([$featured_value, (int)$pid]);
                }
                $message = count($product_ids) . ' products featured status updated.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Error updating products: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get category filter
$categoryFilter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$featuredFilter = isset($_GET['featured']) ? (int)$_GET['featured'] : null;

// Build query with filters
$sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM products p 
        LEFT JOIN product_categories c ON p.category_id = c.id';
$params = [];
$whereClauses = [];

if ($categoryFilter) {
    $whereClauses[] = 'p.category_id = ?';
    $params[] = $categoryFilter;
}

if ($featuredFilter !== null) {
    $whereClauses[] = 'p.featured = ?';
    $params[] = $featuredFilter;
}

if (!empty($whereClauses)) {
    $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
}

$sql .= ' ORDER BY p.sort_order, p.id';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for dropdowns
$categories = $pdo->query('
    SELECT c.*, 
           (SELECT COUNT(*) FROM products WHERE category_id = c.id) AS product_count
    FROM product_categories c 
    WHERE c.is_active = 1 
    ORDER BY c.sort_order, c.name
')->fetchAll();

// Stats
$activeProducts = count(array_filter($products, fn($p) => $p['is_active']));
$productsWithImages = count(array_filter($products, fn($p) => !empty($p['image_path'])));
$featuredProducts = count(array_filter($products, fn($p) => $p['featured']));
$categorizedProducts = count(array_filter($products, fn($p) => !empty($p['category_id'])));
$totalProducts = count($products);

$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
<div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Products</h1>
    <p class="page-header-enhanced__subtitle">Manage your product catalog - add, edit, organize products into categories, and mark featured items.</p>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="product-message">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
  </div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo $totalProducts; ?></div>
    <div class="page-stat-card__label">Total Products</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      All products
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $activeProducts; ?></div>
    <div class="page-stat-card__label">Active</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      <?php echo $totalProducts ? round(($activeProducts / $totalProducts) * 100) : 0; ?>% visible
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $productsWithImages; ?></div>
    <div class="page-stat-card__label">With Images</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <?php echo $totalProducts ? round(($productsWithImages / $totalProducts) * 100) : 0; ?>% with images
    </div>
  </div>

  <div class="page-stat-card page-stat-card--orange">
    <div class="page-stat-card__value"><?php echo $featuredProducts; ?></div>
    <div class="page-stat-card__label">Featured</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      Highlighted products
    </div>
  </div>

  <div class="page-stat-card page-stat-card--teal">
    <div class="page-stat-card__value"><?php echo $categorizedProducts; ?></div>
    <div class="page-stat-card__label">Categorized</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
      <?php echo $totalProducts ? round(($categorizedProducts / $totalProducts) * 100) : 0; ?>% in categories
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="product-search" placeholder="Search products..." onkeyup="filterProducts()">
  </div>
  <div class="filter-toolbar__filters">
    <form method="get" id="category-filter-form" style="display: inline;">
      <select name="category_id" id="category_id" class="filter-toolbar__select" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo (int)$cat['id']; ?>" <?php echo $categoryFilter === (int)$cat['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($cat['name']); ?> (<?php echo (int)$cat['product_count']; ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <select class="filter-toolbar__select" id="status-filter" onchange="filterProducts()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
    <select class="filter-toolbar__select" id="featured-filter" onchange="filterProducts()">
      <option value="">All Products</option>
      <option value="featured">Featured Only</option>
      <option value="not-featured">Not Featured</option>
    </select>
    <select class="filter-toolbar__select" id="image-filter" onchange="filterProducts()">
      <option value="">All Images</option>
      <option value="with-image">With Image</option>
      <option value="no-image">No Image</option>
    </select>
  </div>
  <button type="button" class="btn btn--primary" onclick="var f=document.getElementById('create-form'); f.style.display=f.style.display==='none'?'block':'none'">
    <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Product
  </button>
</div>

<!-- Bulk Actions Bar -->
<div id="bulk-actions-bar" class="bulk-actions-bar" style="display: none;">
  <span class="bulk-actions-count"><span id="selected-count">0</span> products selected</span>
  <div class="bulk-actions-buttons">
    <select id="bulk-category-select" class="filter-toolbar__select filter-toolbar__select--sm">
      <option value="">Assign Category...</option>
      <option value="0">No Category</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" class="btn btn--secondary btn--sm" onclick="applyBulkCategory()">Apply Category</button>
    <button type="button" class="btn btn--secondary btn--sm" onclick="applyBulkFeatured(true)">Mark Featured</button>
    <button type="button" class="btn btn--secondary btn--sm" onclick="applyBulkFeatured(false)">Remove Featured</button>
  </div>
</div>

<!-- Create Form -->
<div id="create-form" style="display: none;" class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      New Product
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post" id="product-create-form">
      <input type="hidden" name="action" value="create">
      
      <div class="form-section">
        <p class="form-section-title">Basic Information</p>
        <div class="form-row">
          <div class="form-group">
            <label>Title <span class="required">*</span></label>
            <input type="text" name="title" id="create-title" class="form-control" required placeholder="e.g., Solar Water Pump 500W">
          </div>
          <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" id="create-slug" class="form-control" placeholder="Auto-generated from title">
          </div>
        </div>
        <div class="form-group">
          <label>Short Description</label>
          <textarea name="short_description" class="form-control" rows="2" placeholder="Brief description for listings"></textarea>
        </div>
        <div class="form-group">
          <label>Full Description</label>
          <textarea name="description" class="form-control" rows="4" placeholder="Detailed product description"></textarea>
        </div>
      </div>
      
      <div class="form-section">
        <p class="form-section-title">Category & Display</p>
        <div class="form-row">
          <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control">
              <option value="">No Category</option>
              <?php foreach ($categories as $cat): ?>
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
          <label>Product Image</label>
          <div class="upload-field">
            <input type="text" id="new_product_image" name="image_path" class="form-control" placeholder="Upload or enter path">
            <button type="button" class="btn btn--secondary btn--sm" onclick="openMediaPicker('new_product_image', 'image')">Select from Media</button>
          </div>
        </div>
        <div class="form-group checkbox-group">
          <label><input type="checkbox" name="is_active" checked> Active (visible on site)</label>
          <label><input type="checkbox" name="featured"> Featured (highlight on homepage)</label>
        </div>
      </div>
      
      <button type="submit" class="btn btn--primary">Create Product</button>
      <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<!-- Products Table -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      All Products
    </h3>
    <div class="admin-card__actions">
      <a href="<?php echo $basePath; ?>/admin/product_categories.php" class="btn btn--secondary btn--sm">
        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Manage Categories
      </a>
    </div>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table enhanced-data-table" id="products-table">
      <thead>
        <tr>
          <th style="width: 30px;">
            <input type="checkbox" id="select-all-products" onchange="toggleSelectAll()">
          </th>
          <th>Image</th>
          <th>Title</th>
          <th>Category</th>
          <th>Status</th>
          <th>Featured</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $prod): ?>
        <tr data-id="<?php echo (int)$prod['id']; ?>" 
            data-title="<?php echo strtolower(htmlspecialchars($prod['title'])); ?>" 
            data-slug="<?php echo strtolower(htmlspecialchars($prod['slug'])); ?>" 
            data-status="<?php echo $prod['is_active'] ? 'active' : 'inactive'; ?>"
            data-featured="<?php echo $prod['featured'] ? 'featured' : 'not-featured'; ?>"
            data-has-image="<?php echo !empty($prod['image_path']) ? 'yes' : 'no'; ?>"
            data-category="<?php echo $prod['category_id'] ? (int)$prod['category_id'] : ''; ?>">
          <td>
            <input type="checkbox" class="product-checkbox" data-id="<?php echo (int)$prod['id']; ?>" onchange="updateBulkActions()">
          </td>
          <td class="table-cell--thumb">
            <?php if (!empty($prod['image_path'])): ?>
              <img src="<?php echo htmlspecialchars($basePath . '/' . $prod['image_path']); ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-md);">
            <?php else: ?>
              <div class="product-placeholder-icon" style="width: 48px; height: 48px; background: var(--bg-secondary); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="var(--text-muted)"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <div class="enhanced-data-table__cell-primary">
              <div class="enhanced-data-table__cell-title"><?php echo htmlspecialchars($prod['title']); ?></div>
              <?php if (!empty($prod['short_description'])): ?>
              <div class="enhanced-data-table__cell-subtitle"><?php echo htmlspecialchars(mb_substr($prod['short_description'], 0, 60)); ?><?php echo mb_strlen($prod['short_description']) > 60 ? '…' : ''; ?></div>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <?php if ($prod['category_name']): ?>
              <a href="?category_id=<?php echo (int)$prod['category_id']; ?>" class="badge badge--info">
                <?php echo htmlspecialchars($prod['category_name']); ?>
              </a>
            <?php else: ?>
              <span class="badge badge--muted">Uncategorized</span>
            <?php endif; ?>
          </td>
          <td><span class="badge <?php echo $prod['is_active'] ? 'badge--success' : 'badge--muted'; ?>"><?php echo $prod['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
          <td>
            <button type="button" class="featured-toggle <?php echo $prod['featured'] ? 'featured-toggle--active' : ''; ?>" 
                    onclick="toggleFeatured(<?php echo (int)$prod['id']; ?>)" 
                    title="<?php echo $prod['featured'] ? 'Remove from featured' : 'Mark as featured'; ?>">
              <svg viewBox="0 0 24 24" width="16" height="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </button>
          </td>
          <td>
            <div style="display: flex; gap: var(--space-2);">
              <button type="button" class="btn btn--primary btn--sm" data-toggle-row="<?php echo (int)$prod['id']; ?>">Edit</button>
              <form method="post" style="display: inline;" onsubmit="return confirm('Delete this product?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$prod['id']; ?>">
                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <tr id="row-form-<?php echo (int)$prod['id']; ?>" class="expand-row" style="display: none;">
          <td colspan="7">
            <form method="post" style="max-width: 640px;">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?php echo (int)$prod['id']; ?>">
              
              <div class="form-section">
                <p class="form-section-title">Basic Information</p>
                <div class="form-row">
                  <div class="form-group">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" name="title" id="edit-title-<?php echo (int)$prod['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($prod['title']); ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" id="edit-slug-<?php echo (int)$prod['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($prod['slug']); ?>">
                  </div>
                </div>
                <div class="form-group">
                  <label>Short Description</label>
                  <textarea name="short_description" class="form-control" rows="2"><?php echo htmlspecialchars($prod['short_description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                  <label>Full Description</label>
                  <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($prod['description'] ?? ''); ?></textarea>
                </div>
              </div>
              
              <div class="form-section">
                <p class="form-section-title">Category & Display</p>
                <div class="form-row">
                  <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" class="form-control">
                      <option value="">No Category</option>
                      <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo $prod['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$prod['sort_order']; ?>" style="max-width: 100px;">
                  </div>
                </div>
                <div class="form-group">
                  <label>Product Image</label>
                  <div class="upload-field">
                    <input type="text" id="image_<?php echo (int)$prod['id']; ?>" name="image_path" class="form-control" value="<?php echo htmlspecialchars($prod['image_path'] ?? ''); ?>" placeholder="Upload or enter path">
                    <button type="button" class="btn btn--secondary btn--sm" onclick="openMediaPicker('image_<?php echo (int)$prod['id']; ?>', 'image')">Select from Media</button>
                  </div>
                  <?php if (!empty($prod['image_path'])): ?>
                    <div class="upload-field-preview">
                      <img src="<?php echo htmlspecialchars($basePath . '/' . $prod['image_path']); ?>" alt="" style="max-height: 120px; border-radius: var(--radius-md);">
                    </div>
                  <?php endif; ?>
                </div>
                <div class="form-group checkbox-group">
                  <label><input type="checkbox" name="is_active" <?php echo $prod['is_active'] ? 'checked' : ''; ?>> Active (visible on site)</label>
                  <label><input type="checkbox" name="featured" <?php echo $prod['featured'] ? 'checked' : ''; ?>> Featured (highlight on homepage)</label>
                </div>
              </div>
              
              <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn--primary">Save Product</button>
                <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('row-form-<?php echo (int)$prod['id']; ?>').style.display='none'">Cancel</button>
              </div>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
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

/* Checkbox Group */
.checkbox-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-4);
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
}

/* Featured Toggle */
.featured-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: var(--space-1);
  color: var(--text-muted);
  transition: color 0.2s, transform 0.2s;
}

.featured-toggle:hover {
  color: var(--color-warning);
  transform: scale(1.1);
}

.featured-toggle--active {
  color: var(--color-warning);
}

.featured-toggle--active svg {
  fill: var(--color-warning);
}

/* Bulk Actions Bar */
.bulk-actions-bar {
  position: sticky;
  top: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-3) var(--space-4);
  background: var(--color-primary);
  color: white;
  border-radius: var(--radius-md);
  margin-bottom: var(--space-4);
}

.bulk-actions-count {
  font-weight: 500;
}

.bulk-actions-buttons {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.bulk-actions-buttons .btn--secondary {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.3);
  color: white;
}

.bulk-actions-buttons .btn--secondary:hover {
  background: rgba(255, 255, 255, 0.3);
}

.filter-toolbar__select--sm {
  padding: var(--space-1) var(--space-2);
  font-size: 0.85rem;
}
</style>

<script>
// Auto-generate slug from name (guarded in case create form is hidden/removed)
(function() {
  var titleInput = document.getElementById('create-title');
  var slugInput = document.getElementById('create-slug');
  if (!titleInput || !slugInput) return;
  titleInput.addEventListener('input', function() {
    var title = this.value || '';
    var slug = title.toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
    slugInput.value = slug;
  });
})();

// Filter products
function filterProducts() {
  var searchValue = document.getElementById('product-search').value.toLowerCase();
  var statusFilter = document.getElementById('status-filter').value;
  var featuredFilter = document.getElementById('featured-filter').value;
  var imageFilter = document.getElementById('image-filter').value;
  var rows = document.querySelectorAll('#products-table tbody tr[data-id]');
  
  rows.forEach(function(row) {
    var title = row.dataset.title;
    var slug = row.dataset.slug;
    var status = row.dataset.status;
    var featured = row.dataset.featured;
    var hasImage = row.dataset.hasImage;
    
    var matchesSearch = title.includes(searchValue) || slug.includes(searchValue);
    var matchesStatus = !statusFilter || status === statusFilter;
    var matchesFeatured = !featuredFilter || featured === featuredFilter;
    var matchesImage = true;
    
    if (imageFilter === 'with-image') {
      matchesImage = hasImage === 'yes';
    } else if (imageFilter === 'no-image') {
      matchesImage = hasImage === 'no';
    }
    
    if (matchesSearch && matchesStatus && matchesFeatured && matchesImage) {
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

// Toggle featured via AJAX
function toggleFeatured(id) {
  var form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="action" value="toggle_featured">' +
                   '<input type="hidden" name="id" value="' + id + '">';
  document.body.appendChild(form);
  form.submit();
}

// Select all products
function toggleSelectAll() {
  var selectAll = document.getElementById('select-all-products');
  var checkboxes = document.querySelectorAll('.product-checkbox');
  checkboxes.forEach(function(cb) {
    cb.checked = selectAll.checked;
  });
  updateBulkActions();
}

// Update bulk actions bar
function updateBulkActions() {
  var checkboxes = document.querySelectorAll('.product-checkbox:checked');
  var count = checkboxes.length;
  var bulkBar = document.getElementById('bulk-actions-bar');
  var countSpan = document.getElementById('selected-count');
  
  if (count > 0) {
    bulkBar.style.display = 'flex';
    countSpan.textContent = count;
  } else {
    bulkBar.style.display = 'none';
  }
}

// Apply bulk category
function applyBulkCategory() {
  var categoryId = document.getElementById('bulk-category-select').value;
  var checkboxes = document.querySelectorAll('.product-checkbox:checked');
  var productIds = Array.from(checkboxes).map(function(cb) { return cb.dataset.id; });
  
  if (productIds.length === 0) {
    alert('Please select products first.');
    return;
  }
  
  var form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="action" value="bulk_category">' +
                   '<input type="hidden" name="bulk_category_id" value="' + categoryId + '">' +
                   '<input type="hidden" name="product_ids" value=\'' + JSON.stringify(productIds) + '\'>';
  document.body.appendChild(form);
  form.submit();
}

// Apply bulk featured
function applyBulkFeatured(featured) {
  var checkboxes = document.querySelectorAll('.product-checkbox:checked');
  var productIds = Array.from(checkboxes).map(function(cb) { return cb.dataset.id; });
  
  if (productIds.length === 0) {
    alert('Please select products first.');
    return;
  }
  
  var form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="action" value="bulk_featured">' +
                   '<input type="hidden" name="featured_value" value="' + (featured ? '1' : '') + '">' +
                   '<input type="hidden" name="product_ids" value=\'' + JSON.stringify(productIds) + '\'>';
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

<?php include __DIR__ . '/includes/footer.php'; ?>
