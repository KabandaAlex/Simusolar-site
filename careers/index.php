<?php
/**
 * Tulima Solar - Careers Listing Page
 * Modern, clean redesign using blog-style layout patterns
 * 
 * @version 2.0.0
 * @created 2026-02-26
 */

session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/job-portal-functions.php';

$pdo = getDb();

// Get site settings
$siteName = getSetting($pdo, 'site_name') ?: 'Tulima Solar';
$pageTitle = 'Careers';

// Get filter parameters from URL
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : null;
$locationId = isset($_GET['location']) ? (int)$_GET['location'] : null;
$jobType = isset($_GET['type']) ? trim($_GET['type']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;

// Build filters array
$filters = [
    'status' => 'active',
    'exclude_expired' => true
];

if ($categorySlug) {
    $filters['category_slug'] = $categorySlug;
}

if ($locationId) {
    $filters['location_id'] = $locationId;
}

if ($jobType) {
    $filters['job_type'] = $jobType;
}

if ($search) {
    $filters['search'] = $search;
}

// Get jobs using helper function
$result = getJobs($pdo, $filters, $page, $perPage);
$jobs = $result['jobs'];
$totalJobs = $result['total'];
$totalPages = $result['total_pages'];

// Get job tags for each job
foreach ($jobs as &$job) {
    $job['tags'] = getJobTagsForJob($pdo, $job['id']);
}
unset($job);

// Get sidebar data
$categories = getJobCategories($pdo, true);
$locations = getJobLocations($pdo, true);

// Get statistics
$stats = getJobStats($pdo);

// Helper function to build pagination URLs
function buildPageUrl($pageNum, $categorySlug, $locationId, $jobType, $search) {
    $params = [];
    if ($categorySlug) $params['category'] = $categorySlug;
    if ($locationId) $params['location'] = $locationId;
    if ($jobType) $params['type'] = $jobType;
    if ($search) $params['search'] = $search;
    if ($pageNum > 1) $params['page'] = $pageNum;
    
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Blog/Careers CSS -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/pages/blog.css">
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/pages/careers.css">

<!-- Careers Hero Section -->
<section class="careers-hero">
    <div class="o-container">
        <div class="careers-hero__content">
            <!-- <div class="careers-hero__badge">
                <i class="fas fa-briefcase"></i>
                Join Our Team
            </div> -->
            <h1 class="careers-hero__title">Build Your Career at <br>Simusolar</h1>
            <p class="careers-hero__subtitle">
                Join a passionate team dedicated to powering a sustainable future through innovative solar energy solutions.
            </p>
            
            <!-- Search Form -->
            <div class="careers-hero__search">
                <form method="GET" action="index.php" class="careers-hero__search-form">
                    <input type="text" name="search" class="careers-hero__search-input" placeholder="Search positions..." value="<?php echo e($search ?? ''); ?>">
                    <?php if ($categorySlug): ?><input type="hidden" name="category" value="<?php echo e($categorySlug); ?>"><?php endif; ?>
                    <?php if ($locationId): ?><input type="hidden" name="location" value="<?php echo e($locationId); ?>"><?php endif; ?>
                    <?php if ($jobType): ?><input type="hidden" name="type" value="<?php echo e($jobType); ?>"><?php endif; ?>
                    <button type="submit" class="careers-hero__search-btn" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="careers-stats">
    <div class="o-container">
        <div class="careers-stats__grid">
            <div class="careers-stats__item">
                <span class="careers-stats__number"><?php echo number_format($stats['active_jobs']); ?></span>
                <span class="careers-stats__label">Open Positions</span>
            </div>
            <div class="careers-stats__item">
                <span class="careers-stats__number"><?php echo number_format($stats['total_applications']); ?></span>
                <span class="careers-stats__label">Applications</span>
            </div>
            <div class="careers-stats__item">
                <span class="careers-stats__number">100%</span>
                <span class="careers-stats__label">Remote Friendly</span>
            </div>
            <div class="careers-stats__item">
                <span class="careers-stats__number"><?php echo number_format($stats['locations']); ?>+</span>
                <span class="careers-stats__label">Locations</span>
            </div>
        </div>
    </div>
</section>

<!-- Careers Content -->
<section class="careers-content">
    <div class="o-container">
        <div class="blog-layout">
            <!-- Main Content Area -->
            <main class="blog-main">
                <!-- Jobs Header -->
                <div class="blog-posts-header">
                    <div class="blog-posts-count">
                        <strong><?php echo number_format($totalJobs); ?></strong> <?php echo $totalJobs === 1 ? 'position' : 'positions'; ?> found
                    </div>
                    <div class="careers-filter">
                        <?php if ($categorySlug || $locationId || $jobType || $search): ?>
                            <a href="index.php" class="c-btn c-btn--outline" style="padding: var(--spacing-2) var(--spacing-4);">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Jobs -->
                <?php if (empty($jobs)): ?>
                    <!-- Empty State -->
                    <div class="blog-empty">
                        <div class="blog-empty__icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h2 class="blog-empty__title">No positions found</h2>
                        <p class="blog-empty__text">
                            <?php if ($search): ?>
                                We couldn't find any positions matching "<?php echo e($search); ?>". Try different keywords or browse all positions.
                            <?php elseif ($categorySlug || $locationId || $jobType): ?>
                                No positions match the selected filters. Try selecting different options or clearing your filters.
                            <?php else: ?>
                                There are no open positions at the moment. Check back soon!
                            <?php endif; ?>
                        </p>
                        <a href="index.php" class="c-btn c-btn--primary">
                            <i class="fas fa-arrow-left"></i> View All Positions
                        </a>
                    </div>
                <?php else: ?>
                    <div class="careers-jobs-list">
                        <?php foreach ($jobs as $job): ?>
                            <?php
                            // Use slug if available, otherwise use ID
                            $jobUrl = !empty($job['slug'])
                                ? 'job.php?slug=' . e($job['slug'])
                                : 'job.php?id=' . (int)$job['id'];
                            ?>
                            <article class="job-card <?php echo !empty($job['is_featured']) ? 'job-card--featured' : ''; ?> <?php echo !empty($job['is_urgent']) ? 'job-card--urgent' : ''; ?>">
                                <div class="job-card__header">
                                    <div class="job-card__title-section">
                                        <h2 class="job-card__title">
                                            <a href="<?php echo $jobUrl; ?>">
                                                <?php echo e($job['title']); ?>
                                            </a>
                                        </h2>
                                        <div class="job-card__badges">
                                            <?php if (!empty($job['is_featured'])): ?>
                                                <span class="job-card__badge job-card__badge--featured">
                                                    <i class="fas fa-star"></i> Featured
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($job['is_urgent'])): ?>
                                                <span class="job-card__badge job-card__badge--urgent">
                                                    <i class="fas fa-bolt"></i> Urgent
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($job['category_name']): ?>
                                        <a href="index.php?category=<?php echo e($job['category_slug']); ?>" class="job-card__category">
                                            <?php echo e($job['category_name']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="job-card__body">
                                    <p class="job-card__description">
                                        <?php 
                                        $desc = strip_tags($job['description']);
                                        echo e(substr($desc, 0, 200) . (strlen($desc) > 200 ? '...' : '')); 
                                        ?>
                                    </p>
                                    
                                    <div class="job-card__meta">
                                        <span class="job-card__meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo e($job['location_name'] ?: 'Remote'); ?>
                                            <?php if (!empty($job['is_remote'])): ?>
                                                <small>(Remote)</small>
                                            <?php endif; ?>
                                        </span>
                                        <span class="job-card__meta-item">
                                            <i class="fas fa-clock"></i>
                                            <?php echo e(getJobTypeLabel($job['job_type'])); ?>
                                        </span>
                                        <span class="job-card__meta-item job-card__salary">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <?php echo e(formatSalary($job['salary_min'] ?? null, $job['salary_max'] ?? null, $job['salary_currency'] ?? 'USD')); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($job['tags'])): ?>
                                        <div class="job-card__tags">
                                            <?php foreach (array_slice($job['tags'], 0, 4) as $tag): ?>
                                                <span class="job-card__tag"><?php echo e($tag['name']); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="job-card__footer">
                                    <span class="job-card__posted">
                                        <i class="fas fa-calendar"></i>
                                        Posted <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                    </span>
                                    <?php
                                    // Use slug if available, otherwise use ID
                                    $jobUrl = !empty($job['slug'])
                                        ? 'job.php?slug=' . e($job['slug'])
                                        : 'job.php?id=' . (int)$job['id'];
                                    ?>
                                    <a href="<?php echo $jobUrl; ?>" class="c-btn c-btn--primary c-btn--sm">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="pagination" aria-label="Careers pagination">
                            <ul class="pagination__list">
                                <!-- Previous -->
                                <?php if ($page > 1): ?>
                                    <li>
                                        <a href="<?php echo buildPageUrl($page - 1, $categorySlug, $locationId, $jobType, $search); ?>" 
                                           class="pagination__link pagination__link--prev"
                                           aria-label="Previous page">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Page Numbers -->
                                <?php 
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1): ?>
                                    <li>
                                        <a href="<?php echo buildPageUrl(1, $categorySlug, $locationId, $jobType, $search); ?>" 
                                           class="pagination__link">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="pagination__ellipsis">...</li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li>
                                        <a href="<?php echo buildPageUrl($i, $categorySlug, $locationId, $jobType, $search); ?>" 
                                           class="pagination__link <?php echo $i === $page ? 'pagination__link--active' : ''; ?>"
                                           <?php echo $i === $page ? 'aria-current="page"' : ''; ?>>
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="pagination__ellipsis">...</li>
                                    <?php endif; ?>
                                    <li>
                                        <a href="<?php echo buildPageUrl($totalPages, $categorySlug, $locationId, $jobType, $search); ?>" 
                                           class="pagination__link"><?php echo $totalPages; ?></a>
                                    </li>
                                <?php endif; ?>

                                <!-- Next -->
                                <?php if ($page < $totalPages): ?>
                                    <li>
                                        <a href="<?php echo buildPageUrl($page + 1, $categorySlug, $locationId, $jobType, $search); ?>" 
                                           class="pagination__link pagination__link--next"
                                           aria-label="Next page">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>

            <!-- Sidebar -->
            <aside class="blog-sidebar">
                <!-- Categories Widget -->
                <div class="sidebar-widget">
                    <h3 class="sidebar-widget__title">
                        <i class="fas fa-folder"></i>
                        Departments
                    </h3>
                    <ul class="sidebar-categories__list">
                        <li class="sidebar-categories__item">
                            <a href="index.php" class="sidebar-categories__link <?php echo !$categorySlug ? 'sidebar-categories__link--active' : ''; ?>">
                                <span>All Positions</span>
                                <span class="sidebar-categories__count"><?php echo $stats['active_jobs']; ?></span>
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <?php 
                            // Get job count for this category
                            $catCountStmt = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE category_id = ? AND status = \'active\' AND (application_deadline IS NULL OR application_deadline >= NOW())');
                            $catCountStmt->execute([$cat['id']]);
                            $catCount = (int)$catCountStmt->fetchColumn();
                            ?>
                            <?php if ($catCount > 0): ?>
                                <li class="sidebar-categories__item">
                                    <a href="index.php?category=<?php echo e($cat['slug']); ?>" 
                                       class="sidebar-categories__link <?php echo $categorySlug === $cat['slug'] ? 'sidebar-categories__link--active' : ''; ?>">
                                        <span><?php echo e($cat['name']); ?></span>
                                        <span class="sidebar-categories__count"><?php echo $catCount; ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Locations Widget -->
                <div class="sidebar-widget">
                    <h3 class="sidebar-widget__title">
                        <i class="fas fa-map-marker-alt"></i>
                        Locations
                    </h3>
                    <ul class="sidebar-categories__list">
                        <?php foreach ($locations as $loc): ?>
                            <?php 
                            // Get job count for this location
                            $locCountStmt = $pdo->prepare('SELECT COUNT(*) FROM jobs WHERE location_id = ? AND status = \'active\' AND (application_deadline IS NULL OR application_deadline >= NOW())');
                            $locCountStmt->execute([$loc['id']]);
                            $locCount = (int)$locCountStmt->fetchColumn();
                            ?>
                            <?php if ($locCount > 0): ?>
                                <li class="sidebar-categories__item">
                                    <a href="index.php?location=<?php echo $loc['id']; ?>" 
                                       class="sidebar-categories__link <?php echo $locationId === (int)$loc['id'] ? 'sidebar-categories__link--active' : ''; ?>">
                                        <span><?php echo e($loc['name']); ?></span>
                                        <span class="sidebar-categories__count"><?php echo $locCount; ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Job Type Widget -->
                <div class="sidebar-widget">
                    <h3 class="sidebar-widget__title">
                        <i class="fas fa-clock"></i>
                        Job Type
                    </h3>
                    <ul class="sidebar-categories__list">
                        <?php 
                        $jobTypes = ['full-time' => 'Full Time', 'part-time' => 'Part Time', 'contract' => 'Contract', 'internship' => 'Internship'];
                        foreach ($jobTypes as $type => $label): 
                        ?>
                            <li class="sidebar-categories__item">
                                <a href="index.php?type=<?php echo e($type); ?>" 
                                   class="sidebar-categories__link <?php echo $jobType === $type ? 'sidebar-categories__link--active' : ''; ?>">
                                    <span><?php echo e($label); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Newsletter Widget -->
                <div class="sidebar-widget sidebar-newsletter">
                    <h3 class="sidebar-widget__title sidebar-newsletter__title">
                        <i class="fas fa-bell"></i>
                        Job Alerts
                    </h3>
                    <p class="sidebar-newsletter__text">
                        Get notified when new positions open up.
                    </p>
                    <form class="sidebar-newsletter__form">
                        <input type="email" placeholder="Your email address" class="sidebar-newsletter__input" required>
                        <button type="submit" class="sidebar-newsletter__btn">
                            Subscribe
                        </button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
