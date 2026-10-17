<?php
/**
 * Tulima Solar - Admin Pages Management
 * Create, edit, and manage dynamic pages
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDb();
$currentNav = 'pages';
$pageTitle = 'Pages';

$message = '';
$messageType = 'success';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Create new page
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if ($title === '') {
            $message = 'Page title is required.';
            $messageType = 'error';
        } else {
            // Auto-generate slug if not provided
            if ($slug === '') {
                $slug = generateUniqueSlug($pdo, $title);
            } else {
                // Check if slug is available
                if (!isSlugAvailable($pdo, $slug)) {
                    $slug = generateUniqueSlug($pdo, $slug);
                }
            }
            
            $pageData = [
                'slug' => $slug,
                'title' => $title,
                'meta_title' => trim($_POST['meta_title'] ?? '') ?: $title . ' | Tulima Solar',
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'template_type' => $_POST['template_type'] ?? 'fullwidth',
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                'hero_title' => trim($_POST['hero_title'] ?? ''),
                'hero_subtitle' => trim($_POST['hero_subtitle'] ?? ''),
                'hero_cta_text' => trim($_POST['hero_cta_text'] ?? ''),
                'hero_cta_url' => trim($_POST['hero_cta_url'] ?? ''),
                'hero_cta_secondary_text' => trim($_POST['hero_cta_secondary_text'] ?? ''),
                'hero_cta_secondary_url' => trim($_POST['hero_cta_secondary_url'] ?? ''),
                'hero_video_path' => trim($_POST['hero_video_path'] ?? '') ?: null,
                'show_in_footer' => isset($_POST['show_in_footer']) ? 1 : 0,
                'requires_auth' => isset($_POST['requires_auth']) ? 1 : 0,
            ];
            
            $newPageId = createPage($pdo, $pageData);
            
            if ($newPageId) {
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity($pdo, 'create', 'page', $newPageId, "Created page: $title ($slug)");
                }
                
                header('Location: pages.php?msg=created&id=' . $newPageId);
                exit;
            } else {
                $message = 'Failed to create page. Please try again.';
                $messageType = 'error';
            }
        }
    }
    
    // Update existing page
    if ($action === 'update' && !empty($_POST['id'])) {
        $pageId = (int)$_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if ($title === '') {
            $message = 'Page title is required.';
            $messageType = 'error';
        } else {
            // Check if slug is available (excluding current page)
            if ($slug === '' || !isSlugAvailable($pdo, $slug, $pageId)) {
                $slug = generateUniqueSlug($pdo, $slug ?: $title, $pageId);
            }
            
            $pageData = [
                'slug' => $slug,
                'title' => $title,
                'meta_title' => trim($_POST['meta_title'] ?? '') ?: $title . ' | Tulima Solar',
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'template_type' => $_POST['template_type'] ?? 'fullwidth',
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                'hero_title' => trim($_POST['hero_title'] ?? ''),
                'hero_subtitle' => trim($_POST['hero_subtitle'] ?? ''),
                'hero_cta_text' => trim($_POST['hero_cta_text'] ?? ''),
                'hero_cta_url' => trim($_POST['hero_cta_url'] ?? ''),
                'hero_cta_secondary_text' => trim($_POST['hero_cta_secondary_text'] ?? ''),
                'hero_cta_secondary_url' => trim($_POST['hero_cta_secondary_url'] ?? ''),
                'hero_video_path' => trim($_POST['hero_video_path'] ?? '') ?: null,
                'show_in_footer' => isset($_POST['show_in_footer']) ? 1 : 0,
                'requires_auth' => isset($_POST['requires_auth']) ? 1 : 0,
            ];
            
            if (updatePage($pdo, $pageId, $pageData)) {
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity($pdo, 'update', 'page', $pageId, "Updated page: $title ($slug)");
                }
                
                $message = 'Page updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to update page. Please try again.';
                $messageType = 'error';
            }
        }
    }
    
    // Delete page
    if ($action === 'delete' && !empty($_POST['id'])) {
        $pageId = (int)$_POST['id'];
        $page = getPageById($pdo, $pageId);
        
        if ($page && deletePage($pdo, $pageId)) {
            // Log activity
            if (function_exists('logActivity')) {
                logActivity($pdo, 'delete', 'page', $pageId, "Deleted page: {$page['title']} ({$page['slug']})");
            }
            
            header('Location: pages.php?msg=deleted');
            exit;
        } else {
            $message = 'Failed to delete page. Please try again.';
            $messageType = 'error';
        }
    }
    
    // Duplicate page
    if ($action === 'duplicate' && !empty($_POST['id'])) {
        $pageId = (int)$_POST['id'];
        $page = getPageById($pdo, $pageId);
        
        if ($page) {
            $newTitle = $page['title'] . ' (Copy)';
            $newPageId = duplicatePage($pdo, $pageId, $newTitle);
            
            if ($newPageId) {
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity($pdo, 'duplicate', 'page', $newPageId, "Duplicated page: $newTitle");
                }
                
                header('Location: pages.php?msg=duplicated&id=' . $newPageId);
                exit;
            }
        }
        
        $message = 'Failed to duplicate page. Please try again.';
        $messageType = 'error';
    }
}

// Check for URL messages
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'created':
            $message = 'Page created successfully. You can now add sections using the Page Builder.';
            $messageType = 'success';
            break;
        case 'updated':
            $message = 'Page updated successfully.';
            $messageType = 'success';
            break;
        case 'deleted':
            $message = 'Page deleted successfully.';
            $messageType = 'success';
            break;
        case 'duplicated':
            $message = 'Page duplicated successfully. The new page is currently inactive.';
            $messageType = 'success';
            break;
    }
}

// Get all pages with parent information
$pages = $pdo->query('
    SELECT p.*, parent.title as parent_title 
    FROM pages p 
    LEFT JOIN pages parent ON p.parent_id = parent.id 
    ORDER BY p.sort_order, p.id
')->fetchAll();

// Check for new columns
$hasHeroCols = !empty($pages[0]) && array_key_exists('hero_title', $pages[0]);
$hasTemplateType = !empty($pages[0]) && array_key_exists('template_type', $pages[0]);
$hasMetaTitle = !empty($pages[0]) && array_key_exists('meta_title', $pages[0]);
$hasShowInFooter = !empty($pages[0]) && array_key_exists('show_in_footer', $pages[0]);
$hasParentId = !empty($pages[0]) && array_key_exists('parent_id', $pages[0]);

// Calculate stats
$activePages = count(array_filter($pages, fn($p) => $p['is_active']));
$pagesWithHero = count(array_filter($pages, fn($p) => !empty($p['hero_video_path'])));
$pagesInFooter = count(array_filter($pages, fn($p) => !empty($p['show_in_footer'])));

// Get templates
$templates = getPageTemplates($pdo);

// Get parent pages for dropdown (top-level pages only)
$parentPages = array_filter($pages, fn($p) => empty($p['parent_id']));

$adminBase = rtrim(BASE_PATH, '/') . '/admin';

include __DIR__ . '/includes/header.php';
?>

<!-- Enhanced Page Header -->
<div class="admin-content">
<div class="page-header-enhanced">
  <div class="page-header-enhanced__content">
    <h1 class="page-header-enhanced__title">Pages Management</h1>
    <p class="page-header-enhanced__subtitle">Create, edit, and manage dynamic pages for your website. Use the Page Builder for advanced content editing.</p>
  </div>
  <div class="page-header-enhanced__actions">
    <button type="button" class="btn btn--primary" onclick="openCreateModal()">
      <svg viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Create New Page
    </button>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Page Stats Overview -->
<div class="page-stats-grid">
  <div class="page-stat-card page-stat-card--purple">
    <div class="page-stat-card__value"><?php echo count($pages); ?></div>
    <div class="page-stat-card__label">Total Pages</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
      All pages
    </div>
  </div>

  <div class="page-stat-card page-stat-card--green">
    <div class="page-stat-card__value"><?php echo $activePages; ?></div>
    <div class="page-stat-card__label">Active Pages</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
      <?php echo $pages ? round(($activePages / count($pages)) * 100) : 0; ?>% published
    </div>
  </div>

  <div class="page-stat-card page-stat-card--blue">
    <div class="page-stat-card__value"><?php echo $pagesWithHero; ?></div>
    <div class="page-stat-card__label">With Hero Video</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2"/><polygon points="9 9 15 15 15 9"/></svg>
      <?php echo $pages ? round(($pagesWithHero / count($pages)) * 100) : 0; ?>% with video
    </div>
  </div>

  <div class="page-stat-card page-stat-card--orange">
    <div class="page-stat-card__value"><?php echo $pagesInFooter; ?></div>
    <div class="page-stat-card__label">In Footer</div>
    <div class="page-stat-card__trend">
      <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
      Footer links
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="filter-toolbar">
  <div class="filter-toolbar__search">
    <svg class="filter-toolbar__search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="page-search" placeholder="Search pages..." onkeyup="filterPages()">
  </div>
  <div class="filter-toolbar__filters">
    <select class="filter-toolbar__select" id="status-filter" onchange="filterPages()">
      <option value="">All Status</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
    <?php if ($hasTemplateType): ?>
    <select class="filter-toolbar__select" id="template-filter" onchange="filterPages()">
      <option value="">All Templates</option>
      <option value="fullwidth">Full Width</option>
      <option value="with_sidebar">With Sidebar</option>
      <option value="landing">Landing Page</option>
      <option value="custom">Custom</option>
    </select>
    <?php endif; ?>
    <select class="filter-toolbar__select" id="hero-filter" onchange="filterPages()">
      <option value="">All Hero Types</option>
      <option value="video">With Video</option>
      <option value="none">No Hero</option>
    </select>
  </div>
  <a href="api/export.php?type=pages&format=csv" class="btn btn--secondary btn--sm">
    <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export CSV
  </a>
</div>

<!-- Pages Table -->
<div class="admin-card">
  <div class="admin-card__header">
    <h3 class="admin-card__title">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      All Pages
    </h3>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table enhanced-data-table" id="pages-table">
      <thead>
        <tr>
          <th>Slug</th>
          <th>Title</th>
          <th>Status</th>
          <th>Template</th>
          <th>Hero</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pages as $p): ?>
        <tr data-slug="<?php echo strtolower(htmlspecialchars($p['slug'])); ?>" 
            data-status="<?php echo $p['is_active'] ? 'active' : 'inactive'; ?>" 
            data-hero="<?php echo !empty($p['hero_video_path']) ? 'video' : 'none'; ?>"
            data-template="<?php echo htmlspecialchars($p['template_type'] ?? 'fullwidth'); ?>">
          <td>
            <code style="font-size: 0.85rem;">
              <?php 
              if (!empty($p['parent_id']) && !empty($p['parent_title'])) {
                  echo '<span style="color: var(--text-muted);">' . htmlspecialchars($p['parent_title']) . '</span> / ';
              }
              echo htmlspecialchars($p['slug']); 
              ?>
            </code>
          </td>
          <td>
            <div class="enhanced-data-table__cell-primary">
              <div class="enhanced-data-table__cell-title"><?php echo htmlspecialchars($p['title']); ?></div>
              <?php if ($hasShowInFooter && !empty($p['show_in_footer'])): ?>
                <div class="enhanced-data-table__cell-meta">In footer</div>
              <?php endif; ?>
            </div>
          </td>
          <td><span class="badge <?php echo $p['is_active'] ? 'badge--success' : 'badge--muted'; ?>"><?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
          <td>
            <?php if ($hasTemplateType): ?>
              <span class="badge badge--info"><?php echo ucfirst(str_replace('_', ' ', $p['template_type'] ?? 'fullwidth')); ?></span>
            <?php else: ?>
              <span class="badge badge--muted">Default</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($p['hero_video_path'])): ?>
              <span class="badge badge--info">Video</span>
            <?php else: ?>
              <span class="badge badge--muted">None</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
              <button type="button" class="btn btn--primary btn--sm" data-toggle-row="<?php echo (int)$p['id']; ?>">Edit</button>
              <a href="page_builder.php?page=<?php echo (int)$p['id']; ?>" class="btn btn--secondary btn--sm" title="Page Builder">
                <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Builder
              </a>
              <a href="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?page=<?php echo htmlspecialchars($p['slug']); ?>" target="_blank" class="btn btn--outline btn--sm" title="View on site">
                <svg viewBox="0 0 24 24" width="16" height="16"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              </a>
              <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to duplicate this page?');">
                <input type="hidden" name="action" value="duplicate">
                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                <button type="submit" class="btn btn--outline btn--sm" title="Duplicate page">
                  <svg viewBox="0 0 24 24" width="14" height="14"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </button>
              </form>
              <form method="post" style="display: inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($p['title']); ?>');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                <button type="submit" class="btn btn--danger btn--sm" title="Delete page">
                  <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <tr id="row-form-<?php echo (int)$p['id']; ?>" class="expand-row" style="display: none;">
          <td colspan="6">
            <form method="post" style="max-width: 800px;">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
              
              <div class="form-grid form-grid--2col">
                <!-- Basic Information -->
                <div class="form-section">
                  <p class="form-section-title">Basic Information</p>
                  <div class="form-group">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($p['title']); ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Slug (URL path)</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($p['slug']); ?>" pattern="[a-z0-9\-]+" title="Lowercase letters, numbers, and hyphens only">
                    <p class="form-hint">URL: <?php echo rtrim(BASE_PATH, '/'); ?>/index.php?page=<span id="slug-preview-<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['slug']); ?></span></p>
                  </div>
                  <?php if ($hasParentId): ?>
                  <div class="form-group">
                    <label>Parent Page</label>
                    <select name="parent_id" class="form-control">
                      <option value="">None (Top Level)</option>
                      <?php foreach ($parentPages as $parent): ?>
                        <?php if ($parent['id'] != $p['id']): ?>
                        <option value="<?php echo (int)$parent['id']; ?>" <?php echo ($p['parent_id'] ?? null) == $parent['id'] ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($parent['title']); ?>
                        </option>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <?php endif; ?>
                  <div class="form-group">
                    <label>Sort order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$p['sort_order']; ?>" style="max-width: 100px;">
                  </div>
                </div>
                
                <!-- SEO Settings -->
                <div class="form-section">
                  <p class="form-section-title">SEO Settings</p>
                  <?php if ($hasMetaTitle): ?>
                  <div class="form-group">
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($p['meta_title'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars($p['title']); ?> | Tulima Solar">
                    <p class="form-hint">Recommended: 50-60 characters</p>
                  </div>
                  <?php endif; ?>
                  <div class="form-group">
                    <label>Meta description</label>
                    <textarea name="meta_description" class="form-control" rows="2" placeholder="Brief description for search engines"><?php echo htmlspecialchars($p['meta_description'] ?? ''); ?></textarea>
                    <p class="form-hint">Recommended: 150-160 characters</p>
                  </div>
                </div>
                
                <!-- Template & Display -->
                <div class="form-section">
                  <p class="form-section-title">Template & Display</p>
                  <?php if ($hasTemplateType): ?>
                  <div class="form-group">
                    <label>Template Type</label>
                    <select name="template_type" class="form-control">
                      <?php foreach ($templates as $tpl): ?>
                      <option value="<?php echo htmlspecialchars($tpl['name']); ?>" <?php echo ($p['template_type'] ?? 'fullwidth') === $tpl['name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tpl['display_name'] ?? $tpl['name']); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <?php endif; ?>
                  <div class="form-group">
                    <label class="checkbox-label">
                      <input type="checkbox" name="is_active" <?php echo $p['is_active'] ? 'checked' : ''; ?>>
                      Active (published)
                    </label>
                  </div>
                  <?php if ($hasShowInFooter): ?>
                  <div class="form-group">
                    <label class="checkbox-label">
                      <input type="checkbox" name="show_in_footer" <?php echo !empty($p['show_in_footer']) ? 'checked' : ''; ?>>
                      Show in footer navigation
                    </label>
                  </div>
                  <?php endif; ?>
                  <div class="form-group">
                    <label class="checkbox-label">
                      <input type="checkbox" name="requires_auth" <?php echo !empty($p['requires_auth']) ? 'checked' : ''; ?>>
                      Requires authentication
                    </label>
                  </div>
                </div>
                
                <!-- Hero Section -->
                <?php if ($hasHeroCols): ?>
                <div class="form-section">
                  <p class="form-section-title">Hero Section</p>
                  <div class="form-group">
                    <label>Hero title</label>
                    <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars($p['hero_title'] ?? ''); ?>" placeholder="Overrides page title in hero">
                  </div>
                  <div class="form-group">
                    <label>Hero subtitle</label>
                    <textarea name="hero_subtitle" class="form-control" rows="2" placeholder="Short line under the title"><?php echo htmlspecialchars($p['hero_subtitle'] ?? ''); ?></textarea>
                  </div>
                  <div class="form-group">
                    <label>Primary CTA text</label>
                    <input type="text" name="hero_cta_text" class="form-control" value="<?php echo htmlspecialchars($p['hero_cta_text'] ?? ''); ?>" placeholder="e.g. Contact Us">
                  </div>
                  <div class="form-group">
                    <label>Primary CTA URL</label>
                    <input type="text" name="hero_cta_url" class="form-control" value="<?php echo htmlspecialchars($p['hero_cta_url'] ?? ''); ?>" placeholder="e.g. index.php?page=contact">
                  </div>
                  <div class="form-group">
                    <label>Secondary CTA text</label>
                    <input type="text" name="hero_cta_secondary_text" class="form-control" value="<?php echo htmlspecialchars($p['hero_cta_secondary_text'] ?? ''); ?>" placeholder="e.g. WhatsApp us">
                  </div>
                  <div class="form-group">
                    <label>Secondary CTA URL</label>
                    <input type="text" name="hero_cta_secondary_url" class="form-control" value="<?php echo htmlspecialchars($p['hero_cta_secondary_url'] ?? ''); ?>" placeholder="Leave blank for WhatsApp link">
                  </div>
                  <div class="form-group">
                    <label>Hero video (home: plays behind transparent menu)</label>
                    <div class="upload-field">
                      <input type="text" id="hero_video_<?php echo (int)$p['id']; ?>" name="hero_video_path" class="form-control" value="<?php echo htmlspecialchars($p['hero_video_path'] ?? ''); ?>" placeholder="uploads/videos/... or leave empty">
                      <button type="button" class="btn btn--secondary btn--sm" onclick="openMediaPicker('hero_video_<?php echo (int)$p['id']; ?>', 'video')">Select from Media</button>
                    </div>
                    <p class="form-hint">MP4, WebM or MOV. Max 100 MB.</p>
                  </div>
                </div>
                <?php endif; ?>
              </div>
              
              <div style="margin-top: 1.5rem; display: flex; gap: var(--space-3);">
                <button type="submit" class="btn btn--primary">Save Changes</button>
                <button type="button" class="btn btn--outline" onclick="document.getElementById('row-form-<?php echo (int)$p['id']; ?>').style.display='none'">Cancel</button>
                <a href="page_builder.php?page=<?php echo (int)$p['id']; ?>" class="btn btn--secondary">
                  <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                  Open Page Builder
                </a>
              </div>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Create New Page Modal -->
<div id="create-modal" class="modal" style="display: none;">
  <div class="modal-overlay" onclick="closeCreateModal()"></div>
  <div class="modal-content" style="max-width: 800px;">
    <div class="modal-header">
      <h2>Create New Page</h2>
      <button type="button" class="modal-close" onclick="closeCreateModal()">&times;</button>
    </div>
    <form method="post" id="create-form">
      <input type="hidden" name="action" value="create">
      
      <div class="modal-body">
        <div class="form-grid form-grid--2col">
          <!-- Basic Information -->
          <div class="form-section">
            <p class="form-section-title">Basic Information</p>
            <div class="form-group">
              <label>Page Title <span class="required">*</span></label>
              <input type="text" name="title" id="new-title" class="form-control" required placeholder="e.g., About Our Company">
            </div>
            <div class="form-group">
              <label>Slug (URL path)</label>
              <input type="text" name="slug" id="new-slug" class="form-control" placeholder="Auto-generated from title" pattern="[a-z0-9\-]+" title="Lowercase letters, numbers, and hyphens only">
              <p class="form-hint">URL: <?php echo rtrim(BASE_PATH, '/'); ?>/index.php?page=<span id="new-slug-preview">your-page-slug</span></p>
            </div>
            <?php if ($hasParentId): ?>
            <div class="form-group">
              <label>Parent Page</label>
              <select name="parent_id" class="form-control">
                <option value="">None (Top Level)</option>
                <?php foreach ($parentPages as $parent): ?>
                <option value="<?php echo (int)$parent['id']; ?>">
                  <?php echo htmlspecialchars($parent['title']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
              <label>Sort order</label>
              <input type="number" name="sort_order" class="form-control" value="0" style="max-width: 100px;">
            </div>
          </div>
          
          <!-- SEO Settings -->
          <div class="form-section">
            <p class="form-section-title">SEO Settings</p>
            <?php if ($hasMetaTitle): ?>
            <div class="form-group">
              <label>Meta Title</label>
              <input type="text" name="meta_title" class="form-control" placeholder="Leave blank to use page title">
              <p class="form-hint">Recommended: 50-60 characters</p>
            </div>
            <?php endif; ?>
            <div class="form-group">
              <label>Meta Description</label>
              <textarea name="meta_description" class="form-control" rows="2" placeholder="Brief description for search engines"></textarea>
              <p class="form-hint">Recommended: 150-160 characters</p>
            </div>
          </div>
          
          <!-- Template & Display -->
          <div class="form-section">
            <p class="form-section-title">Template & Display</p>
            <?php if ($hasTemplateType): ?>
            <div class="form-group">
              <label>Template Type</label>
              <select name="template_type" class="form-control">
                <?php foreach ($templates as $tpl): ?>
                <option value="<?php echo htmlspecialchars($tpl['name']); ?>" <?php echo $tpl['name'] === 'fullwidth' ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($tpl['display_name'] ?? $tpl['name']); ?>
                  <?php if (!empty($tpl['description'])): ?> - <?php echo htmlspecialchars($tpl['description']); ?><?php endif; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" name="is_active" checked>
                Active (published immediately)
              </label>
            </div>
            <?php if ($hasShowInFooter): ?>
            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" name="show_in_footer">
                Show in footer navigation
              </label>
            </div>
            <?php endif; ?>
          </div>
          
          <!-- Hero Section (Optional) -->
          <?php if ($hasHeroCols): ?>
          <div class="form-section">
            <p class="form-section-title">Hero Section (Optional)</p>
            <div class="form-group">
              <label>Hero Title</label>
              <input type="text" name="hero_title" class="form-control" placeholder="Leave blank to use page title">
            </div>
            <div class="form-group">
              <label>Hero Subtitle</label>
              <textarea name="hero_subtitle" class="form-control" rows="2" placeholder="Short tagline under the hero title"></textarea>
            </div>
            <div class="form-group">
              <label>Primary CTA Text</label>
              <input type="text" name="hero_cta_text" class="form-control" placeholder="e.g., Get Started">
            </div>
            <div class="form-group">
              <label>Primary CTA URL</label>
              <input type="text" name="hero_cta_url" class="form-control" placeholder="e.g., index.php?page=contact">
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" onclick="closeCreateModal()">Cancel</button>
        <button type="submit" class="btn btn--primary">
          <svg viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Create Page
        </button>
      </div>
    </form>
  </div>
</div>

<style>
/* Modal Styles */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
}

.modal-content {
  position: relative;
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xl);
  max-height: 90vh;
  overflow-y: auto;
  width: 95%;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4) var(--space-5);
  border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--text-muted);
  padding: 0;
  line-height: 1;
}

.modal-close:hover {
  color: var(--text-primary);
}

.modal-body {
  padding: var(--space-5);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-3);
  padding: var(--space-4) var(--space-5);
  border-top: 1px solid var(--border-color);
}

.form-grid {
  display: grid;
  gap: var(--space-5);
}

.form-grid--2col {
  grid-template-columns: repeat(2, 1fr);
}

@media (max-width: 768px) {
  .form-grid--2col {
    grid-template-columns: 1fr;
  }
}

.form-section {
  background: var(--bg-secondary);
  padding: var(--space-4);
  border-radius: var(--radius-md);
}

.form-section-title {
  font-weight: 600;
  margin-bottom: var(--space-3);
  color: var(--text-primary);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.form-hint {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: var(--space-1);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
}

.required {
  color: var(--color-danger);
}

.page-header-enhanced {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--space-4);
}

.page-header-enhanced__actions {
  display: flex;
  gap: var(--space-3);
}

.upload-field {
  display: flex;
  gap: var(--space-2);
}

.upload-field input {
  flex: 1;
}
</style>

<script>
// Filter pages table
function filterPages() {
  const searchValue = document.getElementById('page-search').value.toLowerCase();
  const statusFilter = document.getElementById('status-filter').value;
  const heroFilter = document.getElementById('hero-filter').value;
  const templateFilterEl = document.getElementById('template-filter');
  const templateFilter = templateFilterEl ? templateFilterEl.value : '';
  const rows = document.querySelectorAll('#pages-table tbody tr[data-slug]');
  
  rows.forEach(row => {
    const slug = row.dataset.slug;
    const status = row.dataset.status;
    const hero = row.dataset.hero;
    const template = row.dataset.template;
    
    let matchesSearch = slug.includes(searchValue);
    let matchesStatus = !statusFilter || status === statusFilter;
    let matchesHero = !heroFilter || hero === heroFilter;
    let matchesTemplate = !templateFilter || template === templateFilter;
    
    if (matchesSearch && matchesStatus && matchesHero && matchesTemplate) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// Auto-generate slug from title
document.addEventListener('DOMContentLoaded', function() {
  const titleInput = document.getElementById('new-title');
  const slugInput = document.getElementById('new-slug');
  const slugPreview = document.getElementById('new-slug-preview');
  
  if (titleInput && slugInput) {
    titleInput.addEventListener('input', function() {
      if (slugInput.value === '') {
        const slug = generateSlug(this.value);
        slugInput.value = slug;
        if (slugPreview) slugPreview.textContent = slug;
      }
    });
    
    slugInput.addEventListener('input', function() {
      if (slugPreview) slugPreview.textContent = this.value || 'your-page-slug';
    });
  }
  
  // Also bind to edit forms
  document.querySelectorAll('[id^="row-form-"]').forEach(form => {
    const id = form.id.replace('row-form-', '');
    const titleInput = form.querySelector('input[name="title"]');
    const slugInput = form.querySelector('input[name="slug"]');
    const slugPreview = document.getElementById('slug-preview-' + id);
    
    if (titleInput && slugInput) {
      titleInput.addEventListener('input', function() {
        if (slugPreview) slugPreview.textContent = slugInput.value || generateSlug(this.value);
      });
      
      slugInput.addEventListener('input', function() {
        if (slugPreview) slugPreview.textContent = this.value;
      });
    }
  });
});

// Generate URL-safe slug
function generateSlug(text) {
  return text
    .toLowerCase()
    .trim()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9\-]/g, '')
    .replace(/\-+/g, '-')
    .replace(/^\-|\-$/g, '');
}

// Modal functions
function openCreateModal() {
  document.getElementById('create-modal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
  document.getElementById('new-title').focus();
}

function closeCreateModal() {
  document.getElementById('create-modal').style.display = 'none';
  document.body.style.overflow = '';
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeCreateModal();
  }
});

// Confirm delete
function confirmDelete(title) {
  return confirm('Are you sure you want to delete the page "' + title + '"?\n\nThis action cannot be undone. All sections associated with this page will also be deleted.');
}

// Toggle row expansion
document.querySelectorAll('[data-toggle-row]').forEach(btn => {
  btn.addEventListener('click', function() {
    const rowId = this.dataset.toggleRow;
    const expandRow = document.getElementById('row-form-' + rowId);
    if (expandRow) {
      expandRow.style.display = expandRow.style.display === 'none' ? 'table-row' : 'none';
    }
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
