<?php
/**
 * Tulima Solar - Blog Post Detail Page
 * Modern, clean redesign for individual blog post
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

// Get post slug from URL
$postSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (!$postSlug) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/../pages/404-new.php';
    exit;
}

// Fetch the post
$stmt = $pdo->prepare('
    SELECT p.*, c.name as category_name, c.slug as category_slug,
           a.username as author_name, a.email as author_email
    FROM blog_posts p
    LEFT JOIN blog_categories c ON p.category_id = c.id
    LEFT JOIN admins a ON p.created_by = a.id
    WHERE p.slug = ? AND p.status = \'published\' AND p.deleted_at IS NULL
');
$stmt->execute([$postSlug]);
$post = $stmt->fetch();

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/../pages/404.php';
    exit;
}

// Increment view count
$pdo->prepare('UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?')->execute([$post['id']]);

// Get post tags
$tagsStmt = $pdo->prepare('
    SELECT t.name, t.slug 
    FROM blog_tags t 
    INNER JOIN blog_post_tags pt ON t.id = pt.tag_id 
    WHERE pt.post_id = ?
    ORDER BY t.name
');
$tagsStmt->execute([$post['id']]);
$tags = $tagsStmt->fetchAll();

// Check if user has liked this post
$userLiked = false;
$userId = $_SESSION['admin_id'] ?? null;
if ($userId) {
    $likeCheck = $pdo->prepare('SELECT 1 FROM blog_post_likes WHERE post_id = ? AND user_id = ?');
    $likeCheck->execute([$post['id'], $userId]);
    $userLiked = (bool)$likeCheck->fetch();
}

// Get related posts (same category, excluding current)
$relatedStmt = $pdo->prepare('
    SELECT id, title, slug, featured_image_path, published_at, excerpt
    FROM blog_posts
    WHERE category_id = ? AND id != ? AND status = \'published\' AND deleted_at IS NULL
    ORDER BY published_at DESC
    LIMIT 3
');
$relatedStmt->execute([$post['category_id'], $post['id']]);
$relatedPosts = $relatedStmt->fetchAll();

// Get previous and next posts
$prevStmt = $pdo->prepare('
    SELECT slug, title FROM blog_posts 
    WHERE status = \'published\' AND deleted_at IS NULL AND published_at < ? 
    ORDER BY published_at DESC LIMIT 1
');
$prevStmt->execute([$post['published_at']]);
$prevPost = $prevStmt->fetch();

$nextStmt = $pdo->prepare('
    SELECT slug, title FROM blog_posts 
    WHERE status = \'published\' AND deleted_at IS NULL AND published_at > ? 
    ORDER BY published_at ASC LIMIT 1
');
$nextStmt->execute([$post['published_at']]);
$nextPost = $nextStmt->fetch();

// Get approved comments with threading
$commentsStmt = $pdo->prepare('
    SELECT c.*, a.username as admin_username
    FROM blog_comments c
    LEFT JOIN admins a ON c.user_id = a.id
    WHERE c.post_id = ? AND c.status = \'approved\'
    ORDER BY c.created_at ASC
');
$commentsStmt->execute([$post['id']]);
$allComments = $commentsStmt->fetchAll();

// Build threaded comments structure
function buildCommentTree(array $comments, $parentId = null): array {
    $tree = [];
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parentId) {
            $children = buildCommentTree($comments, $comment['id']);
            if ($children) {
                $comment['children'] = $children;
            }
            $tree[] = $comment;
        }
    }
    return $tree;
}

$commentTree = buildCommentTree($allComments);

// Count total approved comments
$totalComments = count($allComments);

// Calculate read time
$wordCount = str_word_count(strip_tags($post['content']));
$readTime = max(1, ceil($wordCount / 200));

// SEO meta
$pageTitle = ($post['meta_title'] ?: $post['title']) . ' | Blog';
$metaDescription = $post['meta_description'] ?: substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 160);
$canonicalUrl = BASE_PATH . '/blog/post.php?slug=' . urlencode($post['slug']);

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Blog CSS -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/pages/blog.css">

<!-- Dynamic SEO / Open Graph Tags -->
<meta property="og:title" content="<?php echo e($post['title']); ?>">
<meta property="og:description" content="<?php echo e($metaDescription); ?>">
<meta property="og:url" content="<?php echo e($canonicalUrl); ?>">
<meta property="og:type" content="article">
<meta property="og:site_name" content="<?php echo e($siteName); ?>">
<?php if ($post['featured_image_path']): ?>
<meta property="og:image" content="<?php echo e(BASE_PATH . '/' . $post['featured_image_path']); ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo e($post['title']); ?>">
<meta name="twitter:description" content="<?php echo e($metaDescription); ?>">
<?php if ($post['featured_image_path']): ?>
<meta name="twitter:image" content="<?php echo e(BASE_PATH . '/' . $post['featured_image_path']); ?>">
<?php endif; ?>
<link rel="canonical" href="<?php echo e($canonicalUrl); ?>">

<!-- Article Schema JSON-LD -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": <?php echo json_encode($post['title']); ?>,
    "description": <?php echo json_encode($metaDescription); ?>,
    "author": {
        "@type": "Person",
        "name": <?php echo json_encode($post['author_name'] ?? 'Unknown'); ?>
    },
    "datePublished": <?php echo json_encode($post['published_at']); ?>,
    "dateModified": <?php echo json_encode($post['updated_at']); ?>,
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": <?php echo json_encode($canonicalUrl); ?>
    },
    <?php if ($post['featured_image_path']): ?>
    "image": <?php echo json_encode(BASE_PATH . '/' . $post['featured_image_path']); ?>,
    <?php endif; ?>
    "publisher": {
        "@type": "Organization",
        "name": <?php echo json_encode($siteName); ?>
    }
}
</script>

<!-- Single Post -->
<article class="single-post">
    <!-- Post Header -->
    <header class="single-post__header">
        <?php if ($post['category_name']): ?>
        <a href="category.php?slug=<?php echo e($post['category_slug']); ?>" class="single-post__category">
            <?php echo e($post['category_name']); ?>
        </a>
        <?php endif; ?>
        
        <h1 class="single-post__title"><?php echo e($post['title']); ?></h1>
        
        <div class="single-post__meta">
            <span class="single-post__meta-item">
                <a href="author.php?username=<?php echo e($post['author_name']); ?>" class="single-post__author-link">
                    <i class="fas fa-user"></i>
                    <?php echo e($post['author_name'] ?? 'Unknown'); ?>
                </a>
            </span>
            <span class="single-post__meta-item">
                <i class="fas fa-calendar"></i>
                <?php echo date('F j, Y', strtotime($post['published_at'])); ?>
            </span>
            <span class="single-post__meta-item">
                <i class="fas fa-clock"></i>
                <?php echo $readTime; ?> min read
            </span>
            <span class="single-post__meta-item">
                <i class="fas fa-eye"></i>
                <?php echo number_format($post['view_count'] + 1); ?> views
            </span>
        </div>
    </header>

    <!-- Featured Image -->
    <?php if ($post['featured_image_path']): ?>
    <div class="single-post__featured-image">
        <img src="<?php echo e(BASE_PATH . '/' . $post['featured_image_path']); ?>" 
             alt="<?php echo e($post['featured_image_alt'] ?? $post['title']); ?>">
    </div>
    <?php endif; ?>

    <!-- Post Content -->
    <div class="single-post__content">
        <?php 
        // Output sanitized HTML content
        $allowedTags = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><pre><code><a><img><figure><figcaption><table><thead><tbody><tr><th><td><div><span><iframe>';
        echo strip_tags($post['content'], $allowedTags); 
        ?>
    </div>

    <!-- Tags -->
    <?php if (!empty($tags)): ?>
    <div class="single-post__tags">
        <span class="single-post__tags-label">
            <i class="fas fa-tags"></i> Tags:
        </span>
        <?php foreach ($tags as $tag): ?>
            <a href="index.php?tag=<?php echo e($tag['slug']); ?>" class="single-post__tag">
                <?php echo e($tag['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Share Buttons -->
    <div class="single-post__share">
        <span class="single-post__share-label">
            <i class="fas fa-share-alt"></i> Share this article
        </span>
        <div class="single-post__share-buttons">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($canonicalUrl); ?>" 
               target="_blank" 
               rel="noopener" 
               class="single-post__share-btn single-post__share-btn--facebook">
                <i class="fab fa-facebook-f"></i> Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($canonicalUrl); ?>&text=<?php echo urlencode($post['title']); ?>" 
               target="_blank" 
               rel="noopener" 
               class="single-post__share-btn single-post__share-btn--twitter">
                <i class="fab fa-x-twitter"></i> Twitter
            </a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($canonicalUrl); ?>&title=<?php echo urlencode($post['title']); ?>" 
               target="_blank" 
               rel="noopener" 
               class="single-post__share-btn single-post__share-btn--linkedin">
                <i class="fab fa-linkedin-in"></i> LinkedIn
            </a>
            <a href="https://wa.me/?text=<?php echo urlencode($post['title'] . ' - ' . $canonicalUrl); ?>" 
               target="_blank" 
               rel="noopener" 
               class="single-post__share-btn single-post__share-btn--whatsapp">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
            <button type="button" 
                    class="single-post__share-btn single-post__share-btn--copy btn-copy-link" 
                    data-url="<?php echo e($canonicalUrl); ?>">
                <i class="fas fa-link"></i> Copy Link
            </button>
        </div>
    </div>

    <!-- Like Button -->
    <div class="single-post__like">
        <p class="single-post__like-text">Did you enjoy this article?</p>
        <button type="button" 
                id="likeButton" 
                class="single-post__like-btn <?php echo $userLiked ? 'single-post__like-btn--liked' : ''; ?>" 
                data-post-id="<?php echo $post['id']; ?>" 
                data-liked="<?php echo $userLiked ? '1' : '0'; ?>">
            <i class="fas fa-heart"></i>
            <span id="likeCount"><?php echo number_format($post['like_count']); ?></span> Likes
        </button>
        <?php if (!$userId): ?>
        <p class="comment-form__note">
            <a href="<?php echo BASE_PATH; ?>/admin/login.php" style="color: var(--primary-purple);">Login</a> to like this post
        </p>
        <?php endif; ?>
    </div>

    <!-- Author Bio -->
    <div class="single-post__author-bio">
        <div class="single-post__author-avatar">
            <?php echo strtoupper(substr($post['author_name'] ?? 'U', 0, 1)); ?>
        </div>
        <div class="single-post__author-info">
            <h4 class="single-post__author-name">
                <?php echo e($post['author_name'] ?? 'Unknown Author'); ?>
            </h4>
            <p class="single-post__author-role">Author at <?php echo e($siteName); ?></p>
            <p class="single-post__author-description">
                Sharing insights and expertise on solar energy, sustainable agriculture, and innovative farming solutions.
            </p>
        </div>
    </div>

    <!-- Post Navigation -->
    <div class="single-post__navigation">
        <?php if ($prevPost): ?>
        <a href="post.php?slug=<?php echo e($prevPost['slug']); ?>" class="single-post__nav-link">
            <span class="single-post__nav-label">
                <i class="fas fa-arrow-left"></i> Previous Article
            </span>
            <span class="single-post__nav-title">
                <?php echo e(strlen($prevPost['title']) > 60 ? substr($prevPost['title'], 0, 60) . '...' : $prevPost['title']); ?>
            </span>
        </a>
        <?php else: ?>
        <div></div>
        <?php endif; ?>
        
        <?php if ($nextPost): ?>
        <a href="post.php?slug=<?php echo e($nextPost['slug']); ?>" class="single-post__nav-link single-post__nav-link--next">
            <span class="single-post__nav-label">
                Next Article <i class="fas fa-arrow-right"></i>
            </span>
            <span class="single-post__nav-title">
                <?php echo e(strlen($nextPost['title']) > 60 ? substr($nextPost['title'], 0, 60) . '...' : $nextPost['title']); ?>
            </span>
        </a>
        <?php endif; ?>
    </div>
</article>

<!-- Comments Section -->
<section class="comments-section">
    <h3 class="comments-section__title">
        <i class="fas fa-comments"></i> 
        Comments
        <span class="comments-section__count"><?php echo $totalComments; ?></span>
    </h3>

    <!-- Comment Form -->
    <div class="comment-form">
        <h4 class="comment-form__title">Leave a Comment</h4>
        <form id="commentForm" method="POST" action="api.php">
            <input type="hidden" name="action" value="comment">
            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
            <input type="hidden" name="parent_id" id="commentParentId" value="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <?php if ($userId): ?>
                <!-- Logged in user -->
                <p class="comment-form__note" style="margin-bottom: var(--spacing-4);">
                    <i class="fas fa-user"></i>
                    Commenting as <strong><?php echo e($_SESSION['admin_username'] ?? 'User'); ?></strong>
                </p>
            <?php else: ?>
                <!-- Guest fields -->
                <div class="comment-form__row">
                    <div class="comment-form__field">
                        <label for="guest_name" class="comment-form__label">Name *</label>
                        <input type="text" class="comment-form__input" id="guest_name" name="guest_name" required>
                    </div>
                    <div class="comment-form__field">
                        <label for="guest_email" class="comment-form__label">Email *</label>
                        <input type="email" class="comment-form__input" id="guest_email" name="guest_email" required>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="comment-form__field">
                <label for="comment_content" class="comment-form__label">Comment *</label>
                <textarea class="comment-form__textarea" id="comment_content" name="content" rows="4" required placeholder="Write your comment here..."></textarea>
            </div>
            
            <div class="comment-form__actions">
                <button type="submit" class="c-btn c-btn--primary">
                    <i class="fas fa-paper-plane"></i> Submit Comment
                </button>
                <button type="button" id="cancelReply" class="c-btn c-btn--secondary" style="display: none;">
                    Cancel Reply
                </button>
            </div>
            
            <p class="comment-form__note">
                <i class="fas fa-info-circle"></i> Your comment will be reviewed before publishing.
            </p>
        </form>
    </div>

    <!-- Comments List -->
    <div id="commentsList" class="comments-list">
        <?php 
        // Render comments recursively
        function renderComments(array $comments, string $basePath, int $level = 0): void {
            $maxLevel = 3;
            foreach ($comments as $comment): 
                $indent = min($level, $maxLevel - 1) * 40;
                $authorName = $comment['admin_username'] ?? $comment['author_name'] ?? 'Anonymous';
                $authorInitial = strtoupper(substr($authorName, 0, 1));
        ?>
            <div class="comment <?php echo $level > 0 ? 'comment--reply' : ''; ?>" 
                 id="comment-<?php echo $comment['id']; ?>"
                 style="margin-left: <?php echo $indent; ?>px;">
                <div class="comment__header">
                    <div class="comment__avatar <?php echo $comment['user_id'] ? '' : 'comment__avatar--guest'; ?>">
                        <?php echo $authorInitial; ?>
                    </div>
                    <div class="comment__info">
                        <span class="comment__author">
                            <?php echo e($authorName); ?>
                            <?php if ($comment['user_id']): ?>
                                <span class="comment__badge">Staff</span>
                            <?php endif; ?>
                        </span>
                        <span class="comment__date">
                            <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                        </span>
                    </div>
                </div>
                <div class="comment__content">
                    <?php echo nl2br(e($comment['content'])); ?>
                </div>
                <?php if ($level < $maxLevel - 1): ?>
                <button type="button" 
                        class="comment__reply-btn" 
                        data-comment-id="<?php echo $comment['id']; ?>" 
                        data-author="<?php echo e($authorName); ?>">
                    <i class="fas fa-reply"></i> Reply
                </button>
                <?php endif; ?>
            </div>
            <?php 
            if (!empty($comment['children'])) {
                renderComments($comment['children'], $basePath, $level + 1);
            }
            ?>
        <?php endforeach;
        }
        
        if (!empty($commentTree)) {
            renderComments($commentTree, BASE_PATH, 0);
        } else {
            echo '<div class="comments-empty">';
            echo '<i class="fas fa-comment-slash"></i>';
            echo '<p>No comments yet. Be the first to comment!</p>';
            echo '</div>';
        }
        ?>
    </div>
</section>

<!-- Related Posts -->
<?php if (!empty($relatedPosts)): ?>
<section class="related-posts">
    <h3 class="related-posts__title">Related Articles</h3>
    <div class="related-posts__grid">
        <?php foreach ($relatedPosts as $related): ?>
            <a href="post.php?slug=<?php echo e($related['slug']); ?>" class="related-post-card">
                <?php if ($related['featured_image_path']): ?>
                    <img src="<?php echo e(BASE_PATH . '/' . $related['featured_image_path']); ?>" 
                         alt="" 
                         loading="lazy" 
                         class="related-post-card__image">
                <?php else: ?>
                    <div class="related-post-card__image" style="background: var(--gradient-primary); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-newspaper" style="font-size: 3rem; color: rgba(255,255,255,0.5);"></i>
                    </div>
                <?php endif; ?>
                <div class="related-post-card__content">
                    <h4 class="related-post-card__title">
                        <?php echo e(strlen($related['title']) > 60 ? substr($related['title'], 0, 60) . '...' : $related['title']); ?>
                    </h4>
                    <span class="related-post-card__date">
                        <?php echo date('M j, Y', strtotime($related['published_at'])); ?>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Blog JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy Link functionality
    const copyBtn = document.querySelector('.btn-copy-link');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const url = this.dataset.url;
            navigator.clipboard.writeText(url).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    }

    // Like Button functionality
    const likeButton = document.getElementById('likeButton');
    if (likeButton) {
        likeButton.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const isLiked = this.dataset.liked === '1';
            
            fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=like&post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeCount = document.getElementById('likeCount');
                    likeCount.textContent = data.like_count;
                    likeButton.dataset.liked = data.liked ? '1' : '0';
                    likeButton.classList.toggle('single-post__like-btn--liked', data.liked);
                }
            });
        });
    }

    // Comment Reply functionality
    const replyButtons = document.querySelectorAll('.comment__reply-btn');
    const parentIdInput = document.getElementById('commentParentId');
    const cancelReplyBtn = document.getElementById('cancelReply');
    const commentForm = document.getElementById('commentForm');
    const commentTextarea = document.getElementById('comment_content');

    replyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const authorName = this.dataset.author;
            
            parentIdInput.value = commentId;
            cancelReplyBtn.style.display = 'inline-flex';
            
            commentTextarea.placeholder = `Replying to ${authorName}...`;
            commentTextarea.focus();
            
            commentForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    cancelReplyBtn.addEventListener('click', function() {
        parentIdInput.value = '';
        this.style.display = 'none';
        commentTextarea.placeholder = 'Write your comment here...';
    });

    // Comment Form Submission
    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Comment submitted successfully!');
                this.reset();
                parentIdInput.value = '';
                cancelReplyBtn.style.display = 'none';
            } else {
                alert(data.error || 'Failed to submit comment.');
            }
        })
        .catch(() => {
            alert('An error occurred. Please try again.');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
