<?php
/**
 * Tulima Solar - Job Portal Admin Job Posting Form
 * Form for creating and editing job listings
 * 
 * Redesigned with:
 * - Dynamic dropdowns from database tables
 * - Card-based layout matching blog_post_edit.php
 * - Form validation and character counters
 * - Hierarchical category support
 */

require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/job-portal-functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'job-post';
$pageTitle = 'Post New Job';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize data
$job = null;
$isEdit = false;
$message = '';
$messageType = 'success';

// Helper function to generate unique job slug
function generateJobSlugUnique($pdo, $title, $excludeId = null) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Limit slug length
    if (strlen($slug) > 180) {
        $slug = substr($slug, 0, 180);
        $slug = preg_replace('/-[^-]*$/', '', $slug);
    }
    
    $baseSlug = $slug;
    $counter = 1;
    
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare('SELECT id FROM jobs WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM jobs WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        
        if (!$stmt->fetch()) {
            return $slug;
        }
        
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

// Fetch dynamic dropdown data
// Wrap in try-catch to handle cases where migration hasn't been run yet

// Job Categories - hierarchical
$allCategories = [];
$categories = [];
try {
    $categoriesStmt = $pdo->query("
        SELECT id, name, parent_id, slug
        FROM job_categories
        WHERE is_active = 1
        ORDER BY display_order, name
    ");
    $allCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table or columns don't exist yet - use empty array
    $allCategories = [];
}

// Build hierarchical categories (only if we have categories)
function buildCategoryTree($categories, $parentId = null, $level = 0) {
    $result = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $prefix = str_repeat('— ', $level);
            $result[] = [
                'id' => $category['id'],
                'name' => $prefix . $category['name'],
                'original_name' => $category['name']
            ];
            $result = array_merge($result, buildCategoryTree($categories, $category['id'], $level + 1));
        }
    }
    return $result;
}
$categories = buildCategoryTree($allCategories);

// Job Locations - with region info
$locations = [];
try {
    $locationsStmt = $pdo->query("
        SELECT id, name, region, is_remote
        FROM job_locations
        WHERE is_active = 1
        ORDER BY is_remote, display_order, name
    ");
    $locations = $locationsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table or columns don't exist yet - use empty array
    $locations = [];
}

// Currencies - with default first
$currencies = [];
try {
    $currenciesStmt = $pdo->query("
        SELECT id, code, name, symbol
        FROM currencies
        WHERE is_active = 1
        ORDER BY is_default DESC, display_order
    ");
    $currencies = $currenciesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table or columns don't exist yet - use empty array
    $currencies = [];
}

// Check if editing existing job
if (!empty($_GET['id'])) {
    $jobId = (int)$_GET['id'];
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($job) {
        $isEdit = true;
        $pageTitle = 'Edit Job';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid security token. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? 'save';
        
        // Get and validate form data
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $locationId = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
        $currencyId = !empty($_POST['currency_id']) ? (int)$_POST['currency_id'] : null;
        
        // Handle text input fallbacks when dropdowns don't exist (before migration)
        $categoryName = trim($_POST['category_name'] ?? '');
        $locationName = trim($_POST['location_name'] ?? '');
        $currencyCode = trim($_POST['currency_code'] ?? '');
        
        $jobType = $_POST['job_type'] ?? 'full-time';
        $employmentType = $_POST['employment_type'] ?? 'permanent';
        $description = trim($_POST['description'] ?? '');
        $requirements = trim($_POST['requirements'] ?? '');
        $responsibilities = trim($_POST['responsibilities'] ?? '');
        $qualifications = trim($_POST['qualifications'] ?? '');
        $salaryMin = !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : null;
        $salaryMax = !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : null;
        $applicationDeadline = !empty($_POST['application_deadline']) ? $_POST['application_deadline'] : null;
        $status = $_POST['status'] ?? 'draft';
        $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
        $isUrgent = !empty($_POST['is_urgent']) ? 1 : 0;
        $tags = trim($_POST['tags'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($title)) {
            $errors[] = 'Job title is required.';
        } elseif (strlen($title) > 255) {
            $errors[] = 'Job title must be 255 characters or less.';
        }
        
        // Category validation - require either category_id (dropdown) or category_name (text input)
        if (empty($categoryId) && empty($categoryName)) {
            $errors[] = 'Please provide a job category.';
        }
        
        if (empty($description)) {
            $errors[] = 'Job description is required.';
        }
        
        // Salary validation
        if ($salaryMin !== null && $salaryMax !== null && $salaryMax < $salaryMin) {
            $errors[] = 'Maximum salary must be greater than minimum salary.';
        }
        
        // Deadline validation
        if (!empty($applicationDeadline)) {
            $deadlineTime = strtotime($applicationDeadline);
            $currentTime = time();
            if ($deadlineTime < $currentTime && !$isEdit) {
                $errors[] = 'Application deadline must be a future date.';
            }
        }
        
        // Validate status
        $allowedStatuses = ['draft', 'active', 'closed'];
        if (!in_array($status, $allowedStatuses)) {
            $status = 'draft';
        }
        
        // Handle publish action
        if ($action === 'publish') {
            $status = 'active';
        }
        
        if (!empty($errors)) {
            $message = implode(' ', $errors);
            $messageType = 'error';
        } else {
            try {
                // Generate slug if not provided
                if (empty($slug)) {
                    $slug = generateJobSlugUnique($pdo, $title, $isEdit ? $job['id'] : null);
                }
                
                if ($isEdit && $job) {
                    // Update existing job
                    $stmt = $pdo->prepare("
                        UPDATE jobs SET 
                            title = ?,
                            slug = ?,
                            description = ?,
                            requirements = ?,
                            responsibilities = ?,
                            qualifications = ?,
                            job_type = ?,
                            employment_type = ?,
                            category_id = ?,
                            location_id = ?,
                            currency_id = ?,
                            salary_min = ?,
                            salary_max = ?,
                            application_deadline = ?,
                            status = ?,
                            is_featured = ?,
                            is_urgent = ?,
                            tags = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([
                        $title,
                        $slug,
                        $description,
                        $requirements,
                        $responsibilities,
                        $qualifications,
                        $jobType,
                        $employmentType,
                        $categoryId,
                        $locationId,
                        $currencyId,
                        $salaryMin,
                        $salaryMax,
                        $applicationDeadline,
                        $status,
                        $isFeatured,
                        $isUrgent,
                        $tags,
                        $job['id']
                    ]);
                    
                    // Log activity
                    logActivity($pdo, $_SESSION['admin_id'], 'job_update', "Updated job: $title");
                    
                    $message = 'Job updated successfully!';
                    $messageType = 'success';
                    
                    // Refresh job data
                    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
                    $stmt->execute([$job['id']]);
                    $job = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                } else {
                    // Create new job
                    $stmt = $pdo->prepare("
                        INSERT INTO jobs (
                            title, slug, description, requirements, responsibilities,
                            qualifications, job_type, employment_type, category_id,
                            location_id, currency_id, salary_min, salary_max,
                            application_deadline, status, is_featured, is_urgent, tags,
                            created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        $title,
                        $slug,
                        $description,
                        $requirements,
                        $responsibilities,
                        $qualifications,
                        $jobType,
                        $employmentType,
                        $categoryId,
                        $locationId,
                        $currencyId,
                        $salaryMin,
                        $salaryMax,
                        $applicationDeadline,
                        $status,
                        $isFeatured,
                        $isUrgent,
                        $tags
                    ]);
                    
                    $newJobId = $pdo->lastInsertId();
                    
                    // Log activity
                    logActivity($pdo, $_SESSION['admin_id'], 'job_create', "Created job: $title");
                    
                    // Redirect to edit mode
                    header("Location: job-post.php?id=$newJobId&message=created");
                    exit;
                }
                
            } catch (Exception $e) {
                $message = 'Error saving job: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Check for URL message
if (isset($_GET['message']) && $_GET['message'] === 'created') {
    $message = 'Job created successfully!';
    $messageType = 'success';
}

// Helper function to get field value
function fieldValue($field, $job) {
    if ($job !== null && isset($job[$field])) {
        return $job[$field];
    }
    return '';
}

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page-specific styles -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/admin/assets/css/admin-jobs.css">

<style>
/* Job Post Form Styles - Matching blog_post_edit.php */
.post-editor-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: var(--space-4, 1rem);
    align-items: start;
}

@media (max-width: 1024px) {
    .post-editor-layout {
        grid-template-columns: 1fr;
    }
}

.post-editor-main {
    display: flex;
    flex-direction: column;
    gap: var(--space-4, 1rem);
}

.post-editor-sidebar {
    display: flex;
    flex-direction: column;
    gap: var(--space-4, 1rem);
    position: sticky;
    top: calc(var(--header-height, 60px) + var(--space-4, 1rem));
}

.form-label-lg {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-2, 0.5rem);
    display: block;
}

.form-control--lg {
    font-size: 1.25rem;
    padding: var(--space-3, 0.75rem) var(--space-4, 1rem);
}

.form-hint {
    font-size: 0.85rem;
    color: var(--text-muted, #6b7280);
    margin-top: var(--space-1, 0.25rem);
}

.form-hint .count {
    font-weight: 600;
}

.required {
    color: var(--color-danger, #ef4444);
}

.slug-input-group {
    display: flex;
    align-items: center;
    gap: 0;
    background: var(--bg-secondary, #f3f4f6);
    border-radius: var(--radius, 0.375rem);
    overflow: hidden;
}

.slug-prefix {
    padding: var(--space-2, 0.5rem) var(--space-3, 0.75rem);
    background: var(--bg-tertiary, #e5e7eb);
    color: var(--text-muted, #6b7280);
    font-size: 0.875rem;
    white-space: nowrap;
    border: 1px solid var(--border-color, #d1d5db);
    border-right: none;
    border-radius: var(--radius, 0.375rem) 0 0 var(--radius, 0.375rem);
}

.slug-input-group .form-control {
    flex: 1;
    border-radius: 0;
    border: 1px solid var(--border-color, #d1d5db);
}

.slug-input-group .btn {
    border-radius: 0 var(--radius, 0.375rem) var(--radius, 0.375rem) 0;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4, 1rem);
}

@media (max-width: 640px) {
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
}

.form-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: var(--space-4, 1rem);
}

@media (max-width: 768px) {
    .form-grid-3 {
        grid-template-columns: 1fr;
    }
}

/* Checkbox styling */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--space-2, 0.5rem);
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--color-primary, #3b82f6);
}

/* Validation states */
.form-control.is-invalid {
    border-color: var(--color-danger, #ef4444) !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.invalid-feedback {
    display: none;
    color: var(--color-danger, #ef4444);
    font-size: 0.875rem;
    margin-top: var(--space-1, 0.25rem);
}

.form-control.is-invalid ~ .invalid-feedback {
    display: block;
}

/* Preview button */
.btn-preview {
    background: var(--color-info, #0ea5e9);
    color: white;
}

.btn-preview:hover {
    background: var(--color-info-dark, #0284c7);
}

/* Location badge */
.location-remote {
    color: var(--color-success, #10b981);
    font-weight: 500;
}

/* Currency symbol in salary input */
.input-with-currency {
    position: relative;
}

.input-with-currency .currency-symbol {
    position: absolute;
    left: var(--space-3, 0.75rem);
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted, #6b7280);
    font-weight: 600;
    pointer-events: none;
}

.input-with-currency input {
    padding-left: 2.5rem;
}

/* Textarea with counter */
.textarea-wrapper {
    position: relative;
}

.textarea-counter {
    position: absolute;
    bottom: var(--space-2, 0.5rem);
    right: var(--space-3, 0.75rem);
    font-size: 0.75rem;
    color: var(--text-muted, #6b7280);
    background: var(--bg-secondary, #f3f4f6);
    padding: 2px 6px;
    border-radius: var(--radius-sm, 0.25rem);
}

/* Tooltip */
.tooltip-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--bg-tertiary, #e5e7eb);
    color: var(--text-muted, #6b7280);
    font-size: 10px;
    font-weight: 600;
    cursor: help;
    margin-left: var(--space-1, 0.25rem);
    vertical-align: middle;
}

.tooltip-icon:hover {
    background: var(--color-primary, #3b82f6);
    color: white;
}

/* Form dirty indicator */
.form-dirty-indicator {
    display: none;
    padding: var(--space-2, 0.5rem) var(--space-3, 0.75rem);
    background: var(--color-warning, #f59e0b);
    color: white;
    border-radius: var(--radius, 0.375rem);
    font-size: 0.875rem;
    font-weight: 500;
    position: fixed;
    bottom: var(--space-4, 1rem);
    left: 50%;
    transform: translateX(-50%);
    z-index: 1000;
    box-shadow: var(--shadow-lg, 0 10px 15px -3px rgba(0, 0, 0, 0.1));
}

.form-dirty-indicator.visible {
    display: flex;
    align-items: center;
    gap: var(--space-2, 0.5rem);
}
</style>

<!-- Page Header -->
<div class="admin-content">
  <div class="page-header-enhanced" style="background: linear-gradient(135deg, var(--color-primary, #3b82f6) 0%, var(--color-primary-dark, #1d4ed8) 100%); padding: var(--space-6, 1.5rem) var(--space-8, 2rem); margin-bottom: var(--space-6, 1.5rem); border-radius: var(--radius-lg, 0.5rem);">
    <div class="page-header-enhanced__content">
      <h1 class="page-header-enhanced__title" style="color: white; margin: 0; font-size: 1.75rem; font-weight: 700;">
        <?php echo $isEdit ? 'Edit Job' : 'Post New Job'; ?>
      </h1>
      <p class="page-header-enhanced__subtitle" style="color: rgba(255,255,255,0.8); margin-top: var(--space-1, 0.25rem);">
        <?php echo $isEdit ? 'Update job listing details' : 'Create a new job listing'; ?>
      </p>
    </div>
    <div class="page-header-enhanced__actions" style="display: flex; gap: var(--space-3, 0.75rem);">
      <a href="<?php echo BASE_PATH; ?>/admin/jobs.php" class="btn btn--outline" style="color: white; border-color: rgba(255,255,255,0.3);">
        <svg viewBox="0 0 24 24" width="16" height="16"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Jobs
      </a>
      <?php if ($isEdit && $job && $job['status'] === 'active'): ?>
        <a href="<?php echo BASE_PATH; ?>/job-portal/pages/job-detail.php?id=<?php echo $job['id']; ?>" target="_blank" class="btn btn--secondary">
          <svg viewBox="0 0 24 24" width="16" height="16"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          View Job
        </a>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($message): ?>
    <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="job-message">
      <?php echo htmlspecialchars($message); ?>
      <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
    </div>
  <?php endif; ?>

<form method="post" id="job-form" class="post-editor-form">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
  <input type="hidden" name="action" id="form-action" value="save">
  
  <div class="post-editor-layout">
    <!-- Main Content Area -->
    <div class="post-editor-main">
      
      <!-- Job Title -->
      <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card__body">
          <div class="form-group">
            <label for="title" class="form-label-lg">Job Title <span class="required">*</span></label>
            <input type="text" 
                   id="title" 
                   name="title" 
                   class="form-control form-control--lg" 
                   value="<?php echo htmlspecialchars(fieldValue('title', $job)); ?>" 
                   placeholder="e.g., Senior Solar Engineer"
                   required
                   maxlength="255">
            <div class="form-hint">
              <span id="title-count"><?php echo strlen(fieldValue('title', $job)); ?></span> / 255 characters
            </div>
          </div>
          
          <div class="form-group" style="margin-top: var(--space-4, 1rem);">
            <label for="slug">URL Slug</label>
            <div class="slug-input-group">
              <span class="slug-prefix"><?php echo htmlspecialchars(BASE_PATH); ?>/jobs/</span>
              <input type="text" 
                     id="slug" 
                     name="slug" 
                     class="form-control" 
                     value="<?php echo htmlspecialchars(fieldValue('slug', $job)); ?>" 
                     placeholder="auto-generated-from-title"
                     pattern="[a-z0-9-]+"
                     title="Only lowercase letters, numbers, and hyphens allowed">
              <button type="button" class="btn btn--outline btn--sm" onclick="regenerateSlug()" title="Regenerate from title">
                <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
              </button>
            </div>
            <div class="form-hint">Auto-generated from title. Use lowercase letters, numbers, and hyphens only.</div>
          </div>
        </div>
      </div>
      
      <!-- Job Classification -->
      <div class="admin-card">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            Job Classification
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-grid-2">
            <div class="form-group">
              <label for="category_id">Job Category <span class="required">*</span></label>
              <?php if (!empty($categories)): ?>
                <select id="category_id" name="category_id" class="form-control" required>
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo fieldValue('category_id', $job) == $category['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-hint">
                  <a href="<?php echo BASE_PATH; ?>/admin/job-categories.php" target="_blank">Manage categories</a>
                </div>
              <?php else: ?>
                <input type="text"
                       id="category_name"
                       name="category_name"
                       class="form-control"
                       value="<?php echo htmlspecialchars(fieldValue('category_name', $job)); ?>"
                       placeholder="Enter category name">
                <input type="hidden" name="category_id" value="">
                <div class="form-hint">
                  <span style="color: var(--color-warning, #f59e0b);">⚠️ Run migration to enable category dropdown.</span>
                  <a href="<?php echo BASE_PATH; ?>/admin/job-categories.php" target="_blank">Setup categories</a>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="form-group">
              <label for="location_id">Location</label>
              <?php if (!empty($locations)): ?>
                <select id="location_id" name="location_id" class="form-control">
                  <option value="">Select Location</option>
                  <?php foreach ($locations as $location): ?>
                    <option value="<?php echo $location['id']; ?>"
                            data-remote="<?php echo $location['is_remote']; ?>"
                            <?php echo fieldValue('location_id', $job) == $location['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($location['name']); ?>
                      <?php if ($location['is_remote']): ?>
                        (Remote)
                      <?php elseif ($location['region']): ?>
                        (<?php echo htmlspecialchars($location['region']); ?>)
                      <?php endif; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-hint">
                  <a href="<?php echo BASE_PATH; ?>/admin/job-locations.php" target="_blank">Manage locations</a>
                </div>
              <?php else: ?>
                <input type="text"
                       id="location_name"
                       name="location_name"
                       class="form-control"
                       value="<?php echo htmlspecialchars(fieldValue('location_name', $job)); ?>"
                       placeholder="Enter location name">
                <input type="hidden" name="location_id" value="">
                <div class="form-hint">
                  <span style="color: var(--color-warning, #f59e0b);">⚠️ Run migration to enable location dropdown.</span>
                  <a href="<?php echo BASE_PATH; ?>/admin/job-locations.php" target="_blank">Setup locations</a>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="form-grid-2" style="margin-top: var(--space-4, 1rem);">
            <div class="form-group">
              <label for="job_type">Job Type <span class="required">*</span></label>
              <select id="job_type" name="job_type" class="form-control" required>
                <option value="full-time" <?php echo fieldValue('job_type', $job) === 'full-time' ? 'selected' : ''; ?>>Full-time</option>
                <option value="part-time" <?php echo fieldValue('job_type', $job) === 'part-time' ? 'selected' : ''; ?>>Part-time</option>
                <option value="contract" <?php echo fieldValue('job_type', $job) === 'contract' ? 'selected' : ''; ?>>Contract</option>
                <option value="internship" <?php echo fieldValue('job_type', $job) === 'internship' ? 'selected' : ''; ?>>Internship</option>
                <option value="volunteer" <?php echo fieldValue('job_type', $job) === 'volunteer' ? 'selected' : ''; ?>>Volunteer</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="employment_type">Employment Type <span class="required">*</span></label>
              <select id="employment_type" name="employment_type" class="form-control" required>
                <option value="permanent" <?php echo fieldValue('employment_type', $job) === 'permanent' ? 'selected' : ''; ?>>Permanent</option>
                <option value="temporary" <?php echo fieldValue('employment_type', $job) === 'temporary' ? 'selected' : ''; ?>>Temporary</option>
                <option value="seasonal" <?php echo fieldValue('employment_type', $job) === 'seasonal' ? 'selected' : ''; ?>>Seasonal</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Job Description -->
      <div class="admin-card">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="none" stroke="currentColor" stroke-width="2"/><polyline points="14 2 14 8 20 8" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            Job Description <span class="required">*</span>
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <textarea id="description" 
                      name="description" 
                      class="form-control" 
                      rows="10"
                      placeholder="Describe the role, what the candidate will do, and what makes this opportunity exciting..."
                      required><?php echo htmlspecialchars(fieldValue('description', $job)); ?></textarea>
            <div class="form-hint">
              <span id="description-count"><?php echo strlen(fieldValue('description', $job)); ?></span> characters
            </div>
          </div>
        </div>
      </div>
      
      <!-- Requirements -->
      <div class="admin-card">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M9 11l3 3L22 4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            Requirements
            <span class="tooltip-icon" title="List the skills, experience, and qualifications required">?</span>
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <textarea id="requirements" 
                      name="requirements" 
                      class="form-control" 
                      rows="6"
                      placeholder="• Bachelor's degree in relevant field&#10;• 3+ years of experience&#10;• Strong communication skills&#10;• Proficiency in..."><?php echo htmlspecialchars(fieldValue('requirements', $job)); ?></textarea>
            <div class="form-hint">List each requirement on a new line. Use bullet points for clarity.</div>
          </div>
        </div>
      </div>
      
      <!-- Responsibilities -->
      <div class="admin-card">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" fill="none" stroke="currentColor" stroke-width="2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            Responsibilities
            <span class="tooltip-icon" title="List the key duties and responsibilities">?</span>
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <textarea id="responsibilities" 
                      name="responsibilities" 
                      class="form-control" 
                      rows="6"
                      placeholder="• Lead and manage projects&#10;• Collaborate with cross-functional teams&#10;• Develop and implement strategies&#10;• Report on progress..."><?php echo htmlspecialchars(fieldValue('responsibilities', $job)); ?></textarea>
            <div class="form-hint">List each responsibility on a new line. Use bullet points for clarity.</div>
          </div>
        </div>
      </div>
      
      <!-- Qualifications (Optional) -->
      <div class="admin-card">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="2"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            Qualifications (Optional)
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <textarea id="qualifications" 
                      name="qualifications" 
                      class="form-control" 
                      rows="4"
                      placeholder="• Professional certifications&#10;• Advanced degrees&#10;• Specialized training..."><?php echo htmlspecialchars(fieldValue('qualifications', $job)); ?></textarea>
          </div>
        </div>
      </div>
      
    </div>
    
    <!-- Sidebar -->
    <div class="post-editor-sidebar">
      
      <!-- Publish Box -->
      <div class="admin-card">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            Publish
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
              <option value="draft" <?php echo fieldValue('status', $job) === 'draft' ? 'selected' : ''; ?>>Draft</option>
              <option value="active" <?php echo fieldValue('status', $job) === 'active' ? 'selected' : ''; ?>>Active (Published)</option>
              <option value="closed" <?php echo fieldValue('status', $job) === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
            <div class="form-hint">Only active jobs are visible to candidates.</div>
          </div>
          
          <div class="form-group">
            <label for="application_deadline">Application Deadline</label>
            <input type="datetime-local" 
                   id="application_deadline" 
                   name="application_deadline" 
                   class="form-control"
                   value="<?php echo !empty($job['application_deadline']) ? date('Y-m-d\TH:i', strtotime($job['application_deadline'])) : ''; ?>"
                   min="<?php echo date('Y-m-d\TH:i'); ?>">
            <div class="form-hint">Leave empty for no deadline.</div>
          </div>
          
          <?php if ($isEdit && $job): ?>
          <div class="post-info" style="padding: var(--space-3, 0.75rem); background: var(--bg-secondary, #f3f4f6); border-radius: var(--radius, 0.375rem); margin-top: var(--space-3, 0.75rem);">
            <p style="margin: 0; font-size: 0.875rem; color: var(--text-muted, #6b7280);">
              <strong>Created:</strong> <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
            </p>
            <p style="margin: var(--space-1, 0.25rem) 0 0; font-size: 0.875rem; color: var(--text-muted, #6b7280);">
              <strong>Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($job['updated_at'])); ?>
            </p>
          </div>
          <?php endif; ?>
          
          <div style="margin-top: var(--space-4, 1rem); display: flex; flex-direction: column; gap: var(--space-2, 0.5rem);">
            <button type="submit" class="btn btn--primary btn--block" id="save-btn">
              <svg viewBox="0 0 24 24" width="16" height="16"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" fill="none" stroke="currentColor" stroke-width="2"/><polyline points="17 21 17 13 7 13 7 21" fill="none" stroke="currentColor" stroke-width="2"/><polyline points="7 3 7 8 15 8" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              <?php echo $isEdit ? 'Update Job' : 'Save Draft'; ?>
            </button>
            <button type="submit" class="btn btn--success btn--block" onclick="publishJob(event)">
              <svg viewBox="0 0 24 24" width="16" height="16"><path d="M22 2L11 13" fill="none" stroke="currentColor" stroke-width="2"/><path d="M22 2L15 22L11 13L2 9L22 2Z" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              <?php echo ($isEdit && $job && $job['status'] === 'active') ? 'Update & Keep Published' : 'Save & Publish'; ?>
            </button>
            <?php if ($isEdit && $job && $job['status'] === 'active'): ?>
            <button type="button" class="btn btn-preview btn--block" onclick="previewJob()">
              <svg viewBox="0 0 24 24" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              Preview
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Compensation -->
      <div class="admin-card">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" width="18" height="18"><line x1="12" y1="1" x2="12" y2="23" stroke="currentColor" stroke-width="2"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            Compensation
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <label for="currency_id">Currency</label>
            <?php if (!empty($currencies)): ?>
              <select id="currency_id" name="currency_id" class="form-control">
                <option value="">Select Currency</option>
                <?php foreach ($currencies as $currency): ?>
                  <option value="<?php echo $currency['id']; ?>"
                          data-symbol="<?php echo htmlspecialchars($currency['symbol']); ?>"
                          <?php echo fieldValue('currency_id', $job) == $currency['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($currency['code'] . ' - ' . $currency['name'] . ' (' . $currency['symbol'] . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-hint">
                <a href="<?php echo BASE_PATH; ?>/admin/currencies.php" target="_blank">Manage currencies</a>
              </div>
            <?php else: ?>
              <input type="text"
                     id="currency_code"
                     name="currency_code"
                     class="form-control"
                     value="<?php echo htmlspecialchars(fieldValue('currency_code', $job)); ?>"
                     placeholder="e.g., USD, KES, UGX"
                     maxlength="3"
                     style="max-width: 150px;">
              <input type="hidden" name="currency_id" value="">
              <div class="form-hint">
                <span style="color: var(--color-warning, #f59e0b);">⚠️ Run migration to enable currency dropdown.</span>
                <a href="<?php echo BASE_PATH; ?>/admin/currencies.php" target="_blank">Setup currencies</a>
              </div>
            <?php endif; ?>
          </div>
          
          <div class="form-grid-2" style="margin-top: var(--space-3, 0.75rem);">
            <div class="form-group">
              <label for="salary_min">Min Salary</label>
              <input type="number" 
                     id="salary_min" 
                     name="salary_min" 
                     class="form-control" 
                     value="<?php echo fieldValue('salary_min', $job); ?>"
                     placeholder="0"
                     min="0"
                     step="100">
            </div>
            
            <div class="form-group">
              <label for="salary_max">Max Salary</label>
              <input type="number" 
                     id="salary_max" 
                     name="salary_max" 
                     class="form-control" 
                     value="<?php echo fieldValue('salary_max', $job); ?>"
                     placeholder="0"
                     min="0"
                     step="100">
            </div>
          </div>
          
          <div id="salary-error" class="invalid-feedback" style="display: none; margin-top: var(--space-2, 0.5rem);">
            Maximum salary must be greater than minimum salary.
          </div>
          
          <div class="form-hint" style="margin-top: var(--space-2, 0.5rem);">
            Leave empty to not display salary range.
          </div>
        </div>
      </div>
      
      <!-- Job Options -->
      <div class="admin-card">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" fill="none" stroke="currentColor" stroke-width="2"/></svg>
            Options
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="is_featured" <?php echo !empty($job['is_featured']) ? 'checked' : ''; ?>>
              <span>Featured Job</span>
            </label>
            <div class="form-hint" style="margin-left: 26px;">Highlight this job on the homepage</div>
          </div>
          
          <div class="form-group" style="margin-top: var(--space-3, 0.75rem);">
            <label class="checkbox-label">
              <input type="checkbox" name="is_urgent" <?php echo !empty($job['is_urgent']) ? 'checked' : ''; ?>>
              <span>Urgent Hiring</span>
            </label>
            <div class="form-hint" style="margin-left: 26px;">Mark as urgent for quick filling</div>
          </div>
        </div>
      </div>
      
      <!-- Tags -->
      <div class="admin-card">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" fill="none" stroke="currentColor" stroke-width="2"/><line x1="7" y1="7" x2="7.01" y2="7" stroke="currentColor" stroke-width="2"/></svg>
            Tags
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <input type="text" 
                   id="tags" 
                   name="tags" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars(fieldValue('tags', $job)); ?>"
                   placeholder="solar, engineering, renewable">
            <div class="form-hint">Comma-separated keywords to help candidates find this job.</div>
          </div>
        </div>
      </div>
      
    </div>
  </div>
</form>
</div><!-- /.admin-content -->

<!-- Form Dirty Indicator -->
<div class="form-dirty-indicator" id="form-dirty-indicator">
  <svg viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2"/></svg>
  You have unsaved changes
</div>

<script src="<?php echo BASE_PATH; ?>/admin/assets/js/admin-jobs.js"></script>
<script>
// Store original form data for dirty detection
let originalFormData;
let isFormDirty = false;

document.addEventListener('DOMContentLoaded', function() {
    // Store original form data
    originalFormData = new FormData(document.getElementById('job-form'));
    
    // Initialize character counters
    initCharacterCounters();
    
    // Initialize slug generation
    initSlugGeneration();
    
    // Initialize salary validation
    initSalaryValidation();
    
    // Initialize deadline validation
    initDeadlineValidation();
    
    // Initialize form dirty detection
    initFormDirtyDetection();
    
    // Initialize tooltips
    initTooltips();
});

/**
 * Initialize character counters
 */
function initCharacterCounters() {
    const counters = [
        { input: 'title', counter: 'title-count', max: 255 },
        { input: 'description', counter: 'description-count', max: null }
    ];
    
    counters.forEach(item => {
        const input = document.getElementById(item.input);
        const counter = document.getElementById(item.counter);
        
        if (input && counter) {
            const updateCounter = () => {
                const length = input.value.length;
                counter.textContent = length;
                
                if (item.max) {
                    if (length > item.max * 0.9) {
                        counter.style.color = 'var(--color-danger, #ef4444)';
                    } else if (length > item.max * 0.7) {
                        counter.style.color = 'var(--color-warning, #f59e0b)';
                    } else {
                        counter.style.color = 'var(--text-muted, #6b7280)';
                    }
                }
            };
            
            input.addEventListener('input', updateCounter);
            updateCounter();
        }
    });
}

/**
 * Initialize slug generation from title
 */
function initSlugGeneration() {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function() {
            // Only auto-generate if slug is empty or matches previous title pattern
            if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
                slugInput.value = generateSlug(this.value);
                slugInput.dataset.autoGenerated = 'true';
            }
        });
        
        slugInput.addEventListener('input', function() {
            this.dataset.autoGenerated = 'false';
        });
    }
}

/**
 * Generate URL-friendly slug
 */
function generateSlug(text) {
    return text.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .substring(0, 180);
}

/**
 * Regenerate slug from current title
 */
function regenerateSlug() {
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput) {
        slugInput.value = generateSlug(titleInput.value);
        slugInput.dataset.autoGenerated = 'true';
        slugInput.focus();
    }
}

/**
 * Initialize salary validation
 */
function initSalaryValidation() {
    const minInput = document.getElementById('salary_min');
    const maxInput = document.getElementById('salary_max');
    const errorDiv = document.getElementById('salary-error');
    
    const validateSalary = () => {
        const min = parseFloat(minInput.value) || 0;
        const max = parseFloat(maxInput.value) || 0;
        
        if (max > 0 && min > 0 && max < min) {
            maxInput.classList.add('is-invalid');
            errorDiv.style.display = 'block';
            return false;
        } else {
            maxInput.classList.remove('is-invalid');
            errorDiv.style.display = 'none';
            return true;
        }
    };
    
    if (minInput && maxInput) {
        minInput.addEventListener('input', validateSalary);
        maxInput.addEventListener('input', validateSalary);
    }
    
    // Store validation function for form submission
    window.validateSalary = validateSalary;
}

/**
 * Initialize deadline validation
 */
function initDeadlineValidation() {
    const deadlineInput = document.getElementById('application_deadline');
    
    if (deadlineInput) {
        // Set minimum date to now
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000;
        const localISOTime = new Date(now - offset).toISOString().slice(0, 16);
        deadlineInput.setAttribute('min', localISOTime);
    }
}

/**
 * Initialize form dirty detection
 */
function initFormDirtyDetection() {
    const form = document.getElementById('job-form');
    const indicator = document.getElementById('form-dirty-indicator');
    
    if (form && indicator) {
        form.addEventListener('input', function() {
            isFormDirty = true;
            indicator.classList.add('visible');
        });
        
        form.addEventListener('submit', function() {
            isFormDirty = false;
            indicator.classList.remove('visible');
        });
    }
    
    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (isFormDirty) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('.tooltip-icon');
    
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            this.style.position = 'relative';
        });
    });
}

/**
 * Publish job (set status to active and submit)
 */
function publishJob(event) {
    event.preventDefault();
    
    // Validate salary first
    if (window.validateSalary && !window.validateSalary()) {
        return false;
    }
    
    // Validate required fields
    const form = document.getElementById('job-form');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        showFormError('Please fill in all required fields.');
        return false;
    }
    
    // Set action to publish and submit
    document.getElementById('form-action').value = 'publish';
    form.submit();
}

/**
 * Preview job in new window
 */
function previewJob() {
    const jobId = <?php echo $isEdit && $job ? $job['id'] : 'null'; ?>;
    if (jobId) {
        window.open('<?php echo BASE_PATH; ?>/job-portal/pages/job-detail.php?id=' + jobId, '_blank');
    }
}

/**
 * Show form error message
 */
function showFormError(message) {
    // Remove existing error
    const existingError = document.querySelector('.form-error-toast');
    if (existingError) {
        existingError.remove();
    }
    
    // Create error toast
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error-toast';
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: var(--color-danger, #ef4444);
        color: white;
        border-radius: var(--radius, 0.375rem);
        box-shadow: var(--shadow-lg, 0 10px 15px -3px rgba(0, 0, 0, 0.1));
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    `;
    errorDiv.innerHTML = `
        <svg viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2"/></svg>
        <span>${message}</span>
    `;
    
    document.body.appendChild(errorDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        errorDiv.style.opacity = '0';
        errorDiv.style.transform = 'translateX(100%)';
        errorDiv.style.transition = 'all 0.3s ease';
        setTimeout(() => errorDiv.remove(), 300);
    }, 5000);
}

// Remove error styling on input
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('input', function() {
        this.classList.remove('is-invalid');
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
