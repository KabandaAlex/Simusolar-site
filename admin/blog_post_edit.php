<?php
/**
 * Tulima Solar - Blog Post Editor
 * Create and edit blog posts with TinyMCE rich text editor
 * 
 * RBAC: Requires at least author role
 * - Authors can create new posts and edit their own posts only
 * - Editors and admins can edit any post
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/auth_helpers.php';
requirePostAccess(); // Requires minimum author role

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../includes/blog_helpers.php';

$pdo = getDb();
$currentNav = 'blog-posts';
$pageTitle = 'New Post';

$message = '';
$messageType = 'success';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to generate unique post slug
function generatePostSlug($pdo, $title, $excludeId = null) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Limit slug length
    if (strlen($slug) > 180) {
        $slug = substr($slug, 0, 180);
        $slug = preg_replace('/-[^-]*$/', '', $slug); // Remove partial word at end
    }
    
    $baseSlug = $slug;
    $counter = 1;
    
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = ? AND id != ? AND deleted_at IS NULL');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = ? AND deleted_at IS NULL');
            $stmt->execute([$slug]);
        }
        
        if (!$stmt->fetch()) {
            return $slug;
        }
        
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

// Get post ID for editing
$postId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$post = null;
$existingTags = [];

if ($postId) {
    // Check if user can edit this specific post
    requirePostEditAccess($postId);
    
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = ?');
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: blog_posts.php');
        exit;
    }
    
    $pageTitle = 'Edit Post';
    
    // Get existing tags for this post
    $stmt = $pdo->prepare('
        SELECT t.id, t.name 
        FROM blog_tags t 
        INNER JOIN blog_post_tags pt ON t.id = pt.tag_id 
        WHERE pt.post_id = ?
        ORDER BY t.name
    ');
    $stmt->execute([$postId]);
    $existingTags = $stmt->fetchAll();
}

// Get categories for dropdown
$categories = $pdo->query('
    SELECT id, name, slug 
    FROM blog_categories 
    WHERE is_active = 1 
    ORDER BY name
')->fetchAll();

// Get all tags for autocomplete
$allTags = $pdo->query('
    SELECT id, name, slug 
    FROM blog_tags 
    ORDER BY name
')->fetchAll();

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
        $content = sanitizeHtmlContent($_POST['content'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $metaKeywords = trim($_POST['meta_keywords'] ?? '');
        $featuredImage = trim($_POST['featured_image'] ?? '');
        $featuredImageAlt = trim($_POST['featured_image_alt'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $publishAt = trim($_POST['publish_at'] ?? '');
        $tagIds = $_POST['tag_ids'] ?? [];
        
        // Validate required fields
        $errors = [];
        if (empty($title)) {
            $errors[] = 'Title is required.';
        } elseif (strlen($title) > 255) {
            $errors[] = 'Title must be 255 characters or less.';
        }
        
        if (strlen($excerpt) > 500) {
            $errors[] = 'Excerpt must be 500 characters or less.';
        }
        
        if (strlen($metaTitle) > 255) {
            $errors[] = 'Meta title must be 255 characters or less.';
        }
        
        if (strlen($metaDescription) > 500) {
            $errors[] = 'Meta description must be 500 characters or less.';
        }
        
        // Validate status
        $allowedStatuses = ['draft', 'published', 'scheduled', 'archived'];
        if (!in_array($status, $allowedStatuses)) {
            $status = 'draft';
        }
        
        // Handle publish action
        if ($action === 'publish') {
            $status = 'published';
        }
        
        // Handle schedule
        if ($status === 'scheduled' && empty($publishAt)) {
            $errors[] = 'Publish date is required for scheduled posts.';
        }
        
        if (!empty($errors)) {
            $message = implode(' ', $errors);
            $messageType = 'error';
        } else {
            // Generate slug if not provided
            if (empty($slug)) {
                $slug = generatePostSlug($pdo, $title, $postId);
            } else {
                // Validate slug uniqueness
                if ($postId) {
                    $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = ? AND id != ? AND deleted_at IS NULL');
                    $stmt->execute([$slug, $postId]);
                } else {
                    $stmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = ? AND deleted_at IS NULL');
                    $stmt->execute([$slug]);
                }
                if ($stmt->fetch()) {
                    $slug = generatePostSlug($pdo, $slug, $postId);
                }
            }
            
            // Prepare dates
            $publishAtValue = null;
            $publishedAtValue = null;
            
            if ($status === 'published') {
                // If publishing now, set published_at
                if ($postId && $post && $post['status'] === 'published') {
                    $publishedAtValue = $post['published_at']; // Keep existing
                } else {
                    $publishedAtValue = date('Y-m-d H:i:s');
                }
            } elseif ($status === 'scheduled' && !empty($publishAt)) {
                $publishAtValue = date('Y-m-d H:i:s', strtotime($publishAt));
            }
            
            // Null handling for optional fields
            $excerptValue = $excerpt ?: null;
            $metaTitleValue = $metaTitle ?: null;
            $metaDescriptionValue = $metaDescription ?: null;
            $metaKeywordsValue = $metaKeywords ?: null;
            $featuredImageValue = $featuredImage ?: null;
            $featuredImageAltValue = $featuredImageAlt ?: null;
            
            try {
                $pdo->beginTransaction();
                
                if ($postId) {
                    // Update existing post
                    $stmt = $pdo->prepare('
                        UPDATE blog_posts SET 
                            title = ?,
                            slug = ?,
                            content = ?,
                            excerpt = ?,
                            category_id = ?,
                            meta_title = ?,
                            meta_description = ?,
                            canonical_url = ?,
                            featured_image_path = ?,
                            featured_image_alt = ?,
                            status = ?,
                            publish_at = ?,
                            published_at = ?,
                            updated_by = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ');
                    $stmt->execute([
                        $title,
                        $slug,
                        $content,
                        $excerptValue,
                        $categoryId,
                        $metaTitleValue,
                        $metaDescriptionValue,
                        null, // canonical_url
                        $featuredImageValue,
                        $featuredImageAltValue,
                        $status,
                        $publishAtValue,
                        $publishedAtValue,
                        $_SESSION['admin_id'] ?? null,
                        $postId
                    ]);
                    
                    logActivity('updated', 'blog_post', $postId, $title);
                    $message = 'Post updated successfully.';
                } else {
                    // Create new post
                    $stmt = $pdo->prepare('
                        INSERT INTO blog_posts (
                            title, slug, content, excerpt, category_id,
                            meta_title, meta_description, canonical_url,
                            featured_image_path, featured_image_alt,
                            status, publish_at, published_at,
                            created_by, updated_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([
                        $title,
                        $slug,
                        $content,
                        $excerptValue,
                        $categoryId,
                        $metaTitleValue,
                        $metaDescriptionValue,
                        null, // canonical_url
                        $featuredImageValue,
                        $featuredImageAltValue,
                        $status,
                        $publishAtValue,
                        $publishedAtValue,
                        $_SESSION['admin_id'] ?? null,
                        $_SESSION['admin_id'] ?? null
                    ]);
                    
                    $postId = (int)$pdo->lastInsertId();
                    
                    logActivity('created', 'blog_post', $postId, $title);
                    $message = 'Post created successfully.';
                }
                
                // Handle tags
                // First, remove existing tag associations
                $stmt = $pdo->prepare('DELETE FROM blog_post_tags WHERE post_id = ?');
                $stmt->execute([$postId]);
                
                // Add new tag associations
                if (!empty($tagIds) && is_array($tagIds)) {
                    $stmt = $pdo->prepare('INSERT INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)');
                    foreach ($tagIds as $tagId) {
                        $tagId = (int)$tagId;
                        if ($tagId > 0) {
                            $stmt->execute([$postId, $tagId]);
                        }
                    }
                }
                
                // Handle new tags from text input
                $newTagsText = trim($_POST['new_tags'] ?? '');
                if (!empty($newTagsText)) {
                    $newTagNames = array_map('trim', explode(',', $newTagsText));
                    foreach ($newTagNames as $tagName) {
                        if (empty($tagName)) continue;
                        
                        // Check if tag exists
                        $stmt = $pdo->prepare('SELECT id FROM blog_tags WHERE name = ?');
                        $stmt->execute([$tagName]);
                        $existingTag = $stmt->fetch();
                        
                        if ($existingTag) {
                            // Use existing tag
                            $tagId = (int)$existingTag['id'];
                        } else {
                            // Create new tag
                            $tagSlug = generatePostSlug($pdo, $tagName);
                            $stmt = $pdo->prepare('INSERT INTO blog_tags (name, slug) VALUES (?, ?)');
                            $stmt->execute([$tagName, $tagSlug]);
                            $tagId = (int)$pdo->lastInsertId();
                        }
                        
                        // Associate tag with post
                        $stmt = $pdo->prepare('INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)');
                        $stmt->execute([$postId, $tagId]);
                    }
                }
                
                $pdo->commit();
                $messageType = 'success';
                
                // Redirect to prevent double submission on refresh
                if (!$post) {
                    header('Location: blog_post_edit.php?id=' . $postId . '&saved=1');
                    exit;
                }
                
                // Refresh post data
                $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = ?');
                $stmt->execute([$postId]);
                $post = $stmt->fetch();
                
                // Refresh existing tags
                $stmt = $pdo->prepare('
                    SELECT t.id, t.name 
                    FROM blog_tags t 
                    INNER JOIN blog_post_tags pt ON t.id = pt.tag_id 
                    WHERE pt.post_id = ?
                    ORDER BY t.name
                ');
                $stmt->execute([$postId]);
                $existingTags = $stmt->fetchAll();
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = 'Error saving post: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Check for saved message from redirect
if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $message = 'Post saved successfully.';
    $messageType = 'success';
}

$basePath = BASE_PATH;

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title"><?php echo $postId ? 'Edit Post' : 'New Post'; ?></h1>
    <p class="page-header-enhanced__subtitle">
      <?php if ($post): ?>
        Last updated: <?php echo date('M j, Y \a\t g:i A', strtotime($post['updated_at'])); ?>
      <?php else: ?>
        Create a new blog post with rich content and SEO optimization.
      <?php endif; ?>
    </p>
  </div>
  <div class="page-header-enhanced__actions">
    <a href="<?php echo $basePath; ?>/admin/blog_posts.php" class="btn btn--outline">
      <svg viewBox="0 0 24 24" width="16" height="16"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back to Posts
    </a>
    <?php if ($post && $post['status'] === 'published'): ?>
      <a href="<?php echo $basePath; ?>/blog/<?php echo htmlspecialchars($post['slug']); ?>" target="_blank" class="btn btn--secondary">
        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        View Post
      </a>
    <?php endif; ?>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" id="post-message">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="admin-alert__close" onclick="this.parentElement.remove()">&times;</button>
  </div>
<?php endif; ?>

<form method="post" id="post-form" class="post-editor-form">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
  
  <div class=" post-editor-layout">
    <!-- Main Content Area -->
    <div class="post-editor-main">
      <!-- Title -->
      <div class="admin-card" style="margin-bottom: var(--space-4);">
        <div class="admin-card__body">
          <div class="form-group">
            <label for="title" class="form-label-lg">Title <span class="required">*</span></label>
            <input type="text" 
                   id="title" 
                   name="title" 
                   class="form-control form-control--lg" 
                   value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" 
                   placeholder="Enter post title..."
                   required
                   maxlength="255">
            <div class="form-hint">
              <span id="title-count">0</span> / 255 characters
            </div>
          </div>
          
          <div class="form-group">
            <label for="slug">Slug (URL)</label>
            <div class="slug-input-group">
              <span class="slug-prefix"><?php echo htmlspecialchars($basePath); ?>/blog/</span>
              <input type="text" 
                     id="slug" 
                     name="slug" 
                     class="form-control" 
                     value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>" 
                     placeholder="auto-generated-from-title">
              <button type="button" class="btn btn--outline btn--sm" onclick="generateSlug()" title="Generate from title">
                <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Content Editor -->
      <div class="admin-card" style="margin-bottom: var(--space-4);">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Content
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <textarea id="content" 
                      name="content" 
                      class="tinymce-editor"
                      rows="20"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>
      
      <!-- Excerpt -->
      <div class="admin-card" style="margin-bottom: var(--space-4);">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
            Excerpt
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <textarea id="excerpt" 
                      name="excerpt" 
                      class="form-control" 
                      rows="3"
                      maxlength="500"
                      placeholder="Brief summary for listings and SEO. Leave empty to auto-generate from content."><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
            <div class="form-hint">
              <span id="excerpt-count">0</span> / 500 characters. Used in post listings and SEO meta description fallback.
            </div>
          </div>
        </div>
      </div>
      
      <!-- SEO Section (Collapsible) -->
      <div class="admin-card seo-section" style="margin-bottom: var(--space-4);">
        <div class="admin-card__header collapsible" onclick="toggleSeoSection()">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            SEO Settings
            <span class="collapse-indicator">
              <svg viewBox="0 0 24 24" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </h3>
        </div>
        <div class="admin-card__body seo-content" id="seo-content">
          <div class="seo-preview">
            <p class="seo-preview__label">Search Engine Preview</p>
            <div class="seo-preview__result" id="seo-preview">
              <div class="seo-preview__title" id="seo-preview-title"><?php echo htmlspecialchars($post['meta_title'] ?? $post['title'] ?? 'Your Post Title'); ?></div>
              <div class="seo-preview__url"><?php echo htmlspecialchars($basePath); ?>/blog/<?php echo htmlspecialchars($post['slug'] ?? 'your-post-slug'); ?></div>
              <div class="seo-preview__description" id="seo-preview-desc"><?php echo htmlspecialchars($post['meta_description'] ?? $post['excerpt'] ?? 'Your post description will appear here...'); ?></div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="meta_title">Meta Title</label>
            <input type="text" 
                   id="meta_title" 
                   name="meta_title" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($post['meta_title'] ?? ''); ?>" 
                   placeholder="Leave empty to use post title"
                   maxlength="255">
            <div class="form-hint">
              <span id="meta-title-count">0</span> / 255 characters. Recommended: 50-60 characters.
            </div>
          </div>
          
          <div class="form-group">
            <label for="meta_description">Meta Description</label>
            <textarea id="meta_description" 
                      name="meta_description" 
                      class="form-control" 
                      rows="2"
                      maxlength="500"
                      placeholder="Leave empty to use excerpt"><?php echo htmlspecialchars($post['meta_description'] ?? ''); ?></textarea>
            <div class="form-hint">
              <span id="meta-desc-count">0</span> / 500 characters. Recommended: 150-160 characters.
            </div>
          </div>
          
          <div class="form-group">
            <label for="meta_keywords">Meta Keywords</label>
            <input type="text" 
                   id="meta_keywords" 
                   name="meta_keywords" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($post['meta_keywords'] ?? ''); ?>" 
                   placeholder="keyword1, keyword2, keyword3">
            <div class="form-hint">Comma-separated keywords. Not used by most search engines but may be useful for internal search.</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Sidebar -->
    <div class="post-editor-sidebar">
      <!-- Publish Box -->
      <div class="admin-card" style="margin-bottom: var(--space-4);">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Publish
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
              <option value="draft" <?php echo ($post['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
              <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
              <option value="scheduled" <?php echo ($post['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
              <option value="archived" <?php echo ($post['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
          </div>
          
          <div class="form-group" id="publish-at-group" style="<?php echo ($post['status'] ?? '') !== 'scheduled' ? 'display: none;' : ''; ?>">
            <label for="publish_at">Publish Date/Time</label>
            <input type="datetime-local" 
                   id="publish_at" 
                   name="publish_at" 
                   class="form-control"
                   value="<?php echo !empty($post['publish_at']) ? date('Y-m-d\TH:i', strtotime($post['publish_at'])) : ''; ?>">
            <div class="form-hint">Post will automatically be published at this time.</div>
          </div>
          
          <?php if ($post): ?>
          <div class="post-info">
            <div class="post-info__item">
              <span class="post-info__label">Created:</span>
              <span class="post-info__value"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
            </div>
            <div class="post-info__item">
              <span class="post-info__label">Modified:</span>
              <span class="post-info__value"><?php echo date('M j, Y \a\t g:i A', strtotime($post['updated_at'])); ?></span>
            </div>
            <?php if ($post['published_at']): ?>
            <div class="post-info__item">
              <span class="post-info__label">Published:</span>
              <span class="post-info__value"><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
            </div>
            <?php endif; ?>
            <div class="post-info__item">
              <span class="post-info__label">Views:</span>
              <span class="post-info__value"><?php echo number_format((int)$post['view_count']); ?></span>
            </div>
          </div>
          <?php endif; ?>
          
          <div class="publish-actions">
            <button type="submit" name="action" value="save" class="btn btn--primary btn--block">
              <svg viewBox="0 0 24 24" width="16" height="16"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Save <?php echo $post ? 'Changes' : 'Draft'; ?>
            </button>
            <button type="submit" name="action" value="publish" class="btn btn--success btn--block">
              <svg viewBox="0 0 24 24" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
              <?php echo ($post['status'] ?? '') === 'published' ? 'Update' : 'Publish'; ?>
            </button>
          </div>
          
          <?php if ($post): ?>
          <div class="post-actions-secondary">
            <?php if ($post['status'] === 'published'): ?>
              <a href="<?php echo $basePath; ?>/blog/<?php echo htmlspecialchars($post['slug']); ?>" target="_blank" class="btn btn--outline btn--sm btn--block">
                <svg viewBox="0 0 24 24" width="14" height="14"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                View Post
              </a>
            <?php endif; ?>
            <form method="post" action="blog_posts.php" style="display: inline;" onsubmit="return confirm('Move this post to trash?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              <button type="submit" class="btn btn--danger btn--sm btn--block">
                <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Move to Trash
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Category Box -->
      <div class="admin-card" style="margin-bottom: var(--space-4);">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Category
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <select id="category_id" name="category_id" class="form-control">
              <option value="">No Category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($post['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($cat['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-hint">
            <a href="<?php echo $basePath; ?>/admin/blog_categories.php" target="_blank">Manage categories</a>
          </div>
        </div>
      </div>
      
      <!-- Tags Box -->
      <div class="admin-card" style="margin-bottom: var(--space-4);">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            Tags
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <label>Selected Tags</label>
            <div id="selected-tags" class="tag-checkbox-list">
              <?php foreach ($allTags as $tag): ?>
                <label class="tag-checkbox">
                  <input type="checkbox" 
                         name="tag_ids[]" 
                         value="<?php echo (int)$tag['id']; ?>"
                         <?php echo in_array($tag['id'], array_column($existingTags, 'id')) ? 'checked' : ''; ?>>
                  <span><?php echo htmlspecialchars($tag['name']); ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          
          <div class="form-group">
            <label for="new_tags">Add New Tags</label>
            <input type="text" 
                   id="new_tags" 
                   name="new_tags" 
                   class="form-control" 
                   placeholder="Enter comma-separated tags">
            <div class="form-hint">Separate multiple tags with commas.</div>
          </div>
          
          <div class="form-hint">
            <a href="<?php echo $basePath; ?>/admin/blog_tags.php" target="_blank">Manage tags</a>
          </div>
        </div>
      </div>
      
      <!-- Featured Image Box -->
      <div class="admin-card" style="margin-bottom: var(--space-4);">
        <div class="admin-card__header">
          <h3 class="admin-card__title">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Featured Image
          </h3>
        </div>
        <div class="admin-card__body">
          <div class="form-group">
            <div id="featured-image-preview" class="featured-image-preview" style="<?php echo empty($post['featured_image_path']) ? 'display: none;' : ''; ?>">
              <img id="featured-image-img" src="<?php echo htmlspecialchars($basePath . '/' . ($post['featured_image_path'] ?? '')); ?>" alt="">
              <button type="button" class="featured-image-remove" onclick="removeFeaturedImage()">
                <svg viewBox="0 0 24 24" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <input type="hidden" id="featured_image" name="featured_image" value="<?php echo htmlspecialchars($post['featured_image_path'] ?? ''); ?>">
            <button type="button" class="btn btn--outline btn--sm btn--block" onclick="openMediaPicker('featured_image', 'image')">
              <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              Select Image
            </button>
          </div>
          
          <div class="form-group">
            <label for="featured_image_alt">Alt Text</label>
            <input type="text" 
                   id="featured_image_alt" 
                   name="featured_image_alt" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($post['featured_image_alt'] ?? ''); ?>" 
                   placeholder="Describe the image for accessibility">
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<style>
/* Post Editor Layout */
.post-editor-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: var(--space-4);
  align-items: start;
}

@media (max-width: 1024px) {
  .post-editor-layout {
    grid-template-columns: 1fr;
  }
  
  .post-editor-sidebar {
    order: -1;
  }
}

/* Large Form Control */
.form-control--lg {
  font-size: 1.5rem;
  padding: var(--space-3) var(--space-4);
  border: none;
  border-bottom: 2px solid var(--border-color);
  border-radius: 0;
  background: transparent;
  transition: border-color 0.2s;
}

.form-control--lg:focus {
  border-color: var(--color-primary);
  box-shadow: none;
}

.form-label-lg {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Slug Input Group */
.slug-input-group {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  padding: var(--space-1) var(--space-2);
}

.slug-prefix {
  color: var(--text-muted);
  font-size: 0.875rem;
  white-space: nowrap;
}

.slug-input-group .form-control {
  border: none;
  background: transparent;
  padding: var(--space-1);
  font-size: 0.875rem;
}

.slug-input-group .form-control:focus {
  box-shadow: none;
}

/* SEO Section */
.seo-section .admin-card__header {
  cursor: pointer;
  user-select: none;
}

.seo-section .admin-card__header:hover {
  background: var(--bg-secondary);
}

.collapse-indicator {
  margin-left: auto;
  transition: transform 0.2s;
}

.seo-section.collapsed .collapse-indicator {
  transform: rotate(-90deg);
}

.seo-section.collapsed .seo-content {
  display: none;
}

/* SEO Preview */
.seo-preview {
  margin-bottom: var(--space-4);
  padding: var(--space-3);
  background: #fff;
  border-radius: var(--radius-md);
  font-family: arial, sans-serif;
}

.seo-preview__label {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-bottom: var(--space-2);
  font-family: inherit;
}

.seo-preview__result {
  padding: var(--space-2);
}

.seo-preview__title {
  color: #1a0dab;
  font-size: 1.125rem;
  margin-bottom: 0.25rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.seo-preview__url {
  color: #006621;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.seo-preview__description {
  color: #545454;
  font-size: 0.875rem;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Post Info */
.post-info {
  padding: var(--space-3);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-3);
}

.post-info__item {
  display: flex;
  justify-content: space-between;
  padding: var(--space-1) 0;
  font-size: 0.875rem;
}

.post-info__label {
  color: var(--text-muted);
}

/* Publish Actions */
.publish-actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  margin-bottom: var(--space-3);
}

.post-actions-secondary {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  padding-top: var(--space-3);
  border-top: 1px solid var(--border-color);
}

/* Tag Checkbox List */
.tag-checkbox-list {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--space-2);
}

.tag-checkbox {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-1);
  cursor: pointer;
  border-radius: var(--radius-sm);
  transition: background 0.15s;
}

.tag-checkbox:hover {
  background: var(--bg-secondary);
}

.tag-checkbox input[type="checkbox"] {
  margin: 0;
}

.tag-checkbox span {
  font-size: 0.875rem;
}

/* Featured Image Preview */
.featured-image-preview {
  position: relative;
  margin-bottom: var(--space-2);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--bg-secondary);
}

.featured-image-preview img {
  width: 100%;
  height: auto;
  display: block;
}

.featured-image-remove {
  position: absolute;
  top: var(--space-2);
  right: var(--space-2);
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.featured-image-remove:hover {
  background: rgba(220, 38, 38, 0.9);
}

/* Block Buttons */
.btn--block {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
}

/* Form Hint */
.form-hint {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: var(--space-1);
}

.form-hint a {
  color: var(--color-primary);
}

/* TinyMCE Customization */
.tox-tinymce {
  border-radius: var(--radius-md) !important;
  border: 1px solid var(--border-color) !important;
}

.tox-toolbar__primary {
  background: var(--bg-secondary) !important;
}
</style>

<!-- TinyMCE Editor -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
<script>
// Initialize TinyMCE
tinymce.init({
  selector: '.tinymce-editor',
  height: 500,
  menubar: true,
  plugins: [
    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'codesample',
    'quickbars', 'directionality', 'emoticons', 'hr', 'nonbreaking',
    'pagebreak', 'paste', 'save', 'template', 'visualchars'
  ],
  toolbar: 'undo redo | formatselect | bold italic underline strikethrough | ' +
           'alignleft aligncenter alignright alignjustify | ' +
           'bullist numlist outdent indent | link image media | ' +
           'forecolor backcolor | codesample code | removeformat | help',
  quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote',
  contextmenu: 'link image imagetools table',
  
  // Image settings
  image_advtab: true,
  image_caption: true,
  image_class_list: [
    {title: 'Responsive', value: 'img-responsive'},
    {title: 'None', value: ''}
  ],
  
  // Paste settings
  paste_as_text: false,
  paste_merge_formats: true,
  paste_remove_styles_if_webkit: true,
  
  // Content style
  content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; font-size: 16px; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }',
  
  // Link settings
  link_default_target: '_blank',
  link_assume_external_targets: true,
  link_context_toolbar: true,
  
  // Code sample
  codesample_languages: [
    {text: 'HTML/XML', value: 'markup'},
    {text: 'JavaScript', value: 'javascript'},
    {text: 'CSS', value: 'css'},
    {text: 'PHP', value: 'php'},
    {text: 'Ruby', value: 'ruby'},
    {text: 'Python', value: 'python'},
    {text: 'Java', value: 'java'},
    {text: 'C', value: 'c'},
    {text: 'C#', value: 'csharp'},
    {text: 'C++', value: 'cpp'}
  ],
  
  // File picker callback for image uploads
  file_picker_callback: function(callback, value, meta) {
    if (meta.filetype === 'image') {
      // Open media picker
      var url = '<?php echo $basePath; ?>/admin/media/picker.php?field_id=tinymce&type=image';
      var win = window.open(url, 'mediaPicker', 'width=900,height=600,scrollbars=yes');
      
      // Create a temporary callback
      window.tinymceImageCallback = function(path) {
        callback('<?php echo $basePath; ?>/' + path, {alt: ''});
      };
    }
  },
  
  // Setup
  setup: function(editor) {
    editor.on('change', function() {
      editor.save();
    });
  }
});

// Title character counter
var titleInput = document.getElementById('title');
var titleCount = document.getElementById('title-count');

function updateTitleCount() {
  var len = titleInput.value.length;
  titleCount.textContent = len;
  titleCount.style.color = len > 255 ? 'var(--color-danger)' : '';
}

titleInput.addEventListener('input', updateTitleCount);
updateTitleCount();

// Excerpt character counter
var excerptInput = document.getElementById('excerpt');
var excerptCount = document.getElementById('excerpt-count');

function updateExcerptCount() {
  var len = excerptInput.value.length;
  excerptCount.textContent = len;
  excerptCount.style.color = len > 500 ? 'var(--color-danger)' : '';
}

excerptInput.addEventListener('input', updateExcerptCount);
updateExcerptCount();

// Meta title character counter
var metaTitleInput = document.getElementById('meta_title');
var metaTitleCount = document.getElementById('meta-title-count');

function updateMetaTitleCount() {
  var len = metaTitleInput.value.length;
  metaTitleCount.textContent = len;
  metaTitleCount.style.color = len > 60 ? 'var(--color-warning)' : '';
}

metaTitleInput.addEventListener('input', updateMetaTitleCount);
updateMetaTitleCount();

// Meta description character counter
var metaDescInput = document.getElementById('meta_description');
var metaDescCount = document.getElementById('meta-desc-count');

function updateMetaDescCount() {
  var len = metaDescInput.value.length;
  metaDescCount.textContent = len;
  metaDescCount.style.color = len > 160 ? 'var(--color-warning)' : '';
}

metaDescInput.addEventListener('input', updateMetaDescCount);
updateMetaDescCount();

// Generate slug from title
function generateSlug() {
  var title = document.getElementById('title').value;
  var slug = title.toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
  
  if (slug.length > 180) {
    slug = slug.substring(0, 180);
    slug = slug.replace(/-[^-]*$/, '');
  }
  
  document.getElementById('slug').value = slug;
  updateSeoPreview();
}

// Auto-generate slug on title change (only if slug is empty)
titleInput.addEventListener('input', function() {
  var slugInput = document.getElementById('slug');
  if (!slugInput.value) {
    generateSlug();
  }
  updateSeoPreview();
});

// Update slug on manual change
document.getElementById('slug').addEventListener('input', updateSeoPreview);

// Update SEO preview
function updateSeoPreview() {
  var title = document.getElementById('title').value;
  var metaTitle = document.getElementById('meta_title').value;
  var metaDesc = document.getElementById('meta_description').value;
  var excerpt = document.getElementById('excerpt').value;
  var slug = document.getElementById('slug').value;
  
  var previewTitle = metaTitle || title || 'Your Post Title';
  var previewDesc = metaDesc || excerpt || 'Your post description will appear here...';
  var previewUrl = '<?php echo $basePath; ?>/blog/' + (slug || 'your-post-slug');
  
  document.getElementById('seo-preview-title').textContent = previewTitle;
  document.getElementById('seo-preview-desc').textContent = previewDesc.substring(0, 160) + (previewDesc.length > 160 ? '...' : '');
  document.querySelector('.seo-preview__url').textContent = previewUrl;
}

// Update SEO preview on input
document.getElementById('meta_title').addEventListener('input', updateSeoPreview);
document.getElementById('meta_description').addEventListener('input', updateSeoPreview);
document.getElementById('excerpt').addEventListener('input', updateSeoPreview);

// Toggle SEO section
function toggleSeoSection() {
  var section = document.querySelector('.seo-section');
  section.classList.toggle('collapsed');
}

// Show/hide publish date based on status
document.getElementById('status').addEventListener('change', function() {
  var publishAtGroup = document.getElementById('publish-at-group');
  if (this.value === 'scheduled') {
    publishAtGroup.style.display = 'block';
  } else {
    publishAtGroup.style.display = 'none';
  }
});

// Media picker integration
function openMediaPicker(targetId, type) {
  var url = '<?php echo $basePath; ?>/admin/media/picker.php?field_id=' + encodeURIComponent(targetId) + '&type=' + encodeURIComponent(type);
  var win = window.open(url, 'mediaPicker', 'width=900,height=600,scrollbars=yes');
  win.targetInput = document.getElementById(targetId);
}

// Callback for media picker
window.setUploadPath = function(fieldId, path) {
  var input = document.getElementById(fieldId);
  if (input) {
    input.value = path;
    
    // Update featured image preview
    if (fieldId === 'featured_image') {
      var preview = document.getElementById('featured-image-preview');
      var img = document.getElementById('featured-image-img');
      img.src = '<?php echo $basePath; ?>/' + path;
      preview.style.display = 'block';
    }
  }
};

// Remove featured image
function removeFeaturedImage() {
  document.getElementById('featured_image').value = '';
  document.getElementById('featured-image-preview').style.display = 'none';
  document.getElementById('featured-image-img').src = '';
}

// Form validation before submit
document.getElementById('post-form').addEventListener('submit', function(e) {
  var title = document.getElementById('title').value.trim();
  
  if (!title) {
    e.preventDefault();
    alert('Please enter a post title.');
    document.getElementById('title').focus();
    return false;
  }
  
  // Save TinyMCE content
  if (tinymce.get('content')) {
    tinymce.get('content').save();
  }
  
  return true;
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
