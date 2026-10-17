<?php
/**
 * Tulima Solar - Page Builder
 * Visual interface for building pages with sections
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDb();
$currentNav = 'pages';
$pageTitle = 'Page Builder';

$message = '';
$messageType = 'success';

// Get page ID from URL
$pageId = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$page = null;

if ($pageId > 0) {
    $page = getPageById($pdo, $pageId);
}

// If no valid page, redirect to pages list
if (!$page) {
    header('Location: pages.php');
    exit;
}

// Handle section operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Add new section
    if ($action === 'add_section') {
        $sectionKey = trim($_POST['section_key'] ?? '');
        $sectionTitle = trim($_POST['section_title'] ?? '');
        $sectionContent = trim($_POST['section_content'] ?? '');
        
        if ($sectionKey === '') {
            $message = 'Section key is required.';
            $messageType = 'error';
        } else {
            // Check if section key already exists for this page
            $stmt = $pdo->prepare('SELECT id FROM page_sections WHERE page_id = ? AND section_key = ?');
            $stmt->execute([$pageId, $sectionKey]);
            
            if ($stmt->fetch()) {
                $message = 'A section with this key already exists.';
                $messageType = 'error';
            } else {
                // Get max sort order
                $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM page_sections WHERE page_id = ?');
                $stmt->execute([$pageId]);
                $maxOrder = (int)$stmt->fetchColumn();
                
                $stmt = $pdo->prepare('
                    INSERT INTO page_sections (page_id, section_key, title, content, content_extra, image_path, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $pageId,
                    $sectionKey,
                    $sectionTitle,
                    $sectionContent,
                    trim($_POST['content_extra'] ?? ''),
                    trim($_POST['image_path'] ?? '') ?: null,
                    $maxOrder + 1
                ]);
                
                $message = 'Section added successfully.';
                $messageType = 'success';
            }
        }
    }
    
    // Update section
    if ($action === 'update_section' && !empty($_POST['section_id'])) {
        $sectionId = (int)$_POST['section_id'];
        
        $stmt = $pdo->prepare('
            UPDATE page_sections 
            SET section_key = ?, title = ?, content = ?, content_extra = ?, image_path = ?
            WHERE id = ? AND page_id = ?
        ');
        $stmt->execute([
            trim($_POST['section_key'] ?? ''),
            trim($_POST['section_title'] ?? ''),
            trim($_POST['section_content'] ?? ''),
            trim($_POST['content_extra'] ?? ''),
            trim($_POST['image_path'] ?? '') ?: null,
            $sectionId,
            $pageId
        ]);
        
        $message = 'Section updated successfully.';
        $messageType = 'success';
    }
    
    // Delete section
    if ($action === 'delete_section' && !empty($_POST['section_id'])) {
        $sectionId = (int)$_POST['section_id'];
        
        $stmt = $pdo->prepare('DELETE FROM page_sections WHERE id = ? AND page_id = ?');
        $stmt->execute([$sectionId, $pageId]);
        
        $message = 'Section deleted successfully.';
        $messageType = 'success';
    }
    
    // Update section order (drag and drop)
    if ($action === 'update_order' && !empty($_POST['order'])) {
        $order = json_decode($_POST['order'], true);
        
        if (is_array($order)) {
            foreach ($order as $index => $sectionId) {
                $stmt = $pdo->prepare('UPDATE page_sections SET sort_order = ? WHERE id = ? AND page_id = ?');
                $stmt->execute([$index + 1, (int)$sectionId, $pageId]);
            }
            
            $message = 'Section order updated.';
            $messageType = 'success';
        }
    }
    
    // Duplicate section
    if ($action === 'duplicate_section' && !empty($_POST['section_id'])) {
        $sectionId = (int)$_POST['section_id'];
        
        $stmt = $pdo->prepare('SELECT * FROM page_sections WHERE id = ? AND page_id = ?');
        $stmt->execute([$sectionId, $pageId]);
        $section = $stmt->fetch();
        
        if ($section) {
            // Get max sort order
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM page_sections WHERE page_id = ?');
            $stmt->execute([$pageId]);
            $maxOrder = (int)$stmt->fetchColumn();
            
            // Create duplicate with new key
            $newKey = $section['section_key'] . '-copy';
            $stmt = $pdo->prepare('
                INSERT INTO page_sections (page_id, section_key, title, content, content_extra, image_path, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $pageId,
                $newKey,
                $section['title'] . ' (Copy)',
                $section['content'],
                $section['content_extra'],
                $section['image_path'],
                $maxOrder + 1
            ]);
            
            $message = 'Section duplicated successfully.';
            $messageType = 'success';
        }
    }
}

// Get page sections
$sections = getPageSections($pdo, $pageId);

// Get available section templates (predefined section types)
$sectionTemplates = [
    'hero' => ['name' => 'Hero Section', 'icon' => 'image', 'description' => 'Full-width hero with title and CTA'],
    'features' => ['name' => 'Features Grid', 'icon' => 'grid', 'description' => 'Grid of feature cards'],
    'testimonials' => ['name' => 'Testimonials', 'icon' => 'quote', 'description' => 'Customer testimonials slider'],
    'cta' => ['name' => 'Call to Action', 'icon' => 'mouse-pointer', 'description' => 'Call to action banner'],
    'content' => ['name' => 'Content Block', 'icon' => 'file-text', 'description' => 'Rich text content block'],
    'gallery' => ['name' => 'Image Gallery', 'icon' => 'images', 'description' => 'Grid of images'],
    'stats' => ['name' => 'Statistics', 'icon' => 'bar-chart', 'description' => 'Impact numbers and stats'],
    'faq' => ['name' => 'FAQ Section', 'icon' => 'help-circle', 'description' => 'Frequently asked questions'],
    'contact' => ['name' => 'Contact Form', 'icon' => 'mail', 'description' => 'Contact form section'],
    'partners' => ['name' => 'Partners Logos', 'icon' => 'users', 'description' => 'Partner logos grid'],
];

$adminBase = rtrim(BASE_PATH, '/') . '/admin';
$previewUrl = rtrim(BASE_PATH, '/') . '/index.php?page=' . urlencode($page['slug']);

include __DIR__ . '/includes/header.php';
?>

<!-- Page Builder Header -->
<div class="admin-content">
<div class="builder-header">
  <div class="builder-header__nav">
    <a href="pages.php" class="btn btn--outline btn--sm">
      <svg viewBox="0 0 24 24" width="16" height="16"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back to Pages
    </a>
  </div>
  <div class="builder-header__title">
    <h1>Page Builder</h1>
    <span class="builder-header__page-name"><?php echo htmlspecialchars($page['title']); ?></span>
  </div>
  <div class="builder-header__actions">
    <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" class="btn btn--secondary">
      <svg viewBox="0 0 24 24" width="16" height="16"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      Preview Page
    </a>
    <a href="pages.php" class="btn btn--primary">Save & Exit</a>
  </div>
</div>
</div>

<?php if ($message): ?>
  <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>">
    <?php echo htmlspecialchars($message); ?>
  </div>
<?php endif; ?>

<!-- Page Builder Layout -->
<div class="builder-layout">
  <!-- Left Panel: Section List -->
  <div class="builder-panel builder-panel--left">
    <div class="builder-panel__header">
      <h2>Page Sections</h2>
      <span class="builder-panel__count"><?php echo count($sections); ?> sections</span>
    </div>
    
    <div class="builder-panel__actions">
      <button type="button" class="btn btn--primary btn--sm" onclick="openAddSectionModal()">
        <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Section
      </button>
      <button type="button" class="btn btn--outline btn--sm" onclick="openTemplateModal()">
        <svg viewBox="0 0 24 24" width="14" height="14"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Templates
      </button>
    </div>
    
    <div class="builder-sections-list" id="sections-list">
      <?php if (empty($sections)): ?>
        <div class="builder-empty-state">
          <svg viewBox="0 0 24 24" width="48" height="48"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
          <h3>No sections yet</h3>
          <p>Add your first section to start building this page.</p>
          <button type="button" class="btn btn--primary" onclick="openAddSectionModal()">Add First Section</button>
        </div>
      <?php else: ?>
        <?php foreach ($sections as $index => $section): ?>
        <div class="builder-section-card" 
             data-section-id="<?php echo (int)$section['id']; ?>"
             tabindex="0"
             aria-label="Section: <?php echo htmlspecialchars($section['title'] ?: $section['section_key']); ?>">
           <div class="builder-section-card__handle" title="Drag to reorder">
             <svg viewBox="0 0 24 24" width="16" height="16"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
           </div>
           <div class="builder-section-card__number"><?php echo $index + 1; ?></div>
           <div class="builder-section-card__content">
             <div class="builder-section-card__key">
               <code><?php echo htmlspecialchars($section['section_key']); ?></code>
             </div>
             <div class="builder-section-card__title">
               <?php echo htmlspecialchars($section['title'] ?: 'Untitled Section'); ?>
             </div>
             <?php if ($section['content']): ?>
             <div class="builder-section-card__preview">
               <?php echo htmlspecialchars(substr(strip_tags($section['content']), 0, 80)); ?><?php echo strlen($section['content']) > 80 ? '...' : ''; ?>
             </div>
             <?php endif; ?>
           </div>
           <div class="builder-section-card__move-buttons">
             <button type="button" class="builder-section-card__move-btn" onclick="moveSectionUp(<?php echo (int)$section['id']; ?>)" title="Move up (Alt+Up)" <?php echo $index === 0 ? 'disabled' : ''; ?>>
               <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="18 15 12 9 6 15"/></svg>
             </button>
             <button type="button" class="builder-section-card__move-btn" onclick="moveSectionDown(<?php echo (int)$section['id']; ?>)" title="Move down (Alt+Down)" <?php echo $index === count($sections) - 1 ? 'disabled' : ''; ?>>
               <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg>
             </button>
           </div>
           <div class="builder-section-card__actions">
             <button type="button" class="btn btn--icon btn--sm" onclick="editSection(<?php echo (int)$section['id']; ?>)" title="Edit">
               <svg viewBox="0 0 24 24" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
             </button>
             <button type="button" class="btn btn--icon btn--sm" onclick="duplicateSection(<?php echo (int)$section['id']; ?>)" title="Duplicate">
               <svg viewBox="0 0 24 24" width="14" height="14"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
             </button>
             <button type="button" class="btn btn--icon btn--sm btn--danger" onclick="deleteSection(<?php echo (int)$section['id']; ?>)" title="Delete">
               <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
             </button>
           </div>
         </div>
         <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Right Panel: Preview & Editor -->
  <div class="builder-panel builder-panel--right">
    <div class="builder-panel__header">
      <h2>Live Preview</h2>
      <div class="builder-panel__tabs">
        <button type="button" class="builder-tab active" data-tab="preview">Preview</button>
        <button type="button" class="builder-tab" data-tab="details">Details</button>
      </div>
    </div>
    
    <!-- Preview Tab -->
    <div class="builder-tab-content active" id="tab-preview">
      <div class="builder-preview-frame">
        <div class="builder-preview-toolbar">
          <button type="button" class="builder-preview-device active" data-device="desktop" title="Desktop">
            <svg viewBox="0 0 24 24" width="18" height="18"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          </button>
          <button type="button" class="builder-preview-device" data-device="tablet" title="Tablet">
            <svg viewBox="0 0 24 24" width="18" height="18"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
          </button>
          <button type="button" class="builder-preview-device" data-device="mobile" title="Mobile">
            <svg viewBox="0 0 24 24" width="18" height="18"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
          </button>
          <span class="builder-preview-divider"></span>
          <button type="button" class="builder-preview-refresh" onclick="refreshPreview()" title="Refresh Preview">
            <svg viewBox="0 0 24 24" width="16" height="16"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          </button>
          <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" class="builder-preview-external" title="Open in new tab">
            <svg viewBox="0 0 24 24" width="16" height="16"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          </a>
        </div>
        <div class="builder-preview-container" id="preview-container">
          <iframe id="preview-frame" src="<?php echo htmlspecialchars($previewUrl); ?>?builder=1" frameborder="0"></iframe>
        </div>
      </div>
    </div>
    
    <!-- Details Tab -->
    <div class="builder-tab-content" id="tab-details">
      <div class="builder-details">
        <div class="builder-details-section">
          <h3>Page Information</h3>
          <div class="builder-details-grid">
            <div class="builder-details-item">
              <span class="builder-details-label">Page ID</span>
              <span class="builder-details-value">#<?php echo (int)$page['id']; ?></span>
            </div>
            <div class="builder-details-item">
              <span class="builder-details-label">Slug</span>
              <span class="builder-details-value"><code><?php echo htmlspecialchars($page['slug']); ?></code></span>
            </div>
            <div class="builder-details-item">
              <span class="builder-details-label">Title</span>
              <span class="builder-details-value"><?php echo htmlspecialchars($page['title']); ?></span>
            </div>
            <div class="builder-details-item">
              <span class="builder-details-label">Status</span>
              <span class="builder-details-value">
                <span class="badge <?php echo $page['is_active'] ? 'badge--success' : 'badge--muted'; ?>">
                  <?php echo $page['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
              </span>
            </div>
            <div class="builder-details-item">
              <span class="builder-details-label">Template</span>
              <span class="builder-details-value"><?php echo htmlspecialchars($page['template_type'] ?? 'fullwidth'); ?></span>
            </div>
            <div class="builder-details-item">
              <span class="builder-details-label">Sort Order</span>
              <span class="builder-details-value"><?php echo (int)$page['sort_order']; ?></span>
            </div>
          </div>
        </div>
        
        <div class="builder-details-section">
          <h3>SEO Information</h3>
          <div class="builder-details-grid">
            <div class="builder-details-item full-width">
              <span class="builder-details-label">Meta Title</span>
              <span class="builder-details-value"><?php echo htmlspecialchars($page['meta_title'] ?? $page['title'] . ' | Tulima Solar'); ?></span>
            </div>
            <div class="builder-details-item full-width">
              <span class="builder-details-label">Meta Description</span>
              <span class="builder-details-value"><?php echo htmlspecialchars($page['meta_description'] ?? 'No description set'); ?></span>
            </div>
          </div>
        </div>
        
        <div class="builder-details-section">
          <h3>Quick Actions</h3>
          <div class="builder-details-actions">
            <a href="pages.php" class="btn btn--outline btn--sm">Edit Page Settings</a>
            <a href="sections.php" class="btn btn--outline btn--sm">Manage All Sections</a>
            <form method="post" style="display: inline;" onsubmit="return confirm('Duplicate this page?');">
              <input type="hidden" name="action" value="duplicate_page">
              <button type="submit" class="btn btn--outline btn--sm">Duplicate Page</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Section Modal -->
<div id="section-modal" class="modal" style="display: none;">
  <div class="modal-overlay" onclick="closeSectionModal()"></div>
  <div class="modal-content" style="max-width: 700px;">
    <div class="modal-header">
      <h2 id="section-modal-title">Add Section</h2>
      <button type="button" class="modal-close" onclick="closeSectionModal()">&times;</button>
    </div>
    <form method="post" id="section-form">
      <input type="hidden" name="action" id="section-action" value="add_section">
      <input type="hidden" name="section_id" id="section-id" value="">
      
      <div class="modal-body">
        <div class="form-group">
          <label>Section Key <span class="required">*</span></label>
          <input type="text" name="section_key" id="section-key" class="form-control" required placeholder="e.g., hero, features, testimonials" pattern="[a-z0-9_\-]+" title="Lowercase letters, numbers, underscores, and hyphens only">
          <p class="form-hint">Unique identifier for this section. Use lowercase letters, numbers, and hyphens.</p>
        </div>
        
        <div class="form-group">
          <label>Section Title</label>
          <input type="text" name="section_title" id="section-title" class="form-control" placeholder="e.g., Our Features">
        </div>
        
        <div class="form-group">
          <label>Content</label>
          <textarea name="section_content" id="section-content" class="form-control" rows="6" placeholder="Main content for this section..."></textarea>
          <p class="form-hint">You can use HTML tags for formatting.</p>
        </div>
        
        <div class="form-group">
          <label>Extra Content</label>
          <textarea name="content_extra" id="section-extra" class="form-control" rows="4" placeholder="Additional content, separated by --- for multiple blocks"></textarea>
          <p class="form-hint">Use --- to separate multiple content blocks.</p>
        </div>
        
        <div class="form-group">
          <label>Image/Media</label>
          <div class="upload-field">
            <input type="text" name="image_path" id="section-image" class="form-control" placeholder="uploads/images/...">
            <button type="button" class="btn btn--secondary btn--sm" onclick="openMediaPicker('section-image', 'image')">Select from Media</button>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn--outline" onclick="closeSectionModal()">Cancel</button>
        <button type="submit" class="btn btn--primary" id="section-submit-btn">Add Section</button>
      </div>
    </form>
  </div>
</div>

<!-- Template Selection Modal -->
<div id="template-modal" class="modal" style="display: none;">
  <div class="modal-overlay" onclick="closeTemplateModal()"></div>
  <div class="modal-content" style="max-width: 800px;">
    <div class="modal-header">
      <h2>Section Templates</h2>
      <button type="button" class="modal-close" onclick="closeTemplateModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p class="template-modal-intro">Choose a template to quickly add a pre-configured section:</p>
      <div class="template-grid">
        <?php foreach ($sectionTemplates as $key => $template): ?>
        <div class="template-card" onclick="addFromTemplate('<?php echo htmlspecialchars($key); ?>')">
          <div class="template-card__icon">
            <?php
            $icons = [
              'image' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
              'grid' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
              'quote' => '<path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3"/>',
              'mouse-pointer' => '<path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/><path d="M13 13l6 6"/>',
              'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
              'images' => '<path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>',
              'bar-chart' => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',
              'help-circle' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
              'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
              'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            ];
            echo '<svg viewBox="0 0 24 24" width="24" height="24">' . ($icons[$template['icon']] ?? $icons['file-text']) . '</svg>';
            ?>
          </div>
          <div class="template-card__content">
            <h4><?php echo htmlspecialchars($template['name']); ?></h4>
            <p><?php echo htmlspecialchars($template['description']); ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn--outline" onclick="closeTemplateModal()">Close</button>
    </div>
  </div>
</div>

<style>
/* Builder Layout */
.builder-layout {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 0;
  height: calc(100vh - 140px);
  background: var(--bg-secondary);
}

@media (max-width: 1024px) {
  .builder-layout {
    grid-template-columns: 1fr;
    height: auto;
  }
}

/* Builder Header */
.builder-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4);
  background: var(--bg-primary);
  border-bottom: 1px solid var(--border-color);
  flex-wrap: wrap;
  gap: var(--space-3);
}

.builder-header__nav {
  display: flex;
  gap: var(--space-2);
}

.builder-header__title {
  display: flex;
  align-items: baseline;
  gap: var(--space-3);
}

.builder-header__title h1 {
  margin: 0;
  font-size: 1.25rem;
}

.builder-header__page-name {
  color: var(--text-muted);
  font-size: 0.9rem;
}

.builder-header__actions {
  display: flex;
  gap: var(--space-2);
}

/* Builder Panel */
.builder-panel {
  background: var(--bg-primary);
  display: flex;
  flex-direction: column;
}

.builder-panel--left {
  border-right: 1px solid var(--border-color);
  overflow: hidden;
}

.builder-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4);
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-secondary);
}

.builder-panel__header h2 {
  margin: 0;
  font-size: 1rem;
}

.builder-panel__count {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.builder-panel__tabs {
  display: flex;
  gap: var(--space-1);
}

.builder-tab {
  padding: var(--space-2) var(--space-3);
  background: none;
  border: none;
  font-size: 0.85rem;
  color: var(--text-muted);
  cursor: pointer;
  border-radius: var(--radius-sm);
}

.builder-tab.active {
  background: var(--bg-primary);
  color: var(--text-primary);
  font-weight: 500;
}

.builder-panel__actions {
  display: flex;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-4);
  border-bottom: 1px solid var(--border-color);
}

/* Sections List */
.builder-sections-list {
  flex: 1;
  overflow-y: auto;
  padding: var(--space-3);
}

.builder-empty-state {
  text-align: center;
  padding: var(--space-8) var(--space-4);
  color: var(--text-muted);
}

.builder-empty-state svg {
  opacity: 0.3;
  margin-bottom: var(--space-4);
}

.builder-empty-state h3 {
  margin: 0 0 var(--space-2);
  color: var(--text-primary);
}

.builder-empty-state p {
  margin: 0 0 var(--space-4);
}

/* Section Card */
.builder-section-card {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-3);
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-2);
  cursor: grab;
  transition: all 0.2s ease;
}

.builder-section-card:hover {
  border-color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}

.builder-section-card.dragging {
  opacity: 0.5;
  cursor: grabbing;
}

.builder-section-card__handle {
  color: var(--text-muted);
  cursor: grab;
  padding: var(--space-1);
}

.builder-section-card__number {
  width: 24px;
  height: 24px;
  background: var(--color-primary);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
  flex-shrink: 0;
}

.builder-section-card__content {
  flex: 1;
  min-width: 0;
}

.builder-section-card__key {
  margin-bottom: var(--space-1);
}

.builder-section-card__key code {
  font-size: 0.75rem;
  background: var(--bg-tertiary);
  padding: 2px 6px;
  border-radius: var(--radius-sm);
}

.builder-section-card__title {
  font-weight: 500;
  margin-bottom: var(--space-1);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.builder-section-card__preview {
  font-size: 0.8rem;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.builder-section-card__actions {
  display: flex;
  gap: var(--space-1);
  opacity: 0;
  transition: opacity 0.2s;
}

.builder-section-card:hover .builder-section-card__actions {
  opacity: 1;
}

.btn--icon {
  padding: var(--space-1);
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  border-radius: var(--radius-sm);
}

.btn--icon:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

.btn--icon.btn--danger:hover {
  background: var(--color-danger);
  color: white;
}

/* Preview Frame */
.builder-preview-frame {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.builder-preview-toolbar {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-3);
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-color);
}

.builder-preview-device {
  padding: var(--space-2);
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  border-radius: var(--radius-sm);
}

.builder-preview-device.active {
  background: var(--color-primary);
  color: white;
}

.builder-preview-device:hover:not(.active) {
  background: var(--bg-tertiary);
}

.builder-preview-divider {
  width: 1px;
  height: 20px;
  background: var(--border-color);
  margin: 0 var(--space-2);
}

.builder-preview-refresh,
.builder-preview-external {
  padding: var(--space-2);
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  border-radius: var(--radius-sm);
  text-decoration: none;
}

.builder-preview-refresh:hover,
.builder-preview-external:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

.builder-preview-container {
  flex: 1;
  background: white;
  display: flex;
  justify-content: center;
  padding: var(--space-4);
  overflow: auto;
}

.builder-preview-container iframe {
  width: 100%;
  height: 100%;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: white;
}

.builder-preview-container.tablet iframe {
  max-width: 768px;
}

.builder-preview-container.mobile iframe {
  max-width: 375px;
}

/* Details Tab */
.builder-tab-content {
  display: none;
  flex: 1;
  overflow-y: auto;
}

.builder-tab-content.active {
  display: block;
}

.builder-details {
  padding: var(--space-4);
}

.builder-details-section {
  margin-bottom: var(--space-5);
}

.builder-details-section h3 {
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  margin: 0 0 var(--space-3);
  padding-bottom: var(--space-2);
  border-bottom: 1px solid var(--border-color);
}

.builder-details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-3);
}

.builder-details-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.builder-details-item.full-width {
  grid-column: 1 / -1;
}

.builder-details-label {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.builder-details-value {
  font-size: 0.9rem;
}

.builder-details-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

/* Template Grid */
.template-modal-intro {
  color: var(--text-muted);
  margin-bottom: var(--space-4);
}

.template-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-3);
}

.template-card {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-3);
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s;
}

.template-card:hover {
  border-color: var(--color-primary);
  background: var(--bg-tertiary);
}

.template-card__icon {
  width: 40px;
  height: 40px;
  background: var(--color-primary);
  color: white;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.template-card__content h4 {
  margin: 0 0 var(--space-1);
  font-size: 0.9rem;
}

.template-card__content p {
  margin: 0;
  font-size: 0.8rem;
  color: var(--text-muted);
}

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

.form-hint {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: var(--space-1);
}

.upload-field {
  display: flex;
  gap: var(--space-2);
}

.upload-field input {
  flex: 1;
}

.required {
  color: var(--color-danger);
}

/* Drag and Drop Styles */
.builder-section-card--ghost {
    opacity: 0.4;
    background: var(--color-primary-light);
    border: 2px dashed var(--color-primary);
}

.builder-section-card--chosen {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    transform: scale(1.02);
    z-index: 100;
}

.builder-section-card--drag {
    opacity: 0.9;
}

/* Sortable fallback indicator */
.sortable-fallback {
    opacity: 0.6;
    background: var(--color-primary-light) !important;
}

/* Toast Notifications */
.builder-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-4);
    background: var(--gray-800);
    color: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    z-index: 9999;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
}

.builder-toast.show {
    transform: translateY(0);
    opacity: 1;
}

.builder-toast--success {
    background: var(--success);
}

.builder-toast--error {
    background: var(--danger);
}

.builder-toast svg {
    flex-shrink: 0;
}

/* Save Indicator */
.builder-save-indicator {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-4) var(--space-6);
    background: rgba(0, 0, 0, 0.85);
    color: white;
    border-radius: var(--radius-lg);
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
}

.builder-save-indicator.show {
    opacity: 1;
    visibility: visible;
}

.builder-save-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Drag Handle Enhancement */
.builder-section-card__handle {
    cursor: grab;
    padding: var(--space-2);
    margin: calc(var(--space-2) * -1);
    margin-right: 0;
    border-radius: var(--radius-sm);
    transition: background 0.2s;
}

.builder-section-card__handle:hover {
    background: var(--bg-tertiary);
}

.builder-section-card__handle:active {
    cursor: grabbing;
}

/* Keyboard Focus for Section Cards */
.builder-section-card:focus {
    outline: 3px solid var(--primary);
    outline-offset: 2px;
}

/* Section Card with Up/Down Buttons (Accessibility Fallback) */
.builder-section-card__move-buttons {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-left: auto;
}

.builder-section-card__move-btn {
    padding: 2px;
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    border-radius: var(--radius-sm);
    transition: all 0.2s;
}

.builder-section-card__move-btn:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.builder-section-card__move-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}
</style>

<!-- SortableJS for drag-and-drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Section data for editing
const sectionsData = <?php echo json_encode($sections); ?>;
const pageId = <?php echo $pageId; ?>;

// Toast notification system
function showToast(message, type = 'success') {
    // Remove existing toast
    const existingToast = document.querySelector('.builder-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `builder-toast builder-toast--${type}`;
    toast.innerHTML = `
        <svg viewBox="0 0 24 24" width="18" height="18">
            ${type === 'success' 
                ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
                : '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'
            }
        </svg>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto remove
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Debounce function for auto-save
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Loading indicator
let saveIndicator = null;

function showSaveIndicator() {
    if (!saveIndicator) {
        saveIndicator = document.createElement('div');
        saveIndicator.className = 'builder-save-indicator';
        saveIndicator.innerHTML = `
            <div class="builder-save-spinner"></div>
            <span>Saving order...</span>
        `;
        document.body.appendChild(saveIndicator);
    }
    saveIndicator.classList.add('show');
}

function hideSaveIndicator() {
    if (saveIndicator) {
        saveIndicator.classList.remove('show');
    }
}

// Modal functions
function openAddSectionModal() {
  document.getElementById('section-modal-title').textContent = 'Add Section';
  document.getElementById('section-action').value = 'add_section';
  document.getElementById('section-id').value = '';
  document.getElementById('section-key').value = '';
  document.getElementById('section-title').value = '';
  document.getElementById('section-content').value = '';
  document.getElementById('section-extra').value = '';
  document.getElementById('section-image').value = '';
  document.getElementById('section-submit-btn').textContent = 'Add Section';
  document.getElementById('section-modal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
  document.getElementById('section-key').focus();
}

function closeSectionModal() {
  document.getElementById('section-modal').style.display = 'none';
  document.body.style.overflow = '';
}

function editSection(sectionId) {
  const section = sectionsData.find(s => s.id == sectionId);
  if (!section) return;
  
  document.getElementById('section-modal-title').textContent = 'Edit Section';
  document.getElementById('section-action').value = 'update_section';
  document.getElementById('section-id').value = sectionId;
  document.getElementById('section-key').value = section.section_key;
  document.getElementById('section-title').value = section.title || '';
  document.getElementById('section-content').value = section.content || '';
  document.getElementById('section-extra').value = section.content_extra || '';
  document.getElementById('section-image').value = section.image_path || '';
  document.getElementById('section-submit-btn').textContent = 'Update Section';
  document.getElementById('section-modal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function deleteSection(sectionId) {
  if (!confirm('Are you sure you want to delete this section?')) return;
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = `
    <input type="hidden" name="action" value="delete_section">
    <input type="hidden" name="section_id" value="${sectionId}">
  `;
  document.body.appendChild(form);
  form.submit();
}

function duplicateSection(sectionId) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = `
    <input type="hidden" name="action" value="duplicate_section">
    <input type="hidden" name="section_id" value="${sectionId}">
  `;
  document.body.appendChild(form);
  form.submit();
}

// Template modal
function openTemplateModal() {
  document.getElementById('template-modal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeTemplateModal() {
  document.getElementById('template-modal').style.display = 'none';
  document.body.style.overflow = '';
}

function addFromTemplate(templateKey) {
  closeTemplateModal();
  
  const templates = {
    'hero': { key: 'hero', title: 'Hero Section', content: '', extra: '' },
    'features': { key: 'features', title: 'Our Features', content: 'Feature 1\nFeature 2\nFeature 3', extra: 'Icon 1|Title 1|Description 1\n---\nIcon 2|Title 2|Description 2' },
    'testimonials': { key: 'testimonials', title: 'What Our Customers Say', content: '', extra: '' },
    'cta': { key: 'cta', title: 'Ready to Get Started?', content: 'Contact us today to learn more about our solar solutions.', extra: 'Get Started|contact' },
    'content': { key: 'content', title: '', content: 'Enter your content here...', extra: '' },
    'gallery': { key: 'gallery', title: 'Photo Gallery', content: '', extra: 'uploads/images/image1.jpg|Caption 1\n---\nuploads/images/image2.jpg|Caption 2' },
    'stats': { key: 'stats', title: 'Our Impact', content: '', extra: 'Farmers Served|10,000+|Across East Africa\n---\nAcres Irrigated|50,000+|Solar powered' },
    'faq': { key: 'faq', title: 'Frequently Asked Questions', content: '', extra: 'Question 1?|Answer 1\n---\nQuestion 2?|Answer 2' },
    'contact': { key: 'contact', title: 'Get In Touch', content: '', extra: '' },
    'partners': { key: 'partners', title: 'Our Partners', content: '', extra: '' }
  };
  
  const template = templates[templateKey];
  if (!template) return;
  
  openAddSectionModal();
  document.getElementById('section-key').value = template.key;
  document.getElementById('section-title').value = template.title;
  document.getElementById('section-content').value = template.content;
  document.getElementById('section-extra').value = template.extra;
}

// Tab switching
document.querySelectorAll('.builder-tab').forEach(tab => {
  tab.addEventListener('click', function() {
    document.querySelectorAll('.builder-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.builder-tab-content').forEach(c => c.classList.remove('active'));
    
    this.classList.add('active');
    document.getElementById('tab-' + this.dataset.tab).classList.add('active');
  });
});

// Preview device switching
document.querySelectorAll('.builder-preview-device').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.builder-preview-device').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    
    const container = document.getElementById('preview-container');
    container.className = 'builder-preview-container ' + this.dataset.device;
  });
});

// Refresh preview
function refreshPreview() {
  const iframe = document.getElementById('preview-frame');
  iframe.src = iframe.src;
}

// Initialize SortableJS for drag-and-drop
let sortableInstance = null;

function initSortable() {
    const sectionsList = document.getElementById('sections-list');
    if (!sectionsList) return;
    
    sortableInstance = new Sortable(sectionsList, {
        animation: 200,
        ghostClass: 'builder-section-card--ghost',
        chosenClass: 'builder-section-card--chosen',
        dragClass: 'builder-section-card--drag',
        handle: '.builder-section-card__handle',
        fallbackOnBody: true,
        swapThreshold: 0.65,
        onChoose: function(evt) {
            evt.item.classList.add('dragging');
        },
        onUnchoose: function(evt) {
            evt.item.classList.remove('dragging');
        },
        onEnd: function(evt) {
            updateNumbers();
            debouncedSaveOrder();
        }
    });
}

function updateNumbers() {
    document.querySelectorAll('.builder-section-card').forEach((card, index) => {
        card.querySelector('.builder-section-card__number').textContent = index + 1;
    });
}

// Debounced save function (500ms delay)
const debouncedSaveOrder = debounce(function() {
    saveOrderViaAjax();
}, 500);

// Save order via AJAX API call
function saveOrderViaAjax() {
    const sectionIds = [...document.querySelectorAll('.builder-section-card')]
        .map(card => parseInt(card.dataset.sectionId));
    
    showSaveIndicator();
    
    fetch('api/sections.php?action=reorder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            page_id: pageId,
            section_ids: sectionIds
        })
    })
    .then(response => response.json())
    .then(data => {
        hideSaveIndicator();
        if (data.success) {
            showToast('Section order saved successfully', 'success');
            // Refresh preview to show new order
            refreshPreview();
        } else {
            showToast(data.error || 'Failed to save order', 'error');
        }
    })
    .catch(error => {
        hideSaveIndicator();
        showToast('Network error. Please try again.', 'error');
        console.error('Save order error:', error);
    });
}

// Fallback: Move section up via API
function moveSectionUp(sectionId) {
    fetch('api/sections.php?action=move-up', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ section_id: sectionId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Section moved up', 'success');
            location.reload();
        } else {
            showToast(data.error || 'Could not move section', 'error');
        }
    })
    .catch(error => {
        showToast('Network error', 'error');
    });
}

// Fallback: Move section down via API
function moveSectionDown(sectionId) {
    fetch('api/sections.php?action=move-down', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ section_id: sectionId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Section moved down', 'success');
            location.reload();
        } else {
            showToast(data.error || 'Could not move section', 'error');
        }
    })
    .catch(error => {
        showToast('Network error', 'error');
    });
}

// Keyboard navigation for accessibility
document.addEventListener('keydown', function(e) {
    const focused = document.activeElement;
    if (!focused.classList.contains('builder-section-card')) return;
    
    if (e.key === 'ArrowUp' && e.altKey) {
        e.preventDefault();
        const sectionId = parseInt(focused.dataset.sectionId);
        moveSectionUp(sectionId);
    } else if (e.key === 'ArrowDown' && e.altKey) {
        e.preventDefault();
        const sectionId = parseInt(focused.dataset.sectionId);
        moveSectionDown(sectionId);
    }
});

// Initialize sortable on DOM ready
document.addEventListener('DOMContentLoaded', initSortable);

// Legacy drag and drop (fallback if SortableJS fails)
let draggedItem = null;

// Close modals on escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeSectionModal();
    closeTemplateModal();
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
