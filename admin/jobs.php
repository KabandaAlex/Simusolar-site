<?php
/**
 * Tulima Solar - Job Portal Admin Jobs Management
 * Dashboard for managing job listings
 * 
 * Features:
 * - Card-based layout with stats overview
 * - Expired job highlighting with red styling
 * - Repost feature for expired job listings
 * - Enhanced filtering and search
 * - Bulk actions support
 * 
 * Migrated from job-portal/admin/jobs.php
 */

require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/job-portal-functions.php';

$pdo = getDb();
$currentNav = 'jobs';
$pageTitle = 'Jobs Management';

// Initialize data
$jobs = [];
$categories = getJobCategories($pdo);
$locations = getJobLocations($pdo);
$stats = [
    'total' => 0,
    'active' => 0,
    'expired' => 0,
    'draft' => 0,
    'applications' => 0
];

// Get statistics including expired count
try {
    $stats['total'] = (int)$pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
    $stats['active'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active' AND (application_deadline IS NULL OR application_deadline >= NOW())")->fetchColumn();
    $stats['expired'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active' AND application_deadline IS NOT NULL AND application_deadline < NOW()")->fetchColumn();
    $stats['draft'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'draft'")->fetchColumn();
    $stats['closed'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'closed'")->fetchColumn();
    $stats['applications'] = (int)$pdo->query('SELECT COUNT(*) FROM job_applications')->fetchColumn();
} catch (PDOException $e) {
    error_log('Error fetching job stats: ' . $e->getMessage());
}

// Get all jobs with filters
$filterStatus = $_GET['status'] ?? 'all';
$filterCategory = $_GET['category_id'] ?? '';
$filterLocation = $_GET['location_id'] ?? '';
$searchKeyword = $_GET['search'] ?? '';

// Build query with joins to get category, location, and currency data
$sql = 'SELECT j.*, 
               jc.name as category_name, jc.color as category_color,
               jl.name as location_name, jl.is_remote,
               curr.code as currency_code, curr.symbol as currency_symbol,
               (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
        FROM jobs j
        LEFT JOIN job_categories jc ON j.category_id = jc.id
        LEFT JOIN job_locations jl ON j.location_id = jl.id
        LEFT JOIN currencies curr ON j.currency_id = curr.id
        WHERE 1=1';

$params = [];

// Status filter with expired support
if ($filterStatus !== 'all') {
    if ($filterStatus === 'expired') {
        $sql .= ' AND j.status = "active" AND j.application_deadline IS NOT NULL AND j.application_deadline < NOW()';
    } elseif ($filterStatus === 'active') {
        $sql .= ' AND j.status = "active" AND (j.application_deadline IS NULL OR j.application_deadline >= NOW())';
    } else {
        $sql .= ' AND j.status = :status';
        $params['status'] = $filterStatus;
    }
}

if ($filterCategory) {
    $sql .= ' AND j.category_id = :category_id';
    $params['category_id'] = $filterCategory;
}

if ($filterLocation) {
    $sql .= ' AND j.location_id = :location_id';
    $params['location_id'] = $filterLocation;
}

if ($searchKeyword) {
    $sql .= ' AND (j.title LIKE :search OR j.description LIKE :search OR j.requirements LIKE :search)';
    $params['search'] = '%' . $searchKeyword . '%';
}

$sql .= ' ORDER BY j.created_at DESC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Error fetching jobs: ' . $e->getMessage();
    $messageType = 'error';
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $jobId = (int)$_POST['job_id'];
            if (deleteJob($pdo, $jobId, $_SESSION['admin_id'])) {
                $message = 'Job deleted successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete job';
                $messageType = 'error';
            }
        }

        if ($action === 'toggle_status') {
            $jobId = (int)$_POST['job_id'];
            $newStatus = $_POST['new_status'];

            $sql = 'UPDATE jobs SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['status' => $newStatus, 'id' => $jobId]);

            $message = 'Job status updated successfully';
            $messageType = 'success';
        }

        // Handle repost action
        if ($action === 'repost') {
            $jobId = (int)$_POST['job_id'];
            $newDeadline = $_POST['new_deadline'];
            $repostMode = $_POST['repost_mode']; // 'clone' or 'reactivate'

            // Validate deadline
            if (strtotime($newDeadline) < time()) {
                throw new Exception('New deadline must be a future date.');
            }

            if ($repostMode === 'reactivate') {
                // Update existing job with new deadline
                $stmt = $pdo->prepare("UPDATE jobs SET application_deadline = ?, status = 'active', last_reposted_at = NOW(), last_reposted_by = ?, repost_count = repost_count + 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$newDeadline, $_SESSION['admin_id'], $jobId]);
                $message = 'Job reactivated successfully with new deadline';
                $messageType = 'success';
            } else {
                // Clone job as new entry
                $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
                $stmt->execute([$jobId]);
                $originalJob = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($originalJob) {
                    // Generate new slug
                    $baseSlug = $originalJob['slug'] . '-repost';
                    $newSlug = $baseSlug;
                    $counter = 1;
                    while (true) {
                        $checkStmt = $pdo->prepare("SELECT id FROM jobs WHERE slug = ?");
                        $checkStmt->execute([$newSlug]);
                        if (!$checkStmt->fetch()) break;
                        $newSlug = $baseSlug . '-' . $counter++;
                    }

                    // Insert cloned job
                    $insertSql = "INSERT INTO jobs (title, slug, description, requirements, benefits, category_id, location_id, job_type, salary_min, salary_max, currency_id, application_deadline, status, created_by, original_job_id, repost_count, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, 0, NOW(), NOW())";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->execute([
                        $originalJob['title'],
                        $newSlug,
                        $originalJob['description'],
                        $originalJob['requirements'],
                        $originalJob['benefits'],
                        $originalJob['category_id'],
                        $originalJob['location_id'],
                        $originalJob['job_type'],
                        $originalJob['salary_min'],
                        $originalJob['salary_max'],
                        $originalJob['currency_id'],
                        $newDeadline,
                        $_SESSION['admin_id'],
                        $jobId
                    ]);

                    // Increment repost count on original
                    $updateOriginal = $pdo->prepare("UPDATE jobs SET repost_count = repost_count + 1, last_reposted_at = NOW(), last_reposted_by = ? WHERE id = ?");
                    $updateOriginal->execute([$_SESSION['admin_id'], $jobId]);

                    $message = 'Job cloned successfully as a new listing';
                    $messageType = 'success';
                }
            }
        }

        // Bulk actions
        if ($action === 'bulk_status' && !empty($_POST['job_ids'])) {
            $jobIds = json_decode($_POST['job_ids'], true);
            $bulkStatus = $_POST['bulk_status'];

            if (is_array($jobIds) && !empty($jobIds)) {
                $stmt = $pdo->prepare('UPDATE jobs SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                foreach ($jobIds as $jid) {
                    $stmt->execute([$bulkStatus, (int)$jid]);
                }
                $message = count($jobIds) . ' jobs updated successfully';
                $messageType = 'success';
            }
        }

        if ($action === 'bulk_delete' && !empty($_POST['job_ids'])) {
            $jobIds = json_decode($_POST['job_ids'], true);

            if (is_array($jobIds) && !empty($jobIds)) {
                $stmt = $pdo->prepare('DELETE FROM jobs WHERE id = ?');
                foreach ($jobIds as $jid) {
                    $stmt->execute([(int)$jid]);
                }
                $message = count($jobIds) . ' jobs deleted successfully';
                $messageType = 'success';
            }
        }

    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page-specific styles -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/admin/assets/css/admin-jobs.css">

<!-- Page Content -->
<div class="admin-content">
    <!-- Page Header -->
    <div class="page-header-enhanced">
    <div class="page-header-enhanced__content">
        <h1 class="page-header-enhanced__title">Jobs Management</h1>
        <p class="page-header-enhanced__subtitle">Manage job listings, track applications, and repost expired positions.</p>
    </div>
    <div class="page-header-enhanced__actions">
        <a href="job-post.php" class="btn btn--primary">
            <svg viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add New Job
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="job-message">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<!-- Page Stats Overview -->
 
<div class="page-stats-grid">
    <div class="page-stat-card page-stat-card--purple <?php echo $filterStatus === 'all' ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?';" style="cursor: pointer;">
        <div class="page-stat-card__value"><?php echo $stats['total']; ?></div>
        <div class="page-stat-card__label">Total Jobs</div>
        <div class="page-stat-card__trend">
            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            All listings
        </div>
    </div>

    <div class="page-stat-card page-stat-card--green <?php echo $filterStatus === 'active' ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?status=active';" style="cursor: pointer;">
        <div class="page-stat-card__value"><?php echo $stats['active']; ?></div>
        <div class="page-stat-card__label">Active</div>
        <div class="page-stat-card__trend">
            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Accepting applications
        </div>
    </div>

    <div class="page-stat-card page-stat-card--red <?php echo $filterStatus === 'expired' ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?status=expired';" style="cursor: pointer;">
        <div class="page-stat-card__value"><?php echo $stats['expired']; ?></div>
        <div class="page-stat-card__label">Expired</div>
        <div class="page-stat-card__trend">
            <svg viewBox="0 0 24 24" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Need attention
        </div>
    </div>

    <div class="page-stat-card page-stat-card--orange <?php echo $filterStatus === 'draft' ? 'page-stat-card--active' : ''; ?>" onclick="window.location.href='?status=draft';" style="cursor: pointer;">
        <div class="page-stat-card__value"><?php echo $stats['draft']; ?></div>
        <div class="page-stat-card__label">Drafts</div>
        <div class="page-stat-card__trend">
            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Work in progress
        </div>
    </div>

    <div class="page-stat-card page-stat-card--blue" style="cursor: default;">
        <div class="page-stat-card__value"><?php echo $stats['applications']; ?></div>
        <div class="page-stat-card__label">Applications</div>
        <div class="page-stat-card__trend">
            <svg viewBox="0 0 24 24" width="14" height="14"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Total received
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="admin-toolbar">
    <form class="admin-filters-enhanced" method="GET">
        <div class="admin-filter-row">
            <div class="admin-filter-group">
                <label class="admin-filter-label">Status</label>
                <select name="status" class="admin-filter-select">
                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="expired" <?php echo $filterStatus === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="draft" <?php echo $filterStatus === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="closed" <?php echo $filterStatus === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>

            <div class="admin-filter-group">
                <label class="admin-filter-label">Category</label>
                <select name="category_id" class="admin-filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $filterCategory == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo e($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-filter-group">
                <label class="admin-filter-label">Location</label>
                <select name="location_id" class="admin-filter-select">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>" <?php echo $filterLocation == $location['id'] ? 'selected' : ''; ?>>
                            <?php echo e($location['name']); ?>
                            <?php if (!empty($location['is_remote'])): ?> (Remote)<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-filter-group admin-filter-group--search">
                <label class="admin-filter-label">Search</label>
                <input type="text" name="search" class="admin-filter-input" placeholder="Search jobs..." value="<?php echo e($searchKeyword); ?>">
            </div>

            <div class="admin-filter-actions">
                <button type="submit" class="admin-btn admin-btn-secondary">
                    <svg viewBox="0 0 24 24" width="16" height="16"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Filter
                </button>
                <a href="?" class="admin-btn admin-btn-ghost">Clear</a>
            </div>
        </div>
    </form>
</div>

<!-- Bulk Actions Bar -->
<div class="admin-bulk-actions" id="bulkActionsBar" style="display: none;">
    <div class="admin-bulk-actions__content">
        <span class="admin-bulk-actions__count"><span id="selectedCount">0</span> jobs selected</span>
        <div class="admin-bulk-actions__buttons">
            <select id="bulkActionSelect" class="admin-filter-select">
                <option value="">Choose action...</option>
                <option value="active">Set Active</option>
                <option value="draft">Set Draft</option>
                <option value="closed">Set Closed</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button type="button" id="applyBulkAction" class="admin-btn admin-btn-primary">Apply</button>
        </div>
    </div>
</div>

<!-- Jobs Table -->
<div class="admin-table-container">
    <table class="admin-table" id="jobsTable">
        <thead>
            <tr>
                <th class="admin-table__checkbox">
                    <input type="checkbox" id="selectAll" class="admin-checkbox">
                </th>
                <th>Job Title</th>
                <th>Category</th>
                <th>Location</th>
                <th>Type</th>
                <th>Salary</th>
                <th>Applications</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jobs)): ?>
                <tr>
                    <td colspan="10" class="admin-table-empty">
                        <div class="admin-table-empty__content">
                            <svg viewBox="0 0 24 24" width="48" height="48"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <p>No jobs found</p>
                            <a href="job-post.php" class="admin-btn admin-btn-primary">Create your first job listing</a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <?php 
                    // Check if job is expired
                    $isExpired = !empty($job['application_deadline']) && strtotime($job['application_deadline']) < time();
                    $rowClass = $isExpired ? 'job-expired' : '';
                    
                    // Determine display status
                    $displayStatus = $job['status'];
                    if ($job['status'] === 'active' && $isExpired) {
                        $displayStatus = 'expired';
                    }
                    
                    // Format salary
                    $salaryDisplay = '';
                    if (!empty($job['salary_min']) || !empty($job['salary_max'])) {
                        $symbol = $job['currency_symbol'] ?? '$';
                        if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
                            $salaryDisplay = $symbol . number_format($job['salary_min']) . ' - ' . $symbol . number_format($job['salary_max']);
                        } elseif (!empty($job['salary_min'])) {
                            $salaryDisplay = 'From ' . $symbol . number_format($job['salary_min']);
                        } else {
                            $salaryDisplay = 'Up to ' . $symbol . number_format($job['salary_max']);
                        }
                    }
                    
                    // Job type label
                    $jobTypeLabels = [
                        'full-time' => 'Full Time',
                        'part-time' => 'Part Time',
                        'contract' => 'Contract',
                        'internship' => 'Internship',
                        'remote' => 'Remote'
                    ];
                    $jobTypeLabel = $jobTypeLabels[$job['job_type']] ?? $job['job_type'];
                    ?>
                    <tr class="<?php echo $rowClass; ?>" data-job-id="<?php echo $job['id']; ?>" data-status="<?php echo $displayStatus; ?>">
                        <td class="admin-table__checkbox">
                            <input type="checkbox" class="admin-checkbox job-checkbox" value="<?php echo $job['id']; ?>">
                        </td>
                        <td>
                            <div class="admin-table-cell-content">
                                <div class="admin-table-cell-title">
                                    <?php echo e($job['title']); ?>
                                    <?php if ($isExpired): ?>
                                        <span class="badge badge-expired">Expired</span>
                                    <?php endif; ?>
                                </div>
                                <div class="admin-table-cell-subtitle"><?php echo e($job['slug']); ?></div>
                                <?php if (!empty($job['original_job_id'])): ?>
                                    <div class="admin-table-cell-meta">
                                        <svg viewBox="0 0 24 24" width="12" height="12"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
                                        Repost from #<?php echo $job['original_job_id']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($job['category_name']): ?>
                                <span class="admin-badge" style="background-color: <?php echo $job['category_color'] ?: '#660099'; ?>20; color: <?php echo $job['category_color'] ?: '#660099'; ?>;">
                                    <?php echo e($job['category_name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="admin-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($job['location_name']): ?>
                                <div class="admin-table-cell-content">
                                    <span><?php echo e($job['location_name']); ?></span>
                                    <?php if (!empty($job['is_remote'])): ?>
                                        <span class="admin-badge admin-badge--remote">Remote</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="admin-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge--type"><?php echo $jobTypeLabel; ?></span>
                        </td>
                        <td>
                            <?php if ($salaryDisplay): ?>
                                <span class="admin-salary"><?php echo $salaryDisplay; ?></span>
                            <?php else: ?>
                                <span class="admin-text-muted">Not specified</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="applications.php?job_id=<?php echo $job['id']; ?>" class="admin-link admin-application-count">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                <?php echo (int)$job['application_count']; ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($job['application_deadline']): ?>
                                <div class="admin-deadline <?php echo $isExpired ? 'admin-deadline--expired' : ''; ?>">
                                    <?php echo date('M j, Y', strtotime($job['application_deadline'])); ?>
                                    <?php if ($isExpired): ?>
                                        <span class="admin-deadline-text">Expired</span>
                                    <?php else: ?>
                                        <?php
                                        $daysLeft = ceil((strtotime($job['application_deadline']) - time()) / 86400);
                                        if ($daysLeft <= 7):
                                        ?>
                                            <span class="admin-deadline-text admin-deadline--warning"><?php echo $daysLeft; ?> days left</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="admin-text-muted">No deadline</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge-<?php echo $displayStatus; ?>">
                                <?php 
                                $statusLabels = [
                                    'active' => 'Active',
                                    'draft' => 'Draft',
                                    'closed' => 'Closed',
                                    'expired' => 'Expired'
                                ];
                                echo $statusLabels[$displayStatus] ?? ucfirst($displayStatus); 
                                ?>
                            </span>
                        </td>
                        <td>
                            <div class="admin-table-actions">
                                <a href="job-post.php?id=<?php echo $job['id']; ?>" class="admin-btn admin-btn-sm admin-btn-secondary" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <a href="<?php echo BASE_PATH; ?>/job-portal/pages/job-detail.php?slug=<?php echo $job['slug']; ?>" class="admin-btn admin-btn-sm admin-btn-ghost" title="View" target="_blank">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <?php if ($isExpired): ?>
                                    <button type="button" class="admin-btn admin-btn-sm admin-btn-repost" data-job-id="<?php echo $job['id']; ?>" data-job-title="<?php echo e($job['title']); ?>" title="Repost Job">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                <?php endif; ?>
                                <button type="button" onclick="toggleStatus(<?php echo $job['id']; ?>, '<?php echo $job['status']; ?>')" class="admin-btn admin-btn-sm admin-btn-secondary" title="Toggle Status">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </button>
                                <button type="button" onclick="deleteJob(<?php echo $job['id']; ?>)" class="admin-btn admin-btn-sm admin-btn-danger" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Delete Confirmation Modal -->
<div class="admin-modal" id="deleteModal">
    <div class="admin-modal-content">
        <h3 class="admin-modal-title">Delete Job</h3>
        <p class="admin-modal-text">Are you sure you want to delete this job? This action cannot be undone and all associated applications will be removed.</p>
        <div class="admin-modal-actions">
            <button type="button" onclick="closeModal('deleteModal')" class="admin-btn admin-btn-secondary">Cancel</button>
            <button type="button" id="confirmDeleteBtn" class="admin-btn admin-btn-danger">Delete</button>
        </div>
    </div>
</div>

<!-- Repost Modal -->
<div class="admin-modal" id="repostModal">
    <div class="admin-modal-content admin-modal-content--lg">
        <h3 class="admin-modal-title">
            <svg viewBox="0 0 24 24" width="24" height="24" style="vertical-align: middle; margin-right: 0.5rem;"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Repost Job
        </h3>
        <p class="admin-modal-text">You are about to repost: <strong id="repostJobTitle"></strong></p>
        
        <form id="repostForm" method="POST">
            <input type="hidden" name="action" value="repost">
            <input type="hidden" name="job_id" id="repostJobId">
            
            <div class="admin-form-group">
                <label class="admin-form-label">New Application Deadline <span class="admin-required">*</span></label>
                <input type="datetime-local" name="new_deadline" id="newDeadline" class="admin-form-input" required>
                <p class="admin-form-hint">Set a new deadline for applications. This must be a future date.</p>
            </div>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Repost Mode <span class="admin-required">*</span></label>
                <div class="admin-radio-group">
                    <label class="admin-radio-option">
                        <input type="radio" name="repost_mode" value="reactivate" checked>
                        <span class="admin-radio-label">
                            <strong>Reactivate Existing</strong>
                            <small>Update the deadline on the existing job listing. The job will retain its original URL and application history.</small>
                        </span>
                    </label>
                    <label class="admin-radio-option">
                        <input type="radio" name="repost_mode" value="clone">
                        <span class="admin-radio-label">
                            <strong>Clone as New</strong>
                            <small>Create a copy of this job as a new listing with a new URL. Useful for significantly updated positions.</small>
                        </span>
                    </label>
                </div>
            </div>
            
            <div class="admin-modal-actions">
                <button type="button" onclick="closeModal('repostModal')" class="admin-btn admin-btn-secondary">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <svg viewBox="0 0 24 24" width="16" height="16"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Repost Job
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo BASE_PATH; ?>/admin/assets/js/admin-jobs.js"></script>
<script>
// Job management functions
let currentJobId = null;

function deleteJob(jobId) {
    currentJobId = jobId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function toggleStatus(jobId, currentStatus) {
    const statusOrder = ['draft', 'active', 'closed'];
    const currentIndex = statusOrder.indexOf(currentStatus);
    const newStatus = statusOrder[(currentIndex + 1) % statusOrder.length];

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="job_id" value="${jobId}">
        <input type="hidden" name="new_status" value="${newStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Confirm delete button handler
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (currentJobId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="job_id" value="${currentJobId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
});

// Repost modal handlers
const repostButtons = document.querySelectorAll('.admin-btn-repost');
repostButtons.forEach(button => {
    button.addEventListener('click', function() {
        const jobId = this.dataset.jobId;
        const jobTitle = this.dataset.jobTitle;
        
        document.getElementById('repostJobId').value = jobId;
        document.getElementById('repostJobTitle').textContent = jobTitle;
        
        // Set default deadline to 30 days from now
        const defaultDeadline = new Date();
        defaultDeadline.setDate(defaultDeadline.getDate() + 30);
        document.getElementById('newDeadline').value = defaultDeadline.toISOString().slice(0, 16);
        
        document.getElementById('repostModal').classList.add('active');
    });
});

// Close modals on outside click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('deleteModal');
    }
});

document.getElementById('repostModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('repostModal');
    }
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('deleteModal');
        closeModal('repostModal');
    }
});

// Select all checkbox
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.job-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateBulkActionsBar();
});

// Individual checkbox change
document.querySelectorAll('.job-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActionsBar);
});

function updateBulkActionsBar() {
    const selectedCheckboxes = document.querySelectorAll('.job-checkbox:checked');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedCheckboxes.length > 0) {
        bulkActionsBar.style.display = 'block';
        selectedCount.textContent = selectedCheckboxes.length;
    } else {
        bulkActionsBar.style.display = 'none';
    }
}

// Bulk action handler
document.getElementById('applyBulkAction').addEventListener('click', function() {
    const action = document.getElementById('bulkActionSelect').value;
    if (!action) {
        alert('Please select an action');
        return;
    }
    
    const selectedCheckboxes = document.querySelectorAll('.job-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one job');
        return;
    }
    
    const jobIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (action === 'delete') {
        if (!confirm(`Are you sure you want to delete ${jobIds.length} jobs? This cannot be undone.`)) {
            return;
        }
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="bulk_${action === 'delete' ? 'delete' : 'status'}">
        <input type="hidden" name="job_ids" value='${JSON.stringify(jobIds)}'>
        ${action !== 'delete' ? `<input type="hidden" name="bulk_status" value="${action}">` : ''}
    `;
    document.body.appendChild(form);
    form.submit();
});
</script>

</div><!-- /.admin-content -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
