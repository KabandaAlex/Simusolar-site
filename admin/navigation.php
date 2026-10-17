<?php
/**
 * Navigation Menu & Footer Management
 * Allows administrators to manage which pages appear in the main navigation menu and footer
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'navigation';
$pageTitle = 'Navigation & Footer';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_visibility':
                // Update page visibility in menu
                if (!empty($_POST['page_id'])) {
                    $pageId = (int)$_POST['page_id'];
                    $isActive = isset($_POST['is_active']) && (int)$_POST['is_active'] === 1 ? 1 : 0;
                    
                    // Get page details for logging
                    $stmt = $pdo->prepare('SELECT slug, title FROM pages WHERE id = ?');
                    $stmt->execute([$pageId]);
                    $page = $stmt->fetch();
                    
                    if ($page) {
                        $stmt = $pdo->prepare('UPDATE pages SET is_active = ? WHERE id = ?');
                        $result = $stmt->execute([$isActive, $pageId]);
                        
                        if ($result) {
                            $actionText = $isActive ? 'added to' : 'removed from';
                            logActivity(
                                $isActive ? 'added' : 'removed',
                                'navigation',
                                $pageId,
                                "Page '{$page['title']}' ({$page['slug']})"
                            );
                            $message = "Page '{$page['title']}' has been " . ($isActive ? 'added to' : 'removed from') . " the navigation menu.";
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to update page visibility. Please try again.';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Page not found.';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'update_order':
                // Update sort order for multiple pages
                if (!empty($_POST['order'])) {
                    $orderData = json_decode($_POST['order'], true);
                    
                    if (is_array($orderData)) {
                        $success = true;
                        foreach ($orderData as $index => $pageId) {
                            $stmt = $pdo->prepare('UPDATE pages SET sort_order = ? WHERE id = ?');
                            if (!$stmt->execute([$index, (int)$pageId])) {
                                $success = false;
                                break;
                            }
                        }
                        
                        if ($success) {
                            logActivity(
                                'updated',
                                'navigation',
                                null,
                                'Navigation menu order'
                            );
                            $message = 'Menu order updated successfully.';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to update menu order. Please try again.';
                            $messageType = 'error';
                        }
                    }
                }
                break;
                
            case 'bulk_update':
                // Bulk update visibility for multiple pages
                if (!empty($_POST['page_ids']) && isset($_POST['visibility'])) {
                    $pageIds = array_map('intval', $_POST['page_ids']);
                    $visibility = (int)$_POST['visibility'];
                    
                    $placeholders = str_repeat('?,', count($pageIds) - 1) . '?';
                    $stmt = $pdo->prepare("UPDATE pages SET is_active = ? WHERE id IN ($placeholders)");
                    $result = $stmt->execute(array_merge([$visibility], $pageIds));
                    
                    if ($result) {
                        $actionText = $visibility ? 'added to' : 'removed from';
                        logActivity(
                            $visibility ? 'added' : 'removed',
                            'navigation',
                            null,
                            count($pageIds) . " pages (bulk action)"
                        );
                        $message = count($pageIds) . " page(s) have been " . ($visibility ? 'added to' : 'removed from') . " the navigation menu.";
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to update pages. Please try again.';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'update_footer_visibility':
                // Update page visibility in footer
                if (!empty($_POST['page_id'])) {
                    $pageId = (int)$_POST['page_id'];
                    $showInFooter = isset($_POST['show_in_footer']) && (int)$_POST['show_in_footer'] === 1 ? 1 : 0;
                    
                    // Get page details for logging
                    $stmt = $pdo->prepare('SELECT slug, title FROM pages WHERE id = ?');
                    $stmt->execute([$pageId]);
                    $page = $stmt->fetch();
                    
                    if ($page) {
                        $stmt = $pdo->prepare('UPDATE pages SET show_in_footer = ? WHERE id = ?');
                        $result = $stmt->execute([$showInFooter, $pageId]);
                        
                        if ($result) {
                            $actionText = $showInFooter ? 'added to' : 'removed from';
                            logActivity(
                                $showInFooter ? 'added' : 'removed',
                                'footer',
                                $pageId,
                                "Page '{$page['title']}' ({$page['slug']})"
                            );
                            $message = "Page '{$page['title']}' has been " . ($showInFooter ? 'added to' : 'removed from') . " the footer.";
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to update footer visibility. Please try again.';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Page not found.';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'bulk_footer_update':
                // Bulk update footer visibility for multiple pages
                if (!empty($_POST['page_ids']) && isset($_POST['visibility'])) {
                    $pageIds = array_map('intval', $_POST['page_ids']);
                    $visibility = (int)$_POST['visibility'];
                    
                    $placeholders = str_repeat('?,', count($pageIds) - 1) . '?';
                    $stmt = $pdo->prepare("UPDATE pages SET show_in_footer = ? WHERE id IN ($placeholders)");
                    $result = $stmt->execute(array_merge([$visibility], $pageIds));
                    
                    if ($result) {
                        $actionText = $visibility ? 'added to' : 'removed from';
                        logActivity(
                            $visibility ? 'added' : 'removed',
                            'footer',
                            null,
                            count($pageIds) . " pages (bulk action)"
                        );
                        $message = count($pageIds) . " page(s) have been " . ($visibility ? 'added to' : 'removed from') . " the footer.";
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to update footer visibility. Please try again.';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'add_php_file_to_menu':
                // Add a PHP file from /pages folder to navigation
                $slug = trim($_POST['slug'] ?? '');
                $label = trim($_POST['label'] ?? '');
                $path = trim($_POST['path'] ?? '');
                
                if ($slug && $label && $path) {
                    // Check if this page already exists in the database
                    $stmt = $pdo->prepare('SELECT id FROM pages WHERE slug = ?');
                    $stmt->execute([$slug]);
                    $existingPage = $stmt->fetch();
                    
                    if ($existingPage) {
                        // Page exists, add to menu_items if not already there
                        $stmt = $pdo->prepare('SELECT id FROM menu_items WHERE page_id = ? AND menu_type = "main"');
                        $stmt->execute([$existingPage['id']]);
                        $existingMenuItem = $stmt->fetch();
                        
                        if ($existingMenuItem) {
                            // Already in menu, just activate it
                            $stmt = $pdo->prepare('UPDATE menu_items SET is_active = 1 WHERE id = ?');
                            $result = $stmt->execute([$existingMenuItem['id']]);
                        } else {
                            // Add to menu_items table
                            $stmt = $pdo->prepare('INSERT INTO menu_items (title, url, page_id, sort_order, is_active, menu_type) VALUES (?, ?, ?, 0, 1, "main")');
                            $result = $stmt->execute([$label, $slug, $existingPage['id']]);
                        }
                        
                        if ($result) {
                            logActivity('updated', 'page', $existingPage['id'], $label . ' (added to menu)');
                            $message = '"' . $label . '" has been added to the navigation menu.';
                            $messageType = 'success';
                        }
                    } else {
                        // Create new page entry (using actual pages table schema)
                        $stmt = $pdo->prepare('INSERT INTO pages (title, slug, is_active, show_in_footer, sort_order) VALUES (?, ?, 1, 0, 0)');
                        $result = $stmt->execute([$label, $slug]);
                        
                        if ($result) {
                            $newPageId = $pdo->lastInsertId();
                            // Also add to menu_items
                            $stmt = $pdo->prepare('INSERT INTO menu_items (title, url, page_id, sort_order, is_active, menu_type) VALUES (?, ?, ?, 0, 1, "main")');
                            $stmt->execute([$label, $slug, $newPageId]);
                            logActivity('created', 'page', $newPageId, $label . ' (from /pages folder)');
                            $message = '"' . $label . '" has been added to the navigation menu.';
                            $messageType = 'success';
                        }
                    }
                    
                    if (empty($message)) {
                        $message = 'Failed to add page to menu. Please try again.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid page data provided.';
                    $messageType = 'error';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'An error occurred: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all pages from database
$allPages = $pdo->query('SELECT * FROM pages ORDER BY sort_order ASC, id ASC')->fetchAll();

// Scan pages folder for PHP files
$pagesFolder = __DIR__ . '/../pages';
$availablePhpFiles = [];
if (is_dir($pagesFolder)) {
    $files = scandir($pagesFolder);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            // Convert filename to readable label
            $label = ucwords(str_replace(['-', '_'], ' ', preg_replace('/-new$/', '', $filename)));
            $availablePhpFiles[] = [
                'filename' => $file,
                'slug' => $filename,
                'label' => $label,
                'path' => '/pages/' . $file
            ];
        }
    }
}

// Separate pages into different categories
$menuPages = [];
$hiddenMenuPages = [];
$footerPages = [];
$hiddenFooterPages = [];

foreach ($allPages as $page) {
    // Menu visibility
    if ((int)$page['is_active'] === 1) {
        $menuPages[] = $page;
    } else {
        $hiddenMenuPages[] = $page;
    }
    
    // Footer visibility (check if column exists)
    $showInFooter = isset($page['show_in_footer']) ? (int)$page['show_in_footer'] : 1;
    if ($showInFooter === 1) {
        $footerPages[] = $page;
    } else {
        $hiddenFooterPages[] = $page;
    }
}

$adminBase = rtrim(BASE_PATH, '/') . '/admin';

include __DIR__ . '/includes/header.php';
?>

<!-- Content Area -->
<div class="admin-content">
  <?php if ($message): ?>
    <div class="admin-alert admin-alert--<?php echo $messageType; ?> alert-dismissible">
      <?php echo htmlspecialchars($message); ?>
      <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
  <?php endif; ?>

  <!-- Page Stats Overview -->
  <div class="page-stats-grid">
    <div class="page-stat-card page-stat-card--purple">
      <div class="page-stat-card__icon">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div class="page-stat-card__value"><?php echo count($allPages); ?></div>
      <div class="page-stat-card__label">Total Pages</div>
    </div>
  
    <div class="page-stat-card page-stat-card--green">
      <div class="page-stat-card__icon">
        <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </div>
      <div class="page-stat-card__value"><?php echo count($menuPages); ?></div>
      <div class="page-stat-card__label">In Menu</div>
    </div>
  
    <div class="page-stat-card page-stat-card--orange">
      <div class="page-stat-card__icon">
        <svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-5.523 0-10-4.477-10-10a10.07 10.07 0 0 1 .94-2.94L1 1l20 20-1.06 1.06zM2.06 2.06L12 12l9.94 9.94A10.07 10.07 0 0 1 18 0z"/></svg>
      </div>
      <div class="page-stat-card__value"><?php echo count($hiddenMenuPages); ?></div>
      <div class="page-stat-card__label">Menu Hidden</div>
    </div>
    
    <div class="page-stat-card page-stat-card--blue">
      <div class="page-stat-card__icon">
        <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      </div>
      <div class="page-stat-card__value"><?php echo count($footerPages); ?></div>
      <div class="page-stat-card__label">In Footer</div>
    </div>
  </div>

  <!-- Menu Items Section -->
  <div class="admin-card">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          Menu Items
        </h3>
        <p class="admin-card__subtitle">Pages currently visible in navigation menu. Drag to reorder.</p>
      </div>
      <?php if (!empty($menuPages)): ?>
      <div class="admin-card__header-actions">
        <button type="button" class="btn btn--secondary btn--sm" onclick="bulkHideFromMenu()">
          <svg viewBox="0 0 24 24" width="14" height="14"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-5.523 0-10-4.477-10-10a10.07 10.07 0 0 1 .94-2.94L1 1l20 20-1.06 1.06zM2.06 2.06L12 12l9.94 9.94A10.07 10.07 0 0 1 18 0z"/></svg>
          Hide All
        </button>
      </div>
      <?php endif; ?>
    </div>
    <div class="admin-card__body">
      <?php if (!empty($menuPages)): ?>
      <div class="navigation-menu-list" id="menu-items-list">
        <?php foreach ($menuPages as $index => $page): ?>
        <div class="navigation-menu-item" data-id="<?php echo (int)$page['id']; ?>" data-index="<?php echo $index; ?>">
          <div class="navigation-menu-item__drag-handle" title="Drag to reorder">
            <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          </div>
          <div class="navigation-menu-item__checkbox">
            <input type="checkbox" class="menu-checkbox" value="<?php echo (int)$page['id']; ?>">
          </div>
          <div class="navigation-menu-item__info">
            <div class="navigation-menu-item__title"><?php echo htmlspecialchars($page['title']); ?></div>
            <div class="navigation-menu-item__slug"><code><?php echo htmlspecialchars($page['slug']); ?></code></div>
          </div>
          <div class="navigation-menu-item__actions">
            <a href="<?php echo $adminBase; ?>/pages.php" class="btn btn--secondary btn--sm btn-icon" title="Edit page">
              <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><polyline points="11 4 11 8 20 8"/></svg>
            </a>
            <a href="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?page=<?php echo htmlspecialchars($page['slug']); ?>" target="_blank" class="btn btn--outline btn--sm btn-icon" title="View on site">
              <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
            <button type="button" class="btn btn--ghost btn--sm btn-icon btn--danger" onclick="hideFromMenu(<?php echo (int)$page['id']; ?>)" title="Hide from menu">
              <svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-5.523 0-10-4.477-10-10a10.07 10.07 0 0 1 .94-2.94L1 1l20 20-1.06 1.06zM2.06 2.06L12 12l9.94 9.94A10.07 10.07 0 0 1 18 0z"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        <h3 class="empty-state__title">No Menu Items</h3>
        <p class="empty-state__text">Add pages to your navigation menu from available pages below.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Available Pages Section -->
  <div class="admin-card">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          Available Pages
        </h3>
        <p class="admin-card__subtitle">Pages that can be added to menu</p>
      </div>
      <?php if (!empty($hiddenMenuPages)): ?>
      <div class="admin-card__header-actions">
        <button type="button" class="btn btn--primary btn--sm" onclick="bulkShowInMenu()">
          <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add All
        </button>
      </div>
      <?php endif; ?>
    </div>
    <div class="admin-card__body">
      <?php if (!empty($hiddenMenuPages)): ?>
      <div class="navigation-pages-list">
        <?php foreach ($hiddenMenuPages as $page): ?>
        <div class="navigation-page-item">
          <div class="navigation-page-item__checkbox">
            <input type="checkbox" class="available-checkbox" value="<?php echo (int)$page['id']; ?>">
          </div>
          <div class="navigation-page-item__info">
            <div class="navigation-page-item__title"><?php echo htmlspecialchars($page['title']); ?></div>
            <div class="navigation-page-item__slug"><code><?php echo htmlspecialchars($page['slug']); ?></code></div>
          </div>
          <div class="navigation-page-item__actions">
            <a href="<?php echo $adminBase; ?>/pages.php" class="btn btn--secondary btn--sm btn-icon" title="Edit page">
              <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><polyline points="11 4 11 8 20 8"/></svg>
            </a>
            <a href="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?page=<?php echo htmlspecialchars($page['slug']); ?>" target="_blank" class="btn btn--outline btn--sm btn-icon" title="View on site">
              <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
            <button type="button" class="btn btn--primary btn--sm btn-icon" onclick="showInMenu(<?php echo (int)$page['id']; ?>)" title="Add to menu">
              <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <h3 class="empty-state__title">All Pages in Menu</h3>
        <p class="empty-state__text">All available pages are currently visible in the navigation menu.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- PHP Files from /pages Folder -->
  <div class="admin-card">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="M9 15l3 3 3-3"/></svg>
          PHP Files in /pages
        </h3>
        <p class="admin-card__subtitle">Standalone PHP files available in the pages folder</p>
      </div>
    </div>
    <div class="admin-card__body">
      <?php if (!empty($availablePhpFiles)): ?>
      <div class="php-files-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
        <?php foreach ($availablePhpFiles as $phpFile): ?>
        <div class="php-file-chip" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; transition: all 0.2s;">
          <div style="flex: 1; min-width: 0;">
            <div style="font-weight: 500; color: #1e293b; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($phpFile['label']); ?></div>
            <div style="font-size: 12px; color: #64748b; font-family: monospace;"><?php echo htmlspecialchars($phpFile['filename']); ?></div>
          </div>
          <button type="button" class="btn btn--primary btn--sm" onclick="addPhpFileToMenu('<?php echo htmlspecialchars($phpFile['slug']); ?>', '<?php echo htmlspecialchars($phpFile['label']); ?>')" title="Add to navigation">
            <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          </button>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
        <h3 class="empty-state__title">No PHP Files Found</h3>
        <p class="empty-state__text">No PHP files were found in the /pages folder.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Footer Pages Section -->
  <div class="admin-card">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Footer Pages
        </h3>
        <p class="admin-card__subtitle">Pages currently visible in footer navigation</p>
      </div>
      <?php if (!empty($footerPages)): ?>
      <div class="admin-card__header-actions">
        <button type="button" class="btn btn--secondary btn--sm" onclick="bulkHideFromFooter()">
          <svg viewBox="0 0 24 24" width="14" height="14"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-5.523 0-10-4.477-10-10a10.07 10.07 0 0 1 .94-2.94L1 1l20 20-1.06 1.06zM2.06 2.06L12 12l9.94 9.94A10.07 10.07 0 0 1 18 0z"/></svg>
          Hide All
        </button>
      </div>
      <?php endif; ?>
    </div>
    <div class="admin-card__body">
      <?php if (!empty($footerPages)): ?>
      <div class="navigation-pages-list" id="footer-pages-list">
        <?php foreach ($footerPages as $page): ?>
        <div class="navigation-page-item">
          <div class="navigation-page-item__checkbox">
            <input type="checkbox" class="footer-checkbox" value="<?php echo (int)$page['id']; ?>">
          </div>
          <div class="navigation-page-item__info">
            <div class="navigation-page-item__title"><?php echo htmlspecialchars($page['title']); ?></div>
            <div class="navigation-page-item__slug"><code><?php echo htmlspecialchars($page['slug']); ?></code></div>
          </div>
          <div class="navigation-page-item__actions">
            <a href="<?php echo $adminBase; ?>/pages.php" class="btn btn--secondary btn--sm btn-icon" title="Edit page">
              <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><polyline points="11 4 11 8 20 8"/></svg>
            </a>
            <a href="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?page=<?php echo htmlspecialchars($page['slug']); ?>" target="_blank" class="btn btn--outline btn--sm btn-icon" title="View on site">
              <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
            <button type="button" class="btn btn--ghost btn--sm btn-icon btn--danger" onclick="hideFromFooter(<?php echo (int)$page['id']; ?>)" title="Hide from footer">
              <svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-5.523 0-10-4.477-10-10a10.07 10.07 0 0 1 .94-2.94L1 1l20 20-1.06 1.06zM2.06 2.06L12 12l9.94 9.94A10.07 10.07 0 0 1 18 0z"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <h3 class="empty-state__title">No Footer Pages</h3>
        <p class="empty-state__text">Add pages to the footer from available pages below.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Available Pages for Footer Section -->
  <div class="admin-card">
    <div class="admin-card__header">
      <div class="admin-card__header-left">
        <h3 class="admin-card__title">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          Available for Footer
        </h3>
        <p class="admin-card__subtitle">Pages that can be added to footer</p>
      </div>
      <?php if (!empty($hiddenFooterPages)): ?>
      <div class="admin-card__header-actions">
        <button type="button" class="btn btn--primary btn--sm" onclick="bulkShowInFooter()">
          <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add All
        </button>
      </div>
      <?php endif; ?>
    </div>
    <div class="admin-card__body">
      <?php if (!empty($hiddenFooterPages)): ?>
      <div class="navigation-pages-list">
        <?php foreach ($hiddenFooterPages as $page): ?>
        <div class="navigation-page-item">
          <div class="navigation-page-item__checkbox">
            <input type="checkbox" class="available-footer-checkbox" value="<?php echo (int)$page['id']; ?>">
          </div>
          <div class="navigation-page-item__info">
            <div class="navigation-page-item__title"><?php echo htmlspecialchars($page['title']); ?></div>
            <div class="navigation-page-item__slug"><code><?php echo htmlspecialchars($page['slug']); ?></code></div>
          </div>
          <div class="navigation-page-item__actions">
            <a href="<?php echo $adminBase; ?>/pages.php" class="btn btn--secondary btn--sm btn-icon" title="Edit page">
              <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><polyline points="11 4 11 8 20 8"/></svg>
            </a>
            <a href="<?php echo rtrim(BASE_PATH, '/'); ?>/index.php?page=<?php echo htmlspecialchars($page['slug']); ?>" target="_blank" class="btn btn--outline btn--sm btn-icon" title="View on site">
              <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
            <button type="button" class="btn btn--primary btn--sm btn-icon" onclick="showInFooter(<?php echo (int)$page['id']; ?>)" title="Add to footer">
              <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <h3 class="empty-state__title">All Pages in Footer</h3>
        <p class="empty-state__text">All available pages are currently visible in the footer.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
/* Navigation Menu List Styles */
.navigation-menu-list,
.navigation-pages-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  margin: 0;
  padding: 0;
}

.navigation-menu-item,
.navigation-page-item {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-4);
  background: var(--section-white);
  border: 1px solid var(--card-border);
  border-radius: var(--radius-md);
  transition: all var(--transition-base);
  width: 100%;
  box-sizing: border-box;
}

.navigation-menu-item {
  cursor: grab;
}

.navigation-menu-item:active {
  cursor: grabbing;
}

.navigation-menu-item.dragging {
  opacity: 0.5;
  background: var(--primary-purple-light);
  border-color: var(--primary-purple);
}

.navigation-menu-item:hover,
.navigation-page-item:hover {
  box-shadow: var(--shadow-light);
  border-color: var(--primary-purple);
}

.navigation-menu-item__drag-handle {
  flex-shrink: 0;
  color: var(--text-muted);
  cursor: grab;
  padding: var(--space-1);
  border-radius: var(--radius-sm);
  transition: color var(--transition-base);
}

.navigation-menu-item__drag-handle:hover {
  color: var(--primary-purple);
}

.navigation-menu-item__checkbox,
.navigation-page-item__checkbox {
  flex-shrink: 0;
}

.navigation-menu-item__checkbox input,
.navigation-page-item__checkbox input {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--primary-purple);
}

.navigation-menu-item__info,
.navigation-page-item__info {
  flex: 1;
  min-width: 0;
}

.navigation-menu-item__title,
.navigation-page-item__title {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: var(--space-1);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.navigation-menu-item__slug,
.navigation-page-item__slug {
  font-size: var(--font-size-smallest);
  color: var(--text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.navigation-menu-item__actions,
.navigation-page-item__actions {
  display: flex;
  gap: var(--space-2);
  flex-shrink: 0;
}

.btn-icon {
  padding: var(--space-2);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-icon svg {
  width: 16px;
  height: 16px;
}

.btn--danger {
  color: #dc3545;
  border-color: #dc3545;
}

.btn--danger:hover {
  background: #dc3545;
  color: var(--white);
}

.btn--ghost.btn--danger {
  color: #dc3545;
  border-color: #dc3545;
  background: transparent;
}

.btn--ghost.btn--danger:hover {
  background: #dc3545;
  color: var(--white);
}

.admin-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.admin-card__header-left {
  flex: 1;
  min-width: 0;
}

.admin-card__header-actions {
  display: flex;
  gap: var(--space-2);
  flex-shrink: 0;
}

.page-stat-card__icon {
  position: absolute;
  top: var(--space-4);
  right: var(--space-4);
  opacity: 0.15;
  color: currentColor;
}

.page-stat-card {
  position: relative;
}

.alert-dismissible {
  position: relative;
  padding-right: 40px;
}

.alert-close {
  position: absolute;
  top: 50%;
  right: var(--space-3);
  transform: translateY(-50%);
  background: none;
  border: none;
  font-size: 24px;
  color: inherit;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  opacity: 0.7;
}

.alert-close:hover {
  opacity: 1;
}

/* Drag and Drop Visual Feedback */
.drag-over {
  border: 2px dashed var(--primary-purple);
  background: var(--primary-purple-light);
}
</style>

<script>
// Drag and Drop functionality
let draggedItem = null;
let draggedOverItem = null;

document.addEventListener('DOMContentLoaded', function() {
  const menuList = document.getElementById('menu-items-list');
  const menuItems = menuList.querySelectorAll('.navigation-menu-item');
  
  menuItems.forEach(item => {
    item.setAttribute('draggable', 'true');
    
    item.addEventListener('dragstart', handleDragStart);
    item.addEventListener('dragend', handleDragEnd);
    item.addEventListener('dragover', handleDragOver);
    item.addEventListener('dragenter', handleDragEnter);
    item.addEventListener('dragleave', handleDragLeave);
    item.addEventListener('drop', handleDrop);
  });
});

function handleDragStart(e) {
  draggedItem = this;
  this.classList.add('dragging');
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
  this.classList.remove('dragging');
  draggedItem = null;
  
  // Remove all drag-over classes
  document.querySelectorAll('.navigation-menu-item').forEach(item => {
    item.classList.remove('drag-over');
  });
  
  // Update sort order after drag is complete
  updateSortOrder();
}

function handleDragOver(e) {
  if (e.preventDefault) {
    e.preventDefault();
  }
  e.dataTransfer.dropEffect = 'move';
  return false;
}

function handleDragEnter(e) {
  if (this !== draggedItem) {
    this.classList.add('drag-over');
  }
}

function handleDragLeave(e) {
  this.classList.remove('drag-over');
}

function handleDrop(e) {
  if (e.stopPropagation) {
    e.stopPropagation();
  }
  
  if (draggedItem !== this) {
    // Swap the items in the DOM
    const menuList = document.getElementById('menu-items-list');
    const allItems = Array.from(menuList.children);
    const draggedIndex = allItems.indexOf(draggedItem);
    const droppedIndex = allItems.indexOf(this);
    
    if (draggedIndex < droppedIndex) {
      this.parentNode.insertBefore(draggedItem, this.nextSibling);
    } else {
      this.parentNode.insertBefore(draggedItem, this);
    }
  }
  
  return false;
}

function updateSortOrder() {
  const menuList = document.getElementById('menu-items-list');
  const items = menuList.querySelectorAll('.navigation-menu-item');
  const order = Array.from(items).map(item => item.dataset.id);
  
  // Send AJAX request to update order
  const formData = new FormData();
  formData.append('action', 'update_order');
  formData.append('order', JSON.stringify(order));
  
  fetch(window.location.href, {
    method: 'POST',
    body: formData
  })
  .then(response => response.text())
  .then(data => {
    // Page will reload with updated order
    window.location.reload();
  })
  .catch(error => {
    console.error('Error updating order:', error);
  });
}

// Hide page from menu
function hideFromMenu(pageId) {
  if (confirm('Are you sure you want to hide this page from the navigation menu?')) {
    submitForm('update_visibility', {
      page_id: pageId,
      is_active: 0
    });
  }
}

// Show page in menu
function showInMenu(pageId) {
  submitForm('update_visibility', {
    page_id: pageId,
    is_active: 1
  });
}

// Bulk hide from menu
function bulkHideFromMenu() {
  const checkboxes = document.querySelectorAll('.menu-checkbox:checked');
  
  if (checkboxes.length === 0) {
    alert('Please select at least one page to hide.');
    return;
  }
  
  if (confirm(`Are you sure you want to hide ${checkboxes.length} page(s) from the navigation menu?`)) {
    const pageIds = Array.from(checkboxes).map(cb => cb.value);
    submitForm('bulk_update', {
      page_ids: pageIds,
      visibility: 0
    });
  }
}

// Bulk show in menu
function bulkShowInMenu() {
  const checkboxes = document.querySelectorAll('.available-checkbox:checked');
  
  if (checkboxes.length === 0) {
    alert('Please select at least one page to add.');
    return;
  }
  
  if (confirm(`Are you sure you want to add ${checkboxes.length} page(s) to the navigation menu?`)) {
    const pageIds = Array.from(checkboxes).map(cb => cb.value);
    submitForm('bulk_update', {
      page_ids: pageIds,
      visibility: 1
    });
  }
}

// Hide page from footer
function hideFromFooter(pageId) {
  if (confirm('Are you sure you want to hide this page from the footer?')) {
    submitForm('update_footer_visibility', {
      page_id: pageId,
      show_in_footer: 0
    });
  }
}

// Show page in footer
function showInFooter(pageId) {
  submitForm('update_footer_visibility', {
    page_id: pageId,
    show_in_footer: 1
  });
}

// Bulk hide from footer
function bulkHideFromFooter() {
  const checkboxes = document.querySelectorAll('.footer-checkbox:checked');
  
  if (checkboxes.length === 0) {
    alert('Please select at least one page to hide.');
    return;
  }
  
  if (confirm(`Are you sure you want to hide ${checkboxes.length} page(s) from the footer?`)) {
    const pageIds = Array.from(checkboxes).map(cb => cb.value);
    submitForm('bulk_footer_update', {
      page_ids: pageIds,
      visibility: 0
    });
  }
}

// Bulk show in footer
function bulkShowInFooter() {
  const checkboxes = document.querySelectorAll('.available-footer-checkbox:checked');
  
  if (checkboxes.length === 0) {
    alert('Please select at least one page to add.');
    return;
  }
  
  if (confirm(`Are you sure you want to add ${checkboxes.length} page(s) to the footer?`)) {
    const pageIds = Array.from(checkboxes).map(cb => cb.value);
    submitForm('bulk_footer_update', {
      page_ids: pageIds,
      visibility: 1
    });
  }
}

// Helper function to submit form
function submitForm(action, data) {
  const form = document.createElement('form');
  form.method = 'post';
  form.action = window.location.href;
  
  let formData = `<input type="hidden" name="action" value="${action}">`;
  
  for (const [key, value] of Object.entries(data)) {
    if (Array.isArray(value)) {
      value.forEach(v => {
        formData += `<input type="hidden" name="${key}[]" value="${v}">`;
      });
    } else {
      formData += `<input type="hidden" name="${key}" value="${value}">`;
    }
  }
  
  form.innerHTML = formData;
  document.body.appendChild(form);
  form.submit();
}

// Add PHP file from /pages folder to navigation menu
function addPhpFileToMenu(slug, label) {
  if (confirm(`Add "${label}" to the navigation menu?`)) {
    submitForm('add_php_file_to_menu', {
      slug: slug,
      label: label,
      path: '/pages/' + slug + '.php'
    });
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
