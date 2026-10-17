<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';
$pdo = getDb();
$currentNav = 'activity-log';
$pageTitle = 'Activity Log';

// Get filters
$actionFilter = $_GET['action'] ?? '';
$entityFilter = $_GET['entity'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = (int) ($_GET['page'] ?? 1);
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Build query
$where = ['1=1'];
$params = [];

if (!empty($actionFilter)) {
    $where[] = 'action LIKE ?';
    $params[] = "%$actionFilter%";
}

if (!empty($entityFilter)) {
    $where[] = 'entity_type = ?';
    $params[] = $entityFilter;
}

if (!empty($dateFrom)) {
    $where[] = 'created_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}

if (!empty($dateTo)) {
    $where[] = 'created_at <= ?';
    $params[] = $dateTo . ' 23:59:59';
}

$whereClause = implode(' AND ', $where);

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log WHERE $whereClause");
$stmt->execute($params);
$totalCount = (int) $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Get activity logs
$stmt = $pdo->prepare("
    SELECT al.*, a.username 
    FROM activity_log al
    LEFT JOIN admins a ON al.admin_id = a.id
    WHERE $whereClause
    ORDER BY al.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Get activity statistics
$stats = getActivityStats($pdo);

// Get available entity types
$entityTypes = $pdo->query("SELECT DISTINCT entity_type FROM activity_log WHERE entity_type IS NOT NULL ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN);

$adminBase = rtrim(BASE_PATH, '/') . '/admin';

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="admin-page-header">
  <div class="admin-page-header__content">
    <h1 class="admin-page-header__title">Activity Log</h1>
    <p class="admin-page-header__subtitle">Track all administrative actions and changes</p>
  </div>
  <div class="admin-page-header__actions">
    <a href="<?php echo $adminBase; ?>/index.php" class="btn btn--secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Back to Dashboard
    </a>
  </div>
</div>

<!-- Content Area -->
<div class="admin-content">
  <!-- Activity Stats -->
  <div class="activity-stats">
    <div class="activity-stat-item">
      <div class="activity-stat-item__icon activity-stat-item__icon--purple">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
      </div>
      <div class="activity-stat-item__content">
        <div class="activity-stat-item__value"><?php echo number_format($stats['total'] ?? 0); ?></div>
        <div class="activity-stat-item__label">Total Activities</div>
      </div>
    </div>

    <div class="activity-stat-item">
      <div class="activity-stat-item__icon activity-stat-item__icon--green">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>
      </div>
      <div class="activity-stat-item__content">
        <div class="activity-stat-item__value"><?php echo number_format($stats['pages'] ?? 0); ?></div>
        <div class="activity-stat-item__label">Page Activities</div>
      </div>
    </div>

    <div class="activity-stat-item">
      <div class="activity-stat-item__icon activity-stat-item__icon--orange">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
        </svg>
      </div>
      <div class="activity-stat-item__content">
        <div class="activity-stat-item__value"><?php echo number_format($stats['products'] ?? 0); ?></div>
        <div class="activity-stat-item__label">Product Activities</div>
      </div>
    </div>

    <div class="activity-stat-item">
      <div class="activity-stat-item__icon activity-stat-item__icon--blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
      </div>
      <div class="activity-stat-item__content">
        <div class="activity-stat-item__value"><?php echo number_format($stats['contacts'] ?? 0); ?></div>
        <div class="activity-stat-item__label">Contact Activities</div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="admin-card">
    <div class="admin-card__header">
      <h3 class="admin-card__title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="22 3 2 3 10 12.46 10 22 12 16 22 22 16 22 12 22 3 12 14.54 4 22 3z"/>
        </svg>
        Filters
      </h3>
    </div>
    <div class="admin-card__body">
      <form method="GET" class="filter-form">
        <div class="filter-form__row">
          <div class="filter-form__field">
            <label class="filter-form__label">Action</label>
            <select name="action" class="filter-form__select">
              <option value="">All Actions</option>
              <option value="create" <?php echo $actionFilter === 'create' ? 'selected' : ''; ?>>Create</option>
              <option value="update" <?php echo $actionFilter === 'update' ? 'selected' : ''; ?>>Update</option>
              <option value="delete" <?php echo $actionFilter === 'delete' ? 'selected' : ''; ?>>Delete</option>
              <option value="login" <?php echo $actionFilter === 'login' ? 'selected' : ''; ?>>Login</option>
              <option value="logout" <?php echo $actionFilter === 'logout' ? 'selected' : ''; ?>>Logout</option>
            </select>
          </div>

          <div class="filter-form__field">
            <label class="filter-form__label">Entity Type</label>
            <select name="entity" class="filter-form__select">
              <option value="">All Entities</option>
              <?php foreach ($entityTypes as $type): ?>
              <option value="<?php echo e($type); ?>" <?php echo $entityFilter === $type ? 'selected' : ''; ?>><?php echo e($type); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-form__field">
            <label class="filter-form__label">Date From</label>
            <input type="date" name="date_from" class="filter-form__input" value="<?php echo e($dateFrom); ?>">
          </div>

          <div class="filter-form__field">
            <label class="filter-form__label">Date To</label>
            <input type="date" name="date_to" class="filter-form__input" value="<?php echo e($dateTo); ?>">
          </div>

          <div class="filter-form__actions">
            <button type="submit" class="btn btn--primary">Apply Filters</button>
            <a href="<?php echo $adminBase; ?>/activity-log.php" class="btn btn--secondary">Clear</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Activity Log Table -->
  <div class="admin-card">
    <div class="admin-card__header">
      <h3 class="admin-card__title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
        Activity Log
      </h3>
      <div class="admin-card__header-info">
        <span class="text-muted"><?php echo number_format($totalCount); ?> records</span>
      </div>
    </div>
    <div class="admin-card__body">
      <?php if (!empty($activities)): ?>
      <div class="data-table">
        <table class="data-table__table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>User</th>
              <th>Action</th>
              <th>Entity</th>
              <th>Details</th>
              <th>IP Address</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activities as $activity): ?>
            <tr>
              <td>
                <div class="data-table__cell-primary">
                  <div class="data-table__cell-title"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></div>
                  <div class="data-table__cell-subtitle"><?php echo timeAgo(strtotime($activity['created_at'])); ?> ago</div>
                </div>
              </td>
              <td>
                <span class="badge badge--info"><?php echo e($activity['username'] ?? 'System'); ?></span>
              </td>
              <td>
                <span class="badge <?php
                  echo $activity['action'] === 'create' ? 'badge--success' :
                       ($activity['action'] === 'update' ? 'badge--warning' :
                       ($activity['action'] === 'delete' ? 'badge--danger' : 'badge--info'));
                ?>"><?php echo ucfirst(e($activity['action'])); ?></span>
              </td>
              <td>
                <?php if ($activity['entity_type']): ?>
                <div class="data-table__cell-primary">
                  <div class="data-table__cell-title"><?php echo e($activity['entity_type']); ?></div>
                  <?php if ($activity['entity_id']): ?>
                  <div class="data-table__cell-subtitle">ID: <?php echo $activity['entity_id']; ?></div>
                  <?php endif; ?>
                </div>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($activity['details']): ?>
                <?php 
                  $details = json_decode($activity['details'], true);
                  if ($details && isset($details['title'])): 
                ?>
                <div class="data-table__cell-primary">
                  <div class="data-table__cell-title"><?php echo e($details['title']); ?></div>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="text-muted"><?php echo e($activity['ip_address'] ?? '-'); ?></span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo buildQueryString($actionFilter, $entityFilter, $dateFrom, $dateTo); ?>" class="pagination__link pagination__link--prev">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </a>
        <?php endif; ?>

        <div class="pagination__pages">
          <span class="pagination__info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        </div>

        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo buildQueryString($actionFilter, $entityFilter, $dateFrom, $dateTo); ?>" class="pagination__link pagination__link--next">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>
        <p>No activity logs found</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';

// Helper function to build query string
function buildQueryString($action, $entity, $dateFrom, $dateTo) {
    $parts = [];
    if (!empty($action)) $parts[] = 'action=' . urlencode($action);
    if (!empty($entity)) $parts[] = 'entity=' . urlencode($entity);
    if (!empty($dateFrom)) $parts[] = 'date_from=' . urlencode($dateFrom);
    if (!empty($dateTo)) $parts[] = 'date_to=' . urlencode($dateTo);
    return !empty($parts) ? '&' . implode('&', $parts) : '';
}
?>
