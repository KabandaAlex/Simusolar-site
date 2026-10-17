<?php
/**
 * Team Members Management
 * Allows administrators to manage team members displayed on the website
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'team-members';
$pageTitle = 'Team Members';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare('UPDATE team_members SET name = ?, job_title = ?, biography = ?, profile_image = ?, category = ?, display_order = ?, is_active = ? WHERE id = ?');
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                trim($_POST['job_title'] ?? ''),
                trim($_POST['biography'] ?? '') ?: null,
                trim($_POST['profile_image'] ?? '') ?: null,
                $_POST['category'] ?? 'staff',
                (int)($_POST['display_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
                (int)$_POST['id']
            ]);
            $message = 'Team member updated successfully.';
            $messageType = 'success';
            logActivity('updated', 'team_member', (int)$_POST['id'], trim($_POST['name'] ?? ''));
        } elseif ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO team_members (name, job_title, biography, profile_image, category, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['name'] ?? ''),
                trim($_POST['job_title'] ?? ''),
                trim($_POST['biography'] ?? '') ?: null,
                trim($_POST['profile_image'] ?? '') ?: null,
                $_POST['category'] ?? 'staff',
                (int)($_POST['display_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $message = 'Team member added successfully.';
            $messageType = 'success';
            logActivity('created', 'team_member', $pdo->lastInsertId(), trim($_POST['name'] ?? ''));
        } elseif ($action === 'delete' && !empty($_POST['id'])) {
            // Get member name before deletion for logging
            $stmt = $pdo->prepare('SELECT name FROM team_members WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            $member = $stmt->fetch();
            
            $stmt = $pdo->prepare('DELETE FROM team_members WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            $message = 'Team member deleted successfully.';
            $messageType = 'success';
            logActivity('deleted', 'team_member', (int)$_POST['id'], $member['name'] ?? '');
        }
    } catch (PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all team members grouped by category
$items = [];
try {
    $items = $pdo->query('SELECT * FROM team_members ORDER BY category, display_order, name')->fetchAll();
} catch (Throwable $e) { /* table may not exist */ }

// Calculate stats
$totalMembers = count($items);
$activeMembers = count(array_filter($items, fn($m) => $m['is_active']));
$membersWithPhotos = count(array_filter($items, fn($m) => !empty($m['profile_image'])));
$directors = count(array_filter($items, fn($m) => $m['category'] === 'director'));
$advisors = count(array_filter($items, fn($m) => $m['category'] === 'advisor'));
$staff = count(array_filter($items, fn($m) => $m['category'] === 'staff'));

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
        <div class="dashboard-hero__greeting">Team Members</div>
        <h1 class="dashboard-hero__title">Manage Team</h1>
        <p class="dashboard-hero__subtitle">Add and manage team members displayed on your website's team page and other sections</p>
      </div>
      <div class="dashboard-hero__actions">
        <button type="button" class="dashboard-hero__btn" onclick="toggleCreateForm()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Team Member
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
          All members
        </div>
      </div>
      <div class="stat-card__value"><?php echo $totalMembers; ?></div>
      <div class="stat-card__label">Total Team Members</div>
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
          <?php echo $activeMembers; ?> visible
        </div>
      </div>
      <div class="stat-card__value"><?php echo $activeMembers; ?></div>
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
          <?php echo $membersWithPhotos; ?> with photos
        </div>
      </div>
      <div class="stat-card__value"><?php echo $membersWithPhotos; ?></div>
      <div class="stat-card__label">With Photos</div>
    </div>

    <div class="stat-card stat-card--orange">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          Leadership
        </div>
      </div>
      <div class="stat-card__value"><?php echo $directors; ?></div>
      <div class="stat-card__label">Directors</div>
    </div>
  </div>

  <!-- Toolbar -->
  <div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
       
      <input type="text" id="member-search" class="toolbar__search-input" placeholder="Search team members..." onkeyup="filterMembers()">
    </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" class="toolbar__select" onchange="filterMembers()">
        <option value="">All Categories</option>
        <option value="director">Directors</option>
        <option value="advisor">Advisors</option>
        <option value="staff">Staff</option>
      </select>
      <select id="status-filter" class="filter-toolbar__select" onchange="filterMembers()">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <select id="photo-filter" class="filter-toolbar__select" onchange="filterMembers()">
        <option value="">All Photos</option>
        <option value="with-photo">With Photo</option>
        <option value="no-photo">No Photo</option>
      </select>
    </div>
  </div>

  <!-- Create Form -->
  <div id="create-form" style="display: none;" class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <div class="admin-card__header-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <div class="admin-card__header-content">
          <h3 class="admin-card__title">Add New Team Member</h3>
          <p class="admin-card__subtitle">Create a new team member to display on your website</p>
        </div>
      </div>
    </div>
    <div class="admin-card__body">
      <form method="post">
        <input type="hidden" name="action" value="create">
        
        <div class="form-section">
          <h4 class="form-section__title">Personal Information</h4>
          <div class="form-grid">
            <div class="form-group">
              <label for="create_name" class="form-label">
                <span class="form-label__text">Full Name *</span>
                <span class="form-label__required">Required</span>
              </label>
              <div class="form-input-wrapper">
                <input type="text" id="create_name" name="name" class="form-control" required placeholder="Enter full name">
                <span class="form-input-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                </span>
              </div>
              <small class="form-hint">The full name as it should appear on the website</small>
            </div>
            
            <div class="form-group">
              <label for="create_job_title" class="form-label">
                <span class="form-label__text">Job Title *</span>
                <span class="form-label__required">Required</span>
              </label>
              <div class="form-input-wrapper">
                <input type="text" id="create_job_title" name="job_title" class="form-control" required placeholder="e.g. Chief Executive Officer">
                <span class="form-input-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                  </svg>
                </span>
              </div>
              <small class="form-hint">The job title or position</small>
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <h4 class="form-section__title">Biography</h4>
          <div class="form-group">
            <label for="create_biography" class="form-label">
              <span class="form-label__text">Biography (optional)</span>
            </label>
            <textarea id="create_biography" name="biography" class="form-control form-control--full" rows="4" placeholder="Enter a brief biography..."></textarea>
            <small class="form-hint">A short biography describing the team member's background and role</small>
          </div>
        </div>
        
        <div class="form-section">
          <h4 class="form-section__title">Profile Image</h4>
          <div class="form-group">
            <label for="create_profile_image" class="form-label">
              <span class="form-label__text">Profile Image (optional)</span>
            </label>
            <div class="upload-field">
              <input type="text" id="create_profile_image" name="profile_image" class="form-control" placeholder="Path or select from Media">
              <button type="button" class="btn btn--secondary" onclick="openMediaPicker('create_profile_image', 'image')">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="3" width="18" height="18" rx="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <polyline points="21 15 16 10 5 21"/>
                </svg>
                Select from Media
              </button>
            </div>
            <div id="create_profile_image_preview" class="upload-field-preview" style="display: none;">
              <img src="" alt="Preview" style="max-height: 100px; border-radius: var(--radius-md);">
            </div>
            <small class="form-hint">Select a profile photo from the media library</small>
          </div>
        </div>
        
        <div class="form-section">
          <h4 class="form-section__title">Display Settings</h4>
          <div class="form-grid">
            <div class="form-group">
              <label for="create_category" class="form-label">
                <span class="form-label__text">Category *</span>
              </label>
              <select id="create_category" name="category" class="form-control" required>
                <option value="staff">Staff</option>
                <option value="advisor">Advisor</option>
                <option value="director">Director</option>
              </select>
              <small class="form-hint">Determines how the member is grouped on the website</small>
            </div>
            
            <div class="form-group">
              <label for="create_display_order" class="form-label">
                <span class="form-label__text">Display Order</span>
              </label>
              <div class="form-input-wrapper">
                <input type="number" id="create_display_order" name="display_order" class="form-control" value="0" style="max-width: 120px;">
                <span class="form-input-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"/>
                    <line x1="8" y1="12" x2="21" y2="12"/>
                    <line x1="8" y1="18" x2="21" y2="18"/>
                  </svg>
                </span>
              </div>
              <small class="form-hint">Lower numbers appear first within each category</small>
            </div>
            
            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" name="is_active" checked>
                <span class="checkbox-label__text">Active</span>
              </label>
              <small class="form-hint">Make this team member visible on your website</small>
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
            Add Team Member
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

  <!-- Team Members Grid -->
  <?php if (empty($items)): ?>
    <div class="empty-state">
      <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      <h3 class="empty-state__title">No Team Members Yet</h3>
      <p class="empty-state__text">Add your first team member to get started.</p>
      <button type="button" class="btn btn--primary" onclick="toggleCreateForm()">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Team Member
      </button>
    </div>
  <?php endif; ?>

  <div class="team-members-grid" id="team-members-grid">
    <?php 
    $currentCategory = '';
    foreach ($items as $row): 
      // Add category header when category changes
      if ($currentCategory !== $row['category']):
        $currentCategory = $row['category'];
        $categoryLabel = ucfirst($row['category']) . 's';
        $categoryBadgeClass = $row['category'] === 'director' ? 'badge--warning' : 
                              ($row['category'] === 'advisor' ? 'badge--info' : 'badge--default');
    ?>
      <div class="category-header" data-category="<?php echo htmlspecialchars($row['category']); ?>">
        <h3 class="category-header__title">
          <?php echo $categoryLabel; ?>
          <span class="badge <?php echo $categoryBadgeClass; ?>">
            <?php echo count(array_filter($items, fn($m) => $m['category'] === $row['category'])); ?>
          </span>
        </h3>
      </div>
    <?php endif; ?>
    
    <div class="team-member-card" data-name="<?php echo strtolower(htmlspecialchars($row['name'])); ?>"
         data-category="<?php echo htmlspecialchars($row['category']); ?>"
         data-status="<?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"
         data-has-photo="<?php echo !empty($row['profile_image']) ? 'yes' : 'no'; ?>">
      <div class="team-member-card__header">
        <?php if (!empty($row['profile_image'])): ?>
          <img src="<?php echo htmlspecialchars($basePath . '/' . $row['profile_image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="team-member-card__photo">
        <?php else: ?>
          <div class="team-member-card__photo-placeholder">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
        <?php endif; ?>
        <div class="team-member-card__info">
          <h4 class="team-member-card__name"><?php echo e($row['name']); ?></h4>
          <p class="team-member-card__title"><?php echo e($row['job_title']); ?></p>
        </div>
        <div class="team-member-card__badges">
          <span class="badge <?php
            echo $row['category'] === 'director' ? 'badge--warning' : 
                 ($row['category'] === 'advisor' ? 'badge--info' : 'badge--default');
          ?>">
            <?php echo ucfirst($row['category']); ?>
          </span>
          <span class="badge <?php echo $row['is_active'] ? 'badge--success' : 'badge--muted'; ?>">
            <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
          </span>
        </div>
      </div>
      
      <?php if (!empty($row['biography'])): ?>
        <div class="team-member-card__biography">
          <?php echo e(mb_strlen($row['biography']) > 150 ? mb_substr($row['biography'], 0, 150) . '...' : $row['biography']); ?>
        </div>
      <?php endif; ?>
      
      <div class="team-member-card__meta">
        <span class="team-member-card__order">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="8" y1="6" x2="21" y2="6"/>
            <line x1="8" y1="12" x2="21" y2="12"/>
            <line x1="8" y1="18" x2="21" y2="18"/>
          </svg>
          Order: <?php echo (int)$row['display_order']; ?>
        </span>
      </div>
      
      <div class="team-member-card__actions">
        <button type="button" class="btn btn--secondary btn--sm" onclick="toggleEditForm(<?php echo (int)$row['id']; ?>)">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          Edit
        </button>
        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this team member?');">
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
    <div id="edit-form-<?php echo (int)$row['id']; ?>" style="display: none;" class="admin-card admin-card--full team-member-edit-form">
      <div class="admin-card__header">
        <div class="admin-card__header-left">
          <div class="admin-card__header-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </div>
          <div class="admin-card__header-content">
            <h3 class="admin-card__title">Edit Team Member</h3>
            <p class="admin-card__subtitle">Update team member details and settings</p>
          </div>
        </div>
      </div>
      <div class="admin-card__body">
        <form method="post">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
          
          <div class="form-section">
            <h4 class="form-section__title">Personal Information</h4>
            <div class="form-grid">
              <div class="form-group">
                <label for="edit_name_<?php echo (int)$row['id']; ?>" class="form-label">
                  <span class="form-label__text">Full Name *</span>
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
                <label for="edit_job_title_<?php echo (int)$row['id']; ?>" class="form-label">
                  <span class="form-label__text">Job Title *</span>
                  <span class="form-label__required">Required</span>
                </label>
                <div class="form-input-wrapper">
                  <input type="text" id="edit_job_title_<?php echo (int)$row['id']; ?>" name="job_title" class="form-control" value="<?php echo htmlspecialchars($row['job_title']); ?>" required>
                  <span class="form-input-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                      <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="form-section">
            <h4 class="form-section__title">Biography</h4>
            <div class="form-group">
              <label for="edit_biography_<?php echo (int)$row['id']; ?>" class="form-label">
                <span class="form-label__text">Biography (optional)</span>
              </label>
              <textarea id="edit_biography_<?php echo (int)$row['id']; ?>" name="biography" class="form-control form-control--full" rows="4"><?php echo htmlspecialchars($row['biography'] ?? ''); ?></textarea>
            </div>
          </div>
          
          <div class="form-section">
            <h4 class="form-section__title">Profile Image</h4>
            <div class="form-group">
              <label for="edit_profile_image_<?php echo (int)$row['id']; ?>" class="form-label">
                <span class="form-label__text">Profile Image (optional)</span>
              </label>
              <div class="upload-field">
                <input type="text" id="edit_profile_image_<?php echo (int)$row['id']; ?>" name="profile_image" class="form-control" value="<?php echo htmlspecialchars($row['profile_image'] ?? ''); ?>">
                <button type="button" class="btn btn--secondary" onclick="openMediaPicker('edit_profile_image_<?php echo (int)$row['id']; ?>', 'image')">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                  </svg>
                  Select from Media
                </button>
              </div>
              <?php if (!empty($row['profile_image'])): ?>
                <div class="upload-field-preview">
                  <img src="<?php echo htmlspecialchars($basePath . '/' . $row['profile_image']); ?>" alt="" style="max-height: 100px; border-radius: var(--radius-md);">
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="form-section">
            <h4 class="form-section__title">Display Settings</h4>
            <div class="form-grid">
              <div class="form-group">
                <label for="edit_category_<?php echo (int)$row['id']; ?>" class="form-label">
                  <span class="form-label__text">Category *</span>
                </label>
                <select id="edit_category_<?php echo (int)$row['id']; ?>" name="category" class="form-control" required>
                  <option value="staff" <?php echo $row['category'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                  <option value="advisor" <?php echo $row['category'] === 'advisor' ? 'selected' : ''; ?>>Advisor</option>
                  <option value="director" <?php echo $row['category'] === 'director' ? 'selected' : ''; ?>>Director</option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="edit_display_order_<?php echo (int)$row['id']; ?>" class="form-label">
                  <span class="form-label__text">Display Order</span>
                </label>
                <div class="form-input-wrapper">
                  <input type="number" id="edit_display_order_<?php echo (int)$row['id']; ?>" name="display_order" class="form-control" value="<?php echo (int)$row['display_order']; ?>" style="max-width: 120px;">
                  <span class="form-input-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="8" y1="6" x2="21" y2="6"/>
                      <line x1="8" y1="12" x2="21" y2="12"/>
                      <line x1="8" y1="18" x2="21" y2="18"/>
                    </svg>
                  </span>
                </div>
              </div>
              
              <div class="form-group">
                <label class="checkbox-label">
                  <input type="checkbox" name="is_active" <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                  <span class="checkbox-label__text">Active</span>
                </label>
                <small class="form-hint">Make this team member visible on your website</small>
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
    <?php endforeach; ?>
  </div>
</div>

<style>
.team-members-grid {
  display: grid;
  gap: 1rem;
}

.category-header {
  grid-column: 1 / -1;
  padding: 1rem 0 0.5rem;
  border-bottom: 2px solid var(--border-color, #e2e8f0);
  margin-top: 1rem;
}

.category-header:first-child {
  margin-top: 0;
}

.category-header__title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-primary, #1e293b);
}

.team-member-card {
  background: var(--card-bg, #fff);
  border-radius: var(--radius-lg, 0.5rem);
  border: 1px solid var(--border-color, #e2e8f0);
  padding: 1.25rem;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.team-member-card:hover {
  box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
}

.team-member-card__header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.team-member-card__photo {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.team-member-card__photo-placeholder {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--bg-secondary, #f1f5f9);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted, #94a3b8);
  flex-shrink: 0;
}

.team-member-card__info {
  flex: 1;
  min-width: 0;
}

.team-member-card__name {
  margin: 0 0 0.25rem;
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary, #1e293b);
}

.team-member-card__title {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-secondary, #64748b);
}

.team-member-card__badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.team-member-card__biography {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border-color, #e2e8f0);
  font-size: 0.875rem;
  color: var(--text-secondary, #64748b);
  line-height: 1.5;
}

.team-member-card__meta {
  margin-top: 0.75rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.team-member-card__order {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--text-muted, #94a3b8);
}

.team-member-card__actions {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border-color, #e2e8f0);
  display: flex;
  gap: 0.5rem;
}

.team-member-edit-form {
  grid-column: 1 / -1;
}

/* Badge colors */
.badge--warning {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
}

.badge--info {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
}

.badge--default {
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
}

.badge--success {
  background: rgba(34, 197, 94, 0.1);
  color: #16a34a;
}

.badge--muted {
  background: rgba(148, 163, 184, 0.1);
  color: #94a3b8;
}

.dark .team-member-card {
  background: var(--card-bg, #1e293b);
  border-color: var(--border-color, #334155);
}

.dark .category-header {
  border-color: var(--border-color, #334155);
}

.dark .team-member-card__biography,
.dark .team-member-card__actions,
.dark .team-member-card__meta {
  border-color: var(--border-color, #334155);
}
</style>

<script>
function toggleCreateForm() {
  var form = document.getElementById('create-form');
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
  if (form.style.display === 'none') {
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function toggleEditForm(id) {
  var form = document.getElementById('edit-form-' + id);
  if (form) {
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') {
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
}

function filterMembers() {
  var searchValue = document.getElementById('member-search').value.toLowerCase();
  var categoryFilter = document.getElementById('category-filter').value;
  var statusFilter = document.getElementById('status-filter').value;
  var photoFilter = document.getElementById('photo-filter').value;
  
  var cards = document.querySelectorAll('.team-member-card');
  var categoryHeaders = document.querySelectorAll('.category-header');
  var editForms = document.querySelectorAll('.team-member-edit-form');
  
  // Hide all edit forms when filtering
  editForms.forEach(function(form) {
    form.style.display = 'none';
  });
  
  // Track visible items per category
  var visibleInCategory = {};
  
  cards.forEach(function(card) {
    var name = card.dataset.name;
    var category = card.dataset.category;
    var status = card.dataset.status;
    var hasPhoto = card.dataset.hasPhoto;
    
    var matchesSearch = name.includes(searchValue);
    var matchesCategory = !categoryFilter || category === categoryFilter;
    var matchesStatus = !statusFilter || status === statusFilter;
    
    var matchesPhoto = true;
    if (photoFilter === 'with-photo') {
      matchesPhoto = hasPhoto === 'yes';
    } else if (photoFilter === 'no-photo') {
      matchesPhoto = hasPhoto === 'no';
    }
    
    if (matchesSearch && matchesCategory && matchesStatus && matchesPhoto) {
      card.style.display = '';
      visibleInCategory[category] = (visibleInCategory[category] || 0) + 1;
    } else {
      card.style.display = 'none';
    }
  });
  
  // Show/hide category headers based on visible items
  categoryHeaders.forEach(function(header) {
    var cat = header.dataset.category;
    header.style.display = visibleInCategory[cat] > 0 ? '' : 'none';
  });
}

// Media picker function (uses existing implementation from header)
function openMediaPicker(fieldId, type) {
  var url = '<?php echo $adminBase; ?>/media/picker.php?field_id=' + encodeURIComponent(fieldId) + '&type=' + encodeURIComponent(type);
  var width = 900;
  var height = 600;
  var left = (screen.width - width) / 2;
  var top = (screen.height - height) / 2;
  window.open(url, 'mediaPicker', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes');
}

// Callback for media picker
window.setUploadPath = function(fieldId, path) {
  var field = document.getElementById(fieldId);
  if (field) {
    field.value = path;
    // Trigger change event for any listeners
    field.dispatchEvent(new Event('change'));
    
    // Update preview if it exists
    var previewContainer = document.getElementById(fieldId + '_preview');
    if (previewContainer) {
      var img = previewContainer.querySelector('img');
      if (img) {
        img.src = '<?php echo $basePath; ?>/' + path;
        previewContainer.style.display = 'block';
      }
    }
  }
};

// Profile image preview for create form
document.addEventListener('DOMContentLoaded', function() {
  var createImageField = document.getElementById('create_profile_image');
  if (createImageField) {
    createImageField.addEventListener('change', function() {
      var previewContainer = document.getElementById('create_profile_image_preview');
      if (previewContainer && this.value) {
        var img = previewContainer.querySelector('img');
        if (img) {
          img.src = '<?php echo $basePath; ?>/' + this.value;
          previewContainer.style.display = 'block';
        }
      } else if (previewContainer) {
        previewContainer.style.display = 'none';
      }
    });
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
