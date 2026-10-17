<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'partners';
$pageTitle = 'Partners';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare('UPDATE partners SET name = ?, image_path = ?, url = ?, sort_order = ?, is_active = ? WHERE id = ?');
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                trim($_POST['image_path'] ?? '') ?: null,
                trim($_POST['url'] ?? '') ?: null,
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
                (int)$_POST['id']
            ]);
            $message = 'Partner updated successfully.';
            $messageType = 'success';
            logAdminActivity('Updated partner: ' . trim($_POST['name'] ?? ''));
        } elseif ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO partners (name, image_path, url, sort_order, is_active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                trim($_POST['image_path'] ?? '') ?: null,
                trim($_POST['url'] ?? '') ?: null,
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $message = 'Partner added successfully.';
            $messageType = 'success';
            logAdminActivity('Created partner: ' . trim($_POST['name'] ?? ''));
        } elseif ($action === 'delete' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare('SELECT name FROM partners WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            $partner = $stmt->fetch();
            $stmt = $pdo->prepare('DELETE FROM partners WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            $message = 'Partner deleted successfully.';
            $messageType = 'success';
            logAdminActivity('Deleted partner: ' . ($partner['name'] ?? ''));
        }
    } catch (PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$items = [];
try {
    $items = $pdo->query('SELECT * FROM partners ORDER BY sort_order, id')->fetchAll();
} catch (Throwable $e) { /* table may not exist */ }

$activePartners = count(array_filter($items, fn($p) => $p['is_active']));
$partnersWithLogos = count(array_filter($items, fn($p) => !empty($p['image_path'])));

$adminBase = rtrim(BASE_PATH, '/') . '/admin';
$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Content Area -->
<div class="admin-content">
  <?php if ($message): ?>
    <div class="admin-alert admin-alert--<?php echo $messageType; ?> alert-dismissible">
      <?php echo htmlspecialchars($message); ?>
      <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
  <?php endif; ?>

  <!-- Dashboard Hero -->
  <div class="dashboard-hero">
    <div class="dashboard-hero__content">
      <div class="dashboard-hero__text">
        <div class="dashboard-hero__greeting">Partners</div>
        <h1 class="dashboard-hero__title">Manage Partners</h1>
        <p class="dashboard-hero__subtitle">Add and manage partner logos and links displayed in the "Our Partners" section on your website</p>
      </div>
      <div class="dashboard-hero__actions">
        <button type="button" class="dashboard-hero__btn" onclick="toggleCreateForm()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Partner
        </button>
      </div>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="dashboard-stats">
    <div class="stat-card stat-card--purple">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          All partners
        </div>
      </div>
      <div class="stat-card__value"><?php echo count($items); ?></div>
      <div class="stat-card__label">Total Partners</div>
    </div>

    <div class="stat-card stat-card--green">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          <?php echo $activePartners; ?> visible
        </div>
      </div>
      <div class="stat-card__value"><?php echo $activePartners; ?></div>
      <div class="stat-card__label">Active</div>
    </div>

    <div class="stat-card stat-card--blue">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          <?php echo $partnersWithLogos; ?> with logos
        </div>
      </div>
      <div class="stat-card__value"><?php echo $partnersWithLogos; ?></div>
      <div class="stat-card__label">With Logos</div>
    </div>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="toolbar__search">
      <svg class="toolbar__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" id="partner-search" class="toolbar__search-input" placeholder="Search partners..." onkeyup="filterPartners()">
    </div>
    <div class="toolbar__filters">
      <select id="status-filter" class="toolbar__select" onchange="filterPartners()">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <select id="logo-filter" class="toolbar__select" onchange="filterPartners()">
        <option value="">All Logos</option>
        <option value="with-logo">With Logo</option>
        <option value="no-logo">No Logo</option>
      </select>
    </div>
  </div>

  <!-- Create Form -->
  <div id="create-form" style="display: none;" class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <div class="admin-card__header-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="admin-card__header-content">
          <h3 class="admin-card__title">Add New Partner</h3>
          <p class="admin-card__subtitle">Create a new partner to display in your partners section</p>
        </div>
      </div>
    </div>
    <div class="admin-card__body">
      <form method="post">
        <input type="hidden" name="action" value="create">
        
        <div class="form-section">
          <h4 class="form-section__title">Partner Information</h4>
          <div class="form-grid">
            <div class="form-group">
              <label for="create_name" class="form-label">
                <span class="form-label__text">Partner Name *</span>
                <span class="form-label__required">Required</span>
              </label>
              <div class="form-input-wrapper">
                <input type="text" id="create_name" name="name" class="form-control" required placeholder="Enter partner name">
                <span class="form-input-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                </span>
              </div>
              <small class="form-hint">The name that will be displayed for this partner</small>
            </div>
            
            <div class="form-group">
              <label for="create_url" class="form-label">
                <span class="form-label__text">Website URL (optional)</span>
              </label>
              <div class="form-input-wrapper">
                <input type="url" id="create_url" name="url" class="form-control" placeholder="https://example.com">
                <span class="form-input-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                  </svg>
                </span>
              </div>
              <small class="form-hint">Link to the partner's website (optional)</small>
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <h4 class="form-section__title">Logo</h4>
          <div class="form-group">
            <label for="create_image" class="form-label">
              <span class="form-label__text">Logo Image (optional)</span>
            </label>
            <div class="upload-field">
              <input type="text" id="create_image" name="image_path" class="form-control" placeholder="Path or select from Media">
              <button type="button" class="btn btn--secondary" onclick="openMediaPicker('create_image', 'image')">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="3" width="18" height="18" rx="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <polyline points="21 15 16 10 5 21"/>
                </svg>
                Select from Media
              </button>
            </div>
            <small class="form-hint">Upload or select a logo image for this partner (optional)</small>
          </div>
        </div>
        
        <div class="form-section">
          <h4 class="form-section__title">Display Settings</h4>
          <div class="form-grid">
            <div class="form-group">
              <label for="create_sort" class="form-label">
                <span class="form-label__text">Sort Order</span>
              </label>
              <div class="form-input-wrapper">
                <input type="number" id="create_sort" name="sort_order" class="form-control" value="0" style="max-width: 120px;">
                <span class="form-input-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                  </svg>
                </span>
              </div>
              <small class="form-hint">Lower numbers appear first</small>
            </div>
            
            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" name="is_active" checked>
                <span class="checkbox-label__text">Active</span>
              </label>
              <small class="form-hint">Make this partner visible on your website</small>
            </div>
          </div>
        </div>
        
        <div class="form-actions">
          <button type="submit" class="btn btn--primary">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
              <polyline points="17 21 17 13 7 13 7 21"/>
              <polyline points="7 3 7 8 15 8"/>
            </svg>
            Add Partner
          </button>
          <button type="button" class="btn btn--secondary" onclick="toggleCreateForm()">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Partners Grid -->
  <div class="partners-grid" id="partners-grid">
    <?php if (empty($items)): ?>
      <div class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <h3 class="empty-state__title">No Partners Yet</h3>
        <p class="empty-state__text">Add your first partner to get started.</p>
        <button type="button" class="btn btn--primary" onclick="toggleCreateForm()">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Partner
        </button>
      </div>
    <?php endif; ?>
    
    <?php foreach ($items as $row): ?>
    <div class="partner-card" data-name="<?php echo strtolower(htmlspecialchars($row['name'])); ?>"
         data-status="<?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"
         data-has-logo="<?php echo !empty($row['image_path']) ? 'yes' : 'no'; ?>">
      <div class="partner-card__logo-wrapper">
        <?php if (!empty($row['image_path'])): ?>
          <img src="<?php echo htmlspecialchars($basePath . '/' . $row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="partner-card__logo">
        <?php else: ?>
          <div class="partner-card__logo-placeholder">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
        <?php endif; ?>
        <span class="partner-card__status <?php echo $row['is_active'] ? 'partner-card__status--active' : 'partner-card__status--inactive'; ?>">
          <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
        </span>
      </div>
      <div class="partner-card__content">
        <h4 class="partner-card__name"><?php echo e($row['name']); ?></h4>
        <?php if (!empty($row['url'])): ?>
          <a href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank" rel="noopener" class="partner-card__link">
            <?php echo htmlspecialchars($row['url']); ?>
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
              <polyline points="15 3 21 3 21 9"/>
              <line x1="10" y1="14" x2="21" y2="3"/>
            </svg>
          </a>
        <?php endif; ?>
      </div>
      <div class="partner-card__actions">
        <button type="button" class="btn btn--secondary btn--sm" onclick="toggleEditForm(<?php echo (int)$row['id']; ?>)">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          Edit
        </button>
        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this partner?');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
          <button type="submit" class="btn btn--ghost btn--sm btn--danger">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            </svg>
            Delete
          </button>
        </form>
      </div>
    </div>
    
    <!-- Edit Form (Hidden by default) -->
    <div id="edit-form-<?php echo (int)$row['id']; ?>" style="display: none;" class="admin-card admin-card--full partner-edit-form">
      <div class="admin-card__header">
        <div class="admin-card__header-left">
          <div class="admin-card__header-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </div>
          <div class="admin-card__header-content">
            <h3 class="admin-card__title">Edit Partner</h3>
            <p class="admin-card__subtitle">Update partner details and settings</p>
          </div>
        </div>
      </div>
      <div class="admin-card__body">
        <form method="post">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
          
          <div class="form-section">
            <h4 class="form-section__title">Partner Information</h4>
            <div class="form-grid">
              <div class="form-group">
                <label for="edit_name_<?php echo (int)$row['id']; ?>" class="form-label">
                  <span class="form-label__text">Partner Name *</span>
                  <span class="form-label__required">Required</span>
                </label>
                <div class="form-input-wrapper">
                  <input type="text" id="edit_name_<?php echo (int)$row['id']; ?>" name="name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                  <span class="form-input-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                      <circle cx="12" cy="7" r="4"/>
                    </svg>
                  </span>
                </div>
              </div>
              
              <div class="form-group">
                <label for="edit_url_<?php echo (int)$row['id']; ?>" class="form-label">
                  <span class="form-label__text">Website URL (optional)</span>
                </label>
                <div class="form-input-wrapper">
                  <input type="url" id="edit_url_<?php echo (int)$row['id']; ?>" name="url" class="form-control" value="<?php echo htmlspecialchars($row['url'] ?? ''); ?>">
                  <span class="form-input-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                      <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="form-section">
            <h4 class="form-section__title">Logo</h4>
            <div class="form-group">
              <label for="edit_image_<?php echo (int)$row['id']; ?>" class="form-label">
                <span class="form-label__text">Logo Image (optional)</span>
              </label>
              <div class="upload-field">
                <input type="text" id="edit_image_<?php echo (int)$row['id']; ?>" name="image_path" class="form-control" value="<?php echo htmlspecialchars($row['image_path'] ?? ''); ?>">
                <button type="button" class="btn btn--secondary" onclick="openMediaPicker('edit_image_<?php echo (int)$row['id']; ?>', 'image')">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                  </svg>
                  Select from Media
                </button>
              </div>
              <?php if (!empty($row['image_path'])): ?>
                <div class="upload-field-preview">
                  <img src="<?php echo htmlspecialchars($basePath . '/' . $row['image_path']); ?>" alt="" style="max-height: 100px; border-radius: var(--radius-md);">
                </div>
              <?php endif; ?>
              <small class="form-hint">Upload or select a logo image for this partner (optional)</small>
            </div>
          </div>
          
          <div class="form-section">
            <h4 class="form-section__title">Display Settings</h4>
            <div class="form-grid">
              <div class="form-group">
                <label for="edit_sort_<?php echo (int)$row['id']; ?>" class="form-label">
                  <span class="form-label__text">Sort Order</span>
                </label>
                <div class="form-input-wrapper">
                  <input type="number" id="edit_sort_<?php echo (int)$row['id']; ?>" name="sort_order" class="form-control" value="<?php echo (int)$row['sort_order']; ?>" style="max-width: 120px;">
                  <span class="form-input-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="8" y1="6" x2="21" y2="6"/>
                      <line x1="8" y1="12" x2="21" y2="12"/>
                      <line x1="8" y1="18" x2="21" y2="18"/>
                    </svg>
                  </span>
                </div>
                <small class="form-hint">Lower numbers appear first</small>
              </div>
              
              <div class="form-group">
                <label class="checkbox-label">
                  <input type="checkbox" name="is_active" <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                  <span class="checkbox-label__text">Active</span>
                </label>
                <small class="form-hint">Make this partner visible on your website</small>
              </div>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn--primary">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
              </svg>
              Save Changes
            </button>
            <button type="button" class="btn btn--secondary" onclick="toggleEditForm(<?php echo (int)$row['id']; ?>)">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<style>
.toolbar {
  display: flex;
  gap: var(--space-4);
  align-items: center;
  margin-bottom: var(--space-6);
  padding: var(--space-4);
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  flex-wrap: wrap;
}

.toolbar__search {
  flex: 1;
  min-width: 300px;
  position: relative;
}

.toolbar__search-icon {
  position: absolute;
  left: var(--space-3);
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  width: 20px;
  height: 20px;
}

.toolbar__search-input {
  width: 100%;
  padding: var(--space-3) var(--space-3) var(--space-3) var(--space-10);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  background: var(--bg-card);
  color: var(--text-primary);
  transition: all var(--transition-base);
}

.toolbar__search-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(102, 0, 153, 0.1);
}

.toolbar__filters {
  display: flex;
  gap: var(--space-2);
  flex-wrap: wrap;
}

.toolbar__select {
  padding: var(--space-2) var(--space-4);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  background: var(--bg-card);
  color: var(--text-primary);
  cursor: pointer;
  transition: all var(--transition-base);
}

.toolbar__select:focus {
  outline: none;
  border-color: var(--primary);
}

.partners-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--space-4);
}

.partner-card {
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: all var(--transition-base);
  position: relative;
}

.partner-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary);
}

.partner-card__logo-wrapper {
  position: relative;
  height: 120px;
  background: var(--bg-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.partner-card__logo {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  padding: var(--space-3);
}

.partner-card__logo-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
}

.partner-card__logo-placeholder svg {
  opacity: 0.5;
}

.partner-card__status {
  position: absolute;
  top: var(--space-2);
  right: var(--space-2);
  padding: var(--space-1) var(--space-2);
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: var(--radius-full);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.partner-card__status--active {
  background: var(--success-light);
  color: var(--success);
}

.partner-card__status--inactive {
  background: var(--gray-100);
  color: var(--text-muted);
}

.partner-card__content {
  padding: var(--space-4);
}

.partner-card__name {
  margin: 0 0 var(--space-1);
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.4;
}

.partner-card__link {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  font-size: 0.875rem;
  color: var(--info);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.partner-card__link:hover {
  color: var(--info-dark);
  text-decoration: underline;
}

.partner-card__actions {
  display: flex;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-4);
  border-top: 1px solid var(--border-light);
  background: var(--bg-secondary);
}

.partner-edit-form {
  margin-top: var(--space-4);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-4);
  margin-bottom: var(--space-4);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--primary);
}

.form-actions {
  display: flex;
  gap: var(--space-3);
  justify-content: flex-end;
  padding-top: var(--space-4);
  border-top: 1px solid var(--border-light);
}

.form-actions .btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
}

/* Dark mode styles */
.dark .toolbar {
  background: var(--bg-card);
}

.dark .toolbar__search-input {
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.dark .toolbar__select {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--border-dark);
}

.dark .toolbar__select:focus {
  border-color: var(--primary);
}

.dark .partner-card {
  background: var(--bg-card);
  border-color: var(--border-dark);
}

.dark .partner-card:hover {
  border-color: var(--primary);
}

.dark .partner-card__logo-placeholder {
  color: var(--text-muted);
}

.dark .partner-card__logo-placeholder svg {
  opacity: 0.7;
}

.dark .partner-card__status--inactive {
  background: var(--bg-secondary);
  color: var(--text-muted);
}

.dark .partner-card__actions {
  border-top-color: var(--border-dark);
  background: var(--bg-card);
}

.dark .partner-edit-form {
  border-top-color: var(--border-dark);
}
</style>

<script>
function toggleCreateForm() {
  const form = document.getElementById('create-form');
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function toggleEditForm(id) {
  const form = document.getElementById('edit-form-' + id);
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function filterPartners() {
  const searchValue = document.getElementById('partner-search').value.toLowerCase();
  const statusFilter = document.getElementById('status-filter').value;
  const logoFilter = document.getElementById('logo-filter').value;
  const cards = document.querySelectorAll('.partner-card');
  const forms = document.querySelectorAll('.partner-edit-form');
  
  cards.forEach(function(card) {
    const name = card.dataset.name;
    const status = card.dataset.status;
    const hasLogo = card.dataset.hasLogo;
    
    let matchesSearch = name.includes(searchValue);
    let matchesStatus = !statusFilter || status === statusFilter;
    let matchesLogo = true;
    
    if (logoFilter === 'with-logo') {
      matchesLogo = hasLogo === 'yes';
    } else if (logoFilter === 'no-logo') {
      matchesLogo = hasLogo === 'no';
    }
    
    if (matchesSearch && matchesStatus && matchesLogo) {
      card.style.display = '';
      const formId = card.nextElementSibling.id;
      if (formId && document.getElementById(formId)) {
        document.getElementById(formId).style.display = 'none';
      }
    } else {
      card.style.display = 'none';
    }
  });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
