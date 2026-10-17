<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'submissions';
$pageTitle = 'Contact Submissions';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'mark_read' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare('UPDATE contact_submissions SET is_read = 1 WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            $message = 'Marked as read.';
            $messageType = 'success';
        } elseif ($action === 'mark_unread' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare('UPDATE contact_submissions SET is_read = 0 WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            $message = 'Marked as unread.';
            $messageType = 'success';
        } elseif ($action === 'delete' && !empty($_POST['id'])) {
            $stmt = $pdo->prepare('DELETE FROM contact_submissions WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            logAdminActivity('Deleted contact submission ID: ' . (int)$_POST['id']);
            $message = 'Submission deleted.';
            $messageType = 'success';
        } elseif ($action === 'mark_all_read') {
            $stmt = $pdo->prepare('UPDATE contact_submissions SET is_read = 1 WHERE is_read = 0');
            $stmt->execute();
            $affectedRows = $stmt->rowCount();
            logAdminActivity('Marked ' . $affectedRows . ' submissions as read');
            $message = $affectedRows . ' submission(s) marked as read.';
            $messageType = 'success';
        } elseif (!empty($_POST['bulk_action']) && !empty($_POST['ids'])) {
            $bulkAction = $_POST['bulk_action'];
            $ids = array_map('intval', explode(',', $_POST['ids']));
            
            if ($bulkAction === 'delete') {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM contact_submissions WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                logAdminActivity('Bulk deleted ' . count($ids) . ' contact submissions');
                $message = count($ids) . ' submission(s) deleted.';
                $messageType = 'success';
            } elseif ($bulkAction === 'mark_read') {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("UPDATE contact_submissions SET is_read = 1 WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $message = count($ids) . ' submission(s) marked as read.';
                $messageType = 'success';
            } elseif ($bulkAction === 'mark_unread') {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("UPDATE contact_submissions SET is_read = 0 WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $message = count($ids) . ' submission(s) marked as unread.';
                $messageType = 'success';
            }
            
            // Redirect to avoid form resubmission
            echo "<script>window.location.href='contact_submissions.php';</script>";
            exit;
        }
    } catch (PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all submissions
$submissions = getContactSubmissions($pdo);

// Calculate statistics
$stats = [
    'total' => count($submissions),
    'unread' => count(array_filter($submissions, fn($s) => $s['is_read'] == 0)),
    'read' => count(array_filter($submissions, fn($s) => $s['is_read'] == 1)),
];

// Submissions this week
$weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
$stmt = $pdo->prepare('SELECT COUNT(*) FROM contact_submissions WHERE submitted_at >= ?');
$stmt->execute([$weekAgo]);
$stats['this_week'] = (int)$stmt->fetchColumn();

// Submissions this month
$monthAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
$stmt = $pdo->prepare('SELECT COUNT(*) FROM contact_submissions WHERE submitted_at >= ?');
$stmt->execute([$monthAgo]);
$stats['this_month'] = (int)$stmt->fetchColumn();

// Average response time (mock calculation based on read status)
$stats['response_rate'] = $stats['total'] > 0 ? round(($stats['read'] / $stats['total']) * 100) : 0;

$adminBase = rtrim(BASE_PATH, '/') . '/admin';

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
        <div class="dashboard-hero__greeting">Contact Submissions</div>
        <h1 class="dashboard-hero__title">Manage Messages</h1>
        <p class="dashboard-hero__subtitle">View and respond to messages sent through your website's contact form</p>
      </div>
      <div class="dashboard-hero__actions">
        <a href="<?php echo $adminBase; ?>/api/export.php?type=contact_submissions&format=csv" class="dashboard-hero__btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Export CSV
        </a>
      </div>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="dashboard-stats">
    <div class="stat-card stat-card--purple">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          Total
        </div>
      </div>
      <div class="stat-card__value"><?php echo $stats['total']; ?></div>
      <div class="stat-card__label">All Submissions</div>
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
          New
        </div>
      </div>
      <div class="stat-card__value"><?php echo $stats['unread']; ?></div>
      <div class="stat-card__label">Unread Messages</div>
    </div>

    <div class="stat-card stat-card--blue">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          This Week
        </div>
      </div>
      <div class="stat-card__value"><?php echo $stats['this_week']; ?></div>
      <div class="stat-card__label">Last 7 Days</div>
    </div>

    <div class="stat-card stat-card--orange">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          This Month
        </div>
      </div>
      <div class="stat-card__value"><?php echo $stats['this_month']; ?></div>
      <div class="stat-card__label">Last 30 Days</div>
    </div>
  </div>

  <!-- Response Rate Card -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
          </svg>
          Response Overview
        </h3>
        <p class="admin-card__subtitle">Track your response rate and submission status</p>
      </div>
    </div>
    <div class="admin-card__body">
      <div class="response-overview-grid">
        <div class="response-metric-card response-metric-card--success">
          <div class="response-metric-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <div class="response-metric-card__content">
            <div class="response-metric-card__value"><?php echo $stats['read']; ?></div>
            <div class="response-metric-card__label">Read</div>
          </div>
        </div>
        
        <div class="response-metric-card response-metric-card--warning">
          <div class="response-metric-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </div>
          <div class="response-metric-card__content">
            <div class="response-metric-card__value"><?php echo $stats['unread']; ?></div>
            <div class="response-metric-card__label">Unread</div>
          </div>
        </div>
        
        <div class="response-metric-card response-metric-card--info">
          <div class="response-metric-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div class="response-metric-card__content">
            <div class="response-metric-card__value"><?php echo $stats['response_rate']; ?>%</div>
            <div class="response-metric-card__label">Response Rate</div>
          </div>
        </div>
        
        <div class="response-metric-card response-metric-card--primary">
          <div class="response-metric-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <div class="response-metric-card__content">
            <div class="response-metric-card__value"><?php echo $stats['total']; ?></div>
            <div class="response-metric-card__label">Total</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Submissions Table -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
          All Submissions
        </h3>
        <p class="admin-card__subtitle"><?php echo $stats['total']; ?> total messages</p>
      </div>
      <div class="admin-card__header-actions">
        <button type="button" class="btn btn--secondary btn--sm" onclick="markAllAsRead()" <?php echo $stats['unread'] == 0 ? 'disabled' : ''; ?>>
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Mark All Read
        </button>
        <a href="<?php echo $adminBase; ?>/api/export.php?type=contact_submissions&format=csv" class="btn btn--outline btn--sm">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Export CSV
        </a>
      </div>
    </div>
    <div class="admin-card__body">
      <?php if (empty($submissions)): ?>
        <div class="empty-state">
          <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
          <h3 class="empty-state__title">No Submissions Yet</h3>
          <p class="empty-state__text">Contact submissions will appear here when visitors send messages through your website.</p>
        </div>
      <?php else: ?>
        <form method="post" id="bulkActionsForm">
          <input type="hidden" name="bulk_action" id="bulkActionInput">
          <div class="bulk-actions-toolbar" id="bulkActionsToolbar" style="display: none;">
            <span class="bulk-actions-info"><span id="selectedCount">0</span> selected</span>
            <button type="button" class="btn btn--secondary btn--sm" onclick="submitBulkAction('mark_read')">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              Mark as Read
            </button>
            <button type="button" class="btn btn--secondary btn--sm" onclick="submitBulkAction('mark_unread')">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              Mark as Unread
            </button>
            <button type="button" class="btn btn--danger btn--sm" onclick="confirmBulkDelete()">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
              </svg>
              Delete Selected
            </button>
            <button type="button" class="btn btn--secondary btn--sm" onclick="clearSelection()">Clear Selection</button>
          </div>
          
          <div class="data-table">
            <table class="data-table__table">
              <thead>
                <tr>
                  <th style="width: 40px;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                  <th>Date</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Subject</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($submissions as $row): ?>
                <tr class="<?php echo $row['is_read'] ? '' : 'submission-unread'; ?>" data-id="<?php echo (int)$row['id']; ?>">
                  <td><input type="checkbox" class="submission-checkbox" value="<?php echo (int)$row['id']; ?>" onchange="updateBulkActions()"></td>
                  <td><?php echo date('M j, Y H:i', strtotime($row['submitted_at'])); ?></td>
                  <td><?php echo e($row['name']); ?></td>
                  <td><a href="mailto:<?php echo e($row['email']); ?>" class="data-table__link"><?php echo e($row['email']); ?></a></td>
                  <td><?php echo e($row['subject'] ?: '—'); ?></td>
                  <td>
                    <span class="badge badge--<?php echo $row['is_read'] ? 'muted' : 'success'; ?>">
                      <?php echo $row['is_read'] ? 'Read' : 'New'; ?>
                    </span>
                  </td>
                  <td>
                    <div class="data-table__actions">
                      <button type="button" class="btn btn--primary btn--sm btn-icon" onclick="toggleSubmissionDetail(<?php echo (int)$row['id']; ?>)" title="View">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                          <circle cx="12" cy="12" r="3"/>
                        </svg>
                      </button>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="<?php echo $row['is_read'] ? 'mark_unread' : 'mark_read'; ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                        <button type="submit" class="btn btn--secondary btn--sm btn-icon" title="<?php echo $row['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php if ($row['is_read']): ?>
                              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                              <circle cx="12" cy="12" r="3"/>
                            <?php else: ?>
                              <polyline points="20 6 9 17 4 12"/>
                            <?php endif; ?>
                          </svg>
                        </button>
                      </form>
                      <form method="post" style="display:inline;" onsubmit="return confirm('Delete this submission?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                        <button type="submit" class="btn btn--ghost btn--sm btn-icon btn--danger" title="Delete">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                          </svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                <tr id="submission-detail-<?php echo (int)$row['id']; ?>" class="submission-detail-row" style="display: none;">
                  <td colspan="7" class="submission-detail-cell">
                    <div class="submission-detail">
                      <?php if (!empty($row['phone'])): ?>
                      <div class="submission-detail__field">
                        <strong>Phone:</strong> 
                        <a href="tel:<?php echo e($row['phone']); ?>" class="submission-detail__link"><?php echo e($row['phone']); ?></a>
                      </div>
                      <?php endif; ?>
                      <div class="submission-detail__field">
                        <strong>Message:</strong>
                      </div>
                      <div class="submission-message"><?php echo nl2br(e($row['message'])); ?></div>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.bulk-actions-toolbar {
  display: flex;
  gap: var(--space-2);
  align-items: center;
  padding: var(--space-4);
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-light);
  margin: 0 calc(-1 * var(--space-6)) var(--space-4);
  box-shadow: var(--shadow-sm);
}

.bulk-actions-info {
  font-size: 0.875rem;
  color: var(--text-primary);
  font-weight: 500;
  margin-right: var(--space-2);
}

.submission-unread {
  background-color: var(--success-light);
}

/* Data Table Styles */
.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table__table {
  width: 100%;
  border-collapse: collapse;
}

.data-table__table thead {
  background-color: var(--bg-secondary);
}

.data-table__table th {
  padding: var(--space-3) var(--space-4);
  text-align: left;
  font-weight: 600;
  color: var(--text-primary);
  border-bottom: 2px solid var(--border-light);
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
}

.data-table__table td {
  padding: var(--space-3) var(--space-4);
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-light);
}

.data-table__table tbody tr:hover {
  background-color: var(--bg-secondary);
}

.data-table__table tbody tr:last-child td {
  border-bottom: none;
}

.data-table__link {
  color: var(--info);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.data-table__link:hover {
  color: var(--info-dark);
  text-decoration: underline;
}

.data-table__actions {
  display: flex;
  gap: var(--space-2);
}

/* Dark mode table styles */
.dark .data-table__table thead {
  background-color: var(--bg-card);
}

.dark .data-table__table th {
  color: var(--text-primary);
  border-bottom: 2px solid var(--border-dark);
}

.dark .data-table__table td {
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-dark);
}

.dark .data-table__table tbody tr:hover {
  background-color: var(--bg-card);
}

.dark .submission-detail-row {
  background-color: var(--bg-card);
}

.dark .submission-detail-cell {
  background-color: var(--bg-secondary);
}

.dark .submission-message {
  background: var(--bg-card);
  border: 1px solid var(--border-dark);
}

.response-overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--space-6);
}

.response-metric-card {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  padding: var(--space-5);
  border-radius: var(--radius-lg);
  transition: all var(--transition-base);
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-sm);
}

.response-metric-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.response-metric-card--success {
  border-left: 4px solid var(--success);
}

.response-metric-card--warning {
  border-left: 4px solid var(--warning);
}

.response-metric-card--info {
  border-left: 4px solid var(--info);
}

.response-metric-card--primary {
  border-left: 4px solid var(--primary);
}

.response-metric-card__icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-secondary);
  color: var(--text-primary);
  flex-shrink: 0;
}

.response-metric-card__content {
  flex: 1;
  min-width: 0;
}

.response-metric-card__value {
  font-size: 2rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: var(--space-1);
  color: var(--text-primary);
}

.response-metric-card--success .response-metric-card__value {
  color: var(--success);
}

.response-metric-card--warning .response-metric-card__value {
  color: var(--warning);
}

.response-metric-card--info .response-metric-card__value {
  color: var(--info);
}

.response-metric-card--primary .response-metric-card__value {
  color: var(--primary);
}

.response-metric-card__label {
  font-size: 0.875rem;
  color: var(--text-secondary);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.dashboard-hero__actions {
  display: flex;
  gap: 1rem;
}

.dashboard-hero__btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  text-decoration: none;
}

.dashboard-hero__btn:hover {
  background-color: #2563eb;
}

.dashboard-hero__btn svg {
  width: 1.25rem;
  height: 1.25rem;
}

.admin-card__header-left {
  flex: 1;
}

.admin-card__header-actions {
  display: flex;
  gap: var(--space-2);
  align-items: center;
}

.admin-card__subtitle {
  font-size: 0.875rem;
  color: var(--text-muted);
  margin: 0;
  margin-top: var(--space-1);
}
</style>

<script>
function toggleSubmissionDetail(id) {
  const row = document.getElementById('submission-detail-' + id);
  if (!row) return;
  row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

// Bulk Actions Functions
function toggleSelectAll() {
  const selectAll = document.getElementById('selectAll');
  const checkboxes = document.querySelectorAll('.submission-checkbox');
  checkboxes.forEach(checkbox => {
    checkbox.checked = selectAll.checked;
  });
  updateBulkActions();
}

function updateBulkActions() {
  const checkboxes = document.querySelectorAll('.submission-checkbox:checked');
  const toolbar = document.getElementById('bulkActionsToolbar');
  const selectedCount = document.getElementById('selectedCount');
  const selectAll = document.getElementById('selectAll');
  
  selectedCount.textContent = checkboxes.length;
  
  if (checkboxes.length > 0) {
    toolbar.style.display = 'flex';
  } else {
    toolbar.style.display = 'none';
  }
  
  const allCheckboxes = document.querySelectorAll('.submission-checkbox');
  selectAll.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
}

function clearSelection() {
  const checkboxes = document.querySelectorAll('.submission-checkbox');
  checkboxes.forEach(checkbox => {
    checkbox.checked = false;
  });
  document.getElementById('selectAll').checked = false;
  updateBulkActions();
}

function submitBulkAction(action) {
  const checkboxes = document.querySelectorAll('.submission-checkbox:checked');
  if (checkboxes.length === 0) return;
  
  const ids = Array.from(checkboxes).map(cb => cb.value).join(',');
  
  const form = document.getElementById('bulkActionsForm');
  const input = document.getElementById('bulkActionInput');
  input.value = action;
  
  let idsInput = document.getElementById('bulkIdsInput');
  if (!idsInput) {
    idsInput = document.createElement('input');
    idsInput.type = 'hidden';
    idsInput.name = 'ids';
    idsInput.id = 'bulkIdsInput';
    form.appendChild(idsInput);
  }
  idsInput.value = ids;
  
  form.submit();
}

function confirmBulkDelete() {
  const checkboxes = document.querySelectorAll('.submission-checkbox:checked');
  if (checkboxes.length === 0) return;
  
  if (confirm('Are you sure you want to delete ' + checkboxes.length + ' submission(s)? This action cannot be undone.')) {
    submitBulkAction('delete');
  }
}

function markAllAsRead() {
  if (confirm('Are you sure you want to mark all unread submissions as read?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'mark_all_read';
    form.appendChild(actionInput);
    
    document.body.appendChild(form);
    form.submit();
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
