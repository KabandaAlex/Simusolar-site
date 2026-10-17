<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../includes/notifications.php';
$pdo = getDb();
$currentNav = 'dashboard';
$pageTitle = 'Dashboard';

// Get statistics
$pagesCount = (int) $pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
$productsCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$sectionsCount = (int) $pdo->query('SELECT COUNT(*) FROM page_sections')->fetchColumn();
$mediaCount = 0;
$testimonialsCount = 0;
$contactSubmissionsCount = 0;
try {
    $mediaCount = (int) $pdo->query('SELECT COUNT(*) FROM media')->fetchColumn();
} catch (Throwable $e) { /* table may not exist */ }
try {
    $testimonialsCount = (int) $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
} catch (Throwable $e) { /* table may not exist */ }
try {
    $contactSubmissionsCount = (int) $pdo->query('SELECT COUNT(*) FROM contact_submissions')->fetchColumn();
} catch (Throwable $e) { /* table may not exist */ }

$activePages = (int) $pdo->query('SELECT COUNT(*) FROM pages WHERE is_active = 1')->fetchColumn();
$activeProducts = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();

// Get recent contact submissions
$recentSubmissions = [];
try {
    $stmt = $pdo->query('SELECT * FROM contact_submissions ORDER BY submitted_at DESC LIMIT 5');
    $recentSubmissions = $stmt->fetchAll();
} catch (Throwable $e) { /* table may not exist */ }

// Analytics data
$analyticsData = [
    'pageViews' => 0,
    'uniqueVisitors' => 0,
    'viewsTrend' => 0,
    'visitorsTrend' => 0,
    'contactRate' => 0,
    'contactRateTrend' => 0,
];
try {
    // Get page views for last 30 days
    $stmt = $pdo->query('SELECT COUNT(*) FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $analyticsData['pageViews'] = (int) $stmt->fetchColumn();
    
    // Get unique visitors for last 30 days
    $stmt = $pdo->query('SELECT COUNT(DISTINCT visitor_hash) FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $analyticsData['uniqueVisitors'] = (int) $stmt->fetchColumn();
    
    // Get previous period for comparison
    $stmt = $pdo->query('SELECT COUNT(*) FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND viewed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $prevViews = (int) $stmt->fetchColumn();
    $analyticsData['viewsTrend'] = $prevViews > 0 ? round((($analyticsData['pageViews'] - $prevViews) / $prevViews) * 100) : 0;
    
    $stmt = $pdo->query('SELECT COUNT(DISTINCT visitor_hash) FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND viewed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $prevVisitors = (int) $stmt->fetchColumn();
    $analyticsData['visitorsTrend'] = $prevVisitors > 0 ? round((($analyticsData['uniqueVisitors'] - $prevVisitors) / $prevVisitors) * 100) : 0;
    
    // Calculate contact rate (submissions per 1000 views)
    $analyticsData['contactRate'] = $analyticsData['pageViews'] > 0 ? round(($contactSubmissionsCount / $analyticsData['pageViews']) * 1000) / 10 : 0;
    
    // Get previous period contact submissions
    $stmt = $pdo->query('SELECT COUNT(*) FROM contact_submissions WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND submitted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $prevContacts = (int) $stmt->fetchColumn();
    $prevRate = $prevViews > 0 ? round(($prevContacts / $prevViews) * 1000) / 10 : 0;
    $analyticsData['contactRateTrend'] = $prevRate > 0 ? round((($analyticsData['contactRate'] - $prevRate) / $prevRate) * 100) : 0;
} catch (Throwable $e) { /* analytics tables may not exist */ }

// Get top pages
$topPages = [];
try {
    $stmt = $pdo->query('
        SELECT page_slug, page_title, COUNT(*) as view_count 
        FROM page_views 
        WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY page_slug, page_title 
        ORDER BY view_count DESC 
        LIMIT 5
    ');
    $topPages = $stmt->fetchAll();
} catch (Throwable $e) { /* analytics tables may not exist */ }

// Get unread notifications count
$unreadNotificationsCount = 0;
try {
    $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
    $stmt = $pdo->query('SELECT COUNT(*) FROM notifications WHERE (admin_id IS NULL OR admin_id = ' . $adminId . ') AND is_read = 0');
    $unreadNotificationsCount = (int) $stmt->fetchColumn();
} catch (Throwable $e) { /* notifications table may not exist */ }

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$adminBase = rtrim(BASE_PATH, '/') . '/admin';

include __DIR__ . '/includes/header.php';
?>

<!-- Content Area -->
<div class="admin-content">
  <!-- Dashboard Hero -->
  <div class="dashboard-hero">
    <div class="dashboard-hero__content">
      <div class="dashboard-hero__text">
        <div class="dashboard-hero__greeting">Welcome back</div>
        <h1 class="dashboard-hero__title"><?php echo e($greeting); ?>!</h1>
        <p class="dashboard-hero__subtitle">Here's what's happening with your Tulima Solar website today. Manage your content, track engagement, and keep everything running smoothly.</p>
      </div>
      <div class="dashboard-hero__actions">
        <a href="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php" target="_blank" rel="noopener" class="dashboard-hero__btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
            <polyline points="15 3 21 3 21 9"/>
            <line x1="10" y1="14" x2="21" y2="3"/>
          </svg>
          View Live Site
        </a>
        <a href="<?php echo $adminBase; ?>/media/index.php" class="dashboard-hero__btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
          </svg>
          Upload Media
        </a>
      </div>
    </div>
  </div>

  <!-- Analytics Overview Section -->
  <div class="analytics-overview">
    <div class="analytics-overview__header">
      <h2 class="analytics-overview__title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
        </svg>
        Analytics Overview
      </h2>
      <div class="analytics-overview__period">
        <span>Last 30 days</span>
      </div>
    </div>
    
    <!-- Enhanced Stat Cards -->
    <div class="enhanced-stats-grid">
      <div class="enhanced-stat-card enhanced-stat-card--purple">
        <div class="enhanced-stat-card__header">
          <div class="enhanced-stat-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </div>
          <div class="enhanced-stat-card__trend <?php echo $analyticsData['viewsTrend'] >= 0 ? 'enhanced-stat-card__trend--up' : 'enhanced-stat-card__trend--down'; ?>">
            <?php echo $analyticsData['viewsTrend'] >= 0 ? '↑' : '↓'; ?> <?php echo abs($analyticsData['viewsTrend']); ?>%
          </div>
        </div>
        <div class="enhanced-stat-card__value"><?php echo number_format($analyticsData['pageViews']); ?></div>
        <div class="enhanced-stat-card__label">Page Views</div>
        <div class="enhanced-stat-card__sparkline" id="sparkline-views"></div>
      </div>

      <div class="enhanced-stat-card enhanced-stat-card--orange">
        <div class="enhanced-stat-card__header">
          <div class="enhanced-stat-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div class="enhanced-stat-card__trend <?php echo $analyticsData['visitorsTrend'] >= 0 ? 'enhanced-stat-card__trend--up' : 'enhanced-stat-card__trend--down'; ?>">
            <?php echo $analyticsData['visitorsTrend'] >= 0 ? '↑' : '↓'; ?> <?php echo abs($analyticsData['visitorsTrend']); ?>%
          </div>
        </div>
        <div class="enhanced-stat-card__value"><?php echo number_format($analyticsData['uniqueVisitors']); ?></div>
        <div class="enhanced-stat-card__label">Unique Visitors</div>
        <div class="enhanced-stat-card__sparkline" id="sparkline-visitors"></div>
      </div>

      <div class="enhanced-stat-card enhanced-stat-card--green">
        <div class="enhanced-stat-card__header">
          <div class="enhanced-stat-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <div class="enhanced-stat-card__trend <?php echo $analyticsData['contactRateTrend'] >= 0 ? 'enhanced-stat-card__trend--up' : 'enhanced-stat-card__trend--down'; ?>">
            <?php echo $analyticsData['contactRateTrend'] >= 0 ? '↑' : '↓'; ?> <?php echo abs($analyticsData['contactRateTrend']); ?>%
          </div>
        </div>
        <div class="enhanced-stat-card__value"><?php echo $analyticsData['contactRate']; ?>%</div>
        <div class="enhanced-stat-card__label">Contact Rate</div>
        <div class="enhanced-stat-card__sparkline" id="sparkline-contact"></div>
      </div>

      <div class="enhanced-stat-card enhanced-stat-card--blue">
        <div class="enhanced-stat-card__header">
          <div class="enhanced-stat-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
          </div>
        </div>
        <div class="enhanced-stat-card__value"><?php echo $contactSubmissionsCount; ?></div>
        <div class="enhanced-stat-card__label">Total Submissions</div>
        <div class="enhanced-stat-card__sparkline" id="sparkline-submissions"></div>
      </div>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="dashboard-stats">
    <a href="<?php echo $adminBase; ?>/pages.php" class="stat-card stat-card--purple">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          Active
        </div>
      </div>
      <div class="stat-card__value"><?php echo $pagesCount; ?></div>
      <div class="stat-card__label">Total Pages</div>
      <div class="stat-card__footer"><strong><?php echo $activePages; ?></strong> pages are published</div>
    </a>

    <a href="<?php echo $adminBase; ?>/products.php" class="stat-card stat-card--orange">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          Active
        </div>
      </div>
      <div class="stat-card__value"><?php echo $productsCount; ?></div>
      <div class="stat-card__label">Products</div>
      <div class="stat-card__footer"><strong><?php echo $activeProducts; ?></strong> products are visible</div>
    </a>

    <a href="<?php echo $adminBase; ?>/testimonials.php" class="stat-card stat-card--green">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php echo $testimonialsCount; ?></div>
      <div class="stat-card__label">Testimonials</div>
      <div class="stat-card__footer">Client reviews & feedback</div>
    </a>

    <a href="<?php echo $adminBase; ?>/media/index.php" class="stat-card stat-card--blue">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php echo $mediaCount; ?></div>
      <div class="stat-card__label">Media Files</div>
      <div class="stat-card__footer">Images & videos uploaded</div>
    </a>
  </div>

  <!-- Dashboard Grid -->
  <div class="dashboard-grid">
    <!-- Traffic Overview Chart -->
    <div class="admin-card admin-card--full">
      <div class="admin-card__header">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
          </svg>
          Traffic Trends
        </h3>
        <div class="chart-actions">
          <button class="btn btn--sm btn--secondary" onclick="refreshTrafficChart()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="23 4 23 10 17 10"/>
              <polyline points="1 20 1 14 7 14"/>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
            </svg>
            Refresh
          </button>
        </div>
      </div>
      <div class="admin-card__body">
        <div class="chart-container">
          <canvas id="trafficChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Content Performance Section -->
  <div class="content-performance">
    <div class="content-performance__header">
      <h2 class="content-performance__title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>
        Content Performance
      </h2>
    </div>

    <!-- Top Pages Table -->
    <div class="admin-card admin-card--full">
      <div class="admin-card__header">
        <h3 class="admin-card__title">Top Performing Pages</h3>
        <a href="<?php echo $adminBase; ?>/pages.php" class="btn btn--sm btn--secondary">View All Pages</a>
      </div>
      <div class="admin-card__body">
        <?php if (!empty($topPages)): ?>
        <div class="data-table">
          <table class="data-table__table">
            <thead>
              <tr>
                <th>Page</th>
                <th>Views</th>
                <th>Trend</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topPages as $page): ?>
              <tr>
                <td>
                  <div class="data-table__cell-primary">
                    <div class="data-table__cell-title"><?php echo e($page['page_title'] ?: $page['page_slug']); ?></div>
                    <div class="data-table__cell-subtitle">/<?php echo e($page['page_slug']); ?></div>
                  </div>
                </td>
                <td>
                  <div class="data-table__metric"><?php echo number_format($page['view_count']); ?></div>
                </td>
                <td>
                  <span class="badge badge--success">High</span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          <p>No page view data available yet</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Dashboard Grid -->
  <div class="dashboard-grid">
    <!-- Quick Links Card -->
    <div class="admin-card">
      <div class="admin-card__header">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
          </svg>
          Quick Actions
        </h3>
      </div>
      <div class="admin-card__body">
        <div class="quick-links">
          <a href="<?php echo $adminBase; ?>/pages.php" class="quick-link">
            <div class="quick-link__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
            </div>
            <div class="quick-link__content">
              <div class="quick-link__title">Manage Pages</div>
              <div class="quick-link__description">Edit page content & SEO</div>
            </div>
          </a>
          <a href="<?php echo $adminBase; ?>/sections.php" class="quick-link">
            <div class="quick-link__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
              </svg>
            </div>
            <div class="quick-link__content">
              <div class="quick-link__title">Edit Sections</div>
              <div class="quick-link__description">Homepage & page sections</div>
            </div>
          </a>
          <a href="<?php echo $adminBase; ?>/products.php" class="quick-link">
            <div class="quick-link__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
              </svg>
            </div>
            <div class="quick-link__content">
              <div class="quick-link__title">Products</div>
              <div class="quick-link__description">Add or edit products</div>
            </div>
          </a>
          <a href="<?php echo $adminBase; ?>/settings.php" class="quick-link">
            <div class="quick-link__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
              </svg>
            </div>
            <div class="quick-link__content">
              <div class="quick-link__title">Settings</div>
              <div class="quick-link__description">Site configuration</div>
            </div>
          </a>
          <a href="<?php echo $adminBase; ?>/contact_submissions.php" class="quick-link">
            <div class="quick-link__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
              </svg>
            </div>
            <div class="quick-link__content">
              <div class="quick-link__title">Messages</div>
              <div class="quick-link__description"><?php echo $contactSubmissionsCount; ?> submissions</div>
            </div>
          </a>
          <a href="<?php echo $adminBase; ?>/media/index.php" class="quick-link">
            <div class="quick-link__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
              </svg>
            </div>
            <div class="quick-link__content">
              <div class="quick-link__title">Media Library</div>
              <div class="quick-link__description">Images & videos</div>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- Activity Feed Card -->
    <div class="admin-card">
      <div class="admin-card__header">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
          </svg>
          Recent Activity
        </h3>
        <a href="<?php echo $adminBase; ?>/contact_submissions.php" class="btn btn--sm btn--secondary">View All</a>
      </div>
      <div class="admin-card__body">
        <div class="activity-feed">
          <?php if (!empty($recentSubmissions)): ?>
            <?php foreach ($recentSubmissions as $submission): ?>
            <div class="activity-item">
              <div class="activity-item__icon activity-item__icon--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                  <polyline points="22,6 12,13 2,6"/>
                </svg>
              </div>
              <div class="activity-item__content">
                <div class="activity-item__title">New message from <strong><?php echo e($submission['name']); ?></strong></div>
                <div class="activity-item__time"><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="activity-item">
              <div class="activity-item__icon activity-item__icon--info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <line x1="12" y1="16" x2="12" y2="12"/>
                  <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
              </div>
              <div class="activity-item__content">
                <div class="activity-item__title">No recent submissions</div>
                <div class="activity-item__time">Contact form submissions will appear here</div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Contact Analytics Section -->
  <div class="contact-analytics">
    <div class="contact-analytics__header">
      <h2 class="contact-analytics__title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
        Contact Analytics
      </h2>
      <a href="<?php echo $adminBase; ?>/contact_submissions.php" class="btn btn--secondary">View All Submissions</a>
    </div>

    <div class="contact-analytics__grid">
      <!-- Contact Stats -->
      <div class="contact-stat-card">
        <div class="contact-stat-card__icon contact-stat-card__icon--primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
        </div>
        <div class="contact-stat-card__content">
          <div class="contact-stat-card__value"><?php echo $contactSubmissionsCount; ?></div>
          <div class="contact-stat-card__label">Total Submissions</div>
        </div>
      </div>

      <div class="contact-stat-card">
        <div class="contact-stat-card__icon contact-stat-card__icon--success">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="contact-stat-card__content">
          <div class="contact-stat-card__value"><?php
            try {
              echo (int) $pdo->query('SELECT COUNT(*) FROM contact_submissions WHERE status = "replied"')->fetchColumn();
            } catch (Throwable $e) { echo '0'; }
          ?></div>
          <div class="contact-stat-card__label">Replied</div>
        </div>
      </div>

      <div class="contact-stat-card">
        <div class="contact-stat-card__icon contact-stat-card__icon--warning">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <div class="contact-stat-card__content">
          <div class="contact-stat-card__value"><?php
            try {
              echo (int) $pdo->query('SELECT COUNT(*) FROM contact_submissions WHERE status = "new"')->fetchColumn();
            } catch (Throwable $e) { echo '0'; }
          ?></div>
          <div class="contact-stat-card__label">Pending</div>
        </div>
      </div>

      <div class="contact-stat-card">
        <div class="contact-stat-card__icon contact-stat-card__icon--info">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <div class="contact-stat-card__content">
          <div class="contact-stat-card__value"><?php
            try {
              $avg = $pdo->query('SELECT AVG(TIMESTAMPDIFF(HOUR, submitted_at, replied_at)) as avg_hours FROM contact_submissions WHERE replied_at IS NOT NULL')->fetch();
              echo $avg && $avg['avg_hours'] ? round($avg['avg_hours'], 1) : '0';
            } catch (Throwable $e) { echo '0'; }
          ?>h</div>
          <div class="contact-stat-card__label">Avg Response Time</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Additional Stats Row -->
  <div class="dashboard-stats" style="margin-top: var(--space-6);">
    <a href="<?php echo $adminBase; ?>/sections.php" class="stat-card stat-card--purple">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php echo $sectionsCount; ?></div>
      <div class="stat-card__label">Content Sections</div>
      <div class="stat-card__footer">Page content blocks</div>
    </a>

    <a href="<?php echo $adminBase; ?>/partners.php" class="stat-card stat-card--orange">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php
        try {
          echo (int) $pdo->query('SELECT COUNT(*) FROM partners')->fetchColumn();
        } catch (Throwable $e) { echo '0'; }
      ?></div>
      <div class="stat-card__label">Partners</div>
      <div class="stat-card__footer">Trusted organizations</div>
    </a>

    <a href="<?php echo $adminBase; ?>/impact_numbers.php" class="stat-card stat-card--green">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="20" x2="12" y2="10"/>
            <line x1="18" y1="20" x2="18" y2="4"/>
            <line x1="6" y1="20" x2="6" y2="16"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php
        try {
          echo (int) $pdo->query('SELECT COUNT(*) FROM impact_numbers')->fetchColumn();
        } catch (Throwable $e) { echo '0'; }
      ?></div>
      <div class="stat-card__label">Impact Stats</div>
      <div class="stat-card__footer">Key metrics displayed</div>
    </a>

    <a href="<?php echo $adminBase; ?>/office_locations.php" class="stat-card stat-card--blue">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
        </div>
      </div>
      <div class="stat-card__value"><?php
        try {
          echo (int) $pdo->query('SELECT COUNT(*) FROM office_locations')->fetchColumn();
        } catch (Throwable $e) { echo '0'; }
      ?></div>
      <div class="stat-card__label">Office Locations</div>
      <div class="stat-card__footer">Regional offices</div>
    </a>
  </div>
</div>

<!-- Dashboard JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
// Initialize dashboard charts
document.addEventListener('DOMContentLoaded', function() {
    // Traffic Chart
    initTrafficChart();
    
    // Sparklines for stat cards
    initSparklines();
});

function initTrafficChart() {
    const ctx = document.getElementById('trafficChart');
    if (!ctx) return;
    
    // Fetch traffic data from API
    fetch('<?php echo $adminBase; ?>/api/analytics.php?action=traffic-trends&days=30')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                renderTrafficChart(ctx, data.data);
            }
        })
        .catch(error => {
            console.error('Error loading traffic data:', error);
            // Show sample data if API fails
            renderSampleTrafficChart(ctx);
        });
}

function renderTrafficChart(ctx, data) {
    const labels = data.labels || [];
    const viewsData = data.views || [];
    const visitorsData = data.visitors || [];
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Page Views',
                    data: viewsData,
                    borderColor: '#660099',
                    backgroundColor: 'rgba(102, 0, 153, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#660099',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                },
                {
                    label: 'Unique Visitors',
                    data: visitorsData,
                    borderColor: '#E98E39',
                    backgroundColor: 'rgba(233, 142, 57, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#E98E39',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 20,
                        font: {
                            size: 12,
                            family: 'Inter, system-ui, sans-serif'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: '#1E293B',
                    titleColor: '#F1F5F9',
                    bodyColor: '#CBD5E1',
                    borderColor: '#334155',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    displayColors: true,
                    usePointStyle: true
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#64748B',
                        font: {
                            size: 11
                        },
                        maxTicksLimit: 7
                    }
                },
                y: {
                    grid: {
                        color: '#E2E8F0',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748B',
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            if (value >= 1000) {
                                return (value / 1000) + 'k';
                            }
                            return value;
                        }
                    },
                    beginAtZero: true
                }
            }
        }
    });
}

function renderSampleTrafficChart(ctx) {
    // Generate sample data for demonstration
    const days = 30;
    const labels = [];
    const views = [];
    const visitors = [];
    
    for (let i = days; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        views.push(Math.floor(Math.random() * 500) + 200);
        visitors.push(Math.floor(Math.random() * 200) + 80);
    }
    
    renderTrafficChart(ctx, { labels, views, visitors });
}

function initSparklines() {
    const sparklineIds = ['sparkline-views', 'sparkline-visitors', 'sparkline-contact', 'sparkline-submissions'];
    
    sparklineIds.forEach(id => {
        const container = document.getElementById(id);
        if (!container) return;
        
        // Generate sample sparkline data
        const data = Array.from({ length: 10 }, () => Math.floor(Math.random() * 100));
        
        new Chart(container, {
            type: 'line',
            data: {
                labels: Array.from({ length: 10 }, (_, i) => i),
                datasets: [{
                    data: data,
                    borderColor: container.parentElement.classList.contains('enhanced-stat-card--purple') ? '#660099' :
                               container.parentElement.classList.contains('enhanced-stat-card--orange') ? '#E98E39' :
                               container.parentElement.classList.contains('enhanced-stat-card--green') ? '#10B981' : '#3B82F6',
                    borderWidth: 2,
                    fill: false,
                    pointRadius: 0,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        });
    });
}

function refreshTrafficChart() {
    const ctx = document.getElementById('trafficChart');
    if (ctx) {
        // Destroy existing chart and reinitialize
        const chart = Chart.getChart(ctx);
        if (chart) {
            chart.destroy();
        }
        initTrafficChart();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>