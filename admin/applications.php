<?php
/**
 * Tulima Solar – Job Applications
 * View and manage job applications.
 */

require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/job-portal-functions.php';

$pdo = getDb();
$currentNav = 'applications';
$pageTitle = 'Job Applications';

$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';

$applications = [];
$job = null;
$jobs = [];
$stats = ['total' => 0, 'pending' => 0, 'reviewed' => 0, 'accepted' => 0, 'rejected' => 0];

try {
    $stmt = $pdo->query('SELECT id, title, slug FROM jobs ORDER BY title ASC');
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('applications: jobs list ' . $e->getMessage());
}

if ($jobId) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('applications: job ' . $e->getMessage());
    }
}

$whereBase = $jobId ? 'WHERE job_id = ?' : '';
$paramsBase = $jobId ? [$jobId] : [];
$andOrWhere = $jobId ? ' AND' : ' WHERE';

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_applications $whereBase");
    $stmt->execute($paramsBase);
    $stats['total'] = (int)$stmt->fetchColumn();
    foreach (['pending', 'reviewed', 'accepted', 'rejected'] as $s) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_applications $whereBase $andOrWhere status = ?");
        $stmt->execute(array_merge($paramsBase, [$s]));
        $stats[$s] = (int)$stmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log('applications: stats ' . $e->getMessage());
}

$sql = 'SELECT ja.*, j.title AS job_title, j.slug AS job_slug
        FROM job_applications ja
        LEFT JOIN jobs j ON ja.job_id = j.id
        WHERE 1=1';
$params = [];
if ($jobId) { $sql .= ' AND ja.job_id = ?'; $params[] = $jobId; }
if ($filterStatus !== 'all') { $sql .= ' AND ja.status = ?'; $params[] = $filterStatus; }
if ($searchKeyword !== '') {
    $sql .= ' AND (ja.applicant_name LIKE ? OR ja.applicant_email LIKE ?)';
    $params[] = '%' . $searchKeyword . '%';
    $params[] = '%' . $searchKeyword . '%';
}
$sql .= ' ORDER BY ja.created_at DESC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Error loading applications.';
    $messageType = 'error';
}

$message = $message ?? '';
$messageType = $messageType ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'update_status') {
            $applicationId = (int)($_POST['application_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            $notes = trim($_POST['notes'] ?? '');
            $valid = ['pending', 'reviewed', 'accepted', 'rejected'];
            if ($applicationId && in_array($newStatus, $valid)) {
                $stmt = $pdo->prepare('UPDATE job_applications SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                $stmt->execute([$newStatus, $notes, $applicationId]);
                if ($stmt->rowCount()) {
                    $message = 'Status updated.';
                    $messageType = 'success';
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        if ($action === 'delete') {
            $applicationId = (int)($_POST['application_id'] ?? 0);
            if ($applicationId) {
                $stmt = $pdo->prepare('SELECT cv_path, cover_letter_path, resume_path FROM job_applications WHERE id = ?');
                $stmt->execute([$applicationId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $base = rtrim(dirname(__DIR__), '/\\') . '/';
                if ($row) {
                    foreach (['cv_path', 'cover_letter_path', 'resume_path'] as $col) {
                        $path = $row[$col] ?? null;
                        if (!empty($path)) {
                            $fullPath = $base . ltrim($path, '/\\');
                            if (file_exists($fullPath)) {
                                @unlink($fullPath);
                            }
                        }
                    }
                }
                $stmt = $pdo->prepare('DELETE FROM job_applications WHERE id = ?');
                $stmt->execute([$applicationId]);
                $message = 'Application removed.';
                $messageType = 'success';
                header('Location: ' . ($jobId ? "applications.php?job_id=$jobId" : 'applications.php'));
                exit;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$adminBase = rtrim(BASE_PATH, '/') . '/admin';

/** Build full URL for an application document path (resume/CV or cover letter file). */
function applicationDocUrl($path) {
    if (empty($path) || !is_string($path)) return '';
    return rtrim(BASE_PATH, '/') . '/' . ltrim($path, '/');
}

include __DIR__ . '/includes/header.php';
?>

<!-- Content Area -->
<div class="admin-content">
  <?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'success' ? 'success' : 'error'; ?> alert-dismissible">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
  </div>
  <?php endif; ?>

  <!-- Dashboard Hero -->
  <div class="dashboard-hero">
    <div class="dashboard-hero__content">
      <div class="dashboard-hero__text">
        <div class="dashboard-hero__greeting">Job Portal</div>
        <h1 class="dashboard-hero__title">
          <?php echo $job ? e($job['title']) : 'Job Applications'; ?>
        </h1>
        <p class="dashboard-hero__subtitle">
          <?php echo $job ? 'Applications for this position' : 'Review and manage all job applications'; ?>
          <?php if ($job): ?>
            <a href="<?php echo $adminBase; ?>/applications.php" style="color: rgba(255,255,255,0.9); text-decoration: underline; margin-left: 0.5rem;">View all</a>
          <?php endif; ?>
        </p>
      </div>
      <div class="dashboard-hero__actions">
        <a href="<?php echo $adminBase; ?>/jobs.php" class="dashboard-hero__btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
          </svg>
          Back to Jobs
        </a>
      </div>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="dashboard-stats">
    <a href="?<?php echo $jobId ? "job_id=$jobId&" : ''; ?>status=all" class="stat-card stat-card--purple <?php echo $filterStatus === 'all' ? 'stat-card--active' : ''; ?>" style="text-decoration: none; color: inherit; display: block;">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">All</div>
      </div>
      <div class="stat-card__value"><?php echo $stats['total']; ?></div>
      <div class="stat-card__label">Total Applications</div>
    </a>
    <a href="?<?php echo $jobId ? "job_id=$jobId&" : ''; ?>status=pending" class="stat-card stat-card--blue <?php echo $filterStatus === 'pending' ? 'stat-card--active' : ''; ?>" style="text-decoration: none; color: inherit; display: block;">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">New</div>
      </div>
      <div class="stat-card__value"><?php echo $stats['pending']; ?></div>
      <div class="stat-card__label">Pending</div>
    </a>
    <a href="?<?php echo $jobId ? "job_id=$jobId&" : ''; ?>status=reviewed" class="stat-card stat-card--orange <?php echo $filterStatus === 'reviewed' ? 'stat-card--active' : ''; ?>" style="text-decoration: none; color: inherit; display: block;">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">In progress</div>
      </div>
      <div class="stat-card__value"><?php echo $stats['reviewed']; ?></div>
      <div class="stat-card__label">Reviewed</div>
    </a>
    <a href="?<?php echo $jobId ? "job_id=$jobId&" : ''; ?>status=accepted" class="stat-card stat-card--green <?php echo $filterStatus === 'accepted' ? 'stat-card--active' : ''; ?>" style="text-decoration: none; color: inherit; display: block;">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">Hired</div>
      </div>
      <div class="stat-card__value"><?php echo $stats['accepted']; ?></div>
      <div class="stat-card__label">Accepted</div>
    </a>
    <a href="?<?php echo $jobId ? "job_id=$jobId&" : ''; ?>status=rejected" class="stat-card stat-card--red <?php echo $filterStatus === 'rejected' ? 'stat-card--active' : ''; ?>" style="text-decoration: none; color: inherit; display: block;">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--down">Rejected</div>
      </div>
      <div class="stat-card__value"><?php echo $stats['rejected']; ?></div>
      <div class="stat-card__label">Rejected</div>
    </a>
  </div>

  <!-- Applications Table Card -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
          All Applications
        </h3>
        <p class="admin-card__subtitle"><?php echo $stats['total']; ?> total<?php if ($job): ?> for this position<?php endif; ?> · <?php echo count($applications); ?> shown</p>
      </div>
    </div>
    <div class="admin-card__body">
      <!-- Filter toolbar -->
      <form method="get" class="applications-filter-bar">
        <?php if ($jobId): ?><input type="hidden" name="job_id" value="<?php echo $jobId; ?>"><?php endif; ?>
        <div class="applications-filter-bar__group">
          <label class="applications-filter-bar__label">Status</label>
          <select name="status" class="form-select applications-filter-bar__select">
            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All statuses</option>
            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="reviewed" <?php echo $filterStatus === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
            <option value="accepted" <?php echo $filterStatus === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
            <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
          </select>
        </div>
        <?php if (count($jobs) > 0 && !$jobId): ?>
        <div class="applications-filter-bar__group">
          <label class="applications-filter-bar__label">Job</label>
          <select name="job_id" class="form-select applications-filter-bar__select" onchange="this.form.submit()">
            <option value="">All jobs</option>
            <?php foreach ($jobs as $j): ?>
            <option value="<?php echo (int)$j['id']; ?>" <?php echo $jobId == $j['id'] ? 'selected' : ''; ?>><?php echo e($j['title']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="applications-filter-bar__group applications-filter-bar__group--search">
          <label class="applications-filter-bar__label">Search</label>
          <span class="applications-filter-bar__search-wrap">
            <svg class="applications-filter-bar__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" name="search" class="form-input applications-filter-bar__search" placeholder="Name or email…" value="<?php echo e($searchKeyword); ?>">
          </span>
        </div>
        <div class="applications-filter-bar__actions">
          <button type="submit" class="btn btn--primary btn--sm">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Apply filters
          </button>
          <a href="applications.php<?php echo $jobId ? "?job_id=$jobId" : ''; ?>" class="btn btn--outline btn--sm">Clear</a>
        </div>
      </form>

      <?php if (empty($applications)): ?>
      <div class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <h3 class="empty-state__title">No Applications Found</h3>
        <p class="empty-state__text">
          <?php if ($filterStatus !== 'all' || $searchKeyword !== ''): ?>
            No applications match your filters. Try changing status or search.
          <?php else: ?>
            Applications will appear here when candidates apply for jobs.
          <?php endif; ?>
        </p>
      </div>
      <?php else: ?>
      <div class="data-table">
        <table class="data-table__table">
          <thead>
            <tr>
              <th>Applicant</th>
              <?php if (!$jobId): ?><th>Position</th><?php endif; ?>
              <th>Contact</th>
              <th>Applied</th>
              <th>Status</th>
              <th>Documents</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $statusLabels = ['pending' => 'Pending', 'reviewed' => 'Reviewed', 'accepted' => 'Accepted', 'rejected' => 'Rejected'];
            $statusBadges = ['pending' => 'badge--warning', 'reviewed' => 'badge--info', 'accepted' => 'badge--success', 'rejected' => 'badge--danger'];
            foreach ($applications as $app):
              $resumeUrl = !empty($app['cv_path']) ? $app['cv_path'] : ($app['resume_path'] ?? null);
            ?>
            <tr data-id="<?php echo $app['id']; ?>">
              <td>
                <div class="applicant-cell">
                  <div class="applicant-cell__avatar"><?php echo strtoupper(mb_substr($app['applicant_name'], 0, 1)); ?></div>
                  <div class="applicant-cell__info">
                    <div class="applicant-cell__name"><?php echo e($app['applicant_name']); ?></div>
                    <?php if (!empty($app['applicant_phone'])): ?>
                    <div class="applicant-cell__phone"><?php echo e($app['applicant_phone']); ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <?php if (!$jobId): ?>
              <td>
                <?php if (!empty($app['job_title'])): ?>
                <a href="?job_id=<?php echo (int)$app['job_id']; ?>" class="data-table__link"><?php echo e($app['job_title']); ?></a>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <?php endif; ?>
              <td>
                <a href="mailto:<?php echo e($app['applicant_email']); ?>" class="data-table__link"><?php echo e($app['applicant_email']); ?></a>
              </td>
              <td>
                <div class="date-cell">
                  <div class="date-cell__date"><?php echo date('M j, Y', strtotime($app['created_at'])); ?></div>
                  <div class="date-cell__time"><?php echo date('g:i A', strtotime($app['created_at'])); ?></div>
                </div>
              </td>
              <td>
                <span class="badge <?php echo $statusBadges[$app['status']] ?? 'badge--muted'; ?>">
                  <?php echo $statusLabels[$app['status']] ?? ucfirst($app['status']); ?>
                </span>
              </td>
              <td>
                <div class="document-links">
                  <?php if ($resumeUrl): ?>
                  <a href="<?php echo e(applicationDocUrl($resumeUrl)); ?>" target="_blank" rel="noopener" class="document-link" title="View CV">CV</a>
                  <?php endif; ?>
                  <?php if (!empty($app['cover_letter_path'])): ?>
                  <a href="<?php echo e(applicationDocUrl($app['cover_letter_path'])); ?>" target="_blank" rel="noopener" class="document-link" title="Cover letter">Letter</a>
                  <?php endif; ?>
                  <?php if (!$resumeUrl && empty($app['cover_letter_path'])): ?>
                  <span class="text-muted">—</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <div class="data-table__actions">
                  <button type="button" class="btn btn--primary btn--sm btn-icon" title="View" onclick="viewApplicationById(<?php echo $app['id']; ?>)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button type="button" class="btn btn--secondary btn--sm btn-icon" title="Update status" onclick="updateStatusById(<?php echo $app['id']; ?>)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Delete this application?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                    <button type="submit" class="btn btn--ghost btn--sm btn-icon btn--danger" title="Delete">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <script>window.__appData = window.__appData || {}; window.__appData[<?php echo $app['id']; ?>] = <?php echo json_encode($app); ?>;</script>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- View Application Modal -->
<div class="admin-modal" id="viewApplicationModal">
  <div class="admin-modal-content admin-modal-content--lg">
    <h3 class="admin-modal-title">Application Details</h3>
    <div id="viewApplicationContent"></div>
    <div class="admin-modal-actions">
      <button type="button" class="admin-btn admin-btn-secondary" onclick="closeViewModal()">Close</button>
    </div>
  </div>
</div>

<!-- Update Status Modal -->
<div class="admin-modal" id="updateStatusModal">
  <div class="admin-modal-content">
    <form method="post" id="updateStatusForm">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="application_id" id="statusApplicationId">
      <h3 class="admin-modal-title">Update Application Status</h3>
      <div class="admin-form-group">
        <label class="admin-form-label">Status <span class="admin-required">*</span></label>
        <select name="new_status" id="statusSelect" class="admin-form-select" required>
          <option value="pending">Pending</option>
          <option value="reviewed">Reviewed</option>
          <option value="accepted">Accepted</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Notes</label>
        <textarea name="notes" id="statusNotes" class="admin-form-textarea" rows="4" placeholder="Internal notes…"></textarea>
      </div>
      <div class="admin-modal-actions">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeStatusModal()">Cancel</button>
        <button type="submit" class="admin-btn admin-btn-primary">Update Status</button>
      </div>
    </form>
  </div>
</div>

<style>
/* Stat card active + red variant (applications page) */
.stat-card--active { box-shadow: 0 0 0 2px var(--primary); }
.stat-card--active::before { opacity: 1; }
.stat-card--red::before { background: linear-gradient(90deg, var(--danger), #dc2626); }
.stat-card--red .stat-card__icon { background: var(--danger-light); color: var(--danger); }

/* Dashboard stats 5 columns on large screens */
.dashboard-stats { grid-template-columns: repeat(5, 1fr); }
@media (max-width: 1200px) { .dashboard-stats { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .dashboard-stats { grid-template-columns: 1fr 1fr; } }

/* Applications filter bar – horizontal, organized */
.applications-filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: var(--space-4);
  padding: var(--space-4) var(--space-5);
  margin: 0 calc(-1 * var(--space-6)) var(--space-5);
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-light);
}
.applications-filter-bar__group {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}
.applications-filter-bar__label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-muted);
}
.applications-filter-bar__select {
  min-width: 140px;
  padding: var(--space-2) var(--space-3);
  font-size: 0.875rem;
  margin: 0;
}
.applications-filter-bar__group--search { flex: 1; min-width: 200px; }
.applications-filter-bar__search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.applications-filter-bar__search-icon {
  position: absolute;
  left: var(--space-3);
  width: 1rem;
  height: 1rem;
  color: var(--text-muted);
  pointer-events: none;
}
.applications-filter-bar__search {
  width: 100%;
  padding-left: 2.25rem;
  margin: 0;
}
.applications-filter-bar__actions {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}
.applications-filter-bar__actions .btn { white-space: nowrap; }

/* Table container – ensure visible */
.admin-card__body .data-table {
  margin: 0 -var(--space-6);
  overflow-x: auto;
  border-top: 1px solid var(--border-light);
}
.admin-card__body .data-table__table {
  width: 100%;
  min-width: 800px;
  border-collapse: collapse;
}
.admin-card__body .data-table__table thead tr {
  background: var(--gray-50);
}
.dark .admin-card__body .data-table__table thead tr {
  background: var(--bg-secondary);
}
.admin-card__body .data-table__table th {
  text-align: left;
  padding: var(--space-3) var(--space-4);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  border-bottom: 2px solid var(--border-light);
}
.admin-card__body .data-table__table td {
  padding: var(--space-4);
  border-bottom: 1px solid var(--border-light);
  vertical-align: middle;
  color: var(--text-secondary);
}
.admin-card__body .data-table__table tbody tr:hover {
  background: var(--gray-50);
}
.dark .admin-card__body .data-table__table tbody tr:hover {
  background: var(--bg-secondary);
}
.admin-card__body .data-table__table .data-table__link {
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
}
.admin-card__body .data-table__table .data-table__link:hover {
  text-decoration: underline;
}
.admin-card__body .data-table__actions {
  display: flex;
  gap: var(--space-2);
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .applications-filter-bar {
    flex-direction: column;
    align-items: stretch;
    margin-left: calc(-1 * var(--space-4));
    margin-right: calc(-1 * var(--space-4));
  }
  .applications-filter-bar__group--search { min-width: 0; }
  .applications-filter-bar__actions { justify-content: flex-start; }
}

/* Applicant cell */
.applicant-cell { display: flex; align-items: center; gap: var(--space-3); }
.applicant-cell__avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: white; display: flex; align-items: center; justify-content: center;
  font-weight: 600; font-size: 0.875rem; flex-shrink: 0;
}
.applicant-cell__name { font-weight: 500; color: var(--text-primary); }
.applicant-cell__phone { font-size: 0.75rem; color: var(--text-muted); }

/* Date cell */
.date-cell { display: flex; flex-direction: column; }
.date-cell__date { font-weight: 500; color: var(--text-primary); }
.date-cell__time { font-size: 0.75rem; color: var(--text-muted); }

/* Document links */
.document-links { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.document-link {
  display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px;
  background: var(--bg-secondary); border-radius: var(--radius-sm);
  font-size: 0.75rem; color: var(--text-secondary); text-decoration: none;
  transition: all var(--transition-fast);
}
.document-link:hover { background: var(--primary); color: white; }
.text-muted { color: var(--text-muted); }

/* Empty state sub-elements (contact_submissions pattern) */
.empty-state__icon { margin-bottom: var(--space-4); }
.empty-state__title { font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin: 0 0 var(--space-2); }
.empty-state__text { font-size: 0.9375rem; color: var(--text-muted); margin: 0; }

/* Admin modal (applications page) */
.admin-modal {
  display: none; position: fixed; inset: 0; z-index: 1000;
  align-items: center; justify-content: center; padding: var(--space-6);
  background: rgba(0, 0, 0, 0.5);
}
.admin-modal.active { display: flex; }
.admin-modal-content {
  background: var(--bg-card); border-radius: var(--radius-lg);
  padding: var(--space-6); max-width: 500px; width: 90%;
  box-shadow: var(--shadow-xl); border: 1px solid var(--border-light);
}
.admin-modal-content--lg { max-width: 700px; }
.admin-modal-title { font-size: 1.125rem; font-weight: 600; margin: 0 0 var(--space-4); color: var(--text-primary); }
.admin-modal-actions { display: flex; gap: var(--space-3); justify-content: flex-end; margin-top: var(--space-5); }
.admin-form-group { margin-bottom: var(--space-4); }
.admin-form-label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary); font-size: 0.875rem; }
.admin-form-select, .admin-form-textarea {
  width: 100%; padding: 0.75rem; border: 1px solid var(--border-light);
  border-radius: var(--radius-md); font-size: 0.875rem;
  background: var(--bg-primary); color: var(--text-primary);
}
.admin-form-textarea { min-height: 100px; resize: vertical; }
.admin-required { color: var(--danger); }
.admin-btn { padding: 0.5rem 1rem; border-radius: var(--radius-md); font-weight: 600; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; }
.admin-btn-primary { background: var(--primary); color: white; border-color: var(--primary); }
.admin-btn-primary:hover { opacity: 0.9; }
.admin-btn-secondary { background: var(--bg-secondary); color: var(--text-primary); border-color: var(--border-default); }
.admin-btn-secondary:hover { background: var(--border-light); }

/* Application detail in view modal */
.application-detail { padding: 0; }
.application-detail__header { display: flex; align-items: center; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-light); margin-bottom: 1.5rem; }
.application-detail__avatar {
  width: 64px; height: 64px; border-radius: 50%;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  color: white; display: flex; align-items: center; justify-content: center;
  font-weight: 600; font-size: 1.5rem; flex-shrink: 0;
}
.application-detail__info { flex: 1; }
.application-detail__info h2 { margin: 0; font-size: 1.25rem; color: var(--text-primary); }
.application-detail__info p { margin: 0.25rem 0 0; color: var(--text-secondary); font-size: 0.875rem; }
.application-detail__section { margin-bottom: 1.5rem; }
.application-detail__section h4 { margin: 0 0 0.5rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
.application-detail__section p, .application-detail__text { margin: 0; color: var(--text-primary); font-size: 0.9375rem; line-height: 1.5; }
.application-detail__text { background: var(--bg-secondary); padding: 1rem; border-radius: var(--radius-md); white-space: pre-wrap; }
.application-detail__documents { display: flex; gap: 0.75rem; flex-wrap: wrap; }
</style>

<script>
var BASE_PATH_APP = <?php echo json_encode(BASE_PATH); ?>;

function esc(s) {
  if (s == null) return '';
  var d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function docUrl(path) {
  if (!path) return '';
  var base = BASE_PATH_APP.replace(/\/$/, '');
  var p = String(path).replace(/^\//, '');
  return base + '/' + p;
}

function viewApplicationById(id) {
  var app = window.__appData && window.__appData[id];
  if (!app) return;
  var statusLabels = { pending: 'Pending', reviewed: 'Reviewed', accepted: 'Accepted', rejected: 'Rejected' };
  var resumeUrl = app.cv_path || app.resume_path || '';
  var html = '<div class="application-detail">';
  html += '<div class="application-detail__header">';
  html += '<div class="application-detail__avatar">' + esc(app.applicant_name).charAt(0).toUpperCase() + '</div>';
  html += '<div class="application-detail__info">';
  html += '<h2>' + esc(app.applicant_name) + '</h2>';
  html += '<p><a href="mailto:' + esc(app.applicant_email) + '">' + esc(app.applicant_email) + '</a></p>';
  if (app.applicant_phone) html += '<p>' + esc(app.applicant_phone) + '</p>';
  html += '</div>';
  html += '<span class="badge badge--' + (app.status === 'accepted' ? 'success' : app.status === 'rejected' ? 'danger' : app.status === 'reviewed' ? 'info' : 'warning') + '">' + (statusLabels[app.status] || app.status) + '</span>';
  html += '</div>';
  if (app.job_title) { html += '<div class="application-detail__section"><h4>Position</h4><p>' + esc(app.job_title) + '</p></div>'; }
  html += '<div class="application-detail__section"><h4>Applied</h4><p>' + new Date(app.created_at).toLocaleString() + '</p></div>';
  if (app.cover_letter) { html += '<div class="application-detail__section"><h4>Cover letter</h4><div class="application-detail__text">' + esc(app.cover_letter).replace(/\n/g, '<br>') + '</div></div>'; }
  html += '<div class="application-detail__section"><h4>Documents</h4><div class="application-detail__documents">';
  if (resumeUrl) html += '<a href="' + esc(docUrl(resumeUrl)) + '" target="_blank" rel="noopener" class="btn btn--secondary btn--sm">CV / Resume</a>';
  if (app.cover_letter_path) html += '<a href="' + esc(docUrl(app.cover_letter_path)) + '" target="_blank" rel="noopener" class="btn btn--secondary btn--sm">Cover letter (file)</a>';
  html += '</div></div>';
  var notes = app.admin_notes || app.notes || '';
  if (notes) { html += '<div class="application-detail__section"><h4>Admin notes</h4><div class="application-detail__text">' + esc(notes).replace(/\n/g, '<br>') + '</div></div>'; }
  html += '</div>';
  document.getElementById('viewApplicationContent').innerHTML = html;
  document.getElementById('viewApplicationModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeViewModal() {
  document.getElementById('viewApplicationModal').classList.remove('active');
  document.body.style.overflow = '';
}

function updateStatusById(id) {
  var app = window.__appData && window.__appData[id];
  if (!app) return;
  document.getElementById('statusApplicationId').value = id;
  document.getElementById('statusSelect').value = app.status || 'pending';
  document.getElementById('statusNotes').value = app.admin_notes || app.notes || '';
  document.getElementById('updateStatusModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeStatusModal() {
  document.getElementById('updateStatusModal').classList.remove('active');
  document.body.style.overflow = '';
}

document.querySelectorAll('.admin-modal').forEach(function(modal) {
  modal.addEventListener('click', function(e) {
    if (e.target === modal) { modal.classList.remove('active'); document.body.style.overflow = ''; }
  });
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') { closeViewModal(); closeStatusModal(); }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
