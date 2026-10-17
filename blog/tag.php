<?php
/**
 * Tulima Solar - Blog Tag Page
 * Modern, clean redesign for tag archive
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

// Get tag slug from URL
$tagSlug = isset($_GET['slug']) ? trim($_GET['slug']) : null;

if (!$tagSlug) {
    http_response_code(404);
    $pageTitle = 'Tag Not Found';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <!-- Blog CSS -->
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/pages/blog.css">
    
    <div class="blog-empty" style="padding: var(--spacing-20) var(--spacing-4);">
        <div class="blog-empty__icon">
            <i class="fas fa-tag"></i>
        </div>
        <h1 class="blog-empty__title">Tag Not Found</h1>
        <p class="blog-empty__text">The tag you're looking for doesn't exist or has been removed.</p>
        <a href="index.php" class="c-btn c-btn--primary">
            <i class="fas fa-arrow-left"></i> Back to Blog
        </a>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Get tag info
$tagStmt = $pdo->prepare('SELECT id, name, slug FROM blog_tags WHERE slug = ?');
$tagStmt->execute([$tagSlug]);
$tag = $tagStmt->fetch();

if (!$tag) {
    http_response_code(404);
    $pageTitle = 'Tag Not Found';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <!-- Blog CSS -->
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/pages/blog.css">
    
    <div class="blog-empty" style="padding: var(--spacing-20) var(--spacing-4);">
        <div class="blog-empty__icon">
            <i class="fas fa-tag"></i>
        </div>
        <h1 class="blog-empty__title">Tag Not Found</h1>
        <p class="blog-empty__text">The tag "<?php echo e($tagSlug); ?>" doesn't exist or has been removed.</p>
        <a href="index.php" class="c-btn c-btn--primary">
            <i class="fas fa-arrow-left"></i> Back to Blog
        </a>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = 'Tag: ' . $tag['name'];

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;

// Count total posts for this tag
$countStmt = $pdo->prepare('
    SELECT COUNT(DISTINCT p.id) as total
    FROM blog_posts p
    INNER JOIN blog_post_tags pt ON p.id = pt.post_id
    WHERE p.status = \'published\' 
    AND p.deleted_at IS NULL 
    AND pt.tag_id = ?
');
$countStmt->execute([$tag['id']]);
$totalPosts = (int)$countStmt->fetch()['total'];
$totalPages = max(1, ceil($totalPosts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Get posts for this tag
$postsStmt = $pdo->prepare('
    SELECT DISTINCT
        p.id, p.title, p.slug, p.excerpt, p.featured_image_path, p.featured_image_alt,
        p.view_count, p.like_count, p.comment_count, p.published_at,
        c.id as category_id, c.name as category_name, c.slug as category_slug,
        a.username as author_name, a.email as author_email
    FROM blog_posts p
    INNER JOIN blog_post_tags pt ON p.id = pt.post_id
    LEFT JOIN blog_categories c ON p.category_id = c.id
    LEFT JOIN admins a ON p.created_by = a.id
    WHERE p.status = \'published\' 
    AND p.deleted_at IS NULL 
    AND pt.tag_id = ?
    ORDER BY p.published_at DESC
    LIMIT ' . $offset . ', ' . $perPage
);
$postsStmt->execute([$tag['id']]);
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

// Helper function to build pagination URLs
function buildTagPageUrl($pageNum, $slug) {
    $params = ['slug' => $slug];
    if ($pageNum > 1) $params['page'] = $pageNum;
    return 'tag.php?' . http_build_query($params);
}

// Helper function to format numbers
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Blog CSS -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/pages/blog.css">

<!-- Archive Header -->
<header class="archive-header">
    <div class="o-container">
        <div class="archive-header__icon">
            <i class="fas fa-tag"></i>
        </div>
        <span class="archive-header__label">Tag</span>
        <h1 class="archive-header__title">#<?php echo e($tag['name']); ?></h1>
        <p class="archive-header__description">All posts tagged with "<?php echo e($tag['name']); ?>"</p>
    </div>
</header>

<!-- Blog Content -->
<section class="blog-content">
    <div class="o-container">
        <div class="blog-layout">
            <!-- Main Content Area -->
            <main class="blog-main">
                <!-- Posts Header -->
                <div class="blog-posts-header">
                    <div class="blog-posts-count">
                        <strong><?php echo number_format($totalPosts); ?></strong> <?php echo $totalPosts === 1 ? 'article' : 'articles'; ?> with this tag
                    </div>
                    <div class="blog-sort">
                        <a href="index.php" class="c-btn c-btn--outline" style="padding: var(--spacing-2) var(--spacing-4);">
                            <i class="fas fa-arrow-left"></i> All Articles
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
                            No articles have been tagged with "<?php echo e($tag['name']); ?>" yet. Check back soon!
                        </p>
                        <a href="index.php" class="c-btn c-btn--primary">
                            <i class="fas fa-arrow-left"></i> View All Articles
                        </a>
                    </div>
                <?php else: ?>
                    <div class="blog-posts-grid blog-posts-grid--grid">
                        <?php foreach ($posts as $post): ?>
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
                                            <?php foreach (array_slice($post['tags'], 0, 3) as $postTag): ?>
                                                <a href="tag.php?slug=<?php echo e($postTag['slug']); ?>" 
                                                   class="blog-card__tag <?php echo $postTag['slug'] === $tagSlug ? 'blog-card__tag--active' : ''; ?>">
                                                    #<?php echo e($postTag['name']); ?>
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
                                        <a href="<?php echo buildTagPageUrl($page - 1, $tagSlug); ?>" 
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
                                        <a href="<?php echo buildTagPageUrl(1, $tagSlug); ?>" 
                                           class="pagination__link">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="pagination__ellipsis">...</li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li>
                                        <a href="<?php echo buildTagPageUrl($i, $tagSlug); ?>" 
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
                                        <a href="<?php echo buildTagPageUrl($totalPages, $tagSlug); ?>" 
                                           class="pagination__link"><?php echo $totalPages; ?></a>
                                    </li>
                                <?php endif; ?>

                                <!-- Next -->
                                <?php if ($page < $totalPages): ?>
                                    <li>
                                        <a href="<?php echo buildTagPageUrl($page + 1, $tagSlug); ?>" 
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
                        <?php foreach ($categories as $cat): ?>
                            <?php if ($cat['post_count'] > 0): ?>
                                <li class="sidebar-categories__item">
                                    <a href="category.php?slug=<?php echo e($cat['slug']); ?>" class="sidebar-categories__link">
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
                            <?php foreach ($popularTags as $popularTag): ?>
                                <a href="tag.php?slug=<?php echo e($popularTag['slug']); ?>" 
                                   class="sidebar-tags__tag <?php echo $popularTag['slug'] === $tagSlug ? 'sidebar-tags__tag--active' : ''; ?>">
                                    <?php echo e($popularTag['name']); ?>
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
                                    <a href="archive.php?date=<?php echo e($arch['archive_date']); ?>" class="sidebar-archive__link">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
