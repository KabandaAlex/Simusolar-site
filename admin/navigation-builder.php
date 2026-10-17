<?php
/**
 * Tulima Solar - Navigation & Footer Builder
 * Comprehensive admin interface for managing hierarchical menus and footer structures
 */
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$pdo = getDb();
$currentNav = 'navigation-builder';
$pageTitle = 'Navigation & Footer Builder';

$message = '';
$messageType = '';

// Initialize data
$allPages = [];
$menuItems = [];
$footerSections = [];
$footerLinks = [];
$navigationAssets = [];
$socialLinks = [];

// Get all pages from database
try {
    $allPages = $pdo->query('SELECT * FROM pages ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (Exception $e) {
    $message = 'Failed to load pages: ' . $e->getMessage();
    $messageType = 'error';
}

// Scan pages folder for PHP files
$pagesFolder = __DIR__ . '/../pages';
$availablePages = [];
if (is_dir($pagesFolder)) {
    $files = scandir($pagesFolder);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            // Convert filename to readable label
            $label = ucwords(str_replace(['-', '_'], ' ', preg_replace('/-new$/', '', $filename)));
            $availablePages[] = [
                'filename' => $file,
                'slug' => $filename,
                'label' => $label,
                'path' => '/pages/' . $file
            ];
        }
    }
}

// Get menu items (hierarchical)
try {
    $menuItems = $pdo->query('
        SELECT 
            mi.*,
            p.title as page_title,
            p.slug as page_slug,
            p.is_active as page_is_active,
            p.show_in_footer as page_show_in_footer
        FROM menu_items mi
        LEFT JOIN pages p ON mi.page_id = p.id
        ORDER BY mi.menu_type, mi.sort_order ASC
    ')->fetchAll();
} catch (Exception $e) {
    $message = 'Failed to load menu items: ' . $e->getMessage();
    $messageType = 'error';
}

// Get footer sections
try {
    $footerSections = $pdo->query('SELECT * FROM footer_sections ORDER BY sort_order ASC')->fetchAll();
} catch (Exception $e) {
    $message = 'Failed to load footer sections: ' . $e->getMessage();
    $messageType = 'error';
}

// Get footer links
try {
    $footerLinks = $pdo->query('
        SELECT fl.*,
            fs.title as section_title,
            p.title as page_title
        FROM footer_links fl
        LEFT JOIN footer_sections fs ON fl.section_id = fs.id
        LEFT JOIN pages p ON fl.page_id = p.id
        ORDER BY fl.section_id, fl.sort_order ASC
    ')->fetchAll();
} catch (Exception $e) {
    $message = 'Failed to load footer links: ' . $e->getMessage();
    $messageType = 'error';
}

// Get navigation assets
try {
    $navigationAssets = $pdo->query('SELECT * FROM navigation_assets ORDER BY asset_type ASC, id ASC')->fetchAll();
} catch (Exception $e) {
    // Table might not have sort_order column yet
    try {
        $navigationAssets = $pdo->query('SELECT * FROM navigation_assets ORDER BY asset_type ASC, id ASC')->fetchAll();
    } catch (Exception $e2) {
        $message = 'Failed to load navigation assets: ' . $e2->getMessage();
        $messageType = 'error';
    }
}

// Get social links
try {
    $socialLinks = $pdo->query('SELECT * FROM social_media_links ORDER BY sort_order ASC')->fetchAll();
} catch (Exception $e) {
    $message = 'Failed to load social links: ' . $e->getMessage();
    $messageType = 'error';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_menu_item':
                $title = trim($_POST['menu_title'] ?? '');
                $url = trim($_POST['menu_url'] ?? '');
                $pageId = !empty($_POST['menu_page_id']) ? (int)$_POST['menu_page_id'] : null;
                $parentId = !empty($_POST['menu_parent_id']) ? (int)$_POST['menu_parent_id'] : null;
                $icon = trim($_POST['menu_icon'] ?? '');
                $target = $_POST['menu_target'] ?? '_self';
                $menuType = $_POST['menu_type'] ?? 'main';
                $isActive = isset($_POST['menu_is_active']) ? 1 : 0;
                $sortOrder = !empty($_POST['menu_sort_order']) ? (int)$_POST['menu_sort_order'] : 0;
                
                if ($title === '') {
                    throw new Exception('Menu title is required');
                }
                
                $stmt = $pdo->prepare('
                    INSERT INTO menu_items (title, url, page_id, parent_id, icon, target, menu_type, is_active, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$title, $url, $pageId, $parentId, $icon, $target, $menuType, $isActive, $sortOrder]);
                
                logActivity('created', 'navigation', $pdo->lastInsertId(), "Menu item: {$title}");
                $message = 'Menu item added successfully';
                $messageType = 'success';
                break;
                
            case 'update_menu_item':
                $itemId = (int)$_POST['menu_item_id'];
                $title = trim($_POST['menu_title'] ?? '');
                $url = trim($_POST['menu_url'] ?? '');
                $pageId = !empty($_POST['menu_page_id']) ? (int)$_POST['menu_page_id'] : null;
                $parentId = !empty($_POST['menu_parent_id']) ? (int)$_POST['menu_parent_id'] : null;
                $icon = trim($_POST['menu_icon'] ?? '');
                $target = $_POST['menu_target'] ?? '_self';
                $menuType = $_POST['menu_type'] ?? 'main';
                $isActive = isset($_POST['menu_is_active']) ? 1 : 0;
                $sortOrder = !empty($_POST['menu_sort_order']) ? (int)$_POST['menu_sort_order'] : 0;
                
                if ($title === '') {
                    throw new Exception('Menu title is required');
                }
                
                $stmt = $pdo->prepare('
                    UPDATE menu_items 
                    SET title = ?, url = ?, page_id = ?, parent_id = ?, icon = ?, target = ?, menu_type = ?, is_active = ?, sort_order = ?
                    WHERE id = ?
                ');
                $stmt->execute([$title, $url, $pageId, $parentId, $icon, $target, $menuType, $isActive, $sortOrder, $itemId]);
                
                logActivity('updated', 'navigation', $itemId, "Menu item: {$title}");
                $message = 'Menu item updated successfully';
                $messageType = 'success';
                break;
                
            case 'delete_menu_item':
                $itemId = (int)$_POST['menu_item_id'];
                
                // Check if item has children
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM menu_items WHERE parent_id = ?');
                $stmt->execute([$itemId]);
                $hasChildren = (int)$stmt->fetchColumn() > 0;
                
                if ($hasChildren) {
                    throw new Exception('Cannot delete menu item with children. Delete children first.');
                }
                
                $stmt = $pdo->prepare('DELETE FROM menu_items WHERE id = ?');
                $stmt->execute([$itemId]);
                
                logActivity('deleted', 'navigation', $itemId, 'Menu item deleted');
                $message = 'Menu item deleted successfully';
                $messageType = 'success';
                break;
                
            case 'add_footer_section':
                $title = trim($_POST['footer_title'] ?? '');
                $sectionType = $_POST['footer_section_type'] ?? 'custom';
                $sortOrder = !empty($_POST['footer_sort_order']) ? (int)$_POST['footer_sort_order'] : 0;
                $isActive = isset($_POST['footer_is_active']) ? 1 : 0;
                
                if ($title === '') {
                    throw new Exception('Footer section title is required');
                }
                
                $stmt = $pdo->prepare('
                    INSERT INTO footer_sections (title, section_type, sort_order, is_active)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$title, $sectionType, $sortOrder, $isActive]);
                
                logActivity('created', 'footer', $pdo->lastInsertId(), "Footer section: {$title}");
                $message = 'Footer section added successfully';
                $messageType = 'success';
                break;
                
            case 'update_footer_section':
                $sectionId = (int)$_POST['footer_section_id'];
                $title = trim($_POST['footer_title'] ?? '');
                $sectionType = $_POST['footer_section_type'] ?? 'custom';
                $sortOrder = !empty($_POST['footer_sort_order']) ? (int)$_POST['footer_sort_order'] : 0;
                $isActive = isset($_POST['footer_is_active']) ? 1 : 0;
                
                if ($title === '') {
                    throw new Exception('Footer section title is required');
                }
                
                $stmt = $pdo->prepare('
                    UPDATE footer_sections 
                    SET title = ?, section_type = ?, sort_order = ?, is_active = ?
                    WHERE id = ?
                ');
                $stmt->execute([$title, $sectionType, $sortOrder, $isActive, $sectionId]);
                
                logActivity('updated', 'footer', $sectionId, "Footer section: {$title}");
                $message = 'Footer section updated successfully';
                $messageType = 'success';
                break;
                
            case 'delete_footer_section':
                $sectionId = (int)$_POST['footer_section_id'];
                $stmt = $pdo->prepare('DELETE FROM footer_sections WHERE id = ?');
                $stmt->execute([$sectionId]);
                
                logActivity('deleted', 'footer', $sectionId, 'Footer section deleted');
                $message = 'Footer section deleted successfully';
                $messageType = 'success';
                break;
                
            case 'add_footer_link':
                $sectionId = (int)$_POST['footer_section_id'];
                $title = trim($_POST['footer_link_title'] ?? '');
                $url = trim($_POST['footer_link_url'] ?? '');
                $pageId = !empty($_POST['footer_link_page_id']) ? (int)$_POST['footer_link_page_id'] : null;
                $icon = trim($_POST['footer_link_icon'] ?? '');
                $target = $_POST['footer_link_target'] ?? '_self';
                $sortOrder = !empty($_POST['footer_link_sort_order']) ? (int)$_POST['footer_link_sort_order'] : 0;
                $isActive = isset($_POST['footer_link_is_active']) ? 1 : 0;
                
                if ($title === '' || $url === '') {
                    throw new Exception('Footer link title and URL are required');
                }
                
                $stmt = $pdo->prepare('
                    INSERT INTO footer_links (section_id, title, url, page_id, icon, target, sort_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$sectionId, $title, $url, $pageId, $icon, $target, $sortOrder, $isActive]);
                
                logActivity('created', 'footer', $pdo->lastInsertId(), "Footer link: {$title}");
                $message = 'Footer link added successfully';
                $messageType = 'success';
                break;
                
            case 'delete_footer_link':
                $linkId = (int)$_POST['footer_link_id'];
                $stmt = $pdo->prepare('DELETE FROM footer_links WHERE id = ?');
                $stmt->execute([$linkId]);
                
                logActivity('deleted', 'footer', $linkId, 'Footer link deleted');
                $message = 'Footer link deleted successfully';
                $messageType = 'success';
                break;
                
            case 'add_navigation_asset':
                $assetType = $_POST['nav_asset_type'] ?? 'logo';
                $assetPath = trim($_POST['nav_asset_path'] ?? '');
                $altText = trim($_POST['nav_asset_alt'] ?? '');
                $width = !empty($_POST['nav_asset_width']) ? (int)$_POST['nav_asset_width'] : null;
                $height = !empty($_POST['nav_asset_height']) ? (int)$_POST['nav_asset_height'] : null;
                $isActive = isset($_POST['nav_asset_is_active']) ? 1 : 0;
                
                if ($assetPath === '') {
                    throw new Exception('Asset path is required');
                }
                
                $stmt = $pdo->prepare('
                    INSERT INTO navigation_assets (asset_type, asset_path, alt_text, width, height, is_active)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$assetType, $assetPath, $altText, $width, $height, $isActive]);
                
                logActivity('created', 'navigation', $pdo->lastInsertId(), "Navigation asset: {$assetType}");
                $message = 'Navigation asset added successfully';
                $messageType = 'success';
                break;
                
            case 'delete_navigation_asset':
                $assetId = (int)$_POST['nav_asset_id'];
                $stmt = $pdo->prepare('DELETE FROM navigation_assets WHERE id = ?');
                $stmt->execute([$assetId]);
                
                logActivity('deleted', 'navigation', $assetId, 'Navigation asset deleted');
                $message = 'Navigation asset deleted successfully';
                $messageType = 'success';
                break;
                
            case 'add_social_link':
                $platform = $_POST['social_platform'] ?? 'facebook';
                $url = trim($_POST['social_url'] ?? '');
                $icon = trim($_POST['social_icon'] ?? '');
                $sortOrder = !empty($_POST['social_sort_order']) ? (int)$_POST['social_sort_order'] : 0;
                $isActive = isset($_POST['social_is_active']) ? 1 : 0;
                
                if ($url === '') {
                    throw new Exception('Social URL is required');
                }
                
                $stmt = $pdo->prepare('
                    INSERT INTO social_media_links (platform, url, icon, sort_order, is_active)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([$platform, $url, $icon, $sortOrder, $isActive]);
                
                logActivity('created', 'footer', $pdo->lastInsertId(), "Social link: {$platform}");
                $message = 'Social link added successfully';
                $messageType = 'success';
                break;
                
            case 'delete_social_link':
                $socialId = (int)$_POST['social_id'];
                $stmt = $pdo->prepare('DELETE FROM social_media_links WHERE id = ?');
                $stmt->execute([$socialId]);
                
                logActivity('deleted', 'footer', $socialId, 'Social link deleted');
                $message = 'Social link deleted successfully';
                $messageType = 'success';
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode($message) . '&type=' . $messageType);
        exit;
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Check for message from redirect
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'success';
}

$adminBase = rtrim(BASE_PATH, '/') . '/admin';
include __DIR__ . '/includes/header.php';
?>

<!-- Content Area -->
<div class="admin-content">
    <?php if ($message): ?>
        <div class="admin-alert admin-alert--<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Page Stats Overview -->
    <div class="page-stats-grid">
        <div class="page-stat-card page-stat-card--purple">
            <div class="page-stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="page-stat-card__content">
                <div class="page-stat-card__value"><?php echo count($allPages); ?></div>
                <div class="page-stat-card__label">Total Pages</div>
            </div>
        </div>
        <div class="page-stat-card page-stat-card--green">
            <div class="page-stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </div>
            <div class="page-stat-card__content">
                <div class="page-stat-card__value"><?php echo count($menuItems); ?></div>
                <div class="page-stat-card__label">Menu Items</div>
            </div>
        </div>
        <div class="page-stat-card page-stat-card--orange">
            <div class="page-stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="page-stat-card__content">
                <div class="page-stat-card__value"><?php echo count($footerSections); ?></div>
                <div class="page-stat-card__label">Footer Sections</div>
            </div>
        </div>
        <div class="page-stat-card page-stat-card--blue">
            <div class="page-stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </div>
            <div class="page-stat-card__content">
                <div class="page-stat-card__value"><?php echo count($socialLinks); ?></div>
                <div class="page-stat-card__label">Social Links</div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="admin-tabs">
        <button class="admin-tab active" data-tab="menu-builder">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            Menu Builder
        </button>
        <button class="admin-tab" data-tab="footer-builder">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Footer Builder
        </button>
        <button class="admin-tab" data-tab="assets-builder">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Assets
        </button>
        <button class="admin-tab" data-tab="social-links">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            Social Links
        </button>
    </div>

    <!-- Menu Builder Tab -->
    <div class="admin-tab-content active" id="menu-builder">
        <div class="admin-card">
            <div class="admin-card__header">
                <h3 class="admin-card__title">Menu Items Manager</h3>
            </div>
            <div class="admin-card__body">
                <!-- Menu Item Form -->
                <form method="POST" class="admin-form" id="menuForm">
                    <input type="hidden" name="action" value="add_menu_item" id="menu_action">
                    <input type="hidden" name="menu_item_id" value="" id="menu_item_id">
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-field__label" for="menu_title">Title *</label>
                            <input type="text" name="menu_title" id="menu_title" class="form-field__input" placeholder="Menu item title" required>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="menu_url">URL *</label>
                            <input type="text" name="menu_url" id="menu_url" class="form-field__input" placeholder="https://example.com" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-field__label" for="menu_page_id">Page</label>
                            <select name="menu_page_id" id="menu_page_id" class="form-field__select">
                                <option value="">Select page...</option>
                                <?php foreach ($allPages as $page): ?>
                                    <option value="<?php echo (int)$page['id']; ?>">
                                        <?php echo htmlspecialchars($page['title']); ?> (<?php echo htmlspecialchars($page['slug']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="menu_parent_id">Parent Item</label>
                            <select name="menu_parent_id" id="menu_parent_id" class="form-field__select">
                                <option value="">No parent (top-level)</option>
                                <?php foreach ($menuItems as $item): ?>
                                    <option value="<?php echo (int)$item['id']; ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-field__label" for="menu_type">Menu Type</label>
                            <select name="menu_type" id="menu_type" class="form-field__select">
                                <option value="main">Main Navigation</option>
                                <option value="mobile">Mobile Navigation</option>
                                <option value="footer">Footer Links</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="menu_target">Target</label>
                            <select name="menu_target" id="menu_target" class="form-field__select">
                                <option value="_self">Same tab</option>
                                <option value="_blank">New tab</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="menu_sort_order">Sort Order</label>
                            <input type="number" name="menu_sort_order" id="menu_sort_order" class="form-field__input" value="0">
                        </div>
                        <div class="form-field form-field--checkbox">
                            <label class="checkbox-label">
                                <input type="checkbox" name="menu_is_active" id="menu_is_active" checked>
                                <span>Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary" id="menu_submit_btn">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Menu Item
                        </button>
                        <button type="button" class="btn btn--secondary" onclick="resetMenuForm()">Cancel</button>
                    </div>
                </form>

                <!-- Menu Items List -->
                <div class="items-list">
                    <?php if (!empty($menuItems)): ?>
                        <?php foreach ($menuItems as $item): ?>
                            <div class="item-row">
                                <div class="item-row__info">
                                    <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                    <span class="item-row__meta"><?php echo htmlspecialchars($item['url']); ?> • <?php echo htmlspecialchars($item['menu_type']); ?></span>
                                </div>
                                <div class="item-row__actions">
                                    <button type="button" class="btn btn--secondary btn--sm" onclick='editMenuItem(<?php echo json_encode($item); ?>)'>Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this menu item?')">
                                        <input type="hidden" name="action" value="delete_menu_item">
                                        <input type="hidden" name="menu_item_id" value="<?php echo (int)$item['id']; ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-message">No menu items yet. Add your first menu item above.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Available Pages Section -->
        <div class="admin-card" style="margin-top: 1.5rem;">
            <div class="admin-card__header">
                <h3 class="admin-card__title">Available Pages (from /pages folder)</h3>
                <span class="admin-card__subtitle">Click to add pages to navigation menu</span>
            </div>
            <div class="admin-card__body">
                <div class="available-pages-grid">
                    <?php foreach ($availablePages as $page): ?>
                        <div class="page-chip" draggable="true"
                             data-title="<?php echo htmlspecialchars($page['label']); ?>"
                             data-url="<?php echo htmlspecialchars($page['path']); ?>"
                             onclick="addPageToMenu('<?php echo htmlspecialchars($page['label']); ?>', '<?php echo htmlspecialchars($page['path']); ?>')">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span><?php echo htmlspecialchars($page['label']); ?></span>
                            <small><?php echo htmlspecialchars($page['filename']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Builder Tab -->
    <div class="admin-tab-content" id="footer-builder">
        <div class="admin-card">
            <div class="admin-card__header">
                <h3 class="admin-card__title">Footer Sections</h3>
            </div>
            <div class="admin-card__body">
                <!-- Footer Section Form -->
                <form method="POST" class="admin-form" id="footerSectionForm">
                    <input type="hidden" name="action" value="add_footer_section" id="footer_section_action">
                    <input type="hidden" name="footer_section_id" value="" id="footer_section_id">
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-field__label" for="footer_title">Title *</label>
                            <input type="text" name="footer_title" id="footer_title" class="form-field__input" placeholder="Section title" required>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="footer_section_type">Section Type</label>
                            <select name="footer_section_type" id="footer_section_type" class="form-field__select">
                                <option value="brand">Brand</option>
                                <option value="links">Links</option>
                                <option value="products">Products</option>
                                <option value="contact">Contact</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="footer_sort_order">Sort Order</label>
                            <input type="number" name="footer_sort_order" id="footer_sort_order" class="form-field__input" value="0">
                        </div>
                        <div class="form-field form-field--checkbox">
                            <label class="checkbox-label">
                                <input type="checkbox" name="footer_is_active" id="footer_is_active" checked>
                                <span>Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary" id="footer_section_submit_btn">Add Section</button>
                        <button type="button" class="btn btn--secondary" onclick="resetFooterSectionForm()">Cancel</button>
                    </div>
                </form>

                <!-- Footer Sections List -->
                <div class="items-list">
                    <?php if (!empty($footerSections)): ?>
                        <?php foreach ($footerSections as $section): ?>
                            <div class="item-row item-row--section">
                                <div class="item-row__info">
                                    <strong><?php echo htmlspecialchars($section['title']); ?></strong>
                                    <span class="item-row__meta"><?php echo htmlspecialchars($section['section_type']); ?></span>
                                </div>
                                <div class="item-row__actions">
                                    <button type="button" class="btn btn--secondary btn--sm" onclick='editFooterSection(<?php echo json_encode($section); ?>)'>Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this section and all its links?')">
                                        <input type="hidden" name="action" value="delete_footer_section">
                                        <input type="hidden" name="footer_section_id" value="<?php echo (int)$section['id']; ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Footer Links for this section -->
                            <?php 
                            $sectionLinks = array_filter($footerLinks, function($link) use ($section) {
                                return $link['section_id'] == $section['id'];
                            });
                            ?>
                            <?php if (!empty($sectionLinks)): ?>
                                <div class="sub-items">
                                    <?php foreach ($sectionLinks as $link): ?>
                                        <div class="item-row item-row--sub">
                                            <div class="item-row__info">
                                                <?php echo htmlspecialchars($link['title']); ?>
                                                <span class="item-row__meta"><?php echo htmlspecialchars($link['url']); ?></span>
                                            </div>
                                            <div class="item-row__actions">
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this link?')">
                                                    <input type="hidden" name="action" value="delete_footer_link">
                                                    <input type="hidden" name="footer_link_id" value="<?php echo (int)$link['id']; ?>">
                                                    <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Add Link Form -->
                            <div class="sub-form">
                                <h4>Add Link to <?php echo htmlspecialchars($section['title']); ?></h4>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="add_footer_link">
                                    <input type="hidden" name="footer_section_id" value="<?php echo (int)$section['id']; ?>">
                                    <input type="text" name="footer_link_title" placeholder="Title" class="form-field__input" required>
                                    <input type="text" name="footer_link_url" placeholder="URL" class="form-field__input" required>
                                    <button type="submit" class="btn btn--primary btn--sm">Add Link</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-message">No footer sections yet. Add your first section above.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets Tab -->
    <div class="admin-tab-content" id="assets-builder">
        <div class="admin-card">
            <div class="admin-card__header">
                <h3 class="admin-card__title">Navigation Assets</h3>
            </div>
            <div class="admin-card__body">
                <!-- Asset Form -->
                <form method="POST" class="admin-form" id="assetForm">
                    <input type="hidden" name="action" value="add_navigation_asset">
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-field__label" for="nav_asset_type">Asset Type *</label>
                            <select name="nav_asset_type" id="nav_asset_type" class="form-field__select" required>
                                <option value="logo">Logo</option>
                                <option value="favicon">Favicon</option>
                                <option value="mobile_logo">Mobile Logo</option>
                                <option value="footer_logo">Footer Logo</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="nav_asset_path">Asset Path *</label>
                            <input type="text" name="nav_asset_path" id="nav_asset_path" class="form-field__input" placeholder="/media/logo.png" required>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="nav_asset_alt">Alt Text</label>
                            <input type="text" name="nav_asset_alt" id="nav_asset_alt" class="form-field__input" placeholder="Logo description">
                        </div>
                        <div class="form-field form-field--checkbox">
                            <label class="checkbox-label">
                                <input type="checkbox" name="nav_asset_is_active" checked>
                                <span>Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Add Asset</button>
                    </div>
                </form>

                <!-- Assets List -->
                <div class="items-list">
                    <?php if (!empty($navigationAssets)): ?>
                        <?php foreach ($navigationAssets as $asset): ?>
                            <div class="item-row">
                                <div class="item-row__info">
                                    <strong><?php echo htmlspecialchars($asset['asset_type']); ?></strong>
                                    <span class="item-row__meta"><?php echo htmlspecialchars($asset['asset_path']); ?></span>
                                </div>
                                <div class="item-row__actions">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this asset?')">
                                        <input type="hidden" name="action" value="delete_navigation_asset">
                                        <input type="hidden" name="nav_asset_id" value="<?php echo (int)$asset['id']; ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-message">No navigation assets yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Links Tab -->
    <div class="admin-tab-content" id="social-links">
        <div class="admin-card">
            <div class="admin-card__header">
                <h3 class="admin-card__title">Social Media Links</h3>
            </div>
            <div class="admin-card__body">
                <!-- Social Link Form -->
                <form method="POST" class="admin-form" id="socialForm">
                    <input type="hidden" name="action" value="add_social_link">
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-field__label" for="social_platform">Platform *</label>
                            <select name="social_platform" id="social_platform" class="form-field__select" required>
                                <option value="facebook">Facebook</option>
                                <option value="twitter">Twitter</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="instagram">Instagram</option>
                                <option value="youtube">YouTube</option>
                                <option value="tiktok">TikTok</option>
                                <option value="whatsapp">WhatsApp</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="social_url">URL *</label>
                            <input type="text" name="social_url" id="social_url" class="form-field__input" placeholder="https://facebook.com/yourpage" required>
                        </div>
                        <div class="form-field">
                            <label class="form-field__label" for="social_sort_order">Sort Order</label>
                            <input type="number" name="social_sort_order" id="social_sort_order" class="form-field__input" value="0">
                        </div>
                        <div class="form-field form-field--checkbox">
                            <label class="checkbox-label">
                                <input type="checkbox" name="social_is_active" checked>
                                <span>Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Add Social Link</button>
                    </div>
                </form>

                <!-- Social Links List -->
                <div class="items-list">
                    <?php if (!empty($socialLinks)): ?>
                        <?php foreach ($socialLinks as $link): ?>
                            <div class="item-row">
                                <div class="item-row__info">
                                    <strong><?php echo htmlspecialchars(ucfirst($link['platform'])); ?></strong>
                                    <span class="item-row__meta"><?php echo htmlspecialchars($link['url']); ?></span>
                                </div>
                                <div class="item-row__actions">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this social link?')">
                                        <input type="hidden" name="action" value="delete_social_link">
                                        <input type="hidden" name="social_id" value="<?php echo (int)$link['id']; ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-message">No social links yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Page Stats Grid */
.page-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.page-stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid #660099;
}

.page-stat-card--purple { border-left-color: #660099; }
.page-stat-card--green { border-left-color: #22C55E; }
.page-stat-card--orange { border-left-color: #F97316; }
.page-stat-card--blue { border-left-color: #3B82F6; }

.page-stat-card__icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(102, 0, 153, 0.1);
    border-radius: 0.5rem;
    color: #660099;
}

.page-stat-card--green .page-stat-card__icon { background: rgba(34, 197, 94, 0.1); color: #22C55E; }
.page-stat-card--orange .page-stat-card__icon { background: rgba(249, 115, 22, 0.1); color: #F97316; }
.page-stat-card--blue .page-stat-card__icon { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }

.page-stat-card__icon svg {
    width: 24px;
    height: 24px;
}

.page-stat-card__value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1E293B;
    line-height: 1;
}

.page-stat-card__label {
    font-size: 0.875rem;
    color: #64748B;
}

/* Tabs */
.admin-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #E2E8F0;
    padding-bottom: 0;
}

.admin-tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    font-size: 0.9375rem;
    font-weight: 500;
    color: #64748B;
    transition: all 0.2s;
}

.admin-tab:hover {
    color: #660099;
}

.admin-tab.active {
    color: #660099;
    border-bottom-color: #660099;
}

/* Tab Content */
.admin-tab-content {
    display: none;
}

.admin-tab-content.active {
    display: block;
}

/* Admin Card */
.admin-card {
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.admin-card__header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #E2E8F0;
}

.admin-card__title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1E293B;
    margin: 0;
}

.admin-card__body {
    padding: 1.5rem;
}

/* Form Styles */
.admin-form {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #E2E8F0;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-field__label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.375rem;
}

.form-field__input,
.form-field__select {
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    border: 1px solid #D1D5DB;
    border-radius: 0.375rem;
    background: #fff;
    color: #1F2937;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.form-field__input:focus,
.form-field__select:focus {
    outline: none;
    border-color: #660099;
    box-shadow: 0 0 0 3px rgba(102, 0, 153, 0.1);
}

.form-field__select {
    cursor: pointer;
}

.form-field--checkbox {
    justify-content: flex-end;
    padding-top: 1.75rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
    color: #374151;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #660099;
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    font-size: 0.9375rem;
    font-weight: 500;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.btn--primary {
    background: #660099;
    color: #fff;
}

.btn--primary:hover {
    background: #4A0066;
}

.btn--secondary {
    background: #F3F4F6;
    color: #374151;
    border: 1px solid #D1D5DB;
}

.btn--secondary:hover {
    background: #E5E7EB;
}

.btn--danger {
    background: #DC2626;
    color: #fff;
}

.btn--danger:hover {
    background: #B91C1C;
}

.btn--sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
}

/* Items List */
.items-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem;
    background: #F9FAFB;
    border-radius: 0.375rem;
    border: 1px solid #E5E7EB;
}

.item-row:hover {
    background: #F3F4F6;
}

.item-row__info strong {
    display: block;
    color: #1F2937;
    font-weight: 600;
}

.item-row__meta {
    font-size: 0.8125rem;
    color: #6B7280;
}

.item-row__actions {
    display: flex;
    gap: 0.5rem;
}

.item-row--section {
    background: #fff;
    border-left: 3px solid #660099;
}

.sub-items {
    margin-left: 1.5rem;
    border-left: 2px solid #E5E7EB;
    padding-left: 0.5rem;
}

.item-row--sub {
    background: #F9FAFB;
    font-size: 0.9375rem;
}

.item-row--sub .item-row__info strong {
    font-weight: 500;
    font-size: 0.9375rem;
}

.sub-form {
    margin: 1rem 0;
    padding: 1rem;
    background: #F9FAFB;
    border-radius: 0.375rem;
}

.sub-form h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.inline-form {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.inline-form .form-field__input {
    flex: 1;
    min-width: 150px;
}

.empty-message {
    text-align: center;
    color: #6B7280;
    padding: 2rem;
    background: #F9FAFB;
    border-radius: 0.375rem;
}

/* Available Pages Grid */
.available-pages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
}

.page-chip {
    display: flex;
    flex-direction: column;
    padding: 0.75rem 1rem;
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
}

.page-chip:hover {
    background: #F3F4F6;
    border-color: #660099;
    transform: translateY(-1px);
}

.page-chip svg {
    color: #660099;
    margin-bottom: 0.25rem;
}

.page-chip span {
    font-weight: 600;
    color: #1F2937;
    font-size: 0.9375rem;
}

.page-chip small {
    font-size: 0.75rem;
    color: #6B7280;
    margin-top: 0.25rem;
}

.dark .page-chip {
    background: #334155;
    border-color: #475569;
}

.dark .page-chip:hover {
    background: #475569;
    border-color: #660099;
}

.dark .page-chip span {
    color: #F1F5F9;
}

.dark .page-chip small {
    color: #94A3B8;
}

/* Dark Mode */
.dark .page-stat-card,
.dark .admin-card,
.dark .item-row,
.dark .sub-form {
    background: #1E293B;
    border-color: #334155;
}

.dark .admin-card__header {
    border-color: #334155;
}

.dark .admin-card__title,
.dark .page-stat-card__value,
.dark .item-row__info strong,
.dark .form-field__label,
.dark .checkbox-label,
.dark .admin-tab,
.dark .sub-form h4 {
    color: #F1F5F9;
}

.dark .form-field__input,
.dark .form-field__select {
    background: #334155;
    border-color: #475569;
    color: #F1F5F9;
}

.dark .btn--secondary {
    background: #334155;
    border-color: #475569;
    color: #F1F5F9;
}

.dark .item-row--sub,
.dark .empty-message {
    background: #334155;
}

.dark .item-row__meta,
.dark .page-stat-card__label,
.dark .empty-message {
    color: #94A3B8;
}

.dark .admin-tabs {
    border-bottom-color: #334155;
}

.dark .admin-form {
    border-bottom-color: #334155;
}
</style>

<script>
// Tab switching
document.querySelectorAll('.admin-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        // Update tabs
        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Update content
        const tabId = this.getAttribute('data-tab');
        document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
    });
});

// Edit Menu Item
function editMenuItem(item) {
    document.getElementById('menu_action').value = 'update_menu_item';
    document.getElementById('menu_item_id').value = item.id;
    document.getElementById('menu_title').value = item.title;
    document.getElementById('menu_url').value = item.url;
    document.getElementById('menu_page_id').value = item.page_id || '';
    document.getElementById('menu_parent_id').value = item.parent_id || '';
    document.getElementById('menu_type').value = item.menu_type;
    document.getElementById('menu_target').value = item.target;
    document.getElementById('menu_sort_order').value = item.sort_order;
    document.getElementById('menu_is_active').checked = item.is_active == 1;
    document.getElementById('menu_submit_btn').innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Update Menu Item';
    document.getElementById('menu_title').focus();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Reset Menu Form
function resetMenuForm() {
    document.getElementById('menu_action').value = 'add_menu_item';
    document.getElementById('menu_item_id').value = '';
    document.getElementById('menuForm').reset();
    document.getElementById('menu_is_active').checked = true;
    document.getElementById('menu_submit_btn').innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Menu Item';
}

// Edit Footer Section
function editFooterSection(section) {
    document.getElementById('footer_section_action').value = 'update_footer_section';
    document.getElementById('footer_section_id').value = section.id;
    document.getElementById('footer_title').value = section.title;
    document.getElementById('footer_section_type').value = section.section_type;
    document.getElementById('footer_sort_order').value = section.sort_order;
    document.getElementById('footer_is_active').checked = section.is_active == 1;
    document.getElementById('footer_section_submit_btn').textContent = 'Update Section';
    document.getElementById('footer_title').focus();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Reset Footer Section Form
function resetFooterSectionForm() {
    document.getElementById('footer_section_action').value = 'add_footer_section';
    document.getElementById('footer_section_id').value = '';
    document.getElementById('footerSectionForm').reset();
    document.getElementById('footer_is_active').checked = true;
    document.getElementById('footer_section_submit_btn').textContent = 'Add Section';
}

// Add page to menu from Available Pages
function addPageToMenu(title, url) {
    document.getElementById('menu_action').value = 'add_menu_item';
    document.getElementById('menu_item_id').value = '';
    document.getElementById('menu_title').value = title;
    document.getElementById('menu_url').value = url;
    document.getElementById('menu_page_id').value = '';
    document.getElementById('menu_parent_id').value = '';
    document.getElementById('menu_type').value = 'main';
    document.getElementById('menu_target').value = '_self';
    document.getElementById('menu_sort_order').value = '0';
    document.getElementById('menu_is_active').checked = true;
    document.getElementById('menu_submit_btn').innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Add Menu Item';
    document.getElementById('menu_title').focus();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Add page to footer from Available Pages (in footer tab)
function addPageToFooter(title, url) {
    // Switch to footer tab
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.querySelector('[data-tab="footer-builder"]').classList.add('active');
    document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('footer-builder').classList.add('active');
    
    // Pre-fill the footer link form (first section)
    const firstSectionId = document.querySelector('[name="footer_section_id"]')?.value;
    if (firstSectionId) {
        const titleInput = document.querySelector(`#footer_link_title_${firstSectionId}`);
        const urlInput = document.querySelector(`#footer_link_url_${firstSectionId}`);
        if (titleInput && urlInput) {
            titleInput.value = title;
            urlInput.value = url;
            titleInput.focus();
        }
    }
}

console.log('Navigation Builder initialized');
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
