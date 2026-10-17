<?php
/**
 * Tulima Solar - Blog Comments Management
 * Manage and moderate blog comments with threading support
 * 
 * RBAC: Requires admin or editor role
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/auth_helpers.php';
requireCommentAccess(); // Requires blog_manage_comments permission

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../includes/blog_helpers.php';

$pdo = getDb();
$currentNav = 'blog-comments';
$pageTitle = 'Blog Comments';

$message = '';
$messageType = 'success';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to update post comment count
function updatePostCommentCount($pdo, $postId) {
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM blog_comments 
        WHERE post_id = ? AND status = "approved"
    ');
    $stmt->execute([$postId]);
    $count = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare('UPDATE blog_posts SET comment_count = ? WHERE id = ?');
    $stmt->execute([$count, $postId]);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid security token. Please try again.';
        $messageType = 'error';
    } else {
        // Approve comment
        if ($action === 'approve' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            try {
                $stmt = $pdo->prepare('
                    SELECT c.*, p.title as post_title 
                    FROM blog_comments c 
                    JOIN blog_posts p ON c.post_id = p.id 
                    WHERE c.id = ?
                ');
                $stmt->execute([$id]);
                $comment = $stmt->fetch();
                
                if ($comment) {
                    $stmt = $pdo->prepare('UPDATE blog_comments SET status = "approved" WHERE id = ?');
                    $stmt->execute([$id]);
                    updatePostCommentCount($pdo, $comment['post_id']);
                    
                    logActivity('approved', 'blog_comment', $id, substr($comment['content'], 0, 50) . '...');
                    $message = 'Comment approved.';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error approving comment: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Mark as spam
        if ($action === 'spam' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            try {
                $stmt = $pdo->prepare('
                    SELECT c.*, p.title as post_title 
                    FROM blog_comments c 
                    JOIN blog_posts p ON c.post_id = p.id 
                    WHERE c.id = ?
                ');
                $stmt->execute([$id]);
                $comment = $stmt->fetch();
                
                if ($comment) {
                    $stmt = $pdo->prepare('UPDATE blog_comments SET status = "spam" WHERE id = ?');
                    $stmt->execute([$id]);
                    updatePostCommentCount($pdo, $comment['post_id']);
                    
                    logActivity('marked_spam', 'blog_comment', $id, substr($comment['content'], 0, 50) . '...');
                    $message = 'Comment marked as spam.';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error marking comment as spam: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Move to trash
        if ($action === 'trash' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            try {
                $stmt = $pdo->prepare('
                    SELECT c.*, p.title as post_title 
                    FROM blog_comments c 
                    JOIN blog_posts p ON c.post_id = p.id 
                    WHERE c.id = ?
                ');
                $stmt->execute([$id]);
                $comment = $stmt->fetch();
                
                if ($comment) {
                    $stmt = $pdo->prepare('UPDATE blog_comments SET status = "trash" WHERE id = ?');
                    $stmt->execute([$id]);
                    updatePostCommentCount($pdo, $comment['post_id']);
                    
                    logActivity('trashed', 'blog_comment', $id, substr($comment['content'], 0, 50) . '...');
                    $message = 'Comment moved to trash.';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error moving comment to trash: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Delete permanently
        if ($action === 'delete' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            try {
                $stmt = $pdo->prepare('
                    SELECT c.*, p.title as post_title 
                    FROM blog_comments c 
                    JOIN blog_posts p ON c.post_id = p.id 
                    WHERE c.id = ?
                ');
                $stmt->execute([$id]);
                $comment = $stmt->fetch();
                
                if ($comment) {
                    $stmt = $pdo->prepare('DELETE FROM blog_comments WHERE id = ?');
                    $stmt->execute([$id]);
                    updatePostCommentCount($pdo, $comment['post_id']);
                    
                    logActivity('deleted', 'blog_comment', $id, substr($comment['content'], 0, 50) . '...');
                    $message = 'Comment permanently deleted.';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error deleting comment: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        
        // Edit comment
        if ($action === 'edit' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $content = trim($_POST['content'] ?? '');
            $status = $_POST['status'] ?? 'pending';
            $authorName = trim($_POST['author_name'] ?? '');
            $authorEmail = trim($_POST['author_email'] ?? '');
            
            $allowedStatuses = ['pending', 'approved', 'spam', 'trash'];
            if (!in_array($status, $allowedStatuses)) {
                $message = 'Invalid status.';
                $messageType = 'error';
            } elseif (empty($content)) {
                $message = 'Comment content cannot be empty.';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare('SELECT post_id, status FROM blog_comments WHERE id = ?');
                    $stmt->execute([$id]);
                    $oldComment = $stmt->fetch();
                    
                    if ($oldComment) {
                        $stmt = $pdo->prepare('
                            UPDATE blog_comments 
                            SET content = ?, status = ?, author_name = ?, author_email = ?, updated_at = NOW() 
                            WHERE id = ?
                        ');
                        $stmt->execute([$content, $status, $authorName, $authorEmail, $id]);
                        
                        // Update post comment count if status changed
                        if ($oldComment['status'] !== $status) {
                            updatePostCommentCount($pdo, $oldComment['post_id']);
                        }
                        
                        logActivity('edited', 'blog_comment', $id, substr($content, 0, 50) . '...');
                        $message = 'Comment updated.';
                        $messageType = 'success';
                    }
                } catch (PDOException $e) {
                    $message = 'Error updating comment: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Reply to comment
        if ($action === 'reply' && !empty($_POST['parent_id']) && !empty($_POST['post_id'])) {
            $parentId = (int)$_POST['parent_id'];
            $postId = (int)$_POST['post_id'];
            $content = trim($_POST['reply_content'] ?? '');
            
            if (empty($content)) {
                $message = 'Reply content cannot be empty.';
                $messageType = 'error';
            } else {
                try {
                    // Admin reply - use current admin info
                    $adminId = $_SESSION['admin_id'] ?? null;
                    
                    $stmt = $pdo->prepare('
                        INSERT INTO blog_comments (post_id, parent_id, user_id, content, status, created_at)
                        VALUES (?, ?, ?, ?, "approved", NOW())
                    ');
                    $stmt->execute([$postId, $parentId, $adminId, $content]);
                    
                    updatePostCommentCount($pdo, $postId);
                    
                    logActivity('replied', 'blog_comment', $pdo->lastInsertId(), substr($content, 0, 50) . '...');
                    $message = 'Reply posted.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Error posting reply: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // Bulk actions
        if ($action === 'bulk' && !empty($_POST['comment_ids']) && !empty($_POST['bulk_action'])) {
            $commentIds = json_decode($_POST['comment_ids'], true);
            $bulkAction = $_POST['bulk_action'];
            
            if (is_array($commentIds) && !empty($commentIds)) {
                try {
                    $pdo->beginTransaction();
                    
                    $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
                    
                    // Get all affected post IDs
                    $stmt = $pdo->prepare("SELECT DISTINCT post_id FROM blog_comments WHERE id IN ($placeholders)");
                    $stmt->execute($commentIds);
                    $postIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if ($bulkAction === 'approve') {
                        $stmt = $pdo->prepare("UPDATE blog_comments SET status = 'approved' WHERE id IN ($placeholders)");
                        $stmt->execute($commentIds);
                        $affected = $stmt->rowCount();
                        logActivity('bulk_approved', 'blog_comment', null, "$affected comments");
                    } elseif ($bulkAction === 'spam') {
                        $stmt = $pdo->prepare("UPDATE blog_comments SET status = 'spam' WHERE id IN ($placeholders)");
                        $stmt->execute($commentIds);
                        $affected = $stmt->rowCount();
                        logActivity('bulk_spam', 'blog_comment', null, "$affected comments");
                    } elseif ($bulkAction === 'trash') {
                        $stmt = $pdo->prepare("UPDATE blog_comments SET status = 'trash' WHERE id IN ($placeholders)");
                        $stmt->execute($commentIds);
                        $affected = $stmt->rowCount();
                        logActivity('bulk_trash', 'blog_comment', null, "$affected comments");
                    } elseif ($bulkAction === 'delete') {
                        $stmt = $pdo->prepare("DELETE FROM blog_comments WHERE id IN ($placeholders)");
                        $stmt->execute($commentIds);
                        $affected = $stmt->rowCount();
                        logActivity('bulk_deleted', 'blog_comment', null, "$affected comments");
                    }
                    
                    // Update all affected post comment counts
                    foreach ($postIds as $pid) {
                        updatePostCommentCount($pdo, $pid);
                    }
                    
                    $pdo->commit();
                    $message = $affected . ' comment(s) updated.';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $message = 'Error performing bulk action: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$postFilter = $_GET['post'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query conditions
$conditions = [];
$params = [];

if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'spam', 'trash'])) {
    $conditions[] = 'c.status = ?';
    $params[] = $statusFilter;
}

if ($postFilter) {
    $conditions[] = 'c.post_id = ?';
    $params[] = (int)$postFilter;
}

if ($searchQuery) {
    $conditions[] = '(c.content LIKE ? OR c.author_name LIKE ? OR c.author_email LIKE ?)';
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($dateFrom) {
    $conditions[] = 'DATE(c.created_at) >= ?';
    $params[] = $dateFrom;
}

if ($dateTo) {
    $conditions[] = 'DATE(c.created_at) <= ?';
    $params[] = $dateTo;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM blog_comments c $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalComments = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalComments / $perPage));

// Get comments with post and parent info
$sql = "
    SELECT 
        c.*,
        p.title as post_title,
        p.slug as post_slug,
        pc.content as parent_content,
        u.username as admin_username
    FROM blog_comments c
    LEFT JOIN blog_posts p ON c.post_id = p.id
    LEFT JOIN blog_comments pc ON c.parent_id = pc.id
    LEFT JOIN admins u ON c.user_id = u.id
    $whereClause
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'spam' => 0,
    'trash' => 0
];

$stmt = $pdo->query('
    SELECT status, COUNT(*) as count 
    FROM blog_comments 
    GROUP BY status
');
while ($row = $stmt->fetch()) {
    $stats[$row['status']] = (int)$row['count'];
    $stats['total'] += (int)$row['count'];
}

// Get posts for filter dropdown
$stmt = $pdo->query('
    SELECT id, title 
    FROM blog_posts 
    WHERE deleted_at IS NULL 
    ORDER BY title ASC
');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <div class="dashboard-hero__greeting">Blog Comments</div>
        <h1 class="dashboard-hero__title">Comment Moderation</h1>
        <p class="dashboard-hero__subtitle">Manage and moderate comments on your blog posts</p>
      </div>
      <div class="dashboard-hero__actions">
        <a href="<?php echo $adminBase; ?>/api/export.php?type=blog_comments&format=csv" class="dashboard-hero__btn">
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
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
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
      <div class="stat-card__label">All Comments</div>
    </div>

    <div class="stat-card stat-card--yellow">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          Pending
        </div>
      </div>
      <div class="stat-card__value"><?php echo $stats['pending']; ?></div>
      <div class="stat-card__label">Awaiting Review</div>
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
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Approved
        </div>
      </div>
      <div class="stat-card__value"><?php echo $stats['approved']; ?></div>
      <div class="stat-card__label">Published</div>
    </div>

    <div class="stat-card stat-card--red">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--down">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
            <polyline points="17 18 23 18 23 12"/>
          </svg>
          Spam
        </div>
      </div>
      <div class="stat-card__value"><?php echo $stats['spam']; ?></div>
      <div class="stat-card__label">Marked as Spam</div>
    </div>

    <div class="stat-card stat-card--gray">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
          </svg>
        </div>
        <div class="stat-card__trend">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
          </svg>
          Trash
        </div>
      </div>
      <div class="stat-card__value"><?php echo $stats['trash']; ?></div>
      <div class="stat-card__label">In Trash</div>
    </div>
  </div>

  <!-- Filters Card -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
          </svg>
          Filter Comments
        </h3>
      </div>
    </div>
    <div class="admin-card__body">
      <form method="GET" class="filter-form">
        <div class="filter-grid">
          <div class="filter-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-select">
              <option value="">All Statuses</option>
              <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
              <option value="spam" <?php echo $statusFilter === 'spam' ? 'selected' : ''; ?>>Spam</option>
              <option value="trash" <?php echo $statusFilter === 'trash' ? 'selected' : ''; ?>>Trash</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="post">Post</label>
            <select name="post" id="post" class="form-select">
              <option value="">All Posts</option>
              <?php foreach ($posts as $post): ?>
                <option value="<?php echo $post['id']; ?>" <?php echo $postFilter == $post['id'] ? 'selected' : ''; ?>>
                  <?php echo e($post['title']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="search">Search</label>
            <input type="text" name="search" id="search" class="form-input" placeholder="Search content, author..." value="<?php echo e($searchQuery); ?>">
          </div>
          
          <div class="filter-group">
            <label for="date_from">Date From</label>
            <input type="date" name="date_from" id="date_from" class="form-input" value="<?php echo e($dateFrom); ?>">
          </div>
          
          <div class="filter-group">
            <label for="date_to">Date To</label>
            <input type="date" name="date_to" id="date_to" class="form-input" value="<?php echo e($dateTo); ?>">
          </div>
          
          <div class="filter-group filter-group--actions">
            <button type="submit" class="btn btn--primary">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              Filter
            </button>
            <a href="blog_comments.php" class="btn btn--secondary">Clear</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Comments Table -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          Comments
        </h3>
        <p class="admin-card__subtitle"><?php echo number_format($totalComments); ?> total comments</p>
      </div>
      <div class="admin-card__header-actions">
        <?php if ($stats['spam'] > 0): ?>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete all spam comments? This cannot be undone.');">
          <input type="hidden" name="action" value="bulk">
          <input type="hidden" name="bulk_action" value="delete_spam">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
          <button type="submit" class="btn btn--danger btn--sm">Empty Spam</button>
        </form>
        <?php endif; ?>
        <?php if ($stats['trash'] > 0): ?>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete all trashed comments? This cannot be undone.');">
          <input type="hidden" name="action" value="bulk">
          <input type="hidden" name="bulk_action" value="delete_trash">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
          <button type="submit" class="btn btn--danger btn--sm">Empty Trash</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <div class="admin-card__body">
      <?php if (empty($comments)): ?>
        <div class="empty-state">
          <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 z"/>
          </svg>
          <h3 class="empty-state__title">No Comments Found</h3>
          <p class="empty-state__text">No comments match your current filters.</p>
        </div>
      <?php else: ?>
        <form method="POST" id="bulkActionsForm">
          <input type="hidden" name="action" value="bulk">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
          <input type="hidden" name="comment_ids" id="commentIdsInput">
          <input type="hidden" name="bulk_action" id="bulkActionInput">
          
          <!-- Bulk Actions Toolbar -->
          <div class="bulk-actions-toolbar" id="bulkActionsToolbar" style="display: none;">
            <span class="bulk-actions-info"><span id="selectedCount">0</span> selected</span>
            <button type="button" class="btn btn--success btn--sm" onclick="submitBulkAction('approve')">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              Approve
            </button>
            <button type="button" class="btn btn--warning btn--sm" onclick="submitBulkAction('spam')">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
              </svg>
              Mark Spam
            </button>
            <button type="button" class="btn btn--secondary btn--sm" onclick="submitBulkAction('trash')">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
              </svg>
              Move to Trash
            </button>
            <button type="button" class="btn btn--danger btn--sm" onclick="confirmBulkDelete()">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                <line x1="10" y1="11" x2="10" y2="17"/>
                <line x1="14" y1="11" x2="14" y2="17"/>
              </svg>
              Delete Permanently
            </button>
            <button type="button" class="btn btn--ghost btn--sm" onclick="clearSelection()">Clear</button>
          </div>
          
          <div class="comments-list">
            <?php foreach ($comments as $comment): ?>
            <div class="comment-item <?php echo $comment['parent_id'] ? 'comment-item--reply' : ''; ?>" data-id="<?php echo $comment['id']; ?>">
              <div class="comment-item__checkbox">
                <input type="checkbox" class="comment-checkbox" value="<?php echo $comment['id']; ?>" onchange="updateBulkActions()">
              </div>
              
              <div class="comment-item__content">
                <div class="comment-item__header">
                  <div class="comment-item__author">
                    <?php if ($comment['user_id'] && $comment['admin_username']): ?>
                      <span class="comment-author-name">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                          <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php echo e($comment['admin_username']); ?>
                        <span class="badge badge--info badge--sm">Admin</span>
                      </span>
                    <?php else: ?>
                      <span class="comment-author-name">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                          <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php echo e($comment['author_name'] ?: 'Anonymous'); ?>
                      </span>
                      <?php if ($comment['author_email']): ?>
                        <a href="mailto:<?php echo e($comment['author_email']); ?>" class="comment-author-email">
                          <?php echo e($comment['author_email']); ?>
                        </a>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                  
                  <div class="comment-item__meta">
                    <span class="comment-date">
                      <?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?>
                    </span>
                    <span class="badge badge--<?php 
                      echo $comment['status'] === 'approved' ? 'success' : 
                          ($comment['status'] === 'pending' ? 'warning' : 
                          ($comment['status'] === 'spam' ? 'danger' : 'muted')); 
                    ?>">
                      <?php echo ucfirst($comment['status']); ?>
                    </span>
                  </div>
                </div>
                
                <?php if ($comment['parent_id'] && $comment['parent_content']): ?>
                <div class="comment-parent">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                  </svg>
                  <span>In reply to: "<?php echo e(substr($comment['parent_content'], 0, 100)); ?>..."</span>
                </div>
                <?php endif; ?>
                
                <div class="comment-text" id="comment-text-<?php echo $comment['id']; ?>">
                  <?php echo nl2br(e($comment['content'])); ?>
                </div>
                
                <div class="comment-item__footer">
                  <div class="comment-post-link">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                      <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                    On: <a href="<?php echo $adminBase; ?>/blog_post_edit.php?id=<?php echo $comment['post_id']; ?>" target="_blank">
                      <?php echo e($comment['post_title']); ?>
                    </a>
                  </div>
                  
                  <?php if ($comment['ip_address']): ?>
                  <div class="comment-ip-info">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/>
                      <rect x="2" y="14" width="20" height="8" rx="2" ry="2"/>
                      <line x1="6" y1="6" x2="6.01" y2="6"/>
                      <line x1="6" y1="18" x2="6.01" y2="18"/>
                    </svg>
                    IP: <a href="https://whois.domaintools.com/<?php echo e($comment['ip_address']); ?>" target="_blank" title="WHOIS lookup">
                      <?php echo e($comment['ip_address']); ?>
                    </a>
                  </div>
                  <?php endif; ?>
                </div>
                
                <div class="comment-item__actions">
                  <?php if ($comment['status'] === 'pending'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="btn btn--success btn--sm" title="Approve">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                      </svg>
                      Approve
                    </button>
                  </form>
                  <?php endif; ?>
                  
                  <?php if ($comment['status'] !== 'spam'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="spam">
                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="btn btn--warning btn--sm" title="Mark as Spam">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                      </svg>
                      Spam
                    </button>
                  </form>
                  <?php endif; ?>
                  
                  <?php if ($comment['status'] !== 'trash'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="trash">
                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="btn btn--secondary btn--sm" title="Move to Trash">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                      </svg>
                      Trash
                    </button>
                  </form>
                  <?php endif; ?>
                  
                  <button type="button" class="btn btn--outline btn--sm" onclick="toggleReplyForm(<?php echo $comment['id']; ?>, <?php echo $comment['post_id']; ?>)" title="Reply">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="9 17 4 12 9 7"/>
                      <path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
                    </svg>
                    Reply
                  </button>
                  
                  <button type="button" class="btn btn--outline btn--sm" onclick="toggleEditForm(<?php echo $comment['id']; ?>)" title="Edit">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Edit
                  </button>
                  
                  <button type="button" class="btn btn--ghost btn--sm" onclick="toggleSpamInfo(<?php echo $comment['id']; ?>)" title="View Details">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"/>
                      <line x1="12" y1="16" x2="12" y2="12"/>
                      <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    Details
                  </button>
                  
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this comment?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="btn btn--ghost btn--sm btn--danger" title="Delete Permanently">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <line x1="10" y1="11" x2="10" y2="17"/>
                        <line x1="14" y1="11" x2="14" y2="17"/>
                      </svg>
                    </button>
                  </form>
                </div>
                
                <!-- Reply Form -->
                <div class="comment-reply-form" id="reply-form-<?php echo $comment['id']; ?>" style="display: none;">
                  <form method="POST">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                    <input type="hidden" name="post_id" value="<?php echo $comment['post_id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                      <label>Reply as Admin</label>
                      <textarea name="reply_content" class="form-textarea" rows="3" placeholder="Write your reply..." required></textarea>
                    </div>
                    <div class="form-actions">
                      <button type="submit" class="btn btn--primary btn--sm">Post Reply</button>
                      <button type="button" class="btn btn--secondary btn--sm" onclick="toggleReplyForm(<?php echo $comment['id']; ?>)">Cancel</button>
                    </div>
                  </form>
                </div>
                
                <!-- Edit Form -->
                <div class="comment-edit-form" id="edit-form-<?php echo $comment['id']; ?>" style="display: none;">
                  <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                      <label>Content</label>
                      <textarea name="content" class="form-textarea" rows="4" required><?php echo e($comment['content']); ?></textarea>
                    </div>
                    <?php if (!$comment['user_id']): ?>
                    <div class="form-row">
                      <div class="form-group">
                        <label>Author Name</label>
                        <input type="text" name="author_name" class="form-input" value="<?php echo e($comment['author_name']); ?>">
                      </div>
                      <div class="form-group">
                        <label>Author Email</label>
                        <input type="email" name="author_email" class="form-input" value="<?php echo e($comment['author_email']); ?>">
                      </div>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                      <label>Status</label>
                      <select name="status" class="form-select">
                        <option value="pending" <?php echo $comment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $comment['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="spam" <?php echo $comment['status'] === 'spam' ? 'selected' : ''; ?>>Spam</option>
                        <option value="trash" <?php echo $comment['status'] === 'trash' ? 'selected' : ''; ?>>Trash</option>
                      </select>
                    </div>
                    <div class="form-actions">
                      <button type="submit" class="btn btn--primary btn--sm">Save Changes</button>
                      <button type="button" class="btn btn--secondary btn--sm" onclick="toggleEditForm(<?php echo $comment['id']; ?>)">Cancel</button>
                    </div>
                  </form>
                </div>
                
                <!-- Spam Detection Info -->
                <div class="comment-spam-info" id="spam-info-<?php echo $comment['id']; ?>" style="display: none;">
                  <div class="spam-info-grid">
                    <div class="spam-info-item">
                      <strong>IP Address:</strong>
                      <span><?php echo e($comment['ip_address'] ?: 'Not recorded'); ?></span>
                      <?php if ($comment['ip_address']): ?>
                        <a href="https://whois.domaintools.com/<?php echo e($comment['ip_address']); ?>" target="_blank" class="btn btn--ghost btn--sm">WHOIS</a>
                      <?php endif; ?>
                    </div>
                    <div class="spam-info-item">
                      <strong>Submitted:</strong>
                      <span><?php echo date('M j, Y g:i:s A', strtotime($comment['created_at'])); ?></span>
                    </div>
                    <div class="spam-info-item">
                      <strong>Last Updated:</strong>
                      <span><?php echo date('M j, Y g:i:s A', strtotime($comment['updated_at'])); ?></span>
                    </div>
                    <div class="spam-info-item spam-info-item--full">
                      <strong>User Agent:</strong>
                      <code><?php echo e($comment['user_agent'] ?: 'Not recorded'); ?></code>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </form>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <div class="pagination__info">
            Showing <?php echo number_format($offset + 1); ?> - <?php echo number_format(min($offset + $perPage, $totalComments)); ?> of <?php echo number_format($totalComments); ?> comments
          </div>
          <div class="pagination__links">
            <?php if ($page > 1): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination__link pagination__link--prev">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="15 18 9 12 15 6"/>
                </svg>
                Previous
              </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                 class="pagination__link <?php echo $i === $page ? 'pagination__link--active' : ''; ?>">
                <?php echo $i; ?>
              </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination__link pagination__link--next">
                Next
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="9 18 15 12 9 6"/>
                </svg>
              </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
/* Stats Cards */
.dashboard-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--space-4);
  margin-bottom: var(--space-6);
}

.stat-card--gray {
  --stat-color: #6b7280;
}

.stat-card--gray .stat-card__icon {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
}

.stat-card--gray .stat-card__value {
  color: #6b7280;
}

.stat-card--yellow {
  --stat-color: #f59e0b;
}

.stat-card--yellow .stat-card__icon {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.stat-card--yellow .stat-card__value {
  color: #f59e0b;
}

/* Filter Form */
.filter-form {
  width: 100%;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--space-4);
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-secondary);
}

.filter-group--actions {
  display: flex;
  gap: var(--space-2);
  padding-top: 1.5rem;
}

/* Comments List */
.comments-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.comment-item {
  display: flex;
  gap: var(--space-4);
  padding: var(--space-4);
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-lg);
  transition: all var(--transition-base);
}

.comment-item:hover {
  box-shadow: var(--shadow-md);
}

.comment-item--reply {
  margin-left: var(--space-8);
  border-left: 3px solid var(--primary);
}

.comment-item__checkbox {
  padding-top: var(--space-1);
}

.comment-item__checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.comment-item__content {
  flex: 1;
  min-width: 0;
}

.comment-item__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: var(--space-4);
  margin-bottom: var(--space-2);
}

.comment-item__author {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.comment-author-name {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-weight: 600;
  color: var(--text-primary);
}

.comment-author-email {
  font-size: 0.875rem;
  color: var(--info);
  text-decoration: none;
}

.comment-author-email:hover {
  text-decoration: underline;
}

.comment-item__meta {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  flex-shrink: 0;
}

.comment-date {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.comment-parent {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-3);
  margin-bottom: var(--space-2);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.comment-text {
  padding: var(--space-3);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-3);
  line-height: 1.6;
  word-wrap: break-word;
}

.comment-item__footer {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-4);
  margin-bottom: var(--space-3);
  font-size: 0.875rem;
}

.comment-post-link,
.comment-ip-info {
  display: flex;
  align-items: center;
  gap: var(--space-1);
  color: var(--text-secondary);
}

.comment-post-link a,
.comment-ip-info a {
  color: var(--info);
  text-decoration: none;
}

.comment-post-link a:hover,
.comment-ip-info a:hover {
  text-decoration: underline;
}

.comment-item__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

/* Reply and Edit Forms */
.comment-reply-form,
.comment-edit-form {
  margin-top: var(--space-4);
  padding: var(--space-4);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
}

.form-group {
  margin-bottom: var(--space-3);
}

.form-group label {
  display: block;
  margin-bottom: var(--space-1);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-primary);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-3);
}

.form-actions {
  display: flex;
  gap: var(--space-2);
}

/* Spam Info */
.comment-spam-info {
  margin-top: var(--space-4);
  padding: var(--space-4);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
}

.spam-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--space-3);
}

.spam-info-item {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: 0.875rem;
}

.spam-info-item--full {
  grid-column: 1 / -1;
  flex-direction: column;
  align-items: flex-start;
}

.spam-info-item strong {
  color: var(--text-secondary);
}

.spam-info-item code {
  display: block;
  margin-top: var(--space-1);
  padding: var(--space-2);
  background: var(--bg-card);
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  word-break: break-all;
}

/* Bulk Actions Toolbar */
.bulk-actions-toolbar {
  display: flex;
  flex-wrap: wrap;
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

/* Pagination */
.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: var(--space-6);
  padding-top: var(--space-4);
  border-top: 1px solid var(--border-light);
}

.pagination__info {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.pagination__links {
  display: flex;
  gap: var(--space-2);
  align-items: center;
}

.pagination__link {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  padding: var(--space-2) var(--space-3);
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  color: var(--text-primary);
  text-decoration: none;
  transition: all var(--transition-fast);
}

.pagination__link:hover {
  background: var(--bg-secondary);
  border-color: var(--primary);
}

.pagination__link--active {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
}

.pagination__link--prev,
.pagination__link--next {
  font-weight: 500;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: var(--space-12) var(--space-6);
}

.empty-state__icon {
  width: 64px;
  height: 64px;
  margin: 0 auto var(--space-4);
  color: var(--text-muted);
}

.empty-state__title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: var(--space-2);
}

.empty-state__text {
  color: var(--text-secondary);
}

/* Dark mode adjustments */
.dark .comment-item {
  background: var(--bg-card);
  border-color: var(--border-dark);
}

.dark .comment-text,
.dark .comment-parent,
.dark .comment-reply-form,
.dark .comment-edit-form,
.dark .comment-spam-info {
  background: var(--bg-secondary);
  border-color: var(--border-dark);
}

.dark .spam-info-item code {
  background: var(--bg-card);
}

/* Responsive */
@media (max-width: 768px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }
  
  .comment-item__header {
    flex-direction: column;
    gap: var(--space-2);
  }
  
  .comment-item--reply {
    margin-left: var(--space-4);
  }
  
  .comment-item__actions {
    flex-direction: column;
  }
  
  .comment-item__actions .btn {
    width: 100%;
    justify-content: center;
  }
  
  .pagination {
    flex-direction: column;
    gap: var(--space-4);
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>

<script>
// Toggle reply form
function toggleReplyForm(commentId, postId) {
  const form = document.getElementById('reply-form-' + commentId);
  if (form) {
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    // Hide edit form if open
    const editForm = document.getElementById('edit-form-' + commentId);
    if (editForm) editForm.style.display = 'none';
  }
}

// Toggle edit form
function toggleEditForm(commentId) {
  const form = document.getElementById('edit-form-' + commentId);
  if (form) {
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    // Hide reply form if open
    const replyForm = document.getElementById('reply-form-' + commentId);
    if (replyForm) replyForm.style.display = 'none';
  }
}

// Toggle spam info
function toggleSpamInfo(commentId) {
  const info = document.getElementById('spam-info-' + commentId);
  if (info) {
    info.style.display = info.style.display === 'none' ? 'block' : 'none';
  }
}

// Bulk Actions Functions
function toggleSelectAll() {
  const selectAll = document.getElementById('selectAll');
  const checkboxes = document.querySelectorAll('.comment-checkbox');
  checkboxes.forEach(checkbox => {
    checkbox.checked = selectAll.checked;
  });
  updateBulkActions();
}

function updateBulkActions() {
  const checkboxes = document.querySelectorAll('.comment-checkbox:checked');
  const toolbar = document.getElementById('bulkActionsToolbar');
  const selectedCount = document.getElementById('selectedCount');
  
  selectedCount.textContent = checkboxes.length;
  
  if (checkboxes.length > 0) {
    toolbar.style.display = 'flex';
  } else {
    toolbar.style.display = 'none';
  }
}

function clearSelection() {
  const checkboxes = document.querySelectorAll('.comment-checkbox');
  checkboxes.forEach(checkbox => {
    checkbox.checked = false;
  });
  updateBulkActions();
}

function submitBulkAction(action) {
  const checkboxes = document.querySelectorAll('.comment-checkbox:checked');
  if (checkboxes.length === 0) return;
  
  const ids = Array.from(checkboxes).map(cb => cb.value);
  
  document.getElementById('commentIdsInput').value = JSON.stringify(ids);
  document.getElementById('bulkActionInput').value = action;
  
  document.getElementById('bulkActionsForm').submit();
}

function confirmBulkDelete() {
  const checkboxes = document.querySelectorAll('.comment-checkbox:checked');
  if (checkboxes.length === 0) return;
  
  if (confirm('Are you sure you want to permanently delete ' + checkboxes.length + ' comment(s)? This action cannot be undone.')) {
    submitBulkAction('delete');
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
