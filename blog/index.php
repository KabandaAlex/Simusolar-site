<?php
/**
 * Tulima Solar - Blog Listing Page
 * Modern, clean redesign for client-facing blog
 * 
 * @version 2.0.0
 * @created 2026-02-26
 */

session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDb();

// Get site settings
$siteName = getSetting($pdo, 'site_name') ?: 'Tulima Solar';
$pageTitle = 'Blog';

// Get filter parameters from URL
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : null;
$tagSlug = isset($_GET['tag']) ? trim($_GET['tag']) : null;
$archive = isset($_GET['archive']) ? trim($_GET['archive']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$viewMode = isset($_GET['view']) ? trim($_GET['view']) : 'grid';

// Validate sort option
$allowedSorts = ['newest', 'popular', 'commented'];
if (!in_array($sort, $allowedSorts)) {
    $sort = 'newest';
}

// Build the query for posts
$whereClauses = ['p.status = \'published\'', 'p.deleted_at IS NULL'];
$params = [];

// Filter by category
if ($categorySlug) {
    $whereClauses[] = 'c.slug = ?';
    $params[] = $categorySlug;
}

// Filter by tag
if ($tagSlug) {
    $whereClauses[] = 't.slug = ?';
    $params[] = $tagSlug;
}

// Filter by archive (month/year)
if ($archive && preg_match('/^\d{4}-\d{2}$/', $archive)) {
    $whereClauses[] = 'DATE_FORMAT(p.published_at, \'%Y-%m\') = ?';
    $params[] = $archive;
}

// Search filter
if ($search) {
    $whereClauses[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Determine sort order
switch ($sort) {
    case 'popular':
        $orderBy = 'p.view_count DESC, p.published_at DESC';
        break;
    case 'commented':
        $orderBy = 'p.comment_count DESC, p.published_at DESC';
        break;
    default:
        $orderBy = 'p.published_at DESC';
        break;
}

// Count total posts for pagination
$countSql = '
    SELECT COUNT(DISTINCT p.id) as total
    FROM blog_posts p
    LEFT JOIN blog_categories c ON p.category_id = c.id
    ' . ($tagSlug ? 'LEFT JOIN blog_post_tags pt ON p.id = pt.post_id LEFT JOIN blog_tags t ON pt.tag_id = t.id' : '') . '
    WHERE ' . implode(' AND ', $whereClauses);

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetch()['total'];
$totalPages = max(1, ceil($totalPosts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Get posts
$postsSql = '
    SELECT DISTINCT 
        p.id, p.title, p.slug, p.excerpt, p.featured_image_path, p.featured_image_alt,
        p.view_count, p.like_count, p.comment_count, p.published_at,
        c.id as category_id, c.name as category_name, c.slug as category_slug,
        a.username as author_name, a.email as author_email
    FROM blog_posts p
    LEFT JOIN blog_categories c ON p.category_id = c.id
    LEFT JOIN admins a ON p.created_by = a.id
    ' . ($tagSlug ? 'LEFT JOIN blog_post_tags pt ON p.id = pt.post_id LEFT JOIN blog_tags t ON pt.tag_id = t.id' : '') . '
    WHERE ' . implode(' AND ', $whereClauses) . '
    ORDER BY ' . $orderBy . '
    LIMIT ' . $offset . ', ' . $perPage;

$postsStmt = $pdo->prepare($postsSql);
$postsStmt->execute($params);
$posts = $postsStmt->fetchAll();

// Get tags for each post
foreach ($posts as &$post) {
    $tagStmt = $pdo->prepare('
        SELECT t.name, t.slug 
        FROM blog_tags t 
        INNER JOIN blog_post_tags pt ON t.id = pt.tag_id 
        WHERE pt.post_id = ?
        ORDER BY t.name
    ');
    $tagStmt->execute([$post['id']]);
    $post['tags'] = $tagStmt->fetchAll();
}
unset($post);

// Get sidebar data

// Categories with post counts
$categoriesStmt = $pdo->query('
    SELECT c.id, c.name, c.slug, COUNT(p.id) as post_count
    FROM blog_categories c
    LEFT JOIN blog_posts p ON c.id = p.category_id AND p.status = \'published\' AND p.deleted_at IS NULL
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.sort_order, c.name
');
$categories = $categoriesStmt->fetchAll();

// Popular tags (tag cloud)
$tagsStmt = $pdo->query('
    SELECT t.name, t.slug, COUNT(pt.post_id) as post_count
    FROM blog_tags t
    INNER JOIN blog_post_tags pt ON t.id = pt.tag_id
    INNER JOIN blog_posts p ON pt.post_id = p.id AND p.status = \'published\' AND p.deleted_at IS NULL
    GROUP BY t.id
    HAVING post_count > 0
    ORDER BY post_count DESC
    LIMIT 15
');
$popularTags = $tagsStmt->fetchAll();

// Recent posts
$recentStmt = $pdo->query('
    SELECT id, title, slug, featured_image_path, published_at
    FROM blog_posts
    WHERE status = \'published\' AND deleted_at IS NULL
    ORDER BY published_at DESC
    LIMIT 5
');
$recentPosts = $recentStmt->fetchAll();

// Archive by month
$archiveStmt = $pdo->query('
    SELECT DATE_FORMAT(published_at, \'%Y-%m\') as archive_date, 
           DATE_FORMAT(published_at, \'%M %Y\') as archive_label,
           COUNT(*) as post_count
    FROM blog_posts
    WHERE status = \'published\' AND deleted_at IS NULL AND published_at IS NOT NULL
    GROUP BY DATE_FORMAT(published_at, \'%Y-%m\')
    ORDER BY archive_date DESC
    LIMIT 12
');
$archives = $archiveStmt->fetchAll();

// Get current filter info for display
$currentCategory = null;
if ($categorySlug) {
    $catStmt = $pdo->prepare('SELECT name, description FROM blog_categories WHERE slug = ?');
    $catStmt->execute([$categorySlug]);
    $currentCategory = $catStmt->fetch();
}

$currentTag = null;
if ($tagSlug) {
    $tagInfoStmt = $pdo->prepare('SELECT name FROM blog_tags WHERE slug = ?');
    $tagInfoStmt->execute([$tagSlug]);
    $currentTag = $tagInfoStmt->fetch();
}

// Helper function to build pagination URLs
function buildPageUrl($pageNum, $categorySlug, $tagSlug, $archive, $search, $sort, $viewMode) {
    $params = [];
    if ($categorySlug) $params['category'] = $categorySlug;
    if ($tagSlug) $params['tag'] = $tagSlug;
    if ($archive) $params['archive'] = $archive;
    if ($search) $params['search'] = $search;
    if ($sort !== 'newest') $params['sort'] = $sort;
    if ($viewMode !== 'grid') $params['view'] = $viewMode;
    if ($pageNum > 1) $params['page'] = $pageNum;
    
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Blog CSS -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/pages/blog.css">

<!-- Blog Hero Section -->
<section class="blog-hero">
    <div class="o-container">
        <div class="blog-hero__content">
            <?php if ($search): ?>
                <div class="blog-hero__badge">
                    <i class="fas fa-search"></i>
                    Search Results
                </div>
                <h1 class="blog-hero__title">"<?php echo e($search); ?>"</h1>
                <p class="blog-hero__subtitle">Found <?php echo number_format($totalPosts); ?> article<?php echo $totalPosts !== 1 ? 's' : ''; ?> matching your search</p>
            <?php elseif ($currentCategory): ?>
                <div class="blog-hero__badge">
                    <i class="fas fa-folder"></i>
                    Category
                </div>
                <h1 class="blog-hero__title"><?php echo e($currentCategory['name']); ?></h1>
                <?php if ($currentCategory['description']): ?>
                    <p class="blog-hero__subtitle"><?php echo e($currentCategory['description']); ?></p>
                <?php else: ?>
                    <p class="blog-hero__subtitle">Explore our latest articles in <?php echo e($currentCategory['name']); ?></p>
                <?php endif; ?>
            <?php elseif ($currentTag): ?>
                <div class="blog-hero__badge">
                    <i class="fas fa-tag"></i>
                    Tag
                </div>
                <h1 class="blog-hero__title">#<?php echo e($currentTag['name']); ?></h1>
                <p class="blog-hero__subtitle">All posts tagged with "<?php echo e($currentTag['name']); ?>"</p>
            <?php elseif ($archive): ?>
                <div class="blog-hero__badge">
                    <i class="fas fa-calendar-alt"></i>
                    Archive
                </div>
                <h1 class="blog-hero__title"><?php echo e(date('F Y', strtotime($archive . '-01'))); ?></h1>
                <p class="blog-hero__subtitle">Articles published in <?php echo e(date('F Y', strtotime($archive . '-01'))); ?></p>
            <?php else: ?>
                <div class="blog-hero__badge">
                    <i class="fas fa-newspaper"></i>
                    Our Blog
                </div>
                <h1 class="blog-hero__title">Insights & Stories</h1>
                <p class="blog-hero__subtitle">Discover the latest in solar energy, sustainable farming, and innovative agricultural solutions</p>
            <?php endif; ?>
            
            <!-- Search Form -->
            <div class="blog-hero__search">
                <form method="GET" action="index.php" class="blog-hero__search-form">
                    <input type="text" name="search" class="blog-hero__search-input" placeholder="Search articles..." value="<?php echo e($search ?? ''); ?>">
                    <?php if ($categorySlug): ?><input type="hidden" name="category" value="<?php echo e($categorySlug); ?>"><?php endif; ?>
                    <?php if ($tagSlug): ?><input type="hidden" name="tag" value="<?php echo e($tagSlug); ?>"><?php endif; ?>
                    <?php if ($archive): ?><input type="hidden" name="archive" value="<?php echo e($archive); ?>"><?php endif; ?>
                    <button type="submit" class="blog-hero__search-btn" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Blog Content -->
<section class="blog-content">
    <div class="o-container">
        <div class="blog-layout">
            <!-- Main Content Area -->
            <main class="blog-main">
                <!-- Posts Header -->
                <div class="blog-posts-header">
                    <div class="blog-posts-count">
                        <strong><?php echo number_format($totalPosts); ?></strong> <?php echo $totalPosts === 1 ? 'article' : 'articles'; ?> found
                    </div>
                    <div class="blog-sort">
                        <span class="blog-sort__label">Sort by:</span>
                        <a href="<?php echo buildPageUrl(1, $categorySlug, $tagSlug, $archive, $search, 'newest', $viewMode); ?>" 
                           class="blog-sort__btn <?php echo $sort === 'newest' ? 'blog-sort__btn--active' : ''; ?>">
                            Newest
                        </a>
                        <a href="<?php echo buildPageUrl(1, $categorySlug, $tagSlug, $archive, $search, 'popular', $viewMode); ?>" 
                           class="blog-sort__btn <?php echo $sort === 'popular' ? 'blog-sort__btn--active' : ''; ?>">
                            Most Viewed
                        </a>
                        <a href="<?php echo buildPageUrl(1, $categorySlug, $tagSlug, $archive, $search, 'commented', $viewMode); ?>" 
                           class="blog-sort__btn <?php echo $sort === 'commented' ? 'blog-sort__btn--active' : ''; ?>">
                            Most Discussed
                        </a>
                    </div>
                </div>

                <!-- Posts -->
                <?php if (empty($posts)): ?>
                    <!-- Empty State -->
                    <div class="blog-empty">
                        <div class="blog-empty__icon">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <h2 class="blog-empty__title">No articles found</h2>
                        <p class="blog-empty__text">
                            <?php if ($search): ?>
                                We couldn't find any articles matching "<?php echo e($search); ?>". Try different keywords or browse our categories.
                            <?php elseif ($categorySlug || $tagSlug || $archive): ?>
                                No articles match the selected filter. Try selecting a different category or clearing your filters.
                            <?php else: ?>
                                No articles have been published yet. Check back soon!
                            <?php endif; ?>
                        </p>
                        <a href="index.php" class="c-btn c-btn--primary">
                            <i class="fas fa-arrow-left"></i> View All Articles
                        </a>
                    </div>
                <?php else: ?>
                    <div class="blog-posts-grid blog-posts-grid--grid">
                        <?php foreach ($posts as $index => $post): ?>
                            <article class="blog-card">
                                <?php if ($post['featured_image_path']): ?>
                                    <div class="blog-card__image-wrapper">
                                        <a href="post.php?slug=<?php echo e($post['slug']); ?>">
                                            <img src="<?php echo e(BASE_PATH . '/' . $post['featured_image_path']); ?>" 
                                                 alt="<?php echo e($post['featured_image_alt'] ?? $post['title']); ?>" 
                                                 loading="lazy" 
                                                 class="blog-card__image">
                                        </a>
                                        <?php if ($post['category_name']): ?>
                                            <span class="blog-card__category-badge">
                                                <?php echo e($post['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="blog-card__content">
                                    <?php if (!$post['featured_image_path'] && $post['category_name']): ?>
                                        <a href="category.php?slug=<?php echo e($post['category_slug']); ?>" class="blog-card__category">
                                            <?php echo e($post['category_name']); ?>
                                        </a>
                                    <?php endif; ?>

                                    <h2 class="blog-card__title">
                                        <a href="post.php?slug=<?php echo e($post['slug']); ?>">
                                            <?php echo e($post['title']); ?>
                                        </a>
                                    </h2>

                                    <p class="blog-card__excerpt">
                                        <?php 
                                        $excerpt = $post['excerpt'] ?: strip_tags($post['content'] ?? '');
                                        echo e(substr($excerpt, 0, 150) . (strlen($excerpt) > 150 ? '...' : '')); 
                                        ?>
                                    </p>

                                    <?php if (!empty($post['tags'])): ?>
                                        <div class="blog-card__tags">
                                            <?php foreach (array_slice($post['tags'], 0, 3) as $tag): ?>
                                                <a href="index.php?tag=<?php echo e($tag['slug']); ?>" class="blog-card__tag">
                                                    #<?php echo e($tag['name']); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="blog-card__meta">
                                        <div class="blog-card__author">
                                            <div class="blog-card__author-avatar">
                                                <?php echo strtoupper(substr($post['author_name'] ?? 'U', 0, 1)); ?>
                                            </div>
                                            <div class="blog-card__author-info">
                                                <span class="blog-card__author-name"><?php echo e($post['author_name'] ?? 'Unknown'); ?></span>
                                                <span class="blog-card__author-date"><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="blog-card__stats">
                                            <span class="blog-card__stat">
                                                <i class="fas fa-eye"></i>
                                                <?php echo formatNumber($post['view_count']); ?>
                                            </span>
                                            <span class="blog-card__stat">
                                                <i class="fas fa-comment"></i>
                                                <?php echo $post['comment_count']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="pagination" aria-label="Blog pagination">
                            <ul class="pagination__list">
                                <!-- Previous -->
                                <?php if ($page > 1): ?>
                                    <li>
                                        <a href="<?php echo buildPageUrl($page - 1, $categorySlug, $tagSlug, $archive, $search, $sort, $viewMode); ?>" 
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
                                        <a href="<?php echo buildPageUrl(1, $categorySlug, $tagSlug, $archive, $search, $sort, $viewMode); ?>" 
                                           class="pagination__link">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="pagination__ellipsis">...</li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li>
                                        <a href="<?php echo buildPageUrl($i, $categorySlug, $tagSlug, $archive, $search, $sort, $viewMode); ?>" 
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
                                        <a href="<?php echo buildPageUrl($totalPages, $categorySlug, $tagSlug, $archive, $search, $sort, $viewMode); ?>" 
                                           class="pagination__link"><?php echo $totalPages; ?></a>
                                    </li>
                                <?php endif; ?>

                                <!-- Next -->
                                <?php if ($page < $totalPages): ?>
                                    <li>
                                        <a href="<?php echo buildPageUrl($page + 1, $categorySlug, $tagSlug, $archive, $search, $sort, $viewMode); ?>" 
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
                        Categories
                    </h3>
                    <ul class="sidebar-categories__list">
                        <li class="sidebar-categories__item">
                            <a href="index.php" class="sidebar-categories__link <?php echo !$categorySlug ? 'sidebar-categories__link--active' : ''; ?>">
                                <span>All Posts</span>
                                <span class="sidebar-categories__count"><?php echo $totalPosts; ?></span>
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <?php if ($cat['post_count'] > 0): ?>
                                <li class="sidebar-categories__item">
                                    <a href="category.php?slug=<?php echo e($cat['slug']); ?>" 
                                       class="sidebar-categories__link <?php echo $categorySlug === $cat['slug'] ? 'sidebar-categories__link--active' : ''; ?>">
                                        <span><?php echo e($cat['name']); ?></span>
                                        <span class="sidebar-categories__count"><?php echo $cat['post_count']; ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Popular Tags Widget -->
                <?php if (!empty($popularTags)): ?>
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget__title">
                            <i class="fas fa-tags"></i>
                            Popular Tags
                        </h3>
                        <div class="sidebar-tags__cloud">
                            <?php foreach ($popularTags as $tag): ?>
                                <a href="tag.php?slug=<?php echo e($tag['slug']); ?>" 
                                   class="sidebar-tags__tag <?php echo $tagSlug === $tag['slug'] ? 'sidebar-tags__tag--active' : ''; ?>">
                                    <?php echo e($tag['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recent Posts Widget -->
                <?php if (!empty($recentPosts)): ?>
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget__title">
                            <i class="fas fa-clock"></i>
                            Recent Posts
                        </h3>
                        <ul class="sidebar-recent__list">
                            <?php foreach ($recentPosts as $recent): ?>
                                <li class="sidebar-recent__item">
                                    <?php if ($recent['featured_image_path']): ?>
                                        <img src="<?php echo e(BASE_PATH . '/' . $recent['featured_image_path']); ?>" 
                                             alt="" 
                                             loading="lazy" 
                                             class="sidebar-recent__image">
                                    <?php endif; ?>
                                    <div class="sidebar-recent__content">
                                        <h4 class="sidebar-recent__title">
                                            <a href="post.php?slug=<?php echo e($recent['slug']); ?>">
                                                <?php echo e(strlen($recent['title']) > 50 ? substr($recent['title'], 0, 50) . '...' : $recent['title']); ?>
                                            </a>
                                        </h4>
                                        <span class="sidebar-recent__date">
                                            <?php echo date('M j, Y', strtotime($recent['published_at'])); ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Archive Widget -->
                <?php if (!empty($archives)): ?>
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget__title">
                            <i class="fas fa-calendar"></i>
                            Archives
                        </h3>
                        <ul class="sidebar-archive__list">
                            <?php foreach (array_slice($archives, 0, 6) as $arch): ?>
                                <li class="sidebar-archive__item">
                                    <a href="archive.php?date=<?php echo e($arch['archive_date']); ?>" 
                                       class="sidebar-archive__link <?php echo $archive === $arch['archive_date'] ? 'sidebar-archive__link--active' : ''; ?>">
                                        <span><?php echo e($arch['archive_label']); ?></span>
                                        <span class="sidebar-categories__count"><?php echo $arch['post_count']; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Newsletter Widget -->
                <div class="sidebar-widget sidebar-newsletter">
                    <h3 class="sidebar-widget__title sidebar-newsletter__title">
                        <i class="fas fa-envelope"></i>
                        Newsletter
                    </h3>
                    <p class="sidebar-newsletter__text">
                        Get the latest articles and insights delivered straight to your inbox.
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

<?php 
// Helper function to format numbers
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}

require_once __DIR__ . '/../includes/footer.php'; 
?>
