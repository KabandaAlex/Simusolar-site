<?php
/**
 * Tulima Solar - Blog Posts Management
 * Manage blog posts with categories, tags, and rich content
 * 
 * RBAC: Requires at least author role
 * - Authors can see all posts but can only edit their own
 * - Editors and admins can edit all posts
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/auth_helpers.php';
requirePostAccess(); // Requires minimum author role

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../includes/blog_helpers.php';

$pdo = getDb();
$currentNav = 'blog-posts';
$pageTitle = 'Blog Posts';

$message = '';
$messageType = 'success';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to generate unique post slug
function generatePostSlug($pdo, $title, $excludeId = null) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    $baseSlug = $slug;
    $counter = 1;
    
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = ? AND id != ? AND deleted_at IS NULL');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = ? AND deleted_at IS NULL');
            $stmt->execute([$slug]);
        }
        
        if (!$stmt->fetch()) {
            return $slug;
        }
        
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

// Publish scheduled posts that are due
function publishScheduledPosts($pdo) {
    $stmt = $pdo->prepare("
        UPDATE blog_posts 
        SET status = 'published', 
            published_at = COALESCE(published_at, NOW())
        WHERE status = 'scheduled' 
          AND publish_at <= NOW() 
          AND deleted_at IS NULL
    ");
    $stmt->execute();
    return $stmt->rowCount();
}

// Publish scheduled posts on load
publishScheduledPosts($pdo);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid security token. Please try again.';
        $messageType = 'error';
    } else {
        // Delete post (soft delete)
        if ($action === 'delete' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            try {
                // Get post title before deletion
                $stmt = $pdo->prepare('SELECT title FROM blog_posts WHERE id = ?');
                $stmt->execute([$id]);
                $postTitle = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare('UPDATE blog_posts SET deleted_at = NOW() WHERE id = ?');
                $stmt->execute([$id]);
                
                logActivity('deleted', 'blog_post', $id, $postTitle);
                $message = 'Post moved to trash.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Error deleting post: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Restore post from trash
        if ($action === 'restore' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            try {
                $stmt = $pdo->prepare('SELECT title FROM blog_posts WHERE id = ?');
                $stmt->execute([$id]);
                $postTitle = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare('UPDATE blog_posts SET deleted_at = NULL, status = "draft" WHERE id = ?');
                $stmt->execute([$id]);
                
                logActivity('restored', 'blog_post', $id, $postTitle);
                $message = 'Post restored from trash.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Error restoring post: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Permanently delete post
        if ($action === 'permanent_delete' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            try {
                $stmt = $pdo->prepare('SELECT title FROM blog_posts WHERE id = ?');
                $stmt->execute([$id]);
                $postTitle = $stmt->fetchColumn();
                
                // Delete post tags first
                $stmt = $pdo->prepare('DELETE FROM blog_post_tags WHERE post_id = ?');
                $stmt->execute([$id]);
                
                // Delete the post
                $stmt = $pdo->prepare('DELETE FROM blog_posts WHERE id = ?');
                $stmt->execute([$id]);
                
                logActivity('permanently_deleted', 'blog_post', $id, $postTitle);
                $message = 'Post permanently deleted.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Error deleting post: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Update status
        if ($action === 'update_status' && !empty($_POST['id']) && !empty($_POST['status'])) {
            $id = (int)$_POST['id'];
            $status = $_POST['status'];
            $allowedStatuses = ['draft', 'published', 'scheduled', 'archived'];
            
            if (!in_array($status, $allowedStatuses)) {
                $message = 'Invalid status.';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare('SELECT title, status FROM blog_posts WHERE id = ?');
                    $stmt->execute([$id]);
                    $post = $stmt->fetch();
                    
                    if ($status === 'published' && $post['status'] !== 'published') {
                        $stmt = $pdo->prepare('UPDATE blog_posts SET status = ?, published_at = NOW() WHERE id = ?');
                    } else {
                        $stmt = $pdo->prepare('UPDATE blog_posts SET status = ? WHERE id = ?');
                    }
                    $stmt->execute([$status, $id]);
                    
                    logActivity('status_changed', 'blog_post', $id, $post['title'], ['from' => $post['status'], 'to' => $status]);
                    $message = 'Post status updated.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error updating status: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Bulk status change
        if ($action === 'bulk_status' && !empty($_POST['post_ids']) && !empty($_POST['bulk_status'])) {
            $postIds = json_decode($_POST['post_ids'], true);
            $status = $_POST['bulk_status'];
            $allowedStatuses = ['draft', 'published', 'scheduled', 'archived'];
            
            if (!in_array($status, $allowedStatuses)) {
                $message = 'Invalid status.';
                $messageType = 'error';
            } elseif (is_array($postIds) && !empty($postIds)) {
                try {
                    $pdo->beginTransaction();
                    
                    if ($status === 'published') {
                        $stmt = $pdo->prepare('UPDATE blog_posts SET status = ?, published_at = COALESCE(published_at, NOW()) WHERE id = ?');
                    } else {
                        $stmt = $pdo->prepare('UPDATE blog_posts SET status = ? WHERE id = ?');
                    }
                    
                    foreach ($postIds as $pid) {
                        $stmt->execute([$status, (int)$pid]);
                    }
                    
                    $pdo->commit();
                    
                    logActivity('bulk_status_changed', 'blog_posts', null, count($postIds) . ' posts', ['status' => $status]);
                    $message = count($postIds) . ' posts updated to ' . $status . '.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $message = 'Error updating posts: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Bulk delete (soft delete)
        if ($action === 'bulk_delete' && !empty($_POST['post_ids'])) {
            $postIds = json_decode($_POST['post_ids'], true);
            
            if (is_array($postIds) && !empty($postIds)) {
                try {
                    $stmt = $pdo->prepare('UPDATE blog_posts SET deleted_at = NOW() WHERE id = ?');
                    foreach ($postIds as $pid) {
                        $stmt->execute([(int)$pid]);
                    }
                    
                    logActivity('bulk_deleted', 'blog_posts', null, count($postIds) . ' posts');
                    $message = count($postIds) . ' posts moved to trash.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error deleting posts: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Bulk category assignment
        if ($action === 'bulk_category' && !empty($_POST['post_ids']) && isset($_POST['bulk_category_id'])) {
            $categoryId = !empty($_POST['bulk_category_id']) ? (int)$_POST['bulk_category_id'] : null;
            $postIds = json_decode($_POST['post_ids'], true);
            
            if (is_array($postIds) && !empty($postIds)) {
                try {
                    $stmt = $pdo->prepare('UPDATE blog_posts SET category_id = ? WHERE id = ?');
                    foreach ($postIds as $pid) {
                        $stmt->execute([$categoryId, (int)$pid]);
                    }
                    
                    logActivity('bulk_category_changed', 'blog_posts', null, count($postIds) . ' posts', ['category_id' => $categoryId]);
                    $message = count($postIds) . ' posts category updated.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error updating posts: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

// Get filters from query string
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$searchQuery = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$authorFilter = isset($_GET['author_id']) ? (int)$_GET['author_id'] : null;
$showTrashed = isset($_GET['trashed']) && $_GET['trashed'] === '1';
$sortBy = $_GET['sort'] ?? 'updated_at';
$sortOrder = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

// Validate sort column
$allowedSortColumns = ['title', 'status', 'created_at', 'updated_at', 'published_at', 'view_count', 'like_count', 'comment_count'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'updated_at';
}

// Build query with filters
$sql = 'SELECT p.*, 
               c.name AS category_name, 
               c.slug AS category_slug,
               a.username AS author_name,
               a.email AS author_email,
               GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ", ") AS tag_names
        FROM blog_posts p 
        LEFT JOIN blog_categories c ON p.category_id = c.id
        LEFT JOIN admins a ON p.created_by = a.id
        LEFT JOIN blog_post_tags pt ON p.id = pt.post_id
        LEFT JOIN blog_tags t ON pt.tag_id = t.id';

$params = [];
$whereClauses = [];

// Soft delete filter
if ($showTrashed) {
    $whereClauses[] = 'p.deleted_at IS NOT NULL';
} else {
    $whereClauses[] = 'p.deleted_at IS NULL';
}

// Status filter
if ($statusFilter && in_array($statusFilter, ['draft', 'published', 'scheduled', 'archived'])) {
    $whereClauses[] = 'p.status = ?';
    $params[] = $statusFilter;
}

// Category filter
if ($categoryFilter) {
    $whereClauses[] = 'p.category_id = ?';
    $params[] = $categoryFilter;
}

// Author filter
if ($authorFilter) {
    $whereClauses[] = 'p.created_by = ?';
    $params[] = $authorFilter;
}

// Search filter
if ($searchQuery) {
    $whereClauses[] = '(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)';
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Date range filter
if ($dateFrom) {
    $whereClauses[] = 'DATE(p.created_at) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo) {
    $whereClauses[] = 'DATE(p.created_at) <= ?';
    $params[] = $dateTo;
}

if (!empty($whereClauses)) {
    $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
}

$sql .= ' GROUP BY p.id';

// Add sorting
$sortColumn = 'p.' . $sortBy;
if ($sortBy === 'category_name') {
    $sortColumn = 'c.name';
} elseif ($sortBy === 'author_name') {
    $sortColumn = 'a.username';
}
$sql .= ' ORDER BY ' . $sortColumn . ' ' . $sortOrder;

// Add pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$countSql = 'SELECT COUNT(DISTINCT p.id) FROM blog_posts p';
if (!empty($whereClauses)) {
    $countSql .= ' WHERE ' . implode(' AND ', $whereClauses);
}
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalPosts = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalPosts / $perPage));

$sql .= ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get categories for dropdowns
$categories = $pdo->query('
    SELECT c.*, 
           (SELECT COUNT(*) FROM blog_posts WHERE category_id = c.id AND deleted_at IS NULL) AS post_count
    FROM blog_categories c 
    WHERE c.is_active = 1 
    ORDER BY c.name
')->fetchAll();

// Get authors for filter
$authors = $pdo->query('
    SELECT DISTINCT a.id, a.username, a.email
    FROM admins a
    INNER JOIN blog_posts p ON a.id = p.created_by
    ORDER BY a.username
')->fetchAll();

// Get stats
$stats = [
    'total' => (int)$pdo->query('SELECT COUNT(*) FROM blog_posts WHERE deleted_at IS NULL')->fetchColumn(),
    'draft' => (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft' AND deleted_at IS NULL")->fetchColumn(),
    'published' => (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published' AND deleted_at IS NULL")->fetchColumn(),
    'scheduled' => (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'scheduled' AND deleted_at IS NULL")->fetchColumn(),
    'archived' => (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'archived' AND deleted_at IS NULL")->fetchColumn(),
    'trashed' => (int)$pdo->query('SELECT COUNT(*) FROM blog_posts WHERE deleted_at IS NOT NULL')->fetchColumn(),
];

$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Blog Posts</h1>
    <p class="page-header-enhanced__subtitle">Create, edit, and manage your blog content with categories, tags, and SEO optimization.</p>
  </div>
  <div class="page-header-enhanced__actions">
    <a href="<?php echo $basePath; ?>/admin/blog_post_edit.php" class="btn btn--primary">
      <svg viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Post
    </a>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="post-message">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
  </div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple <?php echo !$statusFilter && !$showTrashed ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?';" style="cursor: pointer;">
    <div class="page-stat-card__value"><?php echo $stats['total']; ?></div>
    <div class="page-stat-card__label">Total Posts</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      All posts
    </div>
  </div>

  <div class="page-stat-card page-stat-card--orange <?php echo $statusFilter === 'draft' ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?status=draft';" style="cursor: pointer;">
    <div class="page-stat-card__value"><?php echo $stats['draft']; ?></div>
    <div class="page-stat-card__label">Drafts</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Work in progress
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green <?php echo $statusFilter === 'published' ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?status=published';" style="cursor: pointer;">
    <div class="page-stat-card__value"><?php echo $stats['published']; ?></div>
    <div class="page-stat-card__label">Published</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      Live on site
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue <?php echo $statusFilter === 'scheduled' ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?status=scheduled';" style="cursor: pointer;">
    <div class="page-stat-card__value"><?php echo $stats['scheduled']; ?></div>
    <div class="page-stat-card__label">Scheduled</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Pending publish
    </div>
  </div>

  <div class="page-stat-card page-stat-card--gray <?php echo $statusFilter === 'archived' ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?status=archived';" style="cursor: pointer;">
    <div class="page-stat-card__value"><?php echo $stats['archived']; ?></div>
    <div class="page-stat-card__label">Archived</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
      Hidden from public
    </div>
  </div>

  <div class="page-stat-card page-stat-card--red <?php echo $showTrashed ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?trashed=1';" style="cursor: pointer;">
    <div class="page-stat-card__value"><?php echo $stats['trashed']; ?></div>
    <div class="page-stat-card__label">Trash</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      Deleted posts
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <form method="get" id="search-form" style="display: contents;">
      <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>"><?php endif; ?>
      <?php if ($categoryFilter): ?><input type="hidden" name="category_id" value="<?php echo $categoryFilter; ?>"><?php endif; ?>
      <?php if ($showTrashed): ?><input type="hidden" name="trashed" value="1"><?php endif; ?>
      <input type="text" name="search" placeholder="Search posts..." value="<?php echo htmlspecialchars($searchQuery); ?>">
    </form>
  </div>
  <div class="filter-toolbar__filters">
    <form method="get" id="filter-form" style="display: contents;">
      <?php if ($searchQuery): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>"><?php endif; ?>
      <?php if ($showTrashed): ?><input type="hidden" name="trashed" value="1"><?php endif; ?>
      <select name="category_id" class="filter-toolbar__select" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo (int)$cat['id']; ?>" <?php echo $categoryFilter === (int)$cat['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($cat['name']); ?> (<?php echo (int)$cat['post_count']; ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <select name="author_id" class="filter-toolbar__select" onchange="this.form.submit()">
        <option value="">All Authors</option>
        <?php foreach ($authors as $author): ?>
          <option value="<?php echo (int)$author['id']; ?>" <?php echo $authorFilter === (int)$author['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($author['username']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="date_from" class="filter-toolbar__select" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From date" onchange="this.form.submit()">
      <input type="date" name="date_to" class="filter-toolbar__select" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To date" onchange="this.form.submit()">
    </form>
    <?php if ($statusFilter || $categoryFilter || $searchQuery || $dateFrom || $dateTo || $authorFilter || $showTrashed): ?>
      <a href="?" class="btn btn--outline btn--sm">Clear Filters</a>
    <?php endif; ?>
  </div>
</div>

<!-- Bulk Actions Bar -->
<div id="bulk-actions-bar" class="bulk-actions-bar" style="display: none;">
  <span class="bulk-actions-count"><span id="selected-count">0</span> posts selected</span>
  <div class="bulk-actions-buttons">
    <select id="bulk-status-select" class="filter-toolbar__select filter-toolbar__select--sm">
      <option value="">Change Status...</option>
      <option value="draft">Draft</option>
      <option value="published">Published</option>
      <option value="scheduled">Scheduled</option>
      <option value="archived">Archived</option>
    </select>
    <button type="button" class="btn btn--secondary btn--sm" onclick="applyBulkStatus()">Apply Status</button>
    <select id="bulk-category-select" class="filter-toolbar__select filter-toolbar__select--sm">
      <option value="">Assign Category...</option>
      <option value="0">No Category</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" class="btn btn--secondary btn--sm" onclick="applyBulkCategory()">Apply Category</button>
    <?php if (!$showTrashed): ?>
      <button type="button" class="btn btn--danger btn--sm" onclick="applyBulkDelete()">Move to Trash</button>
    <?php endif; ?>
  </div>
</div>

<!-- Posts Table -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      <?php if ($showTrashed): ?>Trashed Posts<?php elseif ($statusFilter): ?><?php echo ucfirst(htmlspecialchars($statusFilter)); ?> Posts<?php else: ?>All Posts<?php endif; ?>
      <span class="admin-card__count">(<?php echo $totalPosts; ?>)</span>
    </h3>
    <div class="admin-card__actions">
      <a href="<?php echo $basePath; ?>/admin/blog_categories.php" class="btn btn--secondary btn--sm">
        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Categories
      </a>
      <a href="<?php echo $basePath; ?>/admin/blog_tags.php" class="btn btn--secondary btn--sm">
        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        Tags
      </a>
    </div>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table enhanced-data-table" id="posts-table">
      <thead>
        <tr>
          <th style="width: 30px;">
            <input type="checkbox" id="select-all-posts" onchange="toggleSelectAll()">
          </th>
          <th>
            <?php if ($sortBy === 'title' && $sortOrder === 'ASC'): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'title', 'order' => 'DESC'])); ?>">Title ↑</a>
            <?php elseif ($sortBy === 'title' && $sortOrder === 'DESC'): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'title', 'order' => 'ASC'])); ?>">Title ↓</a>
            <?php else: ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'title', 'order' => 'ASC'])); ?>">Title</a>
            <?php endif; ?>
          </th>
          <th>Category</th>
          <th>Tags</th>
          <th>
            <?php if ($sortBy === 'status' && $sortOrder === 'ASC'): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => 'DESC'])); ?>">Status ↑</a>
            <?php elseif ($sortBy === 'status' && $sortOrder === 'DESC'): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => 'ASC'])); ?>">Status ↓</a>
            <?php else: ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => 'ASC'])); ?>">Status</a>
            <?php endif; ?>
          </th>
          <th>Author</th>
          <th>
            <?php if ($sortBy === 'published_at' && $sortOrder === 'ASC'): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'published_at', 'order' => 'DESC'])); ?>">Published ↑</a>
            <?php elseif ($sortBy === 'published_at' && $sortOrder === 'DESC'): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'published_at', 'order' => 'ASC'])); ?>">Published ↓</a>
            <?php else: ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'published_at', 'order' => 'DESC'])); ?>">Published</a>
            <?php endif; ?>
          </th>
          <th style="text-align: center;">Views</th>
          <th style="text-align: center;">Likes</th>
          <th style="text-align: center;">Comments</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($posts)): ?>
          <tr>
            <td colspan="11" style="text-align: center; padding: 3rem;">
              <div style="color: var(--text-muted);">
                <svg viewBox="0 0 24 24" width="48" height="48" style="margin: 0 auto 1rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <p>No posts found.</p>
                <?php if (!$showTrashed): ?>
                  <a href="<?php echo $basePath; ?>/admin/blog_post_edit.php" class="btn btn--primary" style="margin-top: 1rem;">Create Your First Post</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($posts as $post): ?>
            <tr data-id="<?php echo (int)$post['id']; ?>" 
                data-title="<?php echo strtolower(htmlspecialchars($post['title'])); ?>"
                data-status="<?php echo htmlspecialchars($post['status']); ?>">
              <td>
                <input type="checkbox" class="post-checkbox" data-id="<?php echo (int)$post['id']; ?>" onchange="updateBulkActions()">
              </td>
              <td>
                <div class="enhanced-data-table__cell-primary">
                  <?php if (!empty($post['featured_image_path'])): ?>
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                      <img src="<?php echo htmlspecialchars($basePath . '/' . $post['featured_image_path']); ?>" 
                           alt="" 
                           style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--radius-md);">
                      <div>
                  <?php endif; ?>
                  <div class="enhanced-data-table__cell-title">
                    <?php if ($showTrashed): ?>
                      <?php echo htmlspecialchars($post['title']); ?>
                    <?php else: ?>
                      <a href="<?php echo $basePath; ?>/admin/blog_post_edit.php?id=<?php echo (int)$post['id']; ?>">
                        <?php echo htmlspecialchars($post['title']); ?>
                      </a>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($post['excerpt'])): ?>
                    <div class="enhanced-data-table__cell-subtitle"><?php echo htmlspecialchars(mb_substr($post['excerpt'], 0, 80)); ?><?php echo mb_strlen($post['excerpt']) > 80 ? '…' : ''; ?></div>
                  <?php endif; ?>
                  <?php if (!empty($post['featured_image_path'])): ?>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <?php if ($post['category_name']): ?>
                  <a href="?category_id=<?php echo (int)$post['category_id']; ?>" class="badge badge--info">
                    <?php echo htmlspecialchars($post['category_name']); ?>
                  </a>
                <?php else: ?>
                  <span class="badge badge--muted">Uncategorized</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($post['tag_names'])): ?>
                  <div class="tag-list">
                    <?php 
                    $tags = explode(', ', $post['tag_names']);
                    $displayTags = array_slice($tags, 0, 2);
                    foreach ($displayTags as $tag): ?>
                      <span class="badge badge--secondary badge--sm"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                    <?php if (count($tags) > 2): ?>
                      <span class="badge badge--muted badge--sm">+<?php echo count($tags) - 2; ?></span>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php 
                $statusBadgeClass = [
                    'draft' => 'badge--muted',
                    'published' => 'badge--success',
                    'scheduled' => 'badge--info',
                    'archived' => 'badge--warning'
                ];
                ?>
                <span class="badge <?php echo $statusBadgeClass[$post['status']] ?? 'badge--muted'; ?>">
                  <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
                </span>
                <?php if ($post['status'] === 'scheduled' && !empty($post['publish_at'])): ?>
                  <div class="text-muted" style="font-size: 0.75rem; margin-top: 0.25rem;">
                    <?php echo date('M j, Y g:i A', strtotime($post['publish_at'])); ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($post['author_name']): ?>
                  <?php echo htmlspecialchars($post['author_name']); ?>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($post['published_at']): ?>
                  <?php echo date('M j, Y', strtotime($post['published_at'])); ?>
                <?php elseif ($post['status'] === 'scheduled' && $post['publish_at']): ?>
                  <span class="text-muted"><?php echo date('M j, Y', strtotime($post['publish_at'])); ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <span class="stat-number"><?php echo number_format((int)$post['view_count']); ?></span>
              </td>
              <td style="text-align: center;">
                <span class="stat-number"><?php echo number_format((int)$post['like_count']); ?></span>
              </td>
              <td style="text-align: center;">
                <span class="stat-number"><?php echo number_format((int)$post['comment_count']); ?></span>
              </td>
              <td>
                <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
                  <?php if ($showTrashed): ?>
                    <!-- Trashed post actions -->
                    <form method="post" style="display: inline;">
                      <input type="hidden" name="action" value="restore">
                      <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                      <button type="submit" class="btn btn--secondary btn--sm">Restore</button>
                    </form>
                    <form method="post" style="display: inline;" onsubmit="return confirm('Permanently delete this post? This cannot be undone.');">
                      <input type="hidden" name="action" value="permanent_delete">
                      <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                      <button type="submit" class="btn btn--danger btn--sm">Delete Forever</button>
                    </form>
                  <?php else: ?>
                    <!-- Normal post actions -->
                    <a href="<?php echo $basePath; ?>/admin/blog_post_edit.php?id=<?php echo (int)$post['id']; ?>" class="btn btn--primary btn--sm">Edit</a>
                    <?php if ($post['status'] === 'published'): ?>
                      <a href="<?php echo $basePath; ?>/blog/<?php echo htmlspecialchars($post['slug']); ?>" target="_blank" class="btn btn--outline btn--sm" title="View post">
                        <svg viewBox="0 0 24 24" width="14" height="14"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                      </a>
                    <?php endif; ?>
                    <form method="post" style="display: inline;" onsubmit="return confirm('Move this post to trash?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                      <button type="submit" class="btn btn--danger btn--sm">Trash</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php if ($totalPages > 1): ?>
    <div class="admin-card__footer">
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn--outline btn--sm">&laquo; Previous</a>
        <?php endif; ?>
        
        <span class="pagination__info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        
        <?php if ($page < $totalPages): ?>
          <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn--outline btn--sm">Next &raquo;</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<style>
/* Page Stats Grid Active State */
.page-stat-card--active {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

/* Tag List */
.tag-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.badge--sm {
  font-size: 0.7rem;
  padding: 0.125rem 0.375rem;
}

/* Stat Number */
.stat-number {
  font-variant-numeric: tabular-nums;
  font-weight: 500;
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
  flex-wrap: wrap;
}

.bulk-actions-buttons .btn--secondary {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.3);
  color: white;
}

.bulk-actions-buttons .btn--secondary:hover {
  background: rgba(255, 255, 255, 0.3);
}

.bulk-actions-buttons .btn--danger {
  background: rgba(220, 38, 38, 0.8);
  border-color: rgba(220, 38, 38, 0.9);
  color: white;
}

.filter-toolbar__select--sm {
  padding: var(--space-1) var(--space-2);
  font-size: 0.85rem;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-4);
}

.pagination__info {
  color: var(--text-muted);
  font-size: 0.875rem;
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

/* Status Badge Colors */
.badge--warning {
  background: var(--color-warning);
  color: white;
}

/* Text Muted */
.text-muted {
  color: var(--text-muted);
}

/* Admin Card Footer */
.admin-card__footer {
  padding: var(--space-4);
  border-top: 1px solid var(--border-color);
  background: var(--bg-secondary);
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
}
</style>

<script>
// CSRF token for AJAX requests
var csrfToken = <?php echo json_encode($_SESSION['csrf_token']); ?>;

// Select all posts
function toggleSelectAll() {
  var selectAll = document.getElementById('select-all-posts');
  var checkboxes = document.querySelectorAll('.post-checkbox');
  checkboxes.forEach(function(cb) {
    cb.checked = selectAll.checked;
  });
  updateBulkActions();
}

// Update bulk actions bar
function updateBulkActions() {
  var checkboxes = document.querySelectorAll('.post-checkbox:checked');
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

// Apply bulk status change
function applyBulkStatus() {
  var status = document.getElementById('bulk-status-select').value;
  if (!status) {
    alert('Please select a status.');
    return;
  }
  
  var checkboxes = document.querySelectorAll('.post-checkbox:checked');
  var postIds = Array.from(checkboxes).map(function(cb) { return cb.dataset.id; });
  
  if (postIds.length === 0) {
    alert('Please select posts first.');
    return;
  }
  
  if (!confirm('Change status to ' + status + ' for ' + postIds.length + ' post(s)?')) {
    return;
  }
  
  var form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="action" value="bulk_status">' +
                   '<input type="hidden" name="bulk_status" value="' + status + '">' +
                   '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
                   '<input type="hidden" name="post_ids" value=\'' + JSON.stringify(postIds) + '\'>';
  document.body.appendChild(form);
  form.submit();
}

// Apply bulk category
function applyBulkCategory() {
  var categoryId = document.getElementById('bulk-category-select').value;
  
  var checkboxes = document.querySelectorAll('.post-checkbox:checked');
  var postIds = Array.from(checkboxes).map(function(cb) { return cb.dataset.id; });
  
  if (postIds.length === 0) {
    alert('Please select posts first.');
    return;
  }
  
  var form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="action" value="bulk_category">' +
                   '<input type="hidden" name="bulk_category_id" value="' + categoryId + '">' +
                   '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
                   '<input type="hidden" name="post_ids" value=\'' + JSON.stringify(postIds) + '\'>';
  document.body.appendChild(form);
  form.submit();
}

// Apply bulk delete
function applyBulkDelete() {
  var checkboxes = document.querySelectorAll('.post-checkbox:checked');
  var postIds = Array.from(checkboxes).map(function(cb) { return cb.dataset.id; });
  
  if (postIds.length === 0) {
    alert('Please select posts first.');
    return;
  }
  
  if (!confirm('Move ' + postIds.length + ' post(s) to trash?')) {
    return;
  }
  
  var form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = '<input type="hidden" name="action" value="bulk_delete">' +
                   '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
                   '<input type="hidden" name="post_ids" value=\'' + JSON.stringify(postIds) + '\'>';
  document.body.appendChild(form);
  form.submit();
}

// Search form submit
document.getElementById('search-form').addEventListener('submit', function(e) {
  // Let the form submit normally
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
