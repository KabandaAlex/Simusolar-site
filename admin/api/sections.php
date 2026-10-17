<?php
/**
 * Tulima Solar Admin - Sections API
 * Provides CRUD operations and reordering for page sections
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/path.php';
require_once __DIR__ . '/../auth.php';
requireAdmin();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$pdo = getDb();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $action);
            break;
            
        case 'POST':
            handlePostRequest($pdo, $action);
            break;
            
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Throwable $e) {
    sendJsonResponse(['error' => $e->getMessage()], 500);
}

/**
 * Handle GET requests
 */
function handleGetRequest($pdo, $action) {
    switch ($action) {
        case 'list':
            $pageId = (int)($_GET['page_id'] ?? 0);
            if ($pageId <= 0) {
                sendJsonResponse(['error' => 'Page ID is required'], 400);
            }
            $sections = getSectionsByPage($pdo, $pageId);
            sendJsonResponse(['success' => true, 'sections' => $sections]);
            break;
            
        case 'get':
            $sectionId = (int)($_GET['section_id'] ?? 0);
            if ($sectionId <= 0) {
                sendJsonResponse(['error' => 'Section ID is required'], 400);
            }
            $section = getSectionById($pdo, $sectionId);
            if (!$section) {
                sendJsonResponse(['error' => 'Section not found'], 404);
            }
            sendJsonResponse(['success' => true, 'section' => $section]);
            break;
            
        case 'all':
            // Get all sections with page info
            $pageFilter = isset($_GET['page_id']) ? (int)$_GET['page_id'] : null;
            $sections = getAllSections($pdo, $pageFilter);
            sendJsonResponse(['success' => true, 'sections' => $sections]);
            break;
            
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($pdo, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    switch ($action) {
        case 'reorder':
            $pageId = (int)($input['page_id'] ?? 0);
            $sectionIds = $input['section_ids'] ?? [];
            
            if ($pageId <= 0) {
                sendJsonResponse(['error' => 'Page ID is required'], 400);
            }
            
            if (!is_array($sectionIds) || empty($sectionIds)) {
                sendJsonResponse(['error' => 'Section IDs array is required'], 400);
            }
            
            $result = updateSectionOrder($pdo, $pageId, $sectionIds);
            sendJsonResponse($result);
            break;
            
        case 'move-up':
            $sectionId = (int)($input['section_id'] ?? 0);
            if ($sectionId <= 0) {
                sendJsonResponse(['error' => 'Section ID is required'], 400);
            }
            $result = moveSectionUp($pdo, $sectionId);
            sendJsonResponse($result);
            break;
            
        case 'move-down':
            $sectionId = (int)($input['section_id'] ?? 0);
            if ($sectionId <= 0) {
                sendJsonResponse(['error' => 'Section ID is required'], 400);
            }
            $result = moveSectionDown($pdo, $sectionId);
            sendJsonResponse($result);
            break;
            
        case 'bulk-reorder':
            // Reorder sections across all pages or a specific set
            $orders = $input['orders'] ?? []; // Array of [section_id => new_sort_order]
            
            if (!is_array($orders) || empty($orders)) {
                sendJsonResponse(['error' => 'Orders array is required'], 400);
            }
            
            $result = bulkUpdateSectionOrder($pdo, $orders);
            sendJsonResponse($result);
            break;
            
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

/**
 * Get all sections for a page ordered by sort_order
 */
function getSectionsByPage($pdo, $pageId) {
    $stmt = $pdo->prepare('
        SELECT id, page_id, section_key, title, content, content_extra, image_path, sort_order
        FROM page_sections 
        WHERE page_id = ? 
        ORDER BY sort_order, id
    ');
    $stmt->execute([$pageId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get a single section by ID
 */
function getSectionById($pdo, $sectionId) {
    $stmt = $pdo->prepare('
        SELECT s.*, p.title AS page_title, p.slug AS page_slug
        FROM page_sections s
        JOIN pages p ON p.id = s.page_id
        WHERE s.id = ?
    ');
    $stmt->execute([$sectionId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all sections with optional page filter
 */
function getAllSections($pdo, $pageId = null) {
    $sql = '
        SELECT s.*, p.title AS page_title, p.slug AS page_slug
        FROM page_sections s
        JOIN pages p ON p.id = s.page_id
    ';
    $params = [];
    
    if ($pageId) {
        $sql .= ' WHERE s.page_id = ?';
        $params[] = $pageId;
    }
    
    $sql .= ' ORDER BY p.sort_order, s.sort_order, s.id';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update section order for a page
 * @param PDO $pdo Database connection
 * @param int $pageId Page ID
 * @param array $sectionIds Array of section IDs in new order
 * @return array Result with success status
 */
function updateSectionOrder($pdo, $pageId, $sectionIds) {
    try {
        $pdo->beginTransaction();
        
        // Verify all sections belong to this page
        $stmt = $pdo->prepare('SELECT id FROM page_sections WHERE page_id = ?');
        $stmt->execute([$pageId]);
        $existingIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
        
        // Check for mismatched IDs
        $missingIds = array_diff($existingIds, $sectionIds);
        $extraIds = array_diff($sectionIds, $existingIds);
        
        if (!empty($extraIds)) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Some sections do not belong to this page'];
        }
        
        // Update sort_order for each section
        $stmt = $pdo->prepare('UPDATE page_sections SET sort_order = ? WHERE id = ? AND page_id = ?');
        foreach ($sectionIds as $index => $sectionId) {
            $stmt->execute([$index + 1, (int)$sectionId, $pageId]);
        }
        
        // Handle any sections not in the new order (put them at the end)
        $nextOrder = count($sectionIds) + 1;
        foreach ($missingIds as $id) {
            $stmt->execute([$nextOrder++, $id, $pageId]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Section order updated successfully',
            'updated_count' => count($sectionIds)
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Move a section up one position
 */
function moveSectionUp($pdo, $sectionId) {
    try {
        // Get current section
        $stmt = $pdo->prepare('SELECT id, page_id, sort_order FROM page_sections WHERE id = ?');
        $stmt->execute([$sectionId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) {
            return ['success' => false, 'error' => 'Section not found'];
        }
        
        // Get the section above it
        $stmt = $pdo->prepare('
            SELECT id, sort_order 
            FROM page_sections 
            WHERE page_id = ? AND sort_order < ? 
            ORDER BY sort_order DESC 
            LIMIT 1
        ');
        $stmt->execute([$current['page_id'], $current['sort_order']]);
        $above = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$above) {
            return ['success' => false, 'error' => 'Section is already at the top'];
        }
        
        // Swap sort orders
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare('UPDATE page_sections SET sort_order = ? WHERE id = ?');
        $stmt->execute([$above['sort_order'], $current['id']]);
        $stmt->execute([$current['sort_order'], $above['id']]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Section moved up successfully',
            'new_position' => $above['sort_order']
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Move a section down one position
 */
function moveSectionDown($pdo, $sectionId) {
    try {
        // Get current section
        $stmt = $pdo->prepare('SELECT id, page_id, sort_order FROM page_sections WHERE id = ?');
        $stmt->execute([$sectionId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) {
            return ['success' => false, 'error' => 'Section not found'];
        }
        
        // Get the section below it
        $stmt = $pdo->prepare('
            SELECT id, sort_order 
            FROM page_sections 
            WHERE page_id = ? AND sort_order > ? 
            ORDER BY sort_order ASC 
            LIMIT 1
        ');
        $stmt->execute([$current['page_id'], $current['sort_order']]);
        $below = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$below) {
            return ['success' => false, 'error' => 'Section is already at the bottom'];
        }
        
        // Swap sort orders
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare('UPDATE page_sections SET sort_order = ? WHERE id = ?');
        $stmt->execute([$below['sort_order'], $current['id']]);
        $stmt->execute([$current['sort_order'], $below['id']]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Section moved down successfully',
            'new_position' => $below['sort_order']
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Bulk update section orders
 */
function bulkUpdateSectionOrder($pdo, $orders) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare('UPDATE page_sections SET sort_order = ? WHERE id = ?');
        
        foreach ($orders as $sectionId => $sortOrder) {
            $stmt->execute([(int)$sortOrder, (int)$sectionId]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Section orders updated successfully',
            'updated_count' => count($orders)
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
