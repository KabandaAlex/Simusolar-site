<?php
/**
 * Tulima Solar - Blog Engagement Dashboard
 * View engagement metrics and analytics for blog posts
 * 
 * RBAC: Requires at least author role (view only for authors)
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/auth_helpers.php';
requirePermission('blog_view_engagement', 'index.php'); // Requires blog_view_engagement permission

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDb();
$currentNav = 'blog-engagement';
$pageTitle = 'Blog Engagement';

// Get date range from query string
$days = min(max((int)($_GET['days'] ?? 30), 1), 365);
$startDate = date('Y-m-d', strtotime("-$days days"));
$endDate = date('Y-m-d');

// Get overview statistics
$stats = [
    'total_views' => 0,
    'total_likes' => 0,
    'total_comments' => 0,
    'total_posts' => 0,
    'published_posts' => 0,
    'avg_engagement' => 0
];

// Total views across all posts
$stmt = $pdo->query('SELECT COALESCE(SUM(view_count), 0) FROM blog_posts WHERE deleted_at IS NULL');
$stats['total_views'] = (int)$stmt->fetchColumn();

// Total likes
$stmt = $pdo->query('SELECT COALESCE(SUM(like_count), 0) FROM blog_posts WHERE deleted_at IS NULL');
$stats['total_likes'] = (int)$stmt->fetchColumn();

// Total approved comments
$stmt = $pdo->query('SELECT COUNT(*) FROM blog_comments WHERE status = "approved"');
$stats['total_comments'] = (int)$stmt->fetchColumn();

// Total posts
$stmt = $pdo->query('SELECT COUNT(*) FROM blog_posts WHERE deleted_at IS NULL');
$stats['total_posts'] = (int)$stmt->fetchColumn();

// Published posts
$stmt = $pdo->query('SELECT COUNT(*) FROM blog_posts WHERE status = "published" AND deleted_at IS NULL');
$stats['published_posts'] = (int)$stmt->fetchColumn();

// Average engagement rate (likes + comments per view, as percentage)
if ($stats['total_views'] > 0) {
    $engagementSum = $stats['total_likes'] + $stats['total_comments'];
    $stats['avg_engagement'] = round(($engagementSum / $stats['total_views']) * 100, 2);
}

// Get top performing posts
$topPosts = $pdo->query('
    SELECT 
        p.id,
        p.title,
        p.slug,
        p.view_count,
        p.like_count,
        p.comment_count,
        p.published_at,
        c.name as category_name,
        (p.view_count + (p.like_count * 5) + (p.comment_count * 10)) as engagement_score
    FROM blog_posts p
    LEFT JOIN blog_categories c ON p.category_id = c.id
    WHERE p.status = "published" AND p.deleted_at IS NULL
    ORDER BY engagement_score DESC
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);

// Get category performance
$categoryStats = $pdo->query('
    SELECT 
        c.id,
        c.name,
        c.slug,
        COUNT(p.id) as post_count,
        COALESCE(SUM(p.view_count), 0) as total_views,
        COALESCE(SUM(p.like_count), 0) as total_likes,
        COALESCE(SUM(p.comment_count), 0) as total_comments
    FROM blog_categories c
    LEFT JOIN blog_posts p ON c.id = p.category_id AND p.status = "published" AND p.deleted_at IS NULL
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY total_views DESC
')->fetchAll(PDO::FETCH_ASSOC);

// Get engagement timeline data (last 30 days by default)
$timelineData = [];
$stmt = $pdo->prepare('
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as likes_count
    FROM blog_post_likes
    WHERE created_at >= ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
');
$stmt->execute([$startDate . ' 00:00:00']);
$likesTimeline = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare('
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as comments_count
    FROM blog_comments
    WHERE status = "approved" AND created_at >= ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
');
$stmt->execute([$startDate . ' 00:00:00']);
$commentsTimeline = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Build complete timeline with all dates
for ($i = $days - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $timelineData[] = [
        'date' => $date,
        'label' => date('M j', strtotime($date)),
        'likes' => (int)($likesTimeline[$date] ?? 0),
        'comments' => (int)($commentsTimeline[$date] ?? 0)
    ];
}

// Get recent comments (pending approval)
$recentComments = $pdo->query('
    SELECT 
        c.id,
        c.content,
        c.author_name,
        c.created_at,
        c.status,
        p.title as post_title,
        p.id as post_id
    FROM blog_comments c
    JOIN blog_posts p ON c.post_id = p.id
    WHERE c.status = "pending"
    ORDER BY c.created_at DESC
    LIMIT 5
')->fetchAll(PDO::FETCH_ASSOC);

// Get recent likes
$recentLikes = $pdo->query('
    SELECT 
        l.id,
        l.reaction_type,
        l.created_at,
        p.title as post_title,
        p.id as post_id,
        a.username as user_name
    FROM blog_post_likes l
    JOIN blog_posts p ON l.post_id = p.id
    LEFT JOIN admins a ON l.user_id = a.id
    WHERE p.deleted_at IS NULL
    ORDER BY l.created_at DESC
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);

// Get posts with most views this period
$trendingPosts = $pdo->query('
    SELECT 
        p.id,
        p.title,
        p.slug,
        p.view_count,
        p.like_count,
        p.comment_count,
        p.published_at
    FROM blog_posts p
    WHERE p.status = "published" AND p.deleted_at IS NULL
    ORDER BY p.view_count DESC
    LIMIT 5
')->fetchAll(PDO::FETCH_ASSOC);

// Calculate growth rates (compare to previous period)
$prevStartDate = date('Y-m-d', strtotime("-$days days", strtotime($startDate)));
$prevEndDate = date('Y-m-d', strtotime("-1 day", strtotime($startDate)));

$stmt = $pdo->prepare('
    SELECT COUNT(*) FROM blog_comments 
    WHERE status = "approved" AND created_at >= ? AND created_at <= ?
');
$stmt->execute([$prevStartDate . ' 00:00:00', $prevEndDate . ' 23:59:59']);
$prevComments = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('
    SELECT COUNT(*) FROM blog_post_likes 
    WHERE created_at >= ? AND created_at <= ?
');
$stmt->execute([$prevStartDate . ' 00:00:00', $prevEndDate . ' 23:59:59']);
$prevLikes = (int)$stmt->fetchColumn();

// Current period counts
$currentComments = array_sum(array_column($timelineData, 'comments'));
$currentLikes = array_sum(array_column($timelineData, 'likes'));

$growthRates = [
    'comments' => $prevComments > 0 ? round((($currentComments - $prevComments) / $prevComments) * 100, 1) : ($currentComments > 0 ? 100 : 0),
    'likes' => $prevLikes > 0 ? round((($currentLikes - $prevLikes) / $prevLikes) * 100, 1) : ($currentLikes > 0 ? 100 : 0)
];

$adminBase = rtrim(BASE_PATH, '/') . '/admin';

include __DIR__ . '/includes/header.php';
?>

<!-- Content Area -->
<div class="admin-content">
  <!-- Dashboard Hero -->
  <div class="dashboard-hero">
    <div class="dashboard-hero__content">
      <div class="dashboard-hero__text">
        <div class="dashboard-hero__greeting">Blog Analytics</div>
        <h1 class="dashboard-hero__title">Engagement Dashboard</h1>
        <p class="dashboard-hero__subtitle">Track views, likes, comments, and overall engagement across your blog</p>
      </div>
      <div class="dashboard-hero__actions">
        <div class="date-range-selector">
          <a href="?days=7" class="btn <?php echo $days === 7 ? 'btn--primary' : 'btn--outline'; ?> btn--sm">7 Days</a>
          <a href="?days=30" class="btn <?php echo $days === 30 ? 'btn--primary' : 'btn--outline'; ?> btn--sm">30 Days</a>
          <a href="?days=90" class="btn <?php echo $days === 90 ? 'btn--primary' : 'btn--outline'; ?> btn--sm">90 Days</a>
          <a href="?days=365" class="btn <?php echo $days === 365 ? 'btn--primary' : 'btn--outline'; ?> btn--sm">1 Year</a>
        </div>
        <a href="<?php echo $adminBase; ?>/api/export.php?type=blog_engagement&format=csv&days=<?php echo $days; ?>" class="dashboard-hero__btn">
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

  <!-- Overview Statistics -->
  <div class="dashboard-stats">
    <div class="stat-card stat-card--purple">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
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
      <div class="stat-card__value"><?php echo number_format($stats['total_views']); ?></div>
      <div class="stat-card__label">Total Views</div>
    </div>

    <div class="stat-card stat-card--red">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
          </svg>
        </div>
        <div class="stat-card__trend <?php echo $growthRates['likes'] >= 0 ? 'stat-card__trend--up' : 'stat-card__trend--down'; ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <?php if ($growthRates['likes'] >= 0): ?>
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
            <?php else: ?>
            <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
            <polyline points="17 18 23 18 23 12"/>
            <?php endif; ?>
          </svg>
          <?php echo abs($growthRates['likes']); ?>%
        </div>
      </div>
      <div class="stat-card__value"><?php echo number_format($stats['total_likes']); ?></div>
      <div class="stat-card__label">Total Likes</div>
    </div>

    <div class="stat-card stat-card--blue">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
        <div class="stat-card__trend <?php echo $growthRates['comments'] >= 0 ? 'stat-card__trend--up' : 'stat-card__trend--down'; ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <?php if ($growthRates['comments'] >= 0): ?>
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
            <?php else: ?>
            <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
            <polyline points="17 18 23 18 23 12"/>
            <?php endif; ?>
          </svg>
          <?php echo abs($growthRates['comments']); ?>%
        </div>
      </div>
      <div class="stat-card__value"><?php echo number_format($stats['total_comments']); ?></div>
      <div class="stat-card__label">Total Comments</div>
    </div>

    <div class="stat-card stat-card--green">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="20" x2="12" y2="10"/>
            <line x1="18" y1="20" x2="18" y2="4"/>
            <line x1="6" y1="20" x2="6" y2="16"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Avg
        </div>
      </div>
      <div class="stat-card__value"><?php echo $stats['avg_engagement']; ?>%</div>
      <div class="stat-card__label">Engagement Rate</div>
    </div>

    <div class="stat-card stat-card--orange">
      <div class="stat-card__header">
        <div class="stat-card__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
            <polyline points="10 9 9 9 8 9"/>
          </svg>
        </div>
        <div class="stat-card__trend stat-card__trend--up">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
          </svg>
          Published
        </div>
      </div>
      <div class="stat-card__value"><?php echo number_format($stats['published_posts']); ?></div>
      <div class="stat-card__label">Published Posts</div>
    </div>
  </div>

  <!-- Engagement Timeline Chart -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
          </svg>
          Engagement Timeline
        </h3>
        <p class="admin-card__subtitle">Likes and comments over the last <?php echo $days; ?> days</p>
      </div>
      <div class="admin-card__header-actions">
        <div class="chart-legend">
          <span class="chart-legend__item">
            <span class="chart-legend__color" style="background: #E98E39;"></span>
            Likes
          </span>
          <span class="chart-legend__item">
            <span class="chart-legend__color" style="background: #660099;"></span>
            Comments
          </span>
        </div>
      </div>
    </div>
    <div class="admin-card__body">
      <div class="chart-container" style="height: 350px;">
        <canvas id="engagementTimelineChart"></canvas>
      </div>
    </div>
  </div>

  <div class="dashboard-grid">
    <!-- Top Performing Posts -->
    <div class="admin-card">
      <div class="admin-card__header">
        <div class="admin-card__header-left">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            Top Performing Posts
          </h3>
          <p class="admin-card__subtitle">Ranked by engagement score</p>
        </div>
      </div>
      <div class="admin-card__body">
        <?php if (empty($topPosts)): ?>
          <div class="empty-state empty-state--compact">
            <p class="empty-state__text">No published posts yet.</p>
          </div>
        <?php else: ?>
          <div class="top-posts-list">
            <?php foreach ($topPosts as $index => $post): ?>
              <div class="top-post-item">
                <div class="top-post-rank">
                  <?php echo $index + 1; ?>
                </div>
                <div class="top-post-content">
                  <a href="<?php echo $adminBase; ?>/blog_post_edit.php?id=<?php echo $post['id']; ?>" class="top-post-title">
                    <?php echo e($post['title']); ?>
                  </a>
                  <div class="top-post-meta">
                    <?php if ($post['category_name']): ?>
                      <span class="top-post-category"><?php echo e($post['category_name']); ?></span>
                    <?php endif; ?>
                    <span class="top-post-date"><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
                  </div>
                </div>
                <div class="top-post-stats">
                  <div class="top-post-stat">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <?php echo number_format($post['view_count']); ?>
                  </div>
                  <div class="top-post-stat">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    <?php echo number_format($post['like_count']); ?>
                  </div>
                  <div class="top-post-stat">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <?php echo number_format($post['comment_count']); ?>
                  </div>
                </div>
                <div class="top-post-score">
                  <span class="score-label">Score</span>
                  <span class="score-value"><?php echo number_format($post['engagement_score']); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Category Performance -->
    <div class="admin-card">
      <div class="admin-card__header">
        <div class="admin-card__header-left">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            Category Performance
          </h3>
          <p class="admin-card__subtitle">Views and engagement by category</p>
        </div>
      </div>
      <div class="admin-card__body">
        <?php if (empty($categoryStats) || !array_filter($categoryStats, fn($c) => $c['post_count'] > 0)): ?>
          <div class="empty-state empty-state--compact">
            <p class="empty-state__text">No category data available.</p>
          </div>
        <?php else: ?>
          <div class="category-stats-list">
            <?php foreach ($categoryStats as $category): ?>
              <?php if ($category['post_count'] > 0): ?>
                <div class="category-stat-item">
                  <div class="category-stat-header">
                    <span class="category-stat-name"><?php echo e($category['name']); ?></span>
                    <span class="category-stat-posts"><?php echo $category['post_count']; ?> posts</span>
                  </div>
                  <div class="category-stat-metrics">
                    <div class="category-metric">
                      <span class="metric-value"><?php echo number_format($category['total_views']); ?></span>
                      <span class="metric-label">Views</span>
                    </div>
                    <div class="category-metric">
                      <span class="metric-value"><?php echo number_format($category['total_likes']); ?></span>
                      <span class="metric-label">Likes</span>
                    </div>
                    <div class="category-metric">
                      <span class="metric-value"><?php echo number_format($category['total_comments']); ?></span>
                      <span class="metric-label">Comments</span>
                    </div>
                  </div>
                  <?php 
                  $totalCategoryViews = array_sum(array_column($categoryStats, 'total_views'));
                  $percentage = $totalCategoryViews > 0 ? round(($category['total_views'] / $totalCategoryViews) * 100) : 0;
                  ?>
                  <div class="category-stat-bar">
                    <div class="category-stat-bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                  </div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="dashboard-grid">
    <!-- Recent Comments (Pending) -->
    <div class="admin-card">
      <div class="admin-card__header">
        <div class="admin-card__header-left">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Pending Comments
          </h3>
          <p class="admin-card__subtitle">Comments awaiting moderation</p>
        </div>
        <div class="admin-card__header-actions">
          <a href="<?php echo $adminBase; ?>/blog_comments.php?status=pending" class="btn btn--outline btn--sm">View All</a>
        </div>
      </div>
      <div class="admin-card__body">
        <?php if (empty($recentComments)): ?>
          <div class="empty-state empty-state--compact">
            <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            <p class="empty-state__text">All caught up! No pending comments.</p>
          </div>
        <?php else: ?>
          <div class="recent-activity-list">
            <?php foreach ($recentComments as $comment): ?>
              <div class="recent-activity-item">
                <div class="recent-activity-icon recent-activity-icon--comment">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                  </svg>
                </div>
                <div class="recent-activity-content">
                  <div class="recent-activity-title">
                    <strong><?php echo e($comment['author_name'] ?: 'Anonymous'); ?></strong>
                    commented on
                    <a href="<?php echo $adminBase; ?>/blog_post_edit.php?id=<?php echo $comment['post_id']; ?>">
                      <?php echo e(substr($comment['post_title'], 0, 30)); ?><?php echo strlen($comment['post_title']) > 30 ? '...' : ''; ?>
                    </a>
                  </div>
                  <div class="recent-activity-preview">
                    "<?php echo e(substr($comment['content'], 0, 80)); ?><?php echo strlen($comment['content']) > 80 ? '...' : ''; ?>"
                  </div>
                  <div class="recent-activity-time">
                    <?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?>
                  </div>
                </div>
                <div class="recent-activity-actions">
                  <a href="<?php echo $adminBase; ?>/blog_comments.php?status=pending" class="btn btn--success btn--sm">Review</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Likes -->
    <div class="admin-card">
      <div class="admin-card__header">
        <div class="admin-card__header-left">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
            Recent Likes
          </h3>
          <p class="admin-card__subtitle">Latest post reactions</p>
        </div>
      </div>
      <div class="admin-card__body">
        <?php if (empty($recentLikes)): ?>
          <div class="empty-state empty-state--compact">
            <p class="empty-state__text">No likes recorded yet.</p>
          </div>
        <?php else: ?>
          <div class="recent-activity-list">
            <?php foreach ($recentLikes as $like): ?>
              <div class="recent-activity-item">
                <div class="recent-activity-icon recent-activity-icon--like">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                  </svg>
                </div>
                <div class="recent-activity-content">
                  <div class="recent-activity-title">
                    <strong><?php echo e($like['user_name'] ?: 'Someone'); ?></strong>
                    liked
                    <a href="<?php echo $adminBase; ?>/blog_post_edit.php?id=<?php echo $like['post_id']; ?>">
                      <?php echo e(substr($like['post_title'], 0, 40)); ?><?php echo strlen($like['post_title']) > 40 ? '...' : ''; ?>
                    </a>
                  </div>
                  <div class="recent-activity-time">
                    <?php 
                    $likeTime = strtotime($like['created_at']);
                    $now = time();
                    $diff = $now - $likeTime;
                    if ($diff < 60) {
                        echo 'Just now';
                    } elseif ($diff < 3600) {
                        echo floor($diff / 60) . ' minutes ago';
                    } elseif ($diff < 86400) {
                        echo floor($diff / 3600) . ' hours ago';
                    } else {
                        echo date('M j, Y', $likeTime);
                    }
                    ?>
                  </div>
                </div>
                <div class="recent-activity-reaction">
                  <span class="reaction-badge reaction-badge--<?php echo e($like['reaction_type']); ?>">
                    <?php echo e($like['reaction_type']); ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Engagement Summary Table -->
  <div class="admin-card admin-card--full">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <line x1="3" y1="9" x2="21" y2="9"/>
            <line x1="9" y1="21" x2="9" y2="9"/>
          </svg>
          All Posts Engagement
        </h3>
        <p class="admin-card__subtitle">Complete engagement breakdown by post</p>
      </div>
      <div class="admin-card__header-actions">
        <a href="<?php echo $adminBase; ?>/api/export.php?type=blog_engagement&format=csv&days=<?php echo $days; ?>" class="btn btn--outline btn--sm">
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
      <div class="data-table">
        <table class="data-table__table" id="engagementTable">
          <thead>
            <tr>
              <th class="sortable" data-sort="title">Post Title</th>
              <th class="sortable" data-sort="category">Category</th>
              <th class="sortable" data-sort="views">Views</th>
              <th class="sortable" data-sort="likes">Likes</th>
              <th class="sortable" data-sort="comments">Comments</th>
              <th class="sortable" data-sort="score">Engagement Score</th>
              <th>Published</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            // Get all published posts for the table
            $allPosts = $pdo->query('
                SELECT 
                    p.id,
                    p.title,
                    p.slug,
                    p.view_count,
                    p.like_count,
                    p.comment_count,
                    p.published_at,
                    c.name as category_name,
                    (p.view_count + (p.like_count * 5) + (p.comment_count * 10)) as engagement_score
                FROM blog_posts p
                LEFT JOIN blog_categories c ON p.category_id = c.id
                WHERE p.status = "published" AND p.deleted_at IS NULL
                ORDER BY engagement_score DESC
            ')->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($allPosts)): 
            ?>
              <tr>
                <td colspan="7" class="text-center text-muted">No published posts found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($allPosts as $post): ?>
                <tr>
                  <td>
                    <div class="data-table__cell-primary">
                      <a href="<?php echo $adminBase; ?>/blog_post_edit.php?id=<?php echo $post['id']; ?>" class="data-table__link">
                        <?php echo e($post['title']); ?>
                      </a>
                    </div>
                  </td>
                  <td>
                    <span class="badge badge--muted"><?php echo e($post['category_name'] ?: 'Uncategorized'); ?></span>
                  </td>
                  <td class="data-table__metric"><?php echo number_format($post['view_count']); ?></td>
                  <td class="data-table__metric"><?php echo number_format($post['like_count']); ?></td>
                  <td class="data-table__metric"><?php echo number_format($post['comment_count']); ?></td>
                  <td>
                    <div class="engagement-score">
                      <span class="engagement-score__value"><?php echo number_format($post['engagement_score']); ?></span>
                      <div class="engagement-score__bar">
                        <?php 
                        $maxScore = max(array_column($allPosts, 'engagement_score'));
                        $percentage = $maxScore > 0 ? round(($post['engagement_score'] / $maxScore) * 100) : 0;
                        ?>
                        <div class="engagement-score__bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="data-table__date"><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
/* Date Range Selector */
.date-range-selector {
  display: flex;
  gap: var(--space-2);
}

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-6);
  margin-bottom: var(--space-6);
}

@media (max-width: 1200px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

/* Chart Legend */
.chart-legend {
  display: flex;
  gap: var(--space-4);
}

.chart-legend__item {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.chart-legend__color {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

/* Top Posts List */
.top-posts-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.top-post-item {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-3);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
}

.top-post-item:hover {
  background: var(--bg-tertiary);
}

.top-post-rank {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary);
  color: white;
  border-radius: 50%;
  font-size: 0.875rem;
  font-weight: 600;
  flex-shrink: 0;
}

.top-post-item:nth-child(1) .top-post-rank {
  background: #FFD700;
  color: #333;
}

.top-post-item:nth-child(2) .top-post-rank {
  background: #C0C0C0;
  color: #333;
}

.top-post-item:nth-child(3) .top-post-rank {
  background: #CD7F32;
  color: white;
}

.top-post-content {
  flex: 1;
  min-width: 0;
}

.top-post-title {
  display: block;
  font-weight: 500;
  color: var(--text-primary);
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.top-post-title:hover {
  color: var(--primary);
}

.top-post-meta {
  display: flex;
  gap: var(--space-2);
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: var(--space-1);
}

.top-post-category {
  color: var(--primary);
}

.top-post-stats {
  display: flex;
  gap: var(--space-3);
}

.top-post-stat {
  display: flex;
  align-items: center;
  gap: var(--space-1);
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.top-post-stat svg {
  color: var(--text-muted);
}

.top-post-score {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  min-width: 60px;
}

.score-label {
  font-size: 0.625rem;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 0.5px;
}

.score-value {
  font-size: 1rem;
  font-weight: 700;
  color: var(--primary);
}

/* Category Stats */
.category-stats-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.category-stat-item {
  padding: var(--space-3);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
}

.category-stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-2);
}

.category-stat-name {
  font-weight: 600;
  color: var(--text-primary);
}

.category-stat-posts {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.category-stat-metrics {
  display: flex;
  gap: var(--space-4);
  margin-bottom: var(--space-2);
}

.category-metric {
  display: flex;
  flex-direction: column;
}

.metric-value {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
}

.metric-label {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.category-stat-bar {
  height: 4px;
  background: var(--bg-tertiary);
  border-radius: 2px;
  overflow: hidden;
}

.category-stat-bar-fill {
  height: 100%;
  background: var(--primary);
  border-radius: 2px;
  transition: width var(--transition-base);
}

/* Recent Activity */
.recent-activity-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.recent-activity-item {
  display: flex;
  gap: var(--space-3);
  padding: var(--space-3);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
}

.recent-activity-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  flex-shrink: 0;
}

.recent-activity-icon--comment {
  background: rgba(102, 0, 153, 0.1);
  color: #660099;
}

.recent-activity-icon--like {
  background: rgba(233, 142, 57, 0.1);
  color: #E98E39;
}

.recent-activity-content {
  flex: 1;
  min-width: 0;
}

.recent-activity-title {
  font-size: 0.875rem;
  color: var(--text-primary);
}

.recent-activity-title a {
  color: var(--primary);
  text-decoration: none;
}

.recent-activity-title a:hover {
  text-decoration: underline;
}

.recent-activity-preview {
  font-size: 0.8125rem;
  color: var(--text-secondary);
  font-style: italic;
  margin-top: var(--space-1);
}

.recent-activity-time {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: var(--space-1);
}

.recent-activity-actions {
  display: flex;
  align-items: center;
}

.recent-activity-reaction {
  display: flex;
  align-items: center;
}

.reaction-badge {
  padding: var(--space-1) var(--space-2);
  font-size: 0.75rem;
  border-radius: var(--radius-sm);
  text-transform: capitalize;
}

.reaction-badge--like {
  background: rgba(233, 142, 57, 0.1);
  color: #E98E39;
}

/* Engagement Score */
.engagement-score {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  min-width: 100px;
}

.engagement-score__value {
  font-weight: 600;
  color: var(--text-primary);
}

.engagement-score__bar {
  height: 4px;
  background: var(--bg-tertiary);
  border-radius: 2px;
  overflow: hidden;
}

.engagement-score__bar-fill {
  height: 100%;
  background: var(--primary);
  border-radius: 2px;
}

/* Empty State Compact */
.empty-state--compact {
  padding: var(--space-6);
  text-align: center;
}

.empty-state--compact .empty-state__icon {
  width: 40px;
  height: 40px;
  margin: 0 auto var(--space-2);
  color: var(--success);
}

/* Sortable Table Headers */
.data-table__table th.sortable {
  cursor: pointer;
  user-select: none;
}

.data-table__table th.sortable:hover {
  background: var(--bg-tertiary);
}

/* Data Table Styles */
.data-table__link {
  color: var(--text-primary);
  text-decoration: none;
  font-weight: 500;
}

.data-table__link:hover {
  color: var(--primary);
}

.data-table__metric {
  font-weight: 600;
  color: var(--text-primary);
  text-align: right;
}

.data-table__date {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

/* Dark Mode Adjustments */
.dark .top-post-item:hover,
.dark .category-stat-item,
.dark .recent-activity-item {
  background: var(--bg-card);
}

.dark .category-stat-bar {
  background: var(--bg-card);
}

.dark .engagement-score__bar {
  background: var(--bg-card);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
// Engagement Timeline Chart
const timelineData = <?php echo json_encode($timelineData); ?>;

const ctx = document.getElementById('engagementTimelineChart');
if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: timelineData.map(d => d.label),
      datasets: [
        {
          label: 'Likes',
          data: timelineData.map(d => d.likes),
          backgroundColor: '#E98E39',
          borderRadius: 4,
          barPercentage: 0.7,
          categoryPercentage: 0.8
        },
        {
          label: 'Comments',
          data: timelineData.map(d => d.comments),
          backgroundColor: '#660099',
          borderRadius: 4,
          barPercentage: 0.7,
          categoryPercentage: 0.8
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: 'rgba(255, 255, 255, 0.1)',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            maxRotation: 45,
            minRotation: 0,
            autoSkip: true,
            maxTicksLimit: 15
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            precision: 0
          }
        }
      },
      interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
      }
    }
  });
}

// Table Sorting
document.addEventListener('DOMContentLoaded', function() {
  const table = document.getElementById('engagementTable');
  if (!table) return;
  
  const headers = table.querySelectorAll('th.sortable');
  const tbody = table.querySelector('tbody');
  const rows = Array.from(tbody.querySelectorAll('tr'));
  
  headers.forEach(header => {
    header.addEventListener('click', function() {
      const sortKey = this.dataset.sort;
      const isAsc = this.classList.contains('sort-asc');
      
      // Remove sort classes from all headers
      headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
      
      // Add sort class to current header
      this.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
      
      // Sort rows
      rows.sort((a, b) => {
        let aVal, bVal;
        
        switch (sortKey) {
          case 'title':
            aVal = a.querySelector('.data-table__link')?.textContent.trim() || '';
            bVal = b.querySelector('.data-table__link')?.textContent.trim() || '';
            break;
          case 'category':
            aVal = a.querySelector('.badge')?.textContent.trim() || '';
            bVal = b.querySelector('.badge')?.textContent.trim() || '';
            break;
          case 'views':
            aVal = parseInt(a.querySelectorAll('td')[2]?.textContent.replace(/,/g, '') || 0);
            bVal = parseInt(b.querySelectorAll('td')[2]?.textContent.replace(/,/g, '') || 0);
            break;
          case 'likes':
            aVal = parseInt(a.querySelectorAll('td')[3]?.textContent.replace(/,/g, '') || 0);
            bVal = parseInt(b.querySelectorAll('td')[3]?.textContent.replace(/,/g, '') || 0);
            break;
          case 'comments':
            aVal = parseInt(a.querySelectorAll('td')[4]?.textContent.replace(/,/g, '') || 0);
            bVal = parseInt(b.querySelectorAll('td')[4]?.textContent.replace(/,/g, '') || 0);
            break;
          case 'score':
            aVal = parseInt(a.querySelector('.engagement-score__value')?.textContent.replace(/,/g, '') || 0);
            bVal = parseInt(b.querySelector('.engagement-score__value')?.textContent.replace(/,/g, '') || 0);
            break;
          default:
            return 0;
        }
        
        if (typeof aVal === 'string') {
          return isAsc ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
        }
        return isAsc ? bVal - aVal : aVal - bVal;
      });
      
      // Re-append sorted rows
      rows.forEach(row => tbody.appendChild(row));
    });
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
