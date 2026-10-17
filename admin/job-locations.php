<?php
/**
 * Tulima Solar - Job Locations Management
 * Full CRUD for job locations with remote work support
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
$currentNav = 'job-locations';
$pageTitle = 'Job Locations';

$message = '';
$messageType = 'success';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to generate unique slug
function generateJobLocationSlug($pdo, $name, $excludeId = null) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    $baseSlug = $slug;
    $counter = 1;
    
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare('SELECT id FROM job_locations WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM job_locations WHERE slug = ?');
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
        // Create new location
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if ($name) {
                $slug = trim($_POST['slug'] ?? '') ?: generateJobLocationSlug($pdo, $name);
                $region = trim($_POST['region'] ?? '') ?: null;
                $country = trim($_POST['country'] ?? '') ?: 'Kenya';
                $postalCode = trim($_POST['postal_code'] ?? '') ?: null;
                $metaTitle = trim($_POST['meta_title'] ?? '') ?: null;
                $metaDescription = trim($_POST['meta_description'] ?? '') ?: null;
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                $isRemote = isset($_POST['is_remote']) ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare('INSERT INTO job_locations (name, slug, region, country, postal_code, meta_title, meta_description, display_order, is_active, is_remote) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $slug, $region, $country, $postalCode, $metaTitle, $metaDescription, $displayOrder, $isActive, $isRemote]);
                    $newId = $pdo->lastInsertId();
                    
                    logActivity('created', 'job_location', $newId, $name);
                    $message = 'Job location created successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error creating location: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Update location
        if ($action === 'update' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name'] ?? '');
            if ($name) {
                $slug = trim($_POST['slug'] ?? '') ?: generateJobLocationSlug($pdo, $name, $id);
                $region = trim($_POST['region'] ?? '') ?: null;
                $country = trim($_POST['country'] ?? '') ?: 'Kenya';
                $postalCode = trim($_POST['postal_code'] ?? '') ?: null;
                $metaTitle = trim($_POST['meta_title'] ?? '') ?: null;
                $metaDescription = trim($_POST['meta_description'] ?? '') ?: null;
                $displayOrder = (int)($_POST['display_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                $isRemote = isset($_POST['is_remote']) ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare('UPDATE job_locations SET name = ?, slug = ?, region = ?, country = ?, postal_code = ?, meta_title = ?, meta_description = ?, display_order = ?, is_active = ?, is_remote = ? WHERE id = ?');
                    $stmt->execute([$name, $slug, $region, $country, $postalCode, $metaTitle, $metaDescription, $displayOrder, $isActive, $isRemote, $id]);
                    
                    logActivity('updated', 'job_location', $id, $name);
                    $message = 'Job location updated successfully.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error updating location: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Delete location
        if ($action === 'delete' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            try {
                // Check if location has jobs
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE location_id = ?');
                $stmt->execute([$id]);
                $jobCount = (int)$stmt->fetchColumn();
                
                if ($jobCount > 0) {
                    $message = "Cannot delete location: $jobCount job(s) assigned. Please reassign them first.";
                    $messageType = 'error';
                } else {
                    // Get location name before deletion
                    $stmt = $pdo->prepare('SELECT name FROM job_locations WHERE id = ?');
                    $stmt->execute([$id]);
                    $locName = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare('DELETE FROM job_locations WHERE id = ?');
                    $stmt->execute([$id]);
                    
                    logActivity('deleted', 'job_location', $id, $locName);
                    $message = 'Job location deleted successfully.';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error deleting location: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Toggle active status
        if ($action === 'toggle_active' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare('UPDATE job_locations SET is_active = NOT is_active WHERE id = ?');
            $stmt->execute([$id]);
            
            $stmt = $pdo->prepare('SELECT name FROM job_locations WHERE id = ?');
            $stmt->execute([$id]);
            $locName = $stmt->fetchColumn();
            
            logActivity('toggled', 'job_location', $id, $locName);
            $message = 'Location active status updated.';
            $messageType = 'success';
        }
        
        // Toggle remote status
        if ($action === 'toggle_remote' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare('UPDATE job_locations SET is_remote = NOT is_remote WHERE id = ?');
            $stmt->execute([$id]);
            
            $stmt = $pdo->prepare('SELECT name FROM job_locations WHERE id = ?');
            $stmt->execute([$id]);
            $locName = $stmt->fetchColumn();
            
            logActivity('toggled remote', 'job_location', $id, $locName);
            $message = 'Location remote status updated.';
            $messageType = 'success';
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
                        
                        // Check if location can be deleted
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE location_id = ?');
                        $stmt->execute([$id]);
                        $jobCount = (int)$stmt->fetchColumn();
                        
                        if ($jobCount === 0) {
                            $stmt = $pdo->prepare('SELECT name FROM job_locations WHERE id = ?');
                            $stmt->execute([$id]);
                            $locName = $stmt->fetchColumn();
                            
                            $stmt = $pdo->prepare('DELETE FROM job_locations WHERE id = ?');
                            $stmt->execute([$id]);
                            
                            logActivity('deleted', 'job_location', $id, $locName);
                            $deletedCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                    
                    if ($deletedCount > 0 && $skippedCount > 0) {
                        $message = "Deleted $deletedCount location(s). Skipped $skippedCount with jobs assigned.";
                    } elseif ($deletedCount > 0) {
                        $message = "Successfully deleted $deletedCount location(s).";
                    } else {
                        $message = "No locations deleted. All selected locations have jobs assigned.";
                        $messageType = 'error';
                    }
                } catch (PDOException $e) {
                    $message = 'Error deleting locations: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

// Get all locations with job counts
$locations = $pdo->query('
    SELECT l.*, 
           (SELECT COUNT(*) FROM jobs WHERE location_id = l.id) AS job_count
    FROM job_locations l
    ORDER BY l.display_order, l.id
')->fetchAll();

// Stats
$totalLocations = count($locations);
$activeLocations = count(array_filter($locations, fn($l) => $l['is_active']));
$remoteLocations = count(array_filter($locations, fn($l) => $l['is_remote']));
$locationsWithJobs = count(array_filter($locations, fn($l) => $l['job_count'] > 0));

$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
<div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Job Locations</h1>
    <p class="page-header-enhanced__subtitle">Manage job locations for your job listings. Mark locations as remote for work-from-home opportunities.</p>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="location-message">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
  </div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo $totalLocations; ?></div>
    <div class="page-stat-card__label">Total Locations</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      All locations
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $activeLocations; ?></div>
    <div class="page-stat-card__label">Active</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      <?php echo $totalLocations ? round(($activeLocations / $totalLocations) * 100) : 0; ?>% visible
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $remoteLocations; ?></div>
    <div class="page-stat-card__label">Remote</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Work from home
    </div>
  </div>

  <div class="page-stat-card page-stat-card--orange">
    <div class="page-stat-card__value"><?php echo $locationsWithJobs; ?></div>
    <div class="page-stat-card__label">With Jobs</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      Locations in use
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="location-search" placeholder="Search locations..." onkeyup="filterLocations()">
  </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" id="status-filter" onchange="filterLocations()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
    <select class="filter-toolbar__select" id="remote-filter" onchange="filterLocations()">
      <option value="">All Types</option>
      <option value="remote">Remote</option>
      <option value="onsite">On-site</option>
    </select>
    <select class="filter-toolbar__select" id="country-filter" onchange="filterLocations()">
      <option value="">All Countries</option>
      <?php 
      $countries = array_unique(array_filter(array_column($locations, 'country')));
      sort($countries);
      foreach ($countries as $country): ?>
        <option value="<?php echo htmlspecialchars($country); ?>"><?php echo htmlspecialchars($country); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="button" class="btn btn--primary" onclick="var f=document.getElementById('create-form'); f.style.display=f.style.display==='none'?'block':'none'">
    <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Location
  </button>
</div>

<!-- Create Form -->
<div id="create-form" style="display: none;" class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      New Job Location
    </h3>
  </div>
  <div class="admin-card__body">
    <form method="post" id="location-create-form">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      
      <div class="form-section">
        <p class="form-section-title">Basic Information</p>
        <div class="form-row">
          <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <input type="text" name="name" id="create-name" class="form-control" required placeholder="e.g., Nairobi">
          </div>
          <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" id="create-slug" class="form-control" placeholder="Auto-generated from name">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Region / State</label>
            <input type="text" name="region" class="form-control" placeholder="e.g., Nairobi County">
          </div>
          <div class="form-group">
            <label>Country</label>
            <input type="text" name="country" class="form-control" value="Kenya" placeholder="e.g., Kenya">
          </div>
        </div>
        <div class="form-group">
          <label>Postal Code</label>
          <input type="text" name="postal_code" class="form-control" placeholder="e.g., 00100" style="max-width: 200px;">
        </div>
      </div>
      
      <div class="form-section">
        <p class="form-section-title">Work Type</p>
        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" name="is_remote" id="create-remote">
            <span class="checkbox-text">
              <strong>Remote / Work from Home</strong>
              <small>This location represents a remote work option, not a physical office</small>
            </span>
          </label>
        </div>
      </div>
      
      <div class="form-section">
        <p class="form-section-title">SEO Settings</p>
        <div class="form-group">
          <label>Meta Title</label>
          <input type="text" name="meta_title" class="form-control" placeholder="SEO title for location page">
        </div>
        <div class="form-group">
          <label>Meta Description</label>
          <textarea name="meta_description" class="form-control" rows="2" placeholder="SEO description for location page"></textarea>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Display Order</label>
          <input type="number" name="display_order" class="form-control" value="0" style="max-width: 100px;">
        </div>
      </div>
      
      <div class="form-group">
        <label><input type="checkbox" name="is_active" checked> Active (visible on site)</label>
      </div>
      
      <button type="submit" class="btn btn--primary">Create Location</button>
      <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<!-- Locations Table -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      All Job Locations
    </h3>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table enhanced-data-table" id="locations-table">
      <thead>
        <tr>
          <th style="width: 30px;">
            <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
          </th>
          <th>Name</th>
          <th>Slug</th>
          <th>Region</th>
          <th>Country</th>
          <th>Type</th>
          <th>Jobs</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="locations-tbody">
        <?php foreach ($locations as $loc): ?>
        <tr data-id="<?php echo (int)$loc['id']; ?>"
            data-name="<?php echo strtolower(htmlspecialchars($loc['name'] ?? '')); ?>"
            data-slug="<?php echo strtolower(htmlspecialchars($loc['slug'] ?? '')); ?>"
            data-region="<?php echo strtolower(htmlspecialchars($loc['region'] ?? '')); ?>"
            data-country="<?php echo strtolower(htmlspecialchars($loc['country'] ?? '')); ?>"
            data-status="<?php echo $loc['is_active'] ? 'active' : 'inactive'; ?>"
            data-remote="<?php echo $loc['is_remote'] ? 'remote' : 'onsite'; ?>"
            class="location-row">
          <td>
            <input type="checkbox" class="location-checkbox" value="<?php echo (int)$loc['id']; ?>">
          </td>
          <td>
            <div class="enhanced-data-table__cell-primary">
              <div class="enhanced-data-table__cell-title">
                <?php if ($loc['is_remote']): ?>
                  <svg viewBox="0 0 24 24" width="16" height="16" style="color: var(--color-primary);"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <?php else: ?>
                  <svg viewBox="0 0 24 24" width="16" height="16" style="color: var(--text-muted);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php endif; ?>
                <?php echo htmlspecialchars($loc['name'] ?? ''); ?>
              </div>
              <?php if ($loc['postal_code'] ?? null): ?>
                <div class="enhanced-data-table__cell-subtitle">Postal: <?php echo htmlspecialchars($loc['postal_code']); ?></div>
              <?php endif; ?>
            </div>
          </td>
          <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars($loc['slug'] ?? ''); ?></code></td>
          <td><?php echo htmlspecialchars($loc['region'] ?? '-'); ?></td>
          <td><?php echo htmlspecialchars($loc['country'] ?? '-'); ?></td>
          <td>
            <?php if ($loc['is_remote']): ?>
              <span class="badge badge--info">
                <svg viewBox="0 0 24 24" width="12" height="12"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                Remote
              </span>
            <?php else: ?>
              <span class="badge badge--muted">On-site</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge badge--<?php echo $loc['job_count'] > 0 ? 'info' : 'muted'; ?>">
              <?php echo (int)$loc['job_count']; ?> job<?php echo $loc['job_count'] != 1 ? 's' : ''; ?>
            </span>
          </td>
          <td><span class="badge <?php echo $loc['is_active'] ? 'badge--success' : 'badge--muted'; ?>"><?php echo $loc['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
          <td>
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
              <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="id" value="<?php echo (int)$loc['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="btn btn--<?php echo $loc['is_active'] ? 'warning' : 'success'; ?> btn--sm" title="<?php echo $loc['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                  <?php echo $loc['is_active'] ? 'Deactivate' : 'Activate'; ?>
                </button>
              </form>
              <button type="button" class="btn btn--primary btn--sm" data-toggle-row="<?php echo (int)$loc['id']; ?>">Edit</button>
              <form method="post" style="display: inline;" onsubmit="return confirmDelete(<?php echo (int)$loc['job_count']; ?>);">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$loc['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <tr id="row-form-<?php echo (int)$loc['id']; ?>" class="expand-row" style="display: none;">
          <td colspan="9">
            <form method="post" style="max-width: 640px;">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?php echo (int)$loc['id']; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              
              <div class="form-section">
                <p class="form-section-title">Basic Information</p>
                <div class="form-row">
                  <div class="form-group">
                    <label>Name <span class="required">*</span></label>
                    <input type="text" name="name" id="edit-name-<?php echo (int)$loc['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($loc['name'] ?? ''); ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" id="edit-slug-<?php echo (int)$loc['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($loc['slug'] ?? ''); ?>">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Region / State</label>
                    <input type="text" name="region" class="form-control" value="<?php echo htmlspecialchars($loc['region'] ?? ''); ?>" placeholder="e.g., Nairobi County">
                  </div>
                  <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($loc['country'] ?? 'Kenya'); ?>">
                  </div>
                </div>
                <div class="form-group">
                  <label>Postal Code</label>
                  <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($loc['postal_code'] ?? ''); ?>" style="max-width: 200px;">
                </div>
              </div>
              
              <div class="form-section">
                <p class="form-section-title">Work Type</p>
                <div class="form-group">
                  <label class="checkbox-label">
                    <input type="checkbox" name="is_remote" <?php echo $loc['is_remote'] ? 'checked' : ''; ?>>
                    <span class="checkbox-text">
                      <strong>Remote / Work from Home</strong>
                      <small>This location represents a remote work option, not a physical office</small>
                    </span>
                  </label>
                </div>
              </div>
              
              <div class="form-section">
                <p class="form-section-title">SEO Settings</p>
                <div class="form-group">
                  <label>Meta Title</label>
                  <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($loc['meta_title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                  <label>Meta Description</label>
                  <textarea name="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($loc['meta_description'] ?? ''); ?></textarea>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label>Display Order</label>
                  <input type="number" name="display_order" class="form-control" value="<?php echo (int)$loc['display_order']; ?>" style="max-width: 100px;">
                </div>
              </div>
              
              <div class="form-group">
                <label><input type="checkbox" name="is_active" <?php echo $loc['is_active'] ? 'checked' : ''; ?>> Active (visible on site)</label>
              </div>
              
              <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn--primary">Save Location</button>
                <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('row-form-<?php echo (int)$loc['id']; ?>').style.display='none'">Cancel</button>
              </div>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($locations) > 0): ?>
  <div class="admin-card__footer">
    <button type="button" class="btn btn--danger btn--sm" onclick="bulkDelete()">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      Delete Selected
    </button>
  </div>
  <?php endif; ?>
</div>

<style>
/* Checkbox label styles */
.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  cursor: pointer;
  padding: var(--space-3);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  transition: background 0.2s;
}

.checkbox-label:hover {
  background: var(--bg-tertiary, #e9ecef);
}

.checkbox-label input[type="checkbox"] {
  margin-top: 3px;
}

.checkbox-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.checkbox-text small {
  color: var(--text-muted);
  font-size: 0.85rem;
}

/* Badge with icon */
.badge svg {
  vertical-align: middle;
  margin-right: 2px;
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

// Filter locations
function filterLocations() {
    var search = document.getElementById('location-search').value.toLowerCase();
    var statusFilter = document.getElementById('status-filter').value;
    var remoteFilter = document.getElementById('remote-filter').value;
    var countryFilter = document.getElementById('country-filter').value.toLowerCase();
    
    document.querySelectorAll('#locations-tbody .location-row').forEach(function(row) {
        var name = row.getAttribute('data-name');
        var slug = row.getAttribute('data-slug');
        var region = row.getAttribute('data-region');
        var country = row.getAttribute('data-country');
        var status = row.getAttribute('data-status');
        var remote = row.getAttribute('data-remote');
        
        var matchesSearch = name.includes(search) || slug.includes(search) || region.includes(search);
        var matchesStatus = !statusFilter || status === statusFilter;
        var matchesRemote = !remoteFilter || remote === remoteFilter;
        var matchesCountry = !countryFilter || country === countryFilter;
        
        row.style.display = matchesSearch && matchesStatus && matchesRemote && matchesCountry ? '' : 'none';
        
        // Also hide the corresponding edit form row
        var formRow = document.getElementById('row-form-' + row.getAttribute('data-id'));
        if (formRow) {
            formRow.style.display = 'none';
        }
    });
}

// Select all checkbox
function toggleSelectAll() {
    var selectAll = document.getElementById('select-all').checked;
    document.querySelectorAll('.location-checkbox').forEach(function(cb) {
        cb.checked = selectAll;
    });
}

// Confirm delete
function confirmDelete(jobCount) {
    if (jobCount > 0) {
        alert('Cannot delete location: ' + jobCount + ' job(s) assigned. Please reassign them first.');
        return false;
    }
    return confirm('Are you sure you want to delete this location?');
}

// Bulk delete
function bulkDelete() {
    var selected = [];
    document.querySelectorAll('.location-checkbox:checked').forEach(function(cb) {
        selected.push(cb.value);
    });
    
    if (selected.length === 0) {
        alert('Please select at least one location to delete.');
        return;
    }
    
    if (!confirm('Are you sure you want to delete ' + selected.length + ' selected location(s)? Locations with jobs assigned will be skipped.')) {
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
