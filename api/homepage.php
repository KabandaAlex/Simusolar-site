<?php
/**
 * Tulima Solar - Homepage API Endpoints
 * 
 * Provides data for homepage sections:
 * - Impact statistics
 * - Solutions/Services
 * - Partners
 * 
 * Usage:
 *   GET api/homepage.php?section=impact
 *   GET api/homepage.php?section=solutions
 *   GET api/homepage.php?section=partners
 *   GET api/homepage.php?section=all
 */

// Set JSON content type and prevent caching
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

/**
 * Send JSON response and exit
 * 
 * @param bool $success Whether the request was successful
 * @param mixed $data The data to return
 * @param string $message Optional message (for errors)
 * @param int $httpCode HTTP status code
 */
function sendResponse(bool $success, $data = null, string $message = '', int $httpCode = 200): void {
    http_response_code($httpCode);
    
    $response = [
        'success' => $success,
        'timestamp' => date('c')
    ];
    
    if ($success && $data !== null) {
        $response['data'] = $data;
    } elseif (!$success) {
        $response['error'] = [
            'message' => $message ?: 'An error occurred',
            'code' => $httpCode
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get impact statistics from database
 * 
 * @param PDO $pdo Database connection
 * @return array Impact statistics data
 */
function getImpactData(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare(
            'SELECT 
                id, 
                label as title, 
                value, 
                sublabel as description, 
                icon, 
                sort_order
            FROM impact_numbers 
            WHERE is_active = 1 
            ORDER BY sort_order, id'
        );
        $stmt->execute();
        
        $items = [];
        while ($row = $stmt->fetch()) {
            // Parse the value for count-up animation
            $parsed = parse_impact_value($row['value']);
            
            $items[] = [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'value' => $row['value'],
                'numeric_value' => $parsed['num'],
                'suffix' => $parsed['suffix'],
                'description' => $row['description'],
                'icon' => $row['icon'],
                'sort_order' => (int) $row['sort_order']
            ];
        }
        
        return $items;
    } catch (Throwable $e) {
        // Log error but return empty array
        error_log('Homepage API - Impact data error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get solutions/services from page_sections table
 * 
 * @param PDO $pdo Database connection
 * @return array Solutions/services data
 */
function getSolutionsData(PDO $pdo): array {
    try {
        // First, try to get the solutions/services section from the home page
        $stmt = $pdo->prepare(
            "SELECT ps.id, ps.section_key, ps.title, ps.content, ps.image_path, ps.sort_order
            FROM page_sections ps
            JOIN pages p ON ps.page_id = p.id
            WHERE p.slug = 'home' 
            AND (ps.section_key LIKE '%service%' OR ps.section_key LIKE '%solution%' OR ps.section_key LIKE '%what-we-do%')
            ORDER BY ps.sort_order
            LIMIT 1"
        );
        $stmt->execute();
        $section = $stmt->fetch();
        
        $solutions = [];
        
        if ($section) {
            // Get items for this section
            $stmt = $pdo->prepare(
                'SELECT 
                    id, 
                    title, 
                    content as description, 
                    content_extra,
                    sort_order
                FROM page_section_items 
                WHERE section_id = ? 
                ORDER BY sort_order, id'
            );
            $stmt->execute([$section['id']]);
            
            while ($row = $stmt->fetch()) {
                // Parse content_extra for additional data (icon, link, etc.)
                $extra = [];
                if (!empty($row['content_extra'])) {
                    $parts = explode('|', $row['content_extra']);
                    $extra['icon'] = $parts[0] ?? '';
                    $extra['link'] = $parts[1] ?? '';
                    $extra['image'] = $parts[2] ?? '';
                }
                
                $solutions[] = [
                    'id' => (int) $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'icon' => $extra['icon'] ?? null,
                    'image' => $extra['image'] ?? $section['image_path'],
                    'link' => $extra['link'] ?? null,
                    'sort_order' => (int) $row['sort_order']
                ];
            }
        }
        
        // If no solutions found in page_sections, try products table as fallback
        if (empty($solutions)) {
            $stmt = $pdo->prepare(
                'SELECT 
                    id, 
                    title, 
                    short_description as description, 
                    description as full_description,
                    image_path as image,
                    slug,
                    featured,
                    sort_order
                FROM products 
                WHERE is_active = 1 
                ORDER BY featured DESC, sort_order
                LIMIT 6'
            );
            $stmt->execute();
            
            while ($row = $stmt->fetch()) {
                $solutions[] = [
                    'id' => (int) $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'full_description' => $row['full_description'],
                    'image' => $row['image'],
                    'link' => 'index.php?page=products&slug=' . $row['slug'],
                    'featured' => (bool) $row['featured'],
                    'sort_order' => (int) $row['sort_order']
                ];
            }
        }
        
        return $solutions;
    } catch (Throwable $e) {
        error_log('Homepage API - Solutions data error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get partners from database
 * 
 * @param PDO $pdo Database connection
 * @return array Partners data
 */
function getPartnersData(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare(
            'SELECT 
                id, 
                name, 
                image_path as logo_url, 
                url as website_url, 
                sort_order
            FROM partners 
            WHERE is_active = 1 
            ORDER BY sort_order, id'
        );
        $stmt->execute();
        
        $partners = [];
        while ($row = $stmt->fetch()) {
            $partners[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'logo_url' => $row['logo_url'],
                'website_url' => $row['website_url'],
                'sort_order' => (int) $row['sort_order']
            ];
        }
        
        return $partners;
    } catch (Throwable $e) {
        error_log('Homepage API - Partners data error: ' . $e->getMessage());
        return [];
    }
}

// Main execution
try {
    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse(false, null, 'Method not allowed. Use GET request.', 405);
    }
    
    // Get database connection
    $pdo = getDb();
    
    // Get the requested section
    $section = isset($_GET['section']) ? strtolower(trim($_GET['section'])) : 'all';
    
    // Validate section parameter
    $validSections = ['impact', 'solutions', 'partners', 'all'];
    if (!in_array($section, $validSections)) {
        sendResponse(
            false, 
            null, 
            'Invalid section parameter. Valid options: ' . implode(', ', $validSections), 
            400
        );
    }
    
    // Build response data based on requested section
    $data = [];
    
    switch ($section) {
        case 'impact':
            $data['impact'] = getImpactData($pdo);
            break;
            
        case 'solutions':
            $data['solutions'] = getSolutionsData($pdo);
            break;
            
        case 'partners':
            $data['partners'] = getPartnersData($pdo);
            break;
            
        case 'all':
        default:
            $data['impact'] = getImpactData($pdo);
            $data['solutions'] = getSolutionsData($pdo);
            $data['partners'] = getPartnersData($pdo);
            break;
    }
    
    // Send successful response
    sendResponse(true, $data);
    
} catch (PDOException $e) {
    // Database connection error
    error_log('Homepage API - Database error: ' . $e->getMessage());
    sendResponse(false, null, 'Database connection error. Please try again later.', 503);
    
} catch (Throwable $e) {
    // Unexpected error
    error_log('Homepage API - Unexpected error: ' . $e->getMessage());
    sendResponse(false, null, 'An unexpected error occurred. Please try again later.', 500);
}
