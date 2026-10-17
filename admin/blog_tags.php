<?php
/**
 * Tulima Solar - Blog Tags Management
 * Full CRUD for blog tags with search, filter, and bulk operations
 * 
 * RBAC: Requires admin or editor role
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/auth_helpers.php';
requireTagAccess(); // Requires blog_manage_tags permission

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../includes/blog_helpers.php';

$pdo = getDb();
$currentNav = 'blog-tags';
$pageTitle = 'Blog Tags';

$message = '';
$messageType = 'success';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to generate unique slug
function generateBlogTagSlug($pdo, $name, $excludeId = null) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    $baseSlug = $slug;
    $counter = 1;
    
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare('SELECT id FROM blog_tags WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM blog_tags WHERE slug = ?');
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
        // Create new tag
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if ($name) {
                $slug = trim($_POST['slug'] ?? '') ?: generateBlogTagSlug($pdo, $name);
                $description = trim($_POST['description'] ?? '') ?: null;
                
                try {
                    $stmt = $pdo->prepare('INSERT INTO blog_tags (name, slug, description) VALUES (?, ?, ?)');
                    $stmt->execute([$name, $slug, $description]);
                    $newId = $pdo->lastInsertId();
                    
                    logActivity('created', 'blog_tag', $newId, $name);
                    $message = 'Tag created successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = 'A tag with this name already exists.';
                    } else {
                        $message = 'Error creating tag: ' . $e->getMessage();
                    }
                    $messageType = 'error';
                }
            }
        }
        
        // Update tag
        if ($action === 'update' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name'] ?? '');
            if ($name) {
                $slug = trim($_POST['slug'] ?? '') ?: generateBlogTagSlug($pdo, $name, $id);
                $description = trim($_POST['description'] ?? '') ?: null;
                
                try {
                    $stmt = $pdo->prepare('UPDATE blog_tags SET name = ?, slug = ?, description = ? WHERE id = ?');
                    $stmt->execute([$name, $slug, $description, $id]);
                    
                    logActivity('updated', 'blog_tag', $id, $name);
                    $message = 'Tag updated successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = 'A tag with this name already exists.';
                    } else {
                        $message = 'Error updating tag: ' . $e->getMessage();
                    }
                    $messageType = 'error';
                }
            }
        }
        
        // Delete tag
        if ($action === 'delete' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            try {
                // Get tag name before deletion
                $stmt = $pdo->prepare('SELECT name FROM blog_tags WHERE id = ?');
                $stmt->execute([$id]);
                $tagName = $stmt->fetchColumn();
                
                // Delete tag (post_tags will be cascade deleted)
                $stmt = $pdo->prepare('DELETE FROM blog_tags WHERE id = ?');
                $stmt->execute([$id]);
                
                logActivity('deleted', 'blog_tag', $id, $tagName);
                $message = 'Tag deleted successfully.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Error deleting tag: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Bulk delete
        if ($action === 'bulk_delete' && !empty($_POST['selected_ids'])) {
            $ids = json_decode($_POST['selected_ids'], true);
            if (is_array($ids) && count($ids) > 0) {
                try {
                    $deletedCount = 0;
                    
                    foreach ($ids as $id) {
                        $id = (int)$id;
                        
                        // Get tag name before deletion
                        $stmt = $pdo->prepare('SELECT name FROM blog_tags WHERE id = ?');
                        $stmt->execute([$id]);
                        $tagName = $stmt->fetchColumn();
                        
                        $stmt = $pdo->prepare('DELETE FROM blog_tags WHERE id = ?');
                        $stmt->execute([$id]);
                        
                        logActivity('deleted', 'blog_tag', $id, $tagName);
                        $deletedCount++;
                    }
                    
                    $message = "Successfully deleted $deletedCount tag(s).";
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error deleting tags: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

// Get all tags with post counts
$tags = $pdo->query('
    SELECT t.*, 
           (SELECT COUNT(*) FROM blog_post_tags WHERE tag_id = t.id) AS post_count
    FROM blog_tags t
    ORDER BY t.name
')->fetchAll();

// Stats
$totalTags = count($tags);
$tagsWithPosts = count(array_filter($tags, fn($t) => $t['post_count'] > 0));
$totalPostTags = array_sum(array_map(fn($t) => (int)$t['post_count'], $tags));

$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
<div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Blog Tags</h1>
    <p class="page-header-enhanced__subtitle">Manage tags for organizing your blog posts. Tags provide flexible content organization across categories.</p>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="tag-message">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
  </div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo $totalTags; ?></div>
    <div class="page-stat-card__label">Total Tags</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
      All tags
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $tagsWithPosts; ?></div>
    <div class="page-stat-card__label">In Use</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      <?php echo $totalTags ? round(($tagsWithPosts / $totalTags) * 100) : 0; ?>% utilized
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $totalPostTags; ?></div>
    <div class="page-stat-card__label">Tag Assignments</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
      Post-tag links
    </div>
  </div>

  <div class="page-stat-card page-stat-card--orange">
    <div class="page-stat-card__value"><?php echo $totalTags - $tagsWithPosts; ?></div>
    <div class="page-stat-card__label">Unused Tags</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Can be cleaned up
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="tag-search" placeholder="Search tags..." onkeyup="filterTags()">
  </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" id="usage-filter" onchange="filterTags()">
      <option value="">All Tags</option>
      <option value="in-use">In Use</option>
      <option value="unused">Unused</option>
    </select>
    <select class="filter-toolbar__select" id="sort-filter" onchange="sortTags()">
      <option value="name">Sort by Name</option>
      <option value="posts">Sort by Post Count</option>
      <option value="newest">Sort by Newest</option>
      <option value="oldest">Sort by Oldest</option>
    </select>
  </div>
  <button type="button" class="btn btn--primary" onclick="var f=document.getElementById('create-form'); f.style.display=f.style.display==='none'?'block':'none'">
    <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Tag
  </button>
</div>

<!-- Create Form -->
<div id="create-form" style="display: none;" class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
      New Tag
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post" id="tag-create-form">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      
      <div class="form-row">
        <div class="form-group">
          <label>Name <span class="required">*</span></label>
          <input type="text" name="name" id="create-name" class="form-control" required placeholder="e.g., Solar Tips">
        </div>
        <div class="form-group">
          <label>Slug</label>
          <input type="text" name="slug" id="create-slug" class="form-control" placeholder="Auto-generated from name">
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this tag (optional)"></textarea>
      </div>
      
      <button type="submit" class="btn btn--primary">Create Tag</button>
      <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<!-- Tags Table -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
      All Tags
    </h3>
    <div class="admin-card__actions">
      <?php if (count($tags) > 0): ?>
      <button type="button" class="btn btn--danger btn--sm" onclick="bulkDelete()">
        <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        Delete Selected
      </button>
      <?php endif; ?>
    </div>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table enhanced-data-table" id="tags-table">
      <thead>
        <tr>
          <th style="width: 30px;">
            <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
          </th>
          <th>Name</th>
          <th>Slug</th>
          <th>Description</th>
          <th>Posts</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="tags-tbody">
        <?php if (empty($tags)): ?>
        <tr>
          <td colspan="7" class="text-center" style="padding: var(--space-8);">
            <div style="color: var(--text-muted);">
              <svg viewBox="0 0 24 24" width="48" height="48" style="margin: 0 auto var(--space-4);"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
              <p>No tags created yet.</p>
              <p>Click "Add Tag" to create your first tag.</p>
            </div>
          </td>
        </tr>
        <?php else: ?>
          <?php foreach ($tags as $tag): ?>
          <tr data-id="<?php echo (int)$tag['id']; ?>" 
              data-name="<?php echo strtolower(htmlspecialchars($tag['name'])); ?>" 
              data-slug="<?php echo strtolower(htmlspecialchars($tag['slug'])); ?>" 
              data-posts="<?php echo (int)$tag['post_count']; ?>"
              data-created="<?php echo strtotime($tag['created_at']); ?>"
              data-usage="<?php echo $tag['post_count'] > 0 ? 'in-use' : 'unused'; ?>"
              class="tag-row">
            <td>
              <input type="checkbox" class="tag-checkbox" value="<?php echo (int)$tag['id']; ?>">
            </td>
            <td>
              <div class="enhanced-data-table__cell-primary">
                <div class="enhanced-data-table__cell-title">
                  <?php echo htmlspecialchars($tag['name']); ?>
                </div>
              </div>
            </td>
            <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars($tag['slug']); ?></code></td>
            <td>
              <?php if ($tag['description']): ?>
                <span title="<?php echo htmlspecialchars($tag['description']); ?>">
                  <?php echo htmlspecialchars(mb_strlen($tag['description']) > 50 ? mb_substr($tag['description'], 0, 50) . '...' : $tag['description']); ?>
                </span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge badge--<?php echo $tag['post_count'] > 0 ? 'info' : 'muted'; ?>">
                <?php echo (int)$tag['post_count']; ?> post<?php echo $tag['post_count'] != 1 ? 's' : ''; ?>
              </span>
            </td>
            <td>
              <span class="text-muted" title="<?php echo date('M j, Y g:i A', strtotime($tag['created_at'])); ?>">
                <?php echo date('M j, Y', strtotime($tag['created_at'])); ?>
              </span>
            </td>
            <td>
              <div style="display: flex; gap: var(--space-2);">
                <button type="button" class="btn btn--primary btn--sm" data-toggle-row="<?php echo (int)$tag['id']; ?>">Edit</button>
                <form method="post" style="display: inline;" onsubmit="return confirm('Delete this tag? It will be removed from all posts using it.');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int)$tag['id']; ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                  <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <tr id="row-form-<?php echo (int)$tag['id']; ?>" class="expand-row" style="display: none;">
            <td colspan="7">
              <form method="post" style="max-width: 640px;">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo (int)$tag['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-row">
                  <div class="form-group">
                    <label>Name <span class="required">*</span></label>
                    <input type="text" name="name" id="edit-name-<?php echo (int)$tag['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($tag['name']); ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" id="edit-slug-<?php echo (int)$tag['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($tag['slug']); ?>">
                  </div>
                </div>
                <div class="form-group">
                  <label>Description</label>
                  <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($tag['description'] ?? ''); ?></textarea>
                </div>
                
                <div style="margin-top: 1rem;">
                  <button type="submit" class="btn btn--primary">Save Tag</button>
                  <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('row-form-<?php echo (int)$tag['id']; ?>').style.display='none'">Cancel</button>
                </div>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Tag Cloud Preview -->
<?php if (count($tags) > 0): ?>
<div class="admin-card" style="margin-top: 1.5rem;">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
      Tag Cloud Preview
    </h3>
  </div>
  <div class="admin-card__body">
    <div class="tag-cloud">
      <?php 
      $maxPosts = max(array_map(fn($t) => (int)$t['post_count'], $tags));
      foreach ($tags as $tag): 
        $size = $maxPosts > 0 ? 0.8 + ($tag['post_count'] / $maxPosts) * 0.7 : 0.8;
      ?>
        <span class="tag-cloud-item" style="font-size: <?php echo $size; ?>em;" title="<?php echo (int)$tag['post_count']; ?> posts">
          <?php echo htmlspecialchars($tag['name']); ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

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

/* Tag Cloud */
.tag-cloud {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2) var(--space-3);
  padding: var(--space-4);
  align-items: center;
}

.tag-cloud-item {
  display: inline-block;
  color: var(--color-primary);
  background: var(--primary-light);
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-full);
  transition: all 0.2s ease;
}

.tag-cloud-item:hover {
  background: var(--color-primary);
  color: white;
  transform: scale(1.05);
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

// Add slug auto-generation for edit forms
<?php foreach ($tags as $tag): ?>
document.getElementById('edit-name-<?php echo (int)$tag['id']; ?>')?.addEventListener('input', function() {
  var name = this.value;
  var slug = name.toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
  document.getElementById('edit-slug-<?php echo (int)$tag['id']; ?>').value = slug;
});
<?php endforeach; ?>

// Filter tags
function filterTags() {
  var searchValue = document.getElementById('tag-search').value.toLowerCase();
  var usageFilter = document.getElementById('usage-filter').value;
  var rows = document.querySelectorAll('#tags-tbody tr[data-id]');
  
  rows.forEach(function(row) {
    var name = row.dataset.name;
    var slug = row.dataset.slug;
    var usage = row.dataset.usage;
    
    var matchesSearch = name.includes(searchValue) || slug.includes(searchValue);
    var matchesUsage = !usageFilter || usage === usageFilter;
    
    if (matchesSearch && matchesUsage) {
      row.style.display = '';
      // Also show/hide the corresponding edit form row
      var formRow = document.getElementById('row-form-' + row.dataset.id);
      if (formRow) formRow.style.display = 'none';
    } else {
      row.style.display = 'none';
    }
  });
}

// Sort tags
function sortTags() {
  var sortBy = document.getElementById('sort-filter').value;
  var tbody = document.getElementById('tags-tbody');
  var rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
  
  rows.sort(function(a, b) {
    if (sortBy === 'name') {
      return a.dataset.name.localeCompare(b.dataset.name);
    } else if (sortBy === 'posts') {
      return parseInt(b.dataset.posts) - parseInt(a.dataset.posts);
    } else if (sortBy === 'newest') {
      return parseInt(b.dataset.created) - parseInt(a.dataset.created);
    } else if (sortBy === 'oldest') {
      return parseInt(a.dataset.created) - parseInt(b.dataset.created);
    }
    return 0;
  });
  
  // Re-append sorted rows and their edit forms
  rows.forEach(function(row) {
    var formRow = document.getElementById('row-form-' + row.dataset.id);
    tbody.appendChild(row);
    if (formRow) tbody.appendChild(formRow);
  });
}

// Toggle expand rows
document.querySelectorAll('[data-toggle-row]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id = this.dataset.toggleRow;
    var row = document.getElementById('row-form-' + id);
    if (row) {
      row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
    }
  });
});

// Select all checkbox
function toggleSelectAll() {
  var selectAll = document.getElementById('select-all');
  var checkboxes = document.querySelectorAll('.tag-checkbox');
  checkboxes.forEach(function(cb) {
    cb.checked = selectAll.checked;
  });
}

// Bulk delete
function bulkDelete() {
  var checkboxes = document.querySelectorAll('.tag-checkbox:checked');
  if (checkboxes.length === 0) {
    alert('Please select at least one tag to delete.');
    return;
  }
  
  var ids = [];
  checkboxes.forEach(function(cb) {
    ids.push(cb.value);
  });
  
  if (confirm('Are you sure you want to delete ' + ids.length + ' selected tag(s)? Tags will be removed from all posts.')) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="bulk_delete">' +
                     '<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">' +
                     '<input type="hidden" name="selected_ids" value=\'' + JSON.stringify(ids) + '\'>';
    document.body.appendChild(form);
    form.submit();
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
